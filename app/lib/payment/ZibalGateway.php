<?php
declare(strict_types=1);

final class ZibalGateway extends PaymentDriver
{
    public static function title(): string { return 'زیبال'; }
    public static function fields(): array { return ['merchant' => ['کد مرچنت', 'text', 'در حالت تست مقدار zibal استفاده می‌شود']]; }

    private function merchant(): string { return $this->sandbox ? 'zibal' : $this->c('merchant'); }

    public function request(array $tx, array $order, string $callback): array
    {
        $r = $this->http('https://gateway.zibal.ir/v1/request', ['merchant' => $this->merchant(), 'amount' => $this->rial((int)$tx['amount']),
            'callbackUrl' => $callback, 'orderId' => (string)$tx['id'], 'mobile' => $order['mobile'], 'description' => 'سفارش ' . $order['code']]);
        if ((int)($r['result'] ?? 0) !== 100) return $this->fail('خطای زیبال: ' . ($r['message'] ?? $r['_error'] ?? ''));
        return ['ok' => true, 'authority' => (string)$r['trackId'], 'redirect' => 'https://gateway.zibal.ir/start/' . $r['trackId']];
    }

    public function verify(array $tx, array $in): array
    {
        if ((string)($in['success'] ?? '') !== '1') return $this->fail('پرداخت ناموفق یا لغو شده');
        if ((string)($in['trackId'] ?? '') !== (string)$tx['authority']) return $this->fail('trackId مطابقت ندارد.');
        $r = $this->http('https://gateway.zibal.ir/v1/verify', ['merchant' => $this->merchant(), 'trackId' => $tx['authority']]);
        if (!in_array((int)($r['result'] ?? 0), [100, 201], true)) return $this->fail($r['message'] ?? 'تایید ناموفق');
        if (isset($r['amount']) && (int)$r['amount'] !== $this->rial((int)$tx['amount'])) return $this->fail('مبلغ مطابقت ندارد.');
        return ['ok' => true, 'ref' => (string)($r['refNumber'] ?? ''), 'card' => (string)($r['cardNumber'] ?? '')];
    }
}
