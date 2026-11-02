<?php
declare(strict_types=1);

/** Saman Electronic Payment (SEP) — used by Bank Saman and other banks that contract with SEP (e.g. Sina). */
final class SepGateway extends PaymentDriver
{
    public static function title(): string { return 'سامان کیش (SEP)'; }
    public static function fields(): array { return ['terminal_id' => ['شماره ترمینال (TerminalId)', 'text', '']]; }

    public function request(array $tx, array $order, string $callback): array
    {
        $resNum = 'T' . $tx['id'] . 'R' . random_int(1000, 9999);
        $r = $this->http('https://sep.shaparak.ir/onlinepg/onlinepg', [
            'action' => 'token', 'TerminalId' => $this->c('terminal_id'), 'Amount' => $this->rial((int)$tx['amount']),
            'ResNum' => $resNum, 'RedirectUrl' => $callback, 'CellNumber' => $order['mobile'],
        ]);
        if (($r['status'] ?? 0) != 1 || empty($r['token'])) return $this->fail($r['errorDesc'] ?? $r['_error'] ?? 'خطا در دریافت توکن سامان');
        return ['ok' => true, 'authority' => $resNum, 'meta' => ['token' => $r['token']],
            'form' => ['action' => 'https://sep.shaparak.ir/OnlinePG/OnlinePG', 'method' => 'post', 'fields' => ['Token' => $r['token'], 'GetMethod' => '']]];
    }

    public function verify(array $tx, array $in): array
    {
        if ((string)($in['State'] ?? '') !== 'OK' || (string)($in['Status'] ?? '') !== '2') return $this->fail('پرداخت ناموفق یا لغو شده: ' . ($in['State'] ?? ''));
        if (($in['ResNum'] ?? '') !== $tx['authority']) return $this->fail('شناسه تراکنش مطابقت ندارد.');
        $r = $this->http('https://sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/VerifyTransaction', [
            'RefNum' => (string)($in['RefNum'] ?? ''), 'TerminalNumber' => (int)$this->c('terminal_id'),
        ]);
        $d = $r['TransactionDetail'] ?? [];
        if (empty($r['Success']) || (int)($r['ResultCode'] ?? -1) !== 0) return $this->fail($r['ResultDescription'] ?? 'تایید تراکنش ناموفق بود');
        if ((int)($d['AffectiveAmount'] ?? $d['OrginalAmount'] ?? 0) !== $this->rial((int)$tx['amount'])) return $this->fail('مبلغ تراکنش مطابقت ندارد.');
        return ['ok' => true, 'ref' => (string)($d['RRN'] ?? $in['RRN'] ?? ''), 'card' => (string)($d['MaskedPan'] ?? $in['SecurePan'] ?? '')];
    }
}
