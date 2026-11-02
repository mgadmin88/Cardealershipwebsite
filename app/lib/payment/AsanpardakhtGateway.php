<?php
declare(strict_types=1);

/** Asan Pardakht (Asan Pardakht Persian / APP) — REST IPG v1. Used by several banks contracting with Asan Pardakht. */
final class AsanpardakhtGateway extends PaymentDriver
{
    private const API = 'https://ipgrest.asanpardakht.ir/v1/';

    public static function title(): string { return 'آسان پرداخت پرشین (APP)'; }
    public static function fields(): array
    {
        return [
            'merchant_config_id' => ['شناسه پیکربندی پذیرنده (MerchantConfigurationId)', 'text', ''],
            'username'           => ['نام کاربری', 'text', ''],
            'password'           => ['رمز عبور', 'password', ''],
        ];
    }

    private function headers(): array { return ['usr: ' . $this->c('username'), 'pwd: ' . $this->c('password')]; }

    public function request(array $tx, array $order, string $callback): array
    {
        $invoice = (int)$tx['id'] + 100000;
        $r = $this->http(self::API . 'Token', [
            'serviceTypeId' => 1, 'merchantConfigurationId' => (int)$this->c('merchant_config_id'), 'localInvoiceId' => $invoice,
            'amountInRials' => $this->rial((int)$tx['amount']), 'localDate' => date('Ymd His'), 'callbackURL' => $callback,
            'paymentId' => '0', 'additionalData' => 'Order ' . $order['code'],
        ], $this->headers());
        if (($r['_code'] ?? 0) !== 200) return $this->fail($r['_error'] ?? 'خطای آسان پرداخت، کد: ' . ($r['_code'] ?? '?'));
        $token = trim((string)($r['_raw'] ?? ''), "\" \n\r\t");
        if ($token === '') return $this->fail('توکن پرداخت از آسان پرداخت دریافت نشد.');
        return ['ok' => true, 'authority' => (string)$invoice, 'meta' => ['token' => $token],
            'form' => ['action' => 'https://asan.shaparak.ir', 'method' => 'post', 'fields' => ['RefId' => $token, 'mobileap' => $order['mobile']]]];
    }

    public function verify(array $tx, array $in): array
    {
        $q = http_build_query(['MerchantConfigurationId' => $this->c('merchant_config_id'), 'LocalInvoiceId' => $tx['authority']]);
        $res = $this->http(self::API . 'TranResult?' . $q, [], $this->headers(), 'json', 'GET');
        if (($res['_code'] ?? 0) !== 200 || empty($res['payGateTranID'])) return $this->fail('پرداخت انجام نشد یا لغو شد.');
        if ((int)($res['amount'] ?? 0) !== $this->rial((int)$tx['amount'])) return $this->fail('مبلغ تراکنش مطابقت ندارد.');
        $body = ['merchantConfigurationId' => (int)$this->c('merchant_config_id'), 'payGateTranId' => (int)$res['payGateTranID']];
        $v = $this->http(self::API . 'Verify', $body, $this->headers());
        if (($v['_code'] ?? 0) !== 200) return $this->fail('تایید تراکنش ناموفق، کد: ' . ($v['_code'] ?? '?'));
        $s = $this->http(self::API . 'Settlement', $body, $this->headers());
        if (($s['_code'] ?? 0) !== 200) app_log('payment', 'Asanpardakht settlement pending for tx ' . $tx['id'] . ' (bank settles automatically)');
        return ['ok' => true, 'ref' => (string)($res['rrn'] ?? $res['payGateTranID']), 'card' => (string)($res['cardNumber'] ?? '')];
    }
}
