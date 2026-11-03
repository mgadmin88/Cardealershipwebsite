<?php
declare(strict_types=1);

/**
 * Software license (vendor-side licensing).
 *
 * KEY   = base64url(payload) . "." . base64url(Ed25519 signature of payload)
 *         payload: {v, id, name, plan, domain, iat, exp, srv?, chk?}
 *         Signed ONLY by the vendor's private key (license manager). This file holds the PUBLIC key,
 *         so nobody can forge a key or change its domain / plan / expiry.
 *
 * ONLINE When the key carries `srv` (the vendor's license manager), the site checks in about once a day:
 *         it sends license id, domain, version and fingerprints of its core files; the manager answers with
 *         a SIGNED response {id, status, exp, nonce, ts}. That is how renewals arrive without a new key,
 *         and how a suspended/revoked license stops working. A key with `chk` > 0 must reach the manager at
 *         least once every `chk` days, otherwise the site locks (blocking the network does not help).
 *
 * Verification uses ext-sodium, or the bundled pure-PHP sodium_compat when the extension is missing,
 * so a host without sodium never locks a valid site.
 */
final class License
{
    /** Vendor public key (base64). Must match the license manager's private key. */
    public const PUBLIC_KEY = 'zQ+5JLi8CbwgVOJ3sIsZRQ3uHUzyEFK5hTLGKnXHHfg=';

    public const PLANS = ['trial' => 'آزمایشی ۱۳ ماهه', '1m' => 'یک‌ماهه', '1y' => 'یک‌ساله', 'custom' => 'سفارشی'];

    /** core files whose fingerprints are reported to the license manager */
    private const CORE = ['app/lib/License.php', 'public/index.php', 'app/bootstrap.php', 'app/helpers.php', 'app/routes.php',
        'app/views/site/_layout.php', 'app/views/admin/_layout.php', 'app/lib/payment/Payment.php'];

    private static ?array $cache = null;

    public static function enabled(): bool { return (bool)config('license.enabled', true); }

    /* ---------------- storage (files, not the database: survives DB restores, not editable from the panel) ---------------- */

    public static function keyFile(): string { return STORAGE . '/license.key'; }
    private static function stateFile(): string { return STORAGE . '/license.state'; }

    public static function raw(): string
    {
        $f = self::keyFile();
        return is_file($f) ? trim((string)file_get_contents($f)) : '';
    }

    public static function save(string $key): bool
    {
        $f = self::keyFile();
        if (!is_dir(dirname($f))) @mkdir(dirname($f), 0750, true);
        self::$cache = null;
        $ok = @file_put_contents($f, trim($key) . "\n", LOCK_EX) !== false;
        if ($ok) {   // a new key starts a fresh online state (the install id is kept)
            $s = self::state();
            self::writeState(['install' => $s['install'], 'saved' => time()]);
        }
        return $ok;
    }

    /** state = {install, saved, try_at, err, blob}; blob is the manager's SIGNED reply (verified on every read) */
    private static function state(): array
    {
        $f = self::stateFile();
        $s = is_file($f) ? json_decode((string)file_get_contents($f), true) : null;
        $s = is_array($s) ? $s : [];
        if (empty($s['install'])) { $s['install'] = bin2hex(random_bytes(8)); self::writeState($s); }
        return $s;
    }

