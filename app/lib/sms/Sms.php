<?php
declare(strict_types=1);

/**
 * SMS gateway abstraction. Supports the major Iranian providers + a generic HTTP provider
 * (so any other panel that has a simple HTTP API can be connected without code changes).
 */
final class Sms
{
    /** provider => [title, fields[key => [label, type]]] */
    public const PROVIDERS = [
        'log'         => ['حالت آزمایشی (فقط ثبت در لاگ)', []],
        'kavenegar'   => ['کاوه‌نگار', ['api_key' => ['API Key', 'password'], 'sender' => ['شماره فرستنده', 'text']]],
        'smsir'       => ['SMS.ir (نسخه جدید)', ['api_key' => ['X-API-KEY', 'password'], 'line' => ['شماره خط', 'text']]],
        'melipayamak' => ['ملی پیامک', ['username' => ['نام کاربری', 'text'], 'password' => ['رمز عبور / کلید API', 'password'], 'sender' => ['شماره فرستنده', 'text']]],
        'ippanel'     => ['IPPanel / فراز اس‌ام‌اس', ['api_key' => ['API Key', 'password'], 'sender' => ['شماره فرستنده', 'text']]],
        'ghasedak'    => ['قاصدک', ['api_key' => ['API Key', 'password'], 'sender' => ['شماره خط', 'text']]],
        'magfa'       => ['مگفا', ['username' => ['نام کاربری', 'text'], 'domain' => ['دامنه (domain)', 'text'], 'password' => ['رمز عبور', 'password'], 'sender' => ['شماره فرستنده', 'text']]],
        'generic'     => ['سایر سامانه‌ها (HTTP عمومی)', ['url' => ['آدرس API (متغیرها: {to} {text} {from} {key})', 'text'], 'method' => ['متد (GET یا POST)', 'text'], 'body' => ['بدنه POST (JSON یا فرم؛ با همان متغیرها)', 'text'], 'key' => ['کلید API', 'password'], 'sender' => ['شماره فرستنده', 'text'], 'success' => ['عبارت نشانه موفقیت در پاسخ', 'text']]],
    ];

    public static function send(string $mobile, string $text): bool
    {
        $mobile = normalize_mobile($mobile);
        if (!valid_mobile($mobile) || trim($text) === '') return false;
        $provider = setting('sms_provider', 'log');
        $cfg = Secret::decryptArray(Settings::json('sms_config')[$provider] ?? []);
        try {
            [$ok, $resp] = self::dispatch($provider, $cfg, $mobile, $text);
        } catch (Throwable $e) {
            [$ok, $resp] = [false, $e->getMessage()];
        }
        try {
            DB::insert('sms_log', ['mobile' => $mobile, 'message' => mb_substr($text, 0, 1000), 'provider' => $provider, 'ok' => $ok ? 1 : 0,
                'response' => mb_substr((string)$resp, 0, 1000), 'created_at' => now()]);
        } catch (Throwable) {}
        return $ok;
    }

    /** @return array{0:bool,1:string} */
    private static function dispatch(string $p, array $c, string $to, string $text): array
    {
        $g = fn($k) => trim((string)($c[$k] ?? ''));
        switch ($p) {
            case 'log':
                app_log('sms', "$to: $text");
                return [true, 'logged'];
            case 'kavenegar':
                $r = self::http('https://api.kavenegar.com/v1/' . rawurlencode($g('api_key')) . '/sms/send.json', ['receptor' => $to, 'sender' => $g('sender'), 'message' => $text], 'form');
                return [(int)(json_decode($r, true)['return']['status'] ?? 0) === 200, $r];
            case 'smsir':
                $r = self::http('https://api.sms.ir/v1/send/bulk', ['lineNumber' => (int)$g('line'), 'messageText' => $text, 'mobiles' => [$to]], 'json', ['X-API-KEY: ' . $g('api_key')]);
                return [(int)(json_decode($r, true)['status'] ?? 0) === 1, $r];
            case 'melipayamak':
                $r = self::http('https://rest.payamak-panel.com/api/SendSMS/SendSMS', ['username' => $g('username'), 'password' => $g('password'), 'to' => $to, 'from' => $g('sender'), 'text' => $text, 'isflash' => 'false'], 'form');
                return [(string)(json_decode($r, true)['RetStatus'] ?? '') === '1', $r];
            case 'ippanel':
                $r = self::http('https://api2.ippanel.com/api/v1/sms/send/webservice/single', ['recipient' => [$to], 'sender' => $g('sender'), 'message' => $text], 'json', ['apikey: ' . $g('api_key')]);
                return [strtoupper((string)(json_decode($r, true)['status'] ?? '')) === 'OK', $r];
            case 'ghasedak':
                $r = self::http('https://api.ghasedak.me/v2/sms/send/simple', ['message' => $text, 'receptor' => $to, 'linenumber' => $g('sender')], 'form', ['apikey: ' . $g('api_key')]);
                return [(int)(json_decode($r, true)['result']['code'] ?? 0) === 200, $r];
            case 'magfa':
                $r = self::http('https://sms.magfa.com/api/http/sms/v2/send', ['senders' => [$g('sender')], 'recipients' => [$to], 'messages' => [$text]], 'json',
                    ['Authorization: Basic ' . base64_encode($g('username') . '/' . $g('domain') . ':' . $g('password'))]);
                return [(int)(json_decode($r, true)['status'] ?? -1) === 0, $r];
            case 'generic':
                $rep = fn($s, $enc) => strtr($s, ['{to}' => $enc($to), '{text}' => $enc($text), '{from}' => $enc($g('sender')), '{key}' => $enc($g('key'))]);
                $url = $rep($g('url'), 'rawurlencode');
                if (!preg_match('~^https?://~', $url)) return [false, 'invalid url'];
                if (strtoupper($g('method')) === 'POST') {
                    $body = $rep($g('body'), fn($v) => trim((string)json_encode($v, JSON_UNESCAPED_UNICODE), '"'));
                    $r = self::http($url, $body, str_starts_with(ltrim($body), '{') ? 'json' : 'form');
                } else {
                    $r = self::http($url, [], 'get');
                }
                $succ = $g('success');
                return [$succ === '' ? $r !== '' : str_contains($r, $succ), $r];
        }
        return [false, 'unknown provider'];
    }

    private static function http(string $url, array|string $data, string $type, array $headers = []): string
    {
        $ch = curl_init($url);
        $h = $headers;
        if ($type !== 'get') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($type === 'json') { $h[] = 'Content-Type: application/json'; $body = is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE); }
            else { $body = is_string($data) ? $data : http_build_query($data); $h[] = 'Content-Type: application/x-www-form-urlencoded'; }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $h[] = 'Accept: application/json';
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_HTTPHEADER => $h]);
        $r = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        return $r === false ? 'curl: ' . $err : (string)$r;
    }

    public static function tpl(string $key, array $vars): string
    {
        $t = (string)setting('sms_tpl_' . $key, '');
        $vars['site'] = setting('site_name');
        return strtr($t, array_combine(array_map(fn($k) => '{' . $k . '}', array_keys($vars)), array_map('strval', $vars)));
    }
}
