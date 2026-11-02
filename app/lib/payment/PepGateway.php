<?php
declare(strict_types=1);

/** Pasargad Electronic Payment (PEP) — new REST API (dorsa). */
final class PepGateway extends PaymentDriver
{
    private const BASE = 'https://pep.shaparak.ir/dorsa1';

    public static function title(): string { return 'پرداخت الکترونیک پاسارگاد (PEP)'; }
    public static function fields(): array
    {
        return ['terminal' => ['شماره ترمینال', 'text', ''], 'username' => ['نام کاربری', 'text', ''], 'password' => ['رمز عبور', 'password', '']];
    }
    public static function note(): string { return 'بر اساس API جدید پاسارگاد (dorsa). آدرس‌ها را با مستندات ارسالی PEP تطبیق دهید.'; }

    private function token(): ?string
    {
        $r = $this->http(self::BASE . '/token/getToken', ['username' => $this->c('username'), 'password' => $this->c('password')]);
        return ($r['resultCode'] ?? -1) == 0 ? ($r['token'] ?? null) : null;
    }

    public function request(array $tx, array $order, string $callback): array
    {
        $t = $this->token();
        if (!$t) return $this->fail('احراز هویت پاسارگاد ناموفق بود.');
        $inv = (string)$tx['id'];
        $r = $this->http(self::BASE . '/api/payment/purchase', [
            'amount' => $this->rial((int)$tx['amount']), 'callbackApi' => $callback, 'description' => 'Order ' . $order['code'],
            'invoice' => $inv, 'invoiceDate' => date('Y-m-d'), 'mobileNumber' => $order['mobile'], 'payerName' => $order['customer_name'],
            'serviceCode' => '8', 'serviceType' => 'PURCHASE', 'terminalNumber' => (int)$this->c('terminal'),
        ], ['Authorization: Bearer ' . $t]);
        if (($r['resultCode'] ?? -1) != 0 || empty($r['data']['url'])) return $this->fail($r['resultMsg'] ?? 'خطای پاسارگاد');
        return ['ok' => true, 'authority' => $inv, 'meta' => ['urlId' => $r['data']['urlId'] ?? ''], 'redirect' => $r['data']['url']];
    }

    public function verify(array $tx, array $in): array
    {
        if (strtolower((string)($in['status'] ?? '')) !== 'success') return $this->fail('پرداخت ناموفق یا لغو شده');
        if ((string)($in['invoiceId'] ?? '') !== (string)$tx['authority']) return $this->fail('شماره فاکتور مطابقت ندارد.');
        $meta = json_decode((string)$tx['raw'], true)['req'] ?? [];
        $t = $this->token();
        if (!$t) return $this->fail('احراز هویت پاسارگاد ناموفق بود.');
        $r = $this->http(self::BASE . '/api/payment/confirm-transactions', ['invoice' => $tx['authority'], 'urlId' => $meta['urlId'] ?? ''], ['Authorization: Bearer ' . $t]);
        if (($r['resultCode'] ?? -1) != 0) return $this->fail($r['resultMsg'] ?? 'تایید ناموفق');
        if (isset($r['data']['amount']) && (int)$r['data']['amount'] !== $this->rial((int)$tx['amount'])) return $this->fail('مبلغ مطابقت ندارد.');
        return ['ok' => true, 'ref' => (string)($r['data']['referenceNumber'] ?? $in['referenceNumber'] ?? ''), 'card' => (string)($r['data']['maskedCardNumber'] ?? '')];
    }
}
