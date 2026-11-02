<?php
declare(strict_types=1);

/**
 * Base class for every payment gateway driver.
 * Amounts are stored in TOMAN in the database; drivers convert to RIAL where the bank expects it.
 *
 * request() must return one of:
 *   ['ok'=>true, 'authority'=>string, 'redirect'=>url]
 *   ['ok'=>true, 'authority'=>string, 'form'=>['action'=>url, 'method'=>'post', 'fields'=>[...]]]
 *   ['ok'=>false, 'error'=>string]
 * verify() must return: ['ok'=>bool, 'ref'=>string, 'card'=>string, 'error'=>string]
 */
abstract class PaymentDriver
{
    public function __construct(protected array $gw, protected array $cfg, protected bool $sandbox) {}

    abstract public static function title(): string;

    /** config fields shown in admin: key => [label, type(text|password), hint] */
    abstract public static function fields(): array;

    abstract public function request(array $tx, array $order, string $callback): array;

    abstract public function verify(array $tx, array $in): array;

    public static function note(): string { return ''; }

    protected function rial(int $toman): int { return $toman * 10; }

    protected function c(string $key): string { return trim((string)($this->cfg[$key] ?? '')); }

    /** generic HTTPS call. $type: json | form */
    protected function http(string $url, array|string $body = [], array $headers = [], string $type = 'json', string $method = 'POST'): array
    {
        $ch = curl_init($url);
        $h = ['Accept: application/json'];
        if ($method === 'POST') {
            if ($type === 'json') { $payload = is_string($body) ? $body : json_encode($body, JSON_UNESCAPED_UNICODE); $h[] = 'Content-Type: application/json'; }
            elseif ($type === 'xml') { $payload = $body; $h[] = 'Content-Type: text/xml; charset=utf-8'; }
            else { $payload = is_string($body) ? $body : http_build_query($body); $h[] = 'Content-Type: application/x-www-form-urlencoded'; }
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => array_merge($h, $headers), CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'AutoSite-Payment/1.0',
        ]);
        $res = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        app_log('payment', static::class . " $url [$code] " . ($err ?: mb_substr((string)$res, 0, 600)));
        if ($res === false) return ['_error' => 'خطا در ارتباط با درگاه: ' . $err, '_code' => $code];
        $json = json_decode((string)$res, true);
        return is_array($json) ? $json + ['_code' => $code, '_raw' => $res] : ['_raw' => (string)$res, '_code' => $code];
    }

    /** minimal SOAP 1.1 call without ext-soap */
    protected function soap(string $endpoint, string $ns, string $method, array $params, string $soapAction = '', string $wrap = ''): string
    {
        $inner = '';
        foreach ($params as $k => $v) $inner .= "<$k>" . htmlspecialchars((string)$v, ENT_XML1) . "</$k>";
        if ($wrap) $inner = "<$wrap>$inner</$wrap>";
        $xml = '<?xml version="1.0" encoding="utf-8"?><soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="' . $ns . '">'
            . "<soap:Body><ns1:$method>$inner</ns1:$method></soap:Body></soap:Envelope>";
        $r = $this->http($endpoint, $xml, ['SOAPAction: "' . $soapAction . '"'], 'xml');
        return (string)($r['_raw'] ?? '');
    }

    protected static function xmlVal(string $xml, string $tag): ?string
    {
        return preg_match('~<(?:\w+:)?' . preg_quote($tag, '~') . '>(.*?)</(?:\w+:)?' . preg_quote($tag, '~') . '>~s', $xml, $m) ? html_entity_decode($m[1]) : null;
    }

    protected function fail(string $msg): array { return ['ok' => false, 'error' => $msg]; }
}
