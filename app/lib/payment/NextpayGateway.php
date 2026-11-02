<?php
declare(strict_types=1);

final class NextpayGateway extends PaymentDriver
{
    public static function title(): string { return 'نکست‌پی'; }
    public static function fields(): array { return ['api_key' => ['API Key', 'password', '']]; }

    public function request(array $tx, array $order, string $callback): array
    {
        $r = $this->http('https://nextpay.org/nx/gateway/token', ['api_key' => $this->c('api_key'), 'amount' => (int)$tx['amount'], 'currency' => 'IRT',
            'order_id' => (string)$tx['id'], 'callback_uri' => $callback, 'customer_phone' => $order['mobile']], [], 'form');
        if ((int)($r['code'] ?? 0) !== -1 || empty($r['trans_id'])) return $this->fail('خطای نکست‌پی، کد: ' . ($r['code'] ?? $r['_error'] ?? ''));
        return ['ok' => true, 'authority' => $r['trans_id'], 'redirect' => 'https://nextpay.org/nx/gateway/payment/' . $r['trans_id']];
    }

    public function verify(array $tx, array $in): array
    {
        if (($in['trans_id'] ?? '') !== $tx['authority']) return $this->fail('شناسه تراکنش مطابقت ندارد.');
        $r = $this->http('https://nextpay.org/nx/gateway/verify', ['api_key' => $this->c('api_key'), 'trans_id' => $tx['authority'], 'amount' => (int)$tx['amount'], 'currency' => 'IRT'], [], 'form');
        if ((int)($r['code'] ?? -1) !== 0) return $this->fail('تایید ناموفق، کد: ' . ($r['code'] ?? ''));
        return ['ok' => true, 'ref' => (string)($r['Shaparak_Ref_Id'] ?? ''), 'card' => (string)($r['card_holder'] ?? '')];
    }
}
