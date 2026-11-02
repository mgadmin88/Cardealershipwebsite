<?php
declare(strict_types=1);

final class PayirGateway extends PaymentDriver
{
    public static function title(): string { return 'Pay.ir'; }
    public static function fields(): array { return ['api' => ['API Key', 'password', 'در حالت تست مقدار test استفاده می‌شود']]; }

    private function api(): string { return $this->sandbox ? 'test' : $this->c('api'); }

    public function request(array $tx, array $order, string $callback): array
    {
        $r = $this->http('https://pay.ir/pg/send', ['api' => $this->api(), 'amount' => $this->rial((int)$tx['amount']), 'redirect' => $callback,
            'mobile' => $order['mobile'], 'factorNumber' => (string)$tx['id'], 'description' => 'سفارش ' . $order['code']]);
        if ((int)($r['status'] ?? 0) !== 1 || empty($r['token'])) return $this->fail('خطای Pay.ir: ' . ($r['errorMessage'] ?? $r['_error'] ?? ''));
        return ['ok' => true, 'authority' => (string)$r['token'], 'redirect' => 'https://pay.ir/pg/' . $r['token']];
    }

    public function verify(array $tx, array $in): array
    {
        if ((string)($in['status'] ?? '') !== '1' || ($in['token'] ?? '') !== $tx['authority']) return $this->fail('پرداخت ناموفق یا لغو شده');
        $r = $this->http('https://pay.ir/pg/verify', ['api' => $this->api(), 'token' => $tx['authority']]);
        if ((int)($r['status'] ?? 0) !== 1) return $this->fail($r['errorMessage'] ?? 'تایید ناموفق');
        if ((int)($r['amount'] ?? 0) !== $this->rial((int)$tx['amount']) || (string)($r['factorNumber'] ?? '') !== (string)$tx['id']) return $this->fail('مبلغ یا شماره فاکتور مطابقت ندارد.');
        return ['ok' => true, 'ref' => (string)($r['transId'] ?? ''), 'card' => (string)($r['cardNumber'] ?? '')];
    }
}
