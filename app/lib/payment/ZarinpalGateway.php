<?php
declare(strict_types=1);

final class ZarinpalGateway extends PaymentDriver
{
    public static function title(): string { return 'زرین‌پال'; }
    public static function fields(): array { return ['merchant_id' => ['مرچنت کد (36 کاراکتری)', 'text', '']]; }

    private function host(): string { return $this->sandbox ? 'https://sandbox.zarinpal.com' : 'https://payment.zarinpal.com'; }

    public function request(array $tx, array $order, string $callback): array
    {
        $r = $this->http($this->host() . '/pg/v4/payment/request.json', [
            'merchant_id' => $this->c('merchant_id'), 'amount' => $this->rial((int)$tx['amount']), 'callback_url' => $callback,
            'description' => 'سفارش ' . $order['code'], 'metadata' => ['mobile' => $order['mobile'], 'order_id' => $order['code']],
        ]);
        $d = $r['data'] ?? [];
        if ((int)($d['code'] ?? 0) !== 100 || empty($d['authority'])) return $this->fail('خطای زرین‌پال: ' . json_encode($r['errors'] ?? $r['_error'] ?? '', JSON_UNESCAPED_UNICODE));
        return ['ok' => true, 'authority' => $d['authority'], 'redirect' => $this->host() . '/pg/StartPay/' . $d['authority']];
    }

    public function verify(array $tx, array $in): array
    {
        if (($in['Status'] ?? '') !== 'OK') return $this->fail('پرداخت لغو شد.');
        if (($in['Authority'] ?? '') !== $tx['authority']) return $this->fail('Authority مطابقت ندارد.');
        $r = $this->http($this->host() . '/pg/v4/payment/verify.json', ['merchant_id' => $this->c('merchant_id'), 'amount' => $this->rial((int)$tx['amount']), 'authority' => $tx['authority']]);
        $d = $r['data'] ?? [];
        if (!in_array((int)($d['code'] ?? 0), [100, 101], true)) return $this->fail('تایید ناموفق: ' . json_encode($r['errors'] ?? '', JSON_UNESCAPED_UNICODE));
        return ['ok' => true, 'ref' => (string)($d['ref_id'] ?? ''), 'card' => (string)($d['card_pan'] ?? '')];
    }
}