    private static function writeState(array $s): void
    {
        if (empty($s['install'])) $s['install'] = bin2hex(random_bytes(8));
        @file_put_contents(self::stateFile(), json_encode($s, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }

    /* ---------------- crypto ---------------- */

    private static function b64urlDecode(string $s): string|false { return base64_decode(strtr($s, '-_', '+/'), true); }

    private static function verifySig(string $msg, string $sig): bool
    {
        $pub = base64_decode(self::PUBLIC_KEY, true);
        if ($pub === false || strlen($pub) !== 32 || strlen($sig) !== 64) return false;
        try {
            if (function_exists('sodium_crypto_sign_verify_detached') && extension_loaded('sodium')) {
                return sodium_crypto_sign_verify_detached($sig, $msg, $pub);
            }
            // host without ext-sodium: bundled pure-PHP implementation of the same algorithm
            if (!class_exists('ParagonIE_Sodium_Core_Ed25519', false)) require_once APP . '/vendor/sodium_compat/autoload.php';
            return ParagonIE_Sodium_Core_Ed25519::verify_detached($sig, $msg, $pub);
        } catch (\Throwable) {
            return false;
        }
    }

    /** verify a signed blob "b64url(json).b64url(sig)" → decoded array or null */
    private static function open(string $blob): ?array
    {
        $blob = trim($blob);
        if (substr_count($blob, '.') !== 1) return null;
        [$p, $s] = explode('.', $blob, 2);
        $payload = self::b64urlDecode($p);
        $sig = self::b64urlDecode($s);
        if ($payload === false || $sig === false || !self::verifySig($payload, $sig)) return null;
        $d = json_decode($payload, true);
        return is_array($d) ? $d : null;
    }

    /** verify a license key and return its payload, or null if it is not genuine */
    public static function parse(string $key): ?array
    {
        $d = self::open($key);
        return ($d && !empty($d['id']) && isset($d['exp'])) ? $d : null;
    }

    /* ---------------- domain ---------------- */

    public static function host(): string
    {
        $h = strtolower((string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
        $h = (string)preg_replace('/:\d+$/', '', $h);
        return (string)preg_replace('/^www\./', '', $h);
    }

    public static function domainMatches(string $licensed, string $host): bool
    {
        $licensed = (string)preg_replace('/^www\./', '', strtolower(trim($licensed)));
        if ($licensed === '' || $licensed === '*') return true;
        return $licensed === $host;      // exact match only: no development exception (use a '*' license while developing)
    }

    /* ---------------- online check-in ---------------- */

    /** fingerprints of the core files (so the vendor can see if someone edited the code) */
    public static function fingerprints(): array
    {
        $out = [];
        foreach (self::CORE as $rel) {
            $f = ROOT . '/' . $rel;
            $out[$rel] = is_file($f) ? substr(hash('sha256', str_replace("\r\n", "\n", (string)file_get_contents($f))), 0, 16) : 'missing';
        }
        return $out;
    }

    /**
     * Talk to the license manager when due (daily; retry failures after 6 hours). Cheap when not due.
     * The reply is stored exactly as signed; a copied or edited state file cannot carry a different verdict.
     * @return array{ok:bool, msg:string}
     */
    public static function checkIn(bool $force = false): array
    {
        $d = self::parse(self::raw());
        if (!$d || empty($d['srv'])) return ['ok' => false, 'msg' => 'این لایسنس بررسی آنلاین ندارد.'];
        $s = self::state();
        $now = time();
        $cur = self::verdict($d, $s) ?? [];
        $fresh = !empty($cur) && $now - (int)($cur['ts'] ?? 0) < 86400;
        if (!$force && ($fresh || ($now - (int)($s['try_at'] ?? 0)) < 21600)) return ['ok' => true, 'msg' => 'not due'];
        $s['try_at'] = $now;
        self::writeState($s);

        $nonce = bin2hex(random_bytes(12));
        $body = json_encode(['id' => $d['id'], 'domain' => self::host(), 'install' => $s['install'], 'nonce' => $nonce,
            'ver' => defined('APP_VERSION') ? APP_VERSION : '?', 'php' => PHP_VERSION, 'fp' => self::fingerprints()], JSON_UNESCAPED_SLASHES);
        [$code, $resp] = self::post((string)$d['srv'], (string)$body);

        $j = is_string($resp) ? json_decode($resp, true) : null;
        $r = is_array($j) && isset($j['r']) ? self::open((string)$j['r']) : null;
        if (!$r || ($r['id'] ?? '') !== $d['id'] || ($r['nonce'] ?? '') !== $nonce || ($r['domain'] ?? '') !== self::host()) {
            $s['err'] = $code ? "پاسخ نامعتبر از سرور لایسنس (HTTP $code)" : 'سرور لایسنس در دسترس نبود.';
            self::writeState($s);
            self::$cache = null;
            return ['ok' => false, 'msg' => $s['err']];
        }
        unset($s['err']);
        $s['blob'] = (string)$j['r'];
        self::writeState($s);
        self::$cache = null;
        return ['ok' => true, 'msg' => 'بررسی آنلاین انجام شد.'];
    }

    public static function stateRaw(): array { return self::state(); }

    /** the stored manager reply, if it is genuine and about this license and this domain */
    private static function verdict(array $d, array $s): ?array
    {
        if (empty($s['blob'])) return null;
        $r = self::open((string)$s['blob']);
        if (!$r || ($r['id'] ?? '') !== $d['id'] || ($r['domain'] ?? '') !== self::host()) return null;
        $r['_fresh'] = true;
        return $r;
    }

    /** @return array{0:int, 1:string|false} */
    private static function post(string $url, string $body): array
    {
        if (!preg_match('~^https?://~', $url)) return [0, false];
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body, CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 8, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
                CURLOPT_USERAGENT => 'AutoSite-License/1']);
            $res = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return [$code, $res];
        }
        $ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => "Content-Type: application/json\r\n", 'content' => $body, 'timeout' => 8, 'ignore_errors' => true]]);
        $res = @file_get_contents($url, false, $ctx);
        return [$res === false ? 0 : 200, $res];
    }

    /* ---------------- verdict ---------------- */

    /**
     * ['state' => ok|missing|invalid|domain|expired|suspended|revoked|tampered|offline|clock, ...]
     */
    public static function status(): array
    {
        if (self::$cache !== null) return self::$cache;
        $raw = self::raw();
        if ($raw === '') return self::$cache = ['state' => 'missing'];
        $d = self::parse($raw);
        if (!$d) return self::$cache = ['state' => 'invalid'];
        if (!self::domainMatches((string)($d['domain'] ?? ''), self::host())) return self::$cache = ['state' => 'domain'] + $d;

        $s = self::state();
        $now = time();
        $exp = (int)$d['exp'];
        $out = $d + ['last_ok' => null, 'err' => $s['err'] ?? null, 'msg' => null];

        if (!empty($d['srv'])) {
            $r = self::verdict($d, $s);
            $chk = (int)($d['chk'] ?? 0);
            if ($r) {
                $out['last_ok'] = (int)$r['ts'];
                $out['msg'] = $r['msg'] ?? null;
                if ((int)$r['ts'] > $now + 2 * 86400) return self::$cache = ['state' => 'clock'] + $out;   // clock turned back
                // the manager's answer is only good for a limited time (chk days): after that the site must reach it again
                if ($now > (int)($r['until'] ?? 0)) {
                    $out['offline_days'] = (int)floor(($now - (int)$r['ts']) / 86400);
                    return self::$cache = ['state' => 'offline'] + $out;
                }
                if (in_array($r['status'] ?? '', ['suspended', 'revoked', 'tampered', 'expired'], true)) {
                    return self::$cache = ['state' => $r['status']] + $out;
                }
                $exp = (int)($r['exp'] ?? $exp);             // the manager's expiry is authoritative (renewals)
            } elseif ($chk > 0) {
                // never reached the manager: the clock starts at the key's SIGNED issue date (cannot be reset by editing files)
                $since = (int)($d['iat'] ?? $now);
                $out['offline_days'] = (int)floor(($now - $since) / 86400);
                if ($now - $since >= $chk * 86400) return self::$cache = ['state' => 'offline'] + $out;
            }
        }
        $out['exp'] = $exp;
        if ($exp <= $now) return self::$cache = ['state' => 'expired'] + $out;
        return self::$cache = ['state' => 'ok', 'days_left' => (int)ceil(($exp - $now) / 86400)] + $out;
    }

    public static function ok(): bool
    {
        return !self::enabled() || self::status()['state'] === 'ok';
    }

    /** called on every request: refreshes the online state when due, without slowing normal visits */
    public static function tick(bool $isAdmin): void
    {
        if (!self::enabled()) return;
        $s = self::status();
        if (!in_array($s['state'], ['ok', 'offline', 'expired', 'suspended', 'revoked', 'tampered'], true) || empty($s['srv'])) return;
        // admin pages re-check at most every 10 minutes (so a suspension shows up quickly);
        // visitors re-check once the last verdict is a day old (a revoked license stops within ~24h), or when already locked
        $age = time() - (int)($s['last_ok'] ?? 0);
        $st = self::stateRaw();
        if ($isAdmin && time() - (int)($st['try_at'] ?? 0) > 600) self::checkIn(true);
        elseif ($age > 86400 || $s['state'] !== 'ok') self::checkIn();
    }

    public static function planLabel(?string $p): string { return self::PLANS[$p ?? ''] ?? (string)$p; }

    public static function stateLabel(string $state): string
    {
        return [
            'ok' => 'فعال', 'missing' => 'لایسنس ثبت نشده', 'invalid' => 'لایسنس نامعتبر',
            'domain' => 'لایسنس برای دامنه دیگری', 'expired' => 'لایسنس منقضی شده',
            'suspended' => 'لایسنس معلق شده', 'revoked' => 'لایسنس باطل شده',
            'offline' => 'عدم ارتباط با سرور لایسنس', 'clock' => 'ساعت سرور نادرست است',
            'tampered' => 'فایل‌های سایت تغییر کرده‌اند',
        ][$state] ?? $state;
    }
}
