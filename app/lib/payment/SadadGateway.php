<?php
declare(strict_types=1);

/** Sadad (Bank Melli PSP) */
final class SadadGateway extends PaymentDriver
{
    public static function title(): string { return 'سداد (بانک ملی)'; }
    public static function fields(): array
    {
        return ['merchant_id' => ['شماره پذیرنده (MerchantId)', 'text', ''], 'terminal_id' => ['شماره ترمینال', 'text', ''], 'key' => ['کلید ترمینال (Base64)', 'password', '']];
    }

    private function sign(string $data): string
    {
        return base64_encode((string)openssl_encrypt($data, 'DES-EDE3', base64_decode($this->c('key')), OPENSSL_RAW_DATA));
    }

    public function request(array $tx, array $order, string $callback): array
    {
        $orderId = (int)$tx['id'];
        $amount = $this->rial((int)$tx['amount']);
        $r = $this->http('https://sadad.shaparak.ir/api/v0/Request/PaymentRequest', [
            'MerchantId' => $this->c('merchant_id'), 'TerminalId' => $this->c('terminal_id'), 'Amount' => $amount, 'OrderId' => $orderId,
            'LocalDateTime' => date('m/d/Y g:i:s a'), 'ReturnUrl' => $callback, 'UserId' => $order['mobile'],
            'SignData' => $this->sign($this->c('terminal_id') . ';' . $orderId . ';' . $amount),
        ]);
        if ((string)($r['ResCode'] ?? '') !== '0' || empty($r['Token'])) return $this->fail($r['Description'] ?? $r['_error'] ?? 'خطای سداد');
        return ['ok' => true, 'authority' => $r['Token'], 'redirect' => 'https://sadad.shaparak.ir/Purchase?Token=' . urlencode($r['Token'])];
    }

    public function verify(array $tx, array $in): array
    {
        if ((string)($in['ResCode'] ?? '') !== '0') return $this->fail('پرداخت ناموفق یا لغو شده');
        if (($in['Token'] ?? '') !== $tx['authority']) return $this->fail('توکن مطابقت ندارد.');
        $r = $this->http('https://sadad.shaparak.ir/api/v0/Advice/Verify', ['Token' => $tx['authority'], 'SignData' => $this->sign($tx['authority'])]);
        if ((string)($r['ResCode'] ?? '') !== '0') return $this->fail($r['Description'] ?? 'تایید ناموفق');
        if (isset($r['Amount']) && (int)$r['Amount'] !== $this->rial((int)$tx['amount'])) return $this->fail('مبلغ مطابقت ندارد.');
        return ['ok' => true, 'ref' => (string)($r['RetrivalRefNo'] ?? ''), 'card' => ''];
    }
}
