<?php
declare(strict_types=1);

/** Parsian E-Commerce (PEC) — Bank Parsian. SOAP without ext-soap. */
final class PecGateway extends PaymentDriver
{
    public static function title(): string { return 'تجارت الکترونیک پارسیان (PEC)'; }
    public static function fields(): array { return ['pin' => ['پین (LoginAccount)', 'password', '']]; }

    public function request(array $tx, array $order, string $callback): array
    {
        $xml = $this->soap('https://pec.shaparak.ir/NewIPGServices/Sale/SaleService.asmx', 'https://pec.Shaparak.ir/NewIPGServices/Sale/SaleService',
            'SalePaymentRequest', ['ns1:LoginAccount' => $this->c('pin'), 'ns1:Amount' => $this->rial((int)$tx['amount']), 'ns1:OrderId' => (int)$tx['id'],
                'ns1:CallBackUrl' => $callback, 'ns1:AdditionalData' => $order['code'], 'ns1:Originator' => $order['mobile']],
            'https://pec.Shaparak.ir/NewIPGServices/Sale/SaleService/SalePaymentRequest', 'ns1:requestData');
        $token = self::xmlVal($xml, 'Token'); $status = self::xmlVal($xml, 'Status');
        if ($status !== '0' || !$token || $token === '0') return $this->fail('خطای پارسیان: ' . (self::xmlVal($xml, 'Message') ?: $status));
        return ['ok' => true, 'authority' => $token, 'redirect' => 'https://pec.shaparak.ir/NewIPG/?token=' . urlencode($token)];
    }

    public function verify(array $tx, array $in): array
    {
        if ((string)($in['status'] ?? '') !== '0' || ($in['Token'] ?? '') !== $tx['authority']) return $this->fail('پرداخت ناموفق یا لغو شده');
        $xml = $this->soap('https://pec.shaparak.ir/NewIPGServices/Confirm/ConfirmService.asmx', 'https://pec.Shaparak.ir/NewIPGServices/Confirm/ConfirmService',
            'ConfirmPayment', ['ns1:LoginAccount' => $this->c('pin'), 'ns1:Token' => $tx['authority']],
            'https://pec.Shaparak.ir/NewIPGServices/Confirm/ConfirmService/ConfirmPayment', 'ns1:requestData');
        if (self::xmlVal($xml, 'Status') !== '0') return $this->fail('تایید ناموفق: ' . self::xmlVal($xml, 'Status'));
        return ['ok' => true, 'ref' => (string)self::xmlVal($xml, 'RRN'), 'card' => (string)self::xmlVal($xml, 'CardNumberMasked')];
    }
}
