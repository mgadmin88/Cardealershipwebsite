<?php
declare(strict_types=1);

/** Behpardakht Mellat (BPM) — Bank Mellat and banks contracting with Behpardakht (e.g. Sarmayeh). SOAP without ext-soap. */
final class BehpardakhtGateway extends PaymentDriver
{
    private const WS = 'https://bpm.shaparak.ir/pgwchannel/services/pgw';
    private const NS = 'http://interfaces.core.sw.bps.com/';

    public static function title(): string { return 'به‌پرداخت ملت (BPM)'; }
    public static function fields(): array
    {
        return ['terminal_id' => ['شماره ترمینال', 'text', ''], 'username' => ['نام کاربری', 'text', ''], 'password' => ['رمز عبور', 'password', '']];
    }

    private function auth(): array
    {
        return ['terminalId' => $this->c('terminal_id'), 'userName' => $this->c('username'), 'userPassword' => $this->c('password')];
    }

    private function call(string $method, array $params): string
    {
        return (string)self::xmlVal($this->soap(self::WS, self::NS, $method, $params), 'return');
    }

    public function request(array $tx, array $order, string $callback): array
    {
        $orderId = (int)(time() % 100000000 . str_pad((string)$tx['id'], 6, '0', STR_PAD_LEFT));
        $ret = $this->call('bpPayRequest', $this->auth() + [
            'orderId' => $orderId, 'amount' => $this->rial((int)$tx['amount']), 'localDate' => date('Ymd'), 'localTime' => date('His'),
            'additionalData' => 'Order ' . $order['code'], 'callBackUrl' => $callback, 'payerId' => 0,
        ]);
        $parts = explode(',', $ret);
        if (($parts[0] ?? '') !== '0' || empty($parts[1])) return $this->fail('خطای به‌پرداخت ملت، کد: ' . ($ret ?: 'نامشخص'));
        return ['ok' => true, 'authority' => (string)$orderId, 'meta' => ['refId' => $parts[1]],
            'form' => ['action' => 'https://bpm.shaparak.ir/pgwchannel/startpay.mellat', 'method' => 'post', 'fields' => ['RefId' => $parts[1], 'MobileNo' => '98' . substr($order['mobile'], 1)]]];
    }

    public function verify(array $tx, array $in): array
    {
        if ((string)($in['ResCode'] ?? '') !== '0') return $this->fail('پرداخت ناموفق، کد: ' . ($in['ResCode'] ?? ''));
        if ((string)($in['SaleOrderId'] ?? '') !== (string)$tx['authority']) return $this->fail('شناسه سفارش مطابقت ندارد.');
        $p = $this->auth() + ['orderId' => $tx['authority'], 'saleOrderId' => $tx['authority'], 'saleReferenceId' => (string)($in['SaleReferenceId'] ?? '')];
        $v = $this->call('bpVerifyRequest', $p);
        if ($v !== '0') {
            $q = $this->call('bpInquiryRequest', $p);
            if ($q !== '0') { $this->call('bpReversalRequest', $p); return $this->fail('تایید تراکنش ناموفق، کد: ' . $v); }
        }
        $s = $this->call('bpSettleRequest', $p);
        if ($s !== '0' && $s !== '45') { $this->call('bpReversalRequest', $p); return $this->fail('تسویه تراکنش ناموفق، کد: ' . $s); }
        return ['ok' => true, 'ref' => (string)$in['SaleReferenceId'], 'card' => (string)($in['CardHolderPan'] ?? '')];
    }
}
