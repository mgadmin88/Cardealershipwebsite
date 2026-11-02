<?php
declare(strict_types=1);

/** Built-in sandbox gateway for testing the complete purchase flow without a real bank. */
final class TestGateway extends PaymentDriver
{
    public static function title(): string { return 'درگاه آزمایشی (شبیه‌ساز)'; }
    public static function fields(): array { return []; }
    public static function note(): string { return 'فقط برای تست. قبل از راه‌اندازی واقعی سایت حتماً غیرفعال شود.'; }

    public static function sig(array $tx): string
    {
        return hash_hmac('sha256', $tx['id'] . '|' . $tx['authority'] . '|' . $tx['amount'], (string)config('app_key'));
    }

    public function request(array $tx, array $order, string $callback): array
    {
        $auth = 'TEST-' . bin2hex(random_bytes(8));
        return ['ok' => true, 'authority' => $auth, 'redirect' => url('payment/test-bank/' . $auth)];
    }

    public function verify(array $tx, array $in): array
    {
        if (!hash_equals(self::sig($tx), (string)($in['sig'] ?? '')) || ($in['authority'] ?? '') !== $tx['authority']) return $this->fail('امضای تراکنش نامعتبر است.');
        if (($in['status'] ?? '') !== 'OK') return $this->fail('پرداخت توسط کاربر لغو شد.');
        return ['ok' => true, 'ref' => (string)random_int(100000000, 999999999), 'card' => '6037-99**-****-1234'];
    }
}
