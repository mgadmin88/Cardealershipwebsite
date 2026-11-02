<?php
declare(strict_types=1);

/** Payment gateway registry & orchestration */
final class Payment
{
    /** driver key => class */
    public const DRIVERS = [
        'test'        => 'TestGateway',
        'sep'         => 'SepGateway',
        'behpardakht' => 'BehpardakhtGateway',
        'sadad'       => 'SadadGateway',
        'pec'         => 'PecGateway',
        'sepehr'      => 'SepehrGateway',
        'pep'         => 'PepGateway',
        'asanpardakht'=> 'AsanpardakhtGateway',
        'zarinpal'    => 'ZarinpalGateway',
        'zibal'       => 'ZibalGateway',
        'payir'       => 'PayirGateway',
        'nextpay'     => 'NextpayGateway',
    ];

    /** banks shown in the admin "bank" selector (display only — the PSP driver does the work) */
    public const BANKS = [
        'saman' => 'سامان', 'sina' => 'سینا', 'sarmayeh' => 'سرمایه', 'mellat' => 'ملت', 'melli' => 'ملی', 'parsian' => 'پارسیان',
        'pasargad' => 'پاسارگاد', 'saderat' => 'صادرات', 'tejarat' => 'تجارت', 'refah' => 'رفاه', 'keshavarzi' => 'کشاورزی',
        'maskan' => 'مسکن', 'sepah' => 'سپه', 'eghtesad' => 'اقتصاد نوین', 'ayandeh' => 'آینده', 'shahr' => 'شهر', 'day' => 'دی',
        'karafarin' => 'کارآفرین', 'resalat' => 'رسالت', 'tosee' => 'توسعه تعاون', 'zarinpal' => 'زرین‌پال', 'zibal' => 'زیبال',
        'payir' => 'Pay.ir', 'nextpay' => 'نکست‌پی', 'test' => 'تست', 'other' => 'سایر',
    ];

    public static function driver(array $gw): PaymentDriver
    {
        $cls = self::DRIVERS[$gw['driver']] ?? null;
        if (!$cls) throw new RuntimeException('Unknown payment driver');
        $cfg = json_decode((string)$gw['config'], true) ?: [];
        return new $cls($gw, Secret::decryptArray($cfg), (bool)$gw['sandbox']);
    }

    /** Start a payment for an order. Returns redirect url or form array or error string */
    public static function start(array $order, array $gw): array
    {
        $txId = DB::insert('transactions', ['order_id' => $order['id'], 'gateway_id' => $gw['id'], 'driver' => $gw['driver'],
            'amount' => $order['pay_amount'], 'status' => 'init', 'created_at' => now()]);
        $tx = DB::find('transactions', $txId);
        $cb = abs_url('payment/callback/' . $txId);
        try {
            $r = self::driver($gw)->request($tx, $order, $cb);
        } catch (Throwable $e) {
            app_log('payment', 'request exception: ' . $e->getMessage());
            $r = ['ok' => false, 'error' => 'خطا در اتصال به درگاه پرداخت.'];
        }
        if (!$r['ok']) {
            DB::update('transactions', ['status' => 'failed', 'message' => mb_substr($r['error'] ?? '', 0, 500)], 'id = ?', [$txId]);
            return ['error' => $r['error'] ?? 'خطای نامشخص'];
        }
        DB::update('transactions', ['authority' => (string)$r['authority'], 'raw' => json_encode(['req' => $r['meta'] ?? null], JSON_UNESCAPED_UNICODE)], 'id = ?', [$txId]);
        return $r;
    }

    /** Verify callback. Idempotent: a transaction can only be processed once. */
    public static function callback(int $txId, array $in): array
    {
        $tx = DB::find('transactions', $txId);
        if (!$tx) return ['ok' => false, 'error' => 'تراکنش یافت نشد.'];
        $order = DB::find('orders', (int)$tx['order_id']);
        if ($tx['status'] === 'success') return ['ok' => true, 'order' => $order, 'tx' => $tx];
        // atomic lock — prevents double verification / race conditions
        if (DB::update('transactions', ['status' => 'verifying'], "id = ? AND status = 'init'", [$txId]) !== 1) {
            return ['ok' => false, 'order' => $order, 'error' => 'این تراکنش قبلاً پردازش شده است.'];
        }
        $gw = DB::find('gateways', (int)$tx['gateway_id']);
        try {
            $r = $gw ? self::driver($gw)->verify($tx, $in) : ['ok' => false, 'error' => 'درگاه نامعتبر'];
        } catch (Throwable $e) {
            app_log('payment', 'verify exception: ' . $e->getMessage());
            $r = ['ok' => false, 'error' => 'خطا در تایید تراکنش'];
        }
        $raw = json_decode((string)$tx['raw'], true) ?: [];
        $raw['callback'] = array_map(fn($v) => is_scalar($v) ? mb_substr((string)$v, 0, 200) : null, $in);
        DB::update('transactions', [
            'status' => $r['ok'] ? 'success' : 'failed', 'ref_id' => mb_substr((string)($r['ref'] ?? ''), 0, 100),
            'card_pan' => mb_substr((string)($r['card'] ?? ''), 0, 30), 'message' => mb_substr((string)($r['error'] ?? 'OK'), 0, 500),
            'raw' => json_encode($raw, JSON_UNESCAPED_UNICODE), 'verified_at' => now(),
        ], 'id = ?', [$txId]);
        $tx = DB::find('transactions', $txId);
        if ($r['ok']) Orders::markPaid($order, $tx);
        elseif ($order['status'] === 'pending') DB::update('orders', ['status' => 'failed', 'updated_at' => now()], 'id = ?', [$order['id']]);
        return ['ok' => $r['ok'], 'order' => DB::find('orders', (int)$order['id']), 'tx' => $tx, 'error' => $r['error'] ?? ''];
    }
}
