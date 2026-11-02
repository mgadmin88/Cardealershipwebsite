<?php
declare(strict_types=1);

/** Admin / staff authentication with brute-force protection and role checks. */
final class Auth
{
    public const ROLES = [
        'admin'    => 'مدیر کل',
        'expert'   => 'کارشناس خودرو',
        'operator' => 'اپراتور فروش',
    ];

    /** permission => roles allowed */
    public const PERMS = [
        'cars'      => ['admin', 'expert'],
        'parts'     => ['admin', 'operator'],
        'orders'    => ['admin', 'operator'],
        'requests'  => ['admin', 'operator', 'expert'],
        'content'   => ['admin'],
        'settings'  => ['admin'],
        'users'     => ['admin'],
    ];

    private static ?array $user = null;

    public static function user(): ?array
    {
        if (self::$user) return self::$user;
        $id = $_SESSION['uid'] ?? null;
        if (!$id) return null;
        // bind session to user agent fingerprint
        if (($_SESSION['ua'] ?? '') !== hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '')) { self::logout(); return null; }
        // idle timeout 2h
        if (time() - ($_SESSION['last_seen'] ?? 0) > 7200) { self::logout(); return null; }
        $_SESSION['last_seen'] = time();
        $u = DB::row('SELECT * FROM users WHERE id = ? AND active = 1', [$id]);
        if (!$u || ($_SESSION['pw'] ?? '') !== substr(hash('sha256', $u['password']), 0, 16)) { self::logout(); return null; }
        return self::$user = $u;
    }

    public static function id(): ?int { return isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : null; }

    public static function check(): bool { return self::user() !== null; }

    public static function can(string $perm): bool
    {
        $u = self::user();
        return $u && in_array($u['role'], self::PERMS[$perm] ?? ['admin'], true);
    }

    public static function require(string $perm = ''): void
    {
        if (!self::check()) { $_SESSION['intended'] = $_SERVER['REQUEST_URI'] ?? ''; redirect('admin/login'); }
        if ($perm && !self::can($perm)) abort(403, 'شما به این بخش دسترسی ندارید.');
    }

    /** @return string|null error message */
    public static function attempt(string $username, string $password): ?string
    {
        $ip = client_ip();
        $since = date('Y-m-d H:i:s', time() - 900);
        $fails = (int)DB::val('SELECT COUNT(*) FROM login_attempts WHERE (ip = ? OR username = ?) AND created_at > ?', [$ip, $username, $since]);
        if ($fails >= 5) return 'به دلیل تلاش‌های ناموفق متعدد، ورود تا ۱۵ دقیقه مسدود است.';

        $u = DB::row('SELECT * FROM users WHERE username = ?', [$username]);
        // constant-ish time: always run password_verify
        $hash = $u['password'] ?? '$2y$12$abcdefghijklmnopqrstuuJ8n4Jz8B1jQ3s8bQ0J0Fv9gU5Q0nX9y';
        $ok = password_verify($password, $hash);
        if (!$u || !$ok || !(int)$u['active']) {
            DB::insert('login_attempts', ['ip' => $ip, 'username' => mb_substr($username, 0, 100), 'created_at' => now()]);
            return 'نام کاربری یا رمز عبور اشتباه است.';
        }
        if (password_needs_rehash($u['password'], PASSWORD_DEFAULT)) {
            $u['password'] = password_hash($password, PASSWORD_DEFAULT);
            DB::update('users', ['password' => $u['password']], 'id = ?', [$u['id']]);
        }
        DB::delete('login_attempts', 'ip = ? OR username = ?', [$ip, $username]);
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$u['id'];
        $_SESSION['ua'] = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
        $_SESSION['pw'] = substr(hash('sha256', $u['password']), 0, 16);  // invalidates session on password change
        $_SESSION['last_seen'] = time();
        unset($_SESSION['_csrf']);
        DB::update('users', ['last_login' => now()], 'id = ?', [$u['id']]);
        self::$user = null;
        log_activity('login', $username);
        return null;
    }

    public static function logout(): void
    {
        unset($_SESSION['uid'], $_SESSION['ua'], $_SESSION['pw'], $_SESSION['last_seen']);
        self::$user = null;
        if (session_status() === PHP_SESSION_ACTIVE) session_regenerate_id(true);
    }

    public static function passwordPolicy(string $pw): ?string
    {
        if (mb_strlen($pw) < 10) return 'رمز عبور باید حداقل ۱۰ کاراکتر باشد.';
        if (!preg_match('/\d/', $pw) || !preg_match('/[a-zA-Z]/', $pw)) return 'رمز عبور باید شامل حروف و عدد باشد.';
        return null;
    }
}
