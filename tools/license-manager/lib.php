<?php
declare(strict_types=1);

/**
 * License manager — VENDOR ONLY (never copy this folder to a customer).
 * Holds the Ed25519 private key, issues and renews licenses, and answers the sites' daily check-ins
 * with SIGNED replies. Everything the site trusts comes from a signature made with the private key.
 */

const LM_ROOT   = __DIR__;
define('LM_DATA', getenv('LM_DATA') ?: LM_ROOT . '/data');
const LM_KEYS   = LM_ROOT . '/../../license-keys';
const LM_PRIV   = LM_KEYS . '/private.key';
const LM_PUB    = LM_KEYS . '/public.key';
const LM_SITE   = LM_ROOT . '/../..';
const LM_PLANS  = ['trial' => 'آزمایشی ۱۳ ماهه', '1y' => 'یک‌ساله', '1m' => 'یک‌ماهه', 'custom' => 'سفارشی'];
const LM_STATUS = [
    'active'    => ['فعال', 'ok'],
    'suspended' => ['معلق', 'warn'],
    'revoked'   => ['باطل', 'bad'],
];

if (!extension_loaded('sodium')) require_once LM_SITE . '/app/vendor/sodium_compat/autoload.php';

function lm_db(): PDO
{
    static $pdo = null;
    if ($pdo) return $pdo;
    if (!is_dir(LM_DATA)) mkdir(LM_DATA, 0700, true);
    $pdo = new PDO('sqlite:' . LM_DATA . '/manager.sqlite', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $pdo->exec('PRAGMA journal_mode=WAL');
    $pdo->exec('CREATE TABLE IF NOT EXISTS settings (k TEXT PRIMARY KEY, v TEXT)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS licenses (id TEXT PRIMARY KEY, name TEXT, domain TEXT, plan TEXT, iat INTEGER, exp INTEGER,
        chk INTEGER DEFAULT 14, lock_on_tamper INTEGER DEFAULT 1, status TEXT DEFAULT "active", key_text TEXT, notes TEXT, created INTEGER)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS activations (id INTEGER PRIMARY KEY AUTOINCREMENT, license_id TEXT, install TEXT, domain TEXT,
        ver TEXT, php TEXT, ip TEXT, first_seen INTEGER, last_seen INTEGER, checks INTEGER DEFAULT 0, tampered INTEGER DEFAULT 0, modified TEXT,
        UNIQUE(license_id, install))');
    $pdo->exec('CREATE TABLE IF NOT EXISTS releases (id INTEGER PRIMARY KEY AUTOINCREMENT, label TEXT, file TEXT, hash TEXT, added INTEGER,
        UNIQUE(file, hash))');
    $pdo->exec('CREATE TABLE IF NOT EXISTS events (id INTEGER PRIMARY KEY AUTOINCREMENT, license_id TEXT, at INTEGER, msg TEXT)');
    return $pdo;
}

function lm_q(string $sql, array $p = []): array { $st = lm_db()->prepare($sql); $st->execute($p); return $st->fetchAll(); }
function lm_row(string $sql, array $p = []): ?array { return lm_q($sql, $p)[0] ?? null; }
function lm_val(string $sql, array $p = []): mixed { $r = lm_row($sql, $p); return $r ? array_values($r)[0] : null; }
function lm_run(string $sql, array $p = []): void { $st = lm_db()->prepare($sql); $st->execute($p); }
function lm_set(string $k, string $v): void { lm_run('INSERT INTO settings (k, v) VALUES (?, ?) ON CONFLICT(k) DO UPDATE SET v = excluded.v', [$k, $v]); }
function lm_get(string $k, string $def = ''): string { return (string)(lm_val('SELECT v FROM settings WHERE k = ?', [$k]) ?? $def); }
function lm_event(string $id, string $msg): void { lm_run('INSERT INTO events (license_id, at, msg) VALUES (?, ?, ?)', [$id, time(), $msg]); }

function b64u(string $b): string { return rtrim(strtr(base64_encode($b), '+/', '-_'), '='); }
function b64u_dec(string $s): string|false { return base64_decode(strtr($s, '-_', '+/'), true); }

function lm_secret(): string
{
    $b64 = getenv('LICENSE_PRIVATE_KEY') ?: (is_file(LM_PRIV) ? trim((string)file_get_contents(LM_PRIV)) : '');
    $sk = $b64 !== '' ? base64_decode($b64, true) : false;
    if ($sk === false || strlen($sk) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) throw new RuntimeException('کلید خصوصی پیدا نشد: license-keys/private.key');
    return $sk;
}

/** signed blob "b64u(json).b64u(sig)" */
function lm_sign(array $payload): string
{
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return b64u($json) . '.' . b64u(sodium_crypto_sign_detached($json, lm_secret()));
}

/** plan → lifetime in seconds from `$from` (calendar months for the trial: 1 year + 1 month) */
function lm_expiry(string $plan, int $days, int $from): int
{
    return match ($plan) {
        'trial' => strtotime('+13 months', $from),
        '1y'    => strtotime('+1 year', $from),
        '1m'    => strtotime('+1 month', $from),
        default => $from + max(1, min(3650, $days)) * 86400,
    };
}

function lm_new_id(): string { do { $id = strtoupper(bin2hex(random_bytes(4))); } while (lm_val('SELECT 1 FROM licenses WHERE id = ?', [$id])); return $id; }

/** create a license, return its key */
function lm_create(array $in): array
{
    $plan = array_key_exists($in['plan'] ?? '', LM_PLANS) ? $in['plan'] : 'trial';
    $domain = strtolower(trim((string)($in['domain'] ?? '')));
    $domain = preg_replace(['#^https?://#', '#/.*$#', '#^www\.#'], '', $domain);
    if ($domain === '') throw new RuntimeException('دامنه را وارد کنید (یا * برای هر دامنه).');
    $chk = max(1, min(90, (int)($in['chk'] ?? 3)));
    $now = time();
    $id = lm_new_id();
    $exp = lm_expiry($plan, (int)($in['days'] ?? 365), $now);
    $payload = ['v' => 1, 'id' => $id, 'plan' => $plan, 'domain' => $domain, 'name' => mb_substr((string)($in['name'] ?? ''), 0, 120),
        'iat' => $now, 'exp' => $exp, 'srv' => lm_get('srv_url'), 'chk' => $chk];
    $key = lm_sign($payload);
    lm_run('INSERT INTO licenses (id, name, domain, plan, iat, exp, chk, lock_on_tamper, status, key_text, notes, created) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
        [$id, $payload['name'], $domain, $plan, $now, $exp, $chk, empty($in['lock_on_tamper']) ? 0 : 1, 'active', $key, mb_substr((string)($in['notes'] ?? ''), 0, 1000), $now]);
    lm_event($id, 'صدور لایسنس (' . LM_PLANS[$plan] . ') برای ' . $domain);
    return ['id' => $id, 'key' => $key];
}

/** extend the license by $days (from now if already expired). Online sites pick the new expiry up at their next check-in. */
function lm_renew(string $id, int $days): int
{
    $l = lm_row('SELECT * FROM licenses WHERE id = ?', [$id]);
    if (!$l) throw new RuntimeException('لایسنس پیدا نشد.');
    $base = max((int)$l['exp'], time());
    $exp = $days > 0 ? $base + $days * 86400 : (int)$l['exp'];
    lm_run('UPDATE licenses SET exp = ? WHERE id = ?', [$exp, $id]);
    lm_event($id, 'تمدید ' . $days . ' روزه؛ انقضای جدید ' . date('Y-m-d', $exp));
    return $exp;
}

/** tamper check: every reported core file must match a hash of a registered release */
function lm_judge_files(array $fp): array
{
    $known = [];
    foreach (lm_q('SELECT file, hash FROM releases') as $r) $known[$r['file']][$r['hash']] = true;
    if (!$known) return ['known' => false, 'modified' => []];           // no releases registered yet: nothing to judge
    $bad = [];
    foreach ($fp as $file => $hash) if (!isset($known[$file][$hash])) $bad[] = $file;
    return ['known' => true, 'modified' => $bad];
}

/** the answer to a site's check-in (everything inside is signed) */
function lm_check(array $req, string $ip): array
{
    $id = (string)($req['id'] ?? '');
    $domain = strtolower((string)($req['domain'] ?? ''));
    $l = $id !== '' ? lm_row('SELECT * FROM licenses WHERE id = ?', [$id]) : null;
    $now = time();
    $status = 'active'; $msg = '';
    $fp = is_array($req['fp'] ?? null) ? $req['fp'] : [];
    $j = ['known' => false, 'modified' => []];

    if (!$l) { $status = 'revoked'; $msg = 'لایسنس نامعتبر است.'; }
    else {
        $status = $l['status'];
        if ($status === 'active' && $l['domain'] !== '*' && $domain !== $l['domain']) { $status = 'suspended'; $msg = 'دامنه سایت با لایسنس مطابقت ندارد.'; }
        if ($status === 'active' && (int)$l['exp'] <= $now) { $status = 'expired'; $msg = 'لایسنس منقضی شده است. برای تمدید با ما تماس بگیرید.'; }
        if ($status === 'active' && $fp) {
            $j = lm_judge_files($fp);
            if ($j['modified'] && (int)$l['lock_on_tamper'] === 1) { $status = 'tampered'; $msg = 'فایل‌های سایت با نسخه مجاز مطابقت ندارند.'; }
        }
        $install = substr(preg_replace('/[^a-f0-9]/', '', (string)($req['install'] ?? '')), 0, 32);
        if ($install !== '') {
            lm_run('INSERT INTO activations (license_id, install, domain, ver, php, ip, first_seen, last_seen, checks, tampered, modified)
                VALUES (?,?,?,?,?,?,?,?,1,?,?) ON CONFLICT(license_id, install) DO UPDATE SET domain=excluded.domain, ver=excluded.ver,
                php=excluded.php, ip=excluded.ip, last_seen=excluded.last_seen, checks=checks+1, tampered=excluded.tampered, modified=excluded.modified',
                [$id, $install, $domain, mb_substr((string)($req['ver'] ?? ''), 0, 20), mb_substr((string)($req['php'] ?? ''), 0, 20), $ip, $now, $now,
                 $j['modified'] ? 1 : 0, $j['modified'] ? implode(',', $j['modified']) : null]);
        }
    }
    $chk = (int)($l['chk'] ?? 3);
    return ['r' => lm_sign(['id' => $id, 'status' => $status, 'exp' => (int)($l['exp'] ?? 0), 'nonce' => (string)($req['nonce'] ?? ''),
        'domain' => $domain, 'ts' => $now, 'until' => $now + $chk * 86400, 'msg' => $msg])];
}

/** reads the core files of a site checkout and their fingerprints (same rule as License::fingerprints) */
function lm_site_fingerprints(string $siteDir): array
{
    $src = (string)file_get_contents($siteDir . '/app/lib/License.php');
    preg_match("/private const CORE = \\[(.*?)\\];/s", $src, $m);
    preg_match_all("/'([^']+)'/", $m[1] ?? '', $files);
    $out = [];
    foreach ($files[1] as $rel) {
        $f = $siteDir . '/' . $rel;
        $out[$rel] = is_file($f) ? substr(hash('sha256', str_replace("\r\n", "\n", (string)file_get_contents($f))), 0, 16) : 'missing';
    }
    return $out;
}

function lm_h(mixed $s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function lm_fa(mixed $s): string { return strtr((string)$s, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']); }
function lm_date(?int $ts): string
{
    if (!$ts) return '—';
    [$y, $m, $d] = g2j_lm((int)date('Y', $ts), (int)date('n', $ts), (int)date('j', $ts));
    return lm_fa(sprintf('%04d/%02d/%02d', $y, $m, $d)) . ' ' . lm_fa(date('H:i', $ts));
}

/** Gregorian → Jalali (same algorithm as the site) */
function g2j_lm(int $gy, int $gm, int $gd): array
{
    $gdm = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    $gy2 = $gm > 2 ? $gy + 1 : $gy;
    $days = 355666 + (365 * $gy) + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100) + intdiv($gy2 + 399, 400) + $gd + $gdm[$gm - 1];
    $jy = -1595 + 33 * intdiv($days, 12053); $days %= 12053;
    $jy += 4 * intdiv($days, 1461); $days %= 1461;
    if ($days > 365) { $jy += intdiv($days - 1, 365); $days = ($days - 1) % 365; }
    $jm = $days < 186 ? 1 + intdiv($days, 31) : 7 + intdiv($days - 186, 30);
    $jd = 1 + ($days < 186 ? $days % 31 : ($days - 186) % 30);
    return [$jy, $jm, $jd];
}

function lm_status(array $l): array
{
    $now = time();
    if ($l['status'] === 'revoked') return ['لغو شده', 'bad'];
    if ($l['status'] === 'suspended') return ['معلق', 'warn'];
    if ((int)$l['exp'] <= $now) return ['منقضی', 'bad'];
    $left = (int)ceil(((int)$l['exp'] - $now) / 86400);
    return [lm_fa($left) . ' روز مانده', $left <= 30 ? 'warn' : 'ok'];
}

const LM_CSS = <<<'CSS'
*{box-sizing:border-box}body{margin:0;font:15px/1.8 Tahoma,Vazirmatn,system-ui,sans-serif;background:#f3f5f9;color:#1f2937}
.ltr{direction:ltr;unicode-bidi:embed}.muted{color:#64748b}.small{font-size:.85em}
nav.top{display:flex;gap:16px;align-items:center;padding:12px 24px;background:#0f172a;color:#fff}nav.top a{color:#cbd5e1;text-decoration:none}nav.top a:hover{color:#fff}nav.top b{margin-left:auto}
main{max-width:1100px;margin:24px auto;padding:0 16px}h1{font-size:1.5em;margin:0 0 16px}h2{font-size:1.1em;margin:0 0 12px}h3{font-size:1em;margin:18px 0 8px}
.card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:20px;box-shadow:0 1px 2px #0000000d}.card.wide{max-width:640px}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:24px}@media(max-width:800px){.grid{grid-template-columns:1fr}}
label{display:block;margin-bottom:12px;font-weight:600}label input,label select,label textarea{display:block;width:100%;margin-top:6px;padding:9px 12px;border:1px solid #cbd5e1;border-radius:10px;font:inherit;font-weight:400;background:#fff}
label.check{display:flex;gap:8px;align-items:center;font-weight:400}label.check input{width:auto;margin:0}
.btn{display:inline-block;padding:9px 18px;border-radius:10px;border:0;background:#f59e0b;color:#1f1300;font:inherit;font-weight:700;cursor:pointer}
.btn-ghost{background:#e2e8f0;color:#1e293b}.btn-danger{background:#dc2626;color:#fff}
.row{display:flex;gap:10px;align-items:end;flex-wrap:wrap;margin-bottom:10px}.row label{margin:0}.row input{width:120px}.row form{display:flex;gap:8px;align-items:end}
form.inline{display:inline}button.link{background:none;border:0;color:#fca5a5;cursor:pointer;font:inherit}
table{width:100%;border-collapse:collapse;background:#fff;border-radius:12px;overflow:hidden;margin-bottom:24px;font-size:.92em}th,td{padding:10px 12px;border-bottom:1px solid #eef2f7;text-align:right;vertical-align:top}th{background:#f8fafc;font-weight:700}
table.kv td:first-child{color:#64748b;width:38%}
.badge{display:inline-block;padding:2px 10px;border-radius:99px;font-size:.8em;font-weight:700;background:#e2e8f0}.badge.ok{background:#dcfce7;color:#166534}.badge.warn{background:#fef3c7;color:#92400e}.badge.bad{background:#fee2e2;color:#991b1b}.badge.info{background:#dbeafe;color:#1e40af}
.alert{padding:12px 16px;border-radius:10px;margin-bottom:16px}.alert.ok{background:#dcfce7;color:#166534}.alert.error{background:#fee2e2;color:#991b1b}.alert.warn{background:#fef3c7;color:#92400e}
textarea.key{display:block;width:100%;font:13px/1.5 ui-monospace,monospace;word-break:break-all;margin:6px 0 8px;padding:9px;border:1px solid #cbd5e1;border-radius:10px}code{background:#e2e8f0;padding:2px 6px;border-radius:6px}
.login{max-width:420px;margin-top:80px}.login .card{padding:28px}.events{padding-right:18px}.events li{margin-bottom:6px}
CSS;
