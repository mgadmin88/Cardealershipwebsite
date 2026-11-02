<?php
declare(strict_types=1);

/** Sepehr Electronic Payment — Bank Saderat */
final class SepehrGateway extends PaymentDriver
{
    public static function title(): string { return 'پرداخت الکترونیک سپهر (صادرات)'; }
    public static function fields(): array { return ['terminal_id' => ['شماره ترمینال', 'text', '']]; }

    public function request(array $tx, array $order, string $callback): array
    {
        $r = $this->http('https://sepehr.shaparak.ir:8081/V1/PeymentApi/GetToken', [
            'Amount' => $this->rial((int)$tx['amount']), 'callbackURL' => $callback, 'invoiceID' => (string)$tx['id'],
            'terminalID' => (int)$this->c('terminal_id'), 'Payload' => '',
        ]);
        if ((int)($r['Status'] ?? -1) !== 0 || empty($r['Accesstoken'])) return $this->fail('خطای سپهر، کد: ' . ($r['Status'] ?? $r['_error'] ?? ''));
        return ['ok' => true, 'authority' => (string)$tx['id'],
            'form' => ['action' => 'https://sepehr.shaparak.ir:8080/Pay', 'method' => 'post', 'fields' => ['TerminalID' => $this->c('terminal_id'), 'token' => $r['Accesstoken'], 'getMethod' => '0']]];
    }

    public function verify(array $tx, array $in): array
    {
        if ((string)($in['respcode'] ?? '') !== '0') return $this->fail($in['respmsg'] ?? 'پرداخت ناموفق');
        if ((string)($in['invoiceid'] ?? '') !== (string)$tx['authority']) return $this->fail('شناسه فاکتور مطابقت ندارد.');
        $r = $this->http('https://sepehr.shaparak.ir:8081/V1/PeymentApi/Advice', ['digitalreceipt' => (string)($in['digitalreceipt'] ?? ''), 'Tid' => $this->c('terminal_id')]);
        $ok = in_array(strtolower((string)($r['Status'] ?? '')), ['ok', 'duplicate'], true) && (int)($r['ReturnId'] ?? 0) === $this->rial((int)$tx['amount']);
        if (!$ok) return $this->fail($r['Message'] ?? 'تایید ناموفق');
        return ['ok' => true, 'ref' => (string)($in['rrn'] ?? $in['digitalreceipt']), 'card' => (string)($in['cardnumber'] ?? '')];
    }
}
