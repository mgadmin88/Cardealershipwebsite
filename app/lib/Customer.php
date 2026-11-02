<?php
declare(strict_types=1);

/**
 * Buyer accounts (separate from admin users).
 *  - login with an SMS code (proves the mobile → account is "verified"), or mobile + password
 *  - a customer sees an order/request if it carries their customer_id, OR its mobile equals
 *    their mobile AND that mobile is verified (so nobody can read someone else's orders by typing a number)
 */
final class Customer
{
    private static ?array $me = null;
    private const OTP_TTL = 300;

    public static function enabled(): bool { return module('customer_accounts'); }

    public static function smsReady(): bool { return module('sms') && setting('sms_provider', 'log') !== 'log'; }

    public static function id(): ?int
    {
        $id = (int)($_SESSION['cid'] ?? 0);
        return $id > 0 ? $id : null;
    }

    public static function user(): ?array
    {
        if (self::$me !== null) return self::$me ?: null;
        $id = self::id();
        $u = $id ? DB::row('SELECT * FROM customers WHERE id = ? AND active = 1', [$id]) : null;
        // session bound to the password hash: changing the password logs out other sessions
        if ($u && ($_SESSION['cpw'] ?? '') !== self::pwTag($u)) $u = null;
        if (!$u && $id) unset($_SESSION['cid'], $_SESSION['cpw']);
        self::$me = $u ?: [];
        return $u ?: null;
    }

    public static function check(): bool { return self::user() !== null; }

    private static function pwTag(array $u): string { return substr(hash('sha256', (string)$u['password'] . '|' . $u['id']), 0, 16); }

    public static function login(array $u): void
    {
        session_regenerate_id(true);
        $_SESSION['cid'] = (int)$u['id'];
        $_SESSION['cpw'] = self::pwTag($u);
        DB::update('customers', ['last_login' => now()], 'id = ?', [$u['id']]);
        self::$me = null;
        self::claimSessionOrders((int)$u['id']);
    }

    public static function logout(): void
    {
        unset($_SESSION['cid'], $_SESSION['cpw']);
        session_regenerate_id(true);
        self::$me = null;
    }

    /** SQL condition (on alias $a) selecting rows that belong to the current customer */
    public static function scope(string $a = ''): array
    {
        $u = self::user();
        if (!$u) return ['1 = 0', []];
        $p = $a ? "$a." : '';
        if ((int)$u['verified']) return ["({$p}customer_id = ? OR {$p}mobile = ?)", [$u['id'], $u['mobile']]];
        return ["{$p}customer_id = ?", [$u['id']]];
    }

    public static function owns(array $row): bool
    {
        $u = self::user();
        if (!$u) return false;
        if ((int)($row['customer_id'] ?? 0) === (int)$u['id']) return true;
        return (int)$u['verified'] && ($row['mobile'] ?? '') === $u['mobile'];
    }

    /** orders created in this browser before logging in are attached to the account */
    private static function claimSessionOrders(int $cid): void
    {
        $codes = array_keys($_SESSION['orders'] ?? []);
        if (!$codes) return;
        $mobile = (string)DB::val('SELECT mobile FROM customers WHERE id = ?', [$cid]);
        DB::run('UPDATE orders SET customer_id = ? WHERE customer_id IS NULL AND mobile = ? AND code IN ' . DB::in($codes), [$cid, $mobile, ...$codes]);
    }

    /* ---------- one-time SMS codes (kept hashed in the session, bound to the mobile) ---------- */

    public static function sendOtp(string $mobile): bool
    {
        $last = $_SESSION['cotp']['sent'] ?? 0;
        if (($_SESSION['cotp']['m'] ?? '') === $mobile && time() - $last < 60) return true;    // resend throttle: reuse the code just sent
        $code = (string)random_int(10000, 99999);
        $_SESSION['cotp'] = ['m' => $mobile, 'h' => password_hash($code, PASSWORD_DEFAULT), 'sent' => time(), 'tries' => 0];
        return Sms::send($mobile, 'کد ورود شما به ' . setting('site_name') . ': ' . $code . "\nاین کد را به کسی ندهید.");
    }

    public static function verifyOtp(string $mobile, string $code): bool
    {
        $o = $_SESSION['cotp'] ?? null;
        if (!$o || $o['m'] !== $mobile || time() - $o['sent'] > self::OTP_TTL) return false;
        $_SESSION['cotp']['tries'] = ++$o['tries'];
        if ($o['tries'] > 5) { unset($_SESSION['cotp']); return false; }
        $code = preg_replace('/\D/', '', to_en_digits($code));
        if ($code === '' || !password_verify($code, $o['h'])) return false;
        unset($_SESSION['cotp']);
        return true;
    }

    public static function pendingOtpMobile(): ?string
    {
        $o = $_SESSION['cotp'] ?? null;
        return ($o && time() - $o['sent'] <= self::OTP_TTL) ? $o['m'] : null;
    }

    public static function passwordPolicy(string $pw): ?string
    {
        if (mb_strlen($pw) < 8) return 'رمز عبور باید حداقل ۸ کاراکتر باشد.';
        if (!preg_match('/\d/', $pw) || !preg_match('/\p{L}/u', $pw)) return 'رمز عبور باید شامل حرف و عدد باشد.';
        return null;
    }

    /** order progress steps for the timeline: [label, state(done|current|todo|bad)] */
    public static function timeline(array $o): array
    {
        $st = $o['status'];
        $steps = $o['type'] === 'car'
            ? ['pending' => 'ثبت سفارش', 'paid' => $o['pay_type'] === 'deposit' ? 'پرداخت بیعانه و رزرو' : 'پرداخت و رزرو', 'processing' => 'تنظیم قرارداد', 'completed' => 'انتقال سند و تحویل']
            : ['pending' => 'ثبت سفارش', 'paid' => 'پرداخت', 'processing' => 'آماده‌سازی', 'shipped' => 'ارسال', 'completed' => 'تحویل'];
        $keys = array_keys($steps);
        $map = ['pending' => 0, 'paid' => 1, 'processing' => 2, 'shipped' => 3, 'completed' => count($keys)];
        $bad = in_array($st, ['cancelled', 'failed'], true);
        // steps up to the current status are done; the next one is "in progress"
        $doneUpTo = $bad ? 0 : min($map[$st] ?? 0, count($keys));
        if ($o['type'] === 'car' && $st === 'shipped') $doneUpTo = 2;
        $out = [];
        foreach (array_values($steps) as $i => $l) {
            $state = $i <= $doneUpTo - ($st === 'completed' ? 1 : 0) ? 'done' : ($i === $doneUpTo + 1 && !$bad ? 'current' : 'todo');
            if ($st === 'completed') $state = 'done';
            $out[] = [$l, $state];
        }
        if ($st === 'cancelled') $out[] = ['لغو شده', 'bad'];
        if ($st === 'failed') $out[] = ['پرداخت ناموفق', 'bad'];
        return $out;
    }
}
