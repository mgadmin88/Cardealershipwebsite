<?php
declare(strict_types=1);
/**
 * License generator — VENDOR ONLY. Keep this file and license-keys/ off the
 * customer's server. It signs license keys with your Ed25519 private key.
 *
 * CLI:
 *   php tools/license-gen.php --init                         # create a new keypair (once)
 *   php tools/license-gen.php --plan trial --domain example.com # 1 year + 1 month (trial)
 *   php tools/license-gen.php --plan 1y --domain example.com # 1-year license for a domain
 *   php tools/license-gen.php --plan 1m --domain example.com --name "نمایندگی اکباتان"
 *   php tools/license-gen.php --plan custom --days 90 --domain '*'   # 90 days, any domain
 *
 * Local web form (run on YOUR computer only):
 *   php -S 127.0.0.1:9000 tools/license-gen.php   then open http://127.0.0.1:9000
 */

const KEY_DIR = __DIR__ . '/../license-keys';
const PRIV = KEY_DIR . '/private.key';
const PUB  = KEY_DIR . '/public.key';

if (!function_exists('sodium_crypto_sign_detached')) {
    fwrite(STDERR, "ext-sodium is required.\n");
    exit(1);
}

function b64url(string $b): string { return rtrim(strtr(base64_encode($b), '+/', '-_'), '='); }

function load_private(): string
{
    $b64 = getenv('LICENSE_PRIVATE_KEY') ?: (is_file(PRIV) ? trim((string)file_get_contents(PRIV)) : '');
    $sk = $b64 !== '' ? base64_decode($b64, true) : false;
    if ($sk === false || strlen($sk) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
        throw new RuntimeException('کلید خصوصی پیدا نشد یا نامعتبر است. ابتدا --init را اجرا کنید یا فایل license-keys/private.key را قرار دهید.');
    }
    return $sk;
}

function init_keys(): array
{
    if (!is_dir(KEY_DIR)) mkdir(KEY_DIR, 0700, true);
    $kp = sodium_crypto_sign_keypair();
    $pub = base64_encode(sodium_crypto_sign_publickey($kp));
    file_put_contents(PRIV, base64_encode(sodium_crypto_sign_secretkey($kp)) . "\n");
    file_put_contents(PUB, $pub . "\n");
    @chmod(PRIV, 0600);
    return ['public' => $pub];
}

/** @return array{key:string, payload:array} */
function make_license(string $plan, string $domain, ?int $days, string $name): array
{
    $plan = in_array($plan, ['trial', '1m', '1y', 'custom'], true) ? $plan : 'trial';
    $days ??= ($plan === '1m' ? 30 : ($plan === '1y' ? 365 : 395));
    $days = max(1, min(3650, $days));
    $domain = strtolower(trim($domain));
    $domain = $domain === '' ? '*' : preg_replace('#^https?://#', '', $domain);
    $domain = preg_replace('#/.*$#', '', (string)$domain);
    $domain = (string)preg_replace('/^www\./', '', (string)$domain);

    $now = time();
    $payload = [
        'v' => 1,
        'id' => strtoupper(bin2hex(random_bytes(4))),
        'plan' => $plan,
        'domain' => $domain,
        'name' => mb_substr($name, 0, 120),
        'iat' => $now,
        'exp' => $plan === 'trial' ? strtotime('+13 months', $now) : ($plan === '1y' ? strtotime('+1 year', $now) : ($plan === '1m' ? strtotime('+1 month', $now) : $now + $days * 86400)),
    ];
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $sig = sodium_crypto_sign_detached($json, load_private());
    return ['key' => b64url($json) . '.' . b64url($sig), 'payload' => $payload];
}

/* ------------------------------ CLI ------------------------------ */
if (PHP_SAPI === 'cli') {
    $args = [];
    foreach (array_slice($argv, 1) as $i => $_) {}
    for ($i = 1; $i < count($argv); $i++) {
        if (str_starts_with($argv[$i], '--')) {
            $k = substr($argv[$i], 2);
            $v = ($i + 1 < count($argv) && !str_starts_with($argv[$i + 1], '--')) ? $argv[++$i] : '1';
            $args[$k] = $v;
        }
    }
    if (isset($args['init'])) {
        if (is_file(PRIV) && !isset($args['force'])) { fwrite(STDERR, "کلید از قبل وجود دارد. برای ساخت مجدد --force بدهید (همه لایسنس‌های قبلی باطل می‌شوند).\n"); exit(1); }
        $r = init_keys();
        echo "کلید ساخته شد. این مقدار را در app/lib/License.php در PUBLIC_KEY بگذارید:\n\n{$r['public']}\n";
        exit(0);
    }
    try {
        $r = make_license($args['plan'] ?? '1y', $args['domain'] ?? '*', isset($args['days']) ? (int)$args['days'] : null, $args['name'] ?? '');
    } catch (Throwable $e) { fwrite(STDERR, $e->getMessage() . "\n"); exit(1); }
    $p = $r['payload'];
    fwrite(STDERR, sprintf("کد:%s  پلن:%s  دامنه:%s  انقضا:%s\n", $p['id'], $p['plan'], $p['domain'], date('Y-m-d', $p['exp'])));
    echo $r['key'] . "\n";
    exit(0);
}

/* --------------------------- local web --------------------------- */
$out = null; $err = null; $pub = is_file(PUB) ? trim((string)file_get_contents(PUB)) : '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        if (($_POST['do'] ?? '') === 'init') { $pub = init_keys()['public']; }
        else {
            $r = make_license($_POST['plan'] ?? '1y', $_POST['domain'] ?? '*',
                ($_POST['plan'] ?? '') === 'custom' ? (int)($_POST['days'] ?? 365) : null, $_POST['name'] ?? '');
            $out = $r;
        }
    } catch (Throwable $e) { $err = $e->getMessage(); }
}
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
?><!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><title>تولید لایسنس</title>
<style>
body{font-family:Tahoma,system-ui,sans-serif;background:#0f1320;color:#e8ecf4;margin:0;padding:32px;line-height:1.9}
.box{max-width:620px;margin:auto;background:#19202f;border:1px solid #2a3346;border-radius:16px;padding:26px}
h1{font-size:1.3rem;margin:0 0 4px}.muted{color:#93a0b8;font-size:.85rem}
label{display:block;margin:14px 0 4px;font-size:.9rem}
input,select{width:100%;box-sizing:border-box;padding:10px 12px;border-radius:10px;border:1px solid #2f3a50;background:#0f1524;color:#e8ecf4;font:inherit}
button{margin-top:18px;padding:11px 20px;border:0;border-radius:10px;background:linear-gradient(135deg,#ff9a2e,#ff7a3d);color:#1a1209;font-weight:800;cursor:pointer;font:inherit}
.key{margin-top:18px;background:#0b1020;border:1px solid #2f3a50;border-radius:10px;padding:14px;word-break:break-all;direction:ltr;font-family:monospace;font-size:.82rem}
.err{background:#3a1420;border:1px solid #7a2436;color:#ffb4c0;padding:10px 14px;border-radius:10px;margin-top:12px}
.row{display:flex;gap:12px}.row>div{flex:1}
.warn{background:#2a2410;border:1px solid #5a4a16;color:#ffd98a;padding:8px 12px;border-radius:10px;font-size:.82rem;margin-top:14px}
</style></head><body><div class="box">
<h1>تولید لایسنس — فقط برای فروشنده</h1>
<p class="muted">این ابزار باید فقط روی رایانه خودتان اجرا شود و هرگز روی سرور مشتری قرار نگیرد.</p>
<?php if (!is_file(PRIV)): ?>
  <div class="warn">کلید خصوصی وجود ندارد.</div>
  <form method="post"><input type="hidden" name="do" value="init"><button>ساخت کلید جدید</button></form>
<?php else: ?>
  <?php if ($err): ?><div class="err"><?= $e($err) ?></div><?php endif ?>
  <form method="post">
    <div class="row">
      <div><label>نوع لایسنس</label><select name="plan" onchange="document.getElementById('d').style.display=this.value==='custom'?'block':'none'">
        <option value="1y">یک ساله</option><option value="1m">یک ماهه</option><option value="custom">سفارشی (روز دلخواه)</option></select></div>
      <div id="d" style="display:none"><label>تعداد روز</label><input name="days" type="number" value="90" min="1" max="3650"></div>
    </div>
    <label>دامنه مشتری (برای «هر دامنه» ستاره بگذارید)</label><input name="domain" placeholder="example.com" dir="ltr">
    <label>نام مشتری / نمایندگی (اختیاری)</label><input name="name" placeholder="نمایشگاه ...">
    <button>تولید لایسنس</button>
  </form>
  <?php if ($out): $p = $out['payload']; ?>
    <p class="muted" style="margin-top:20px">کد <?= $e($p['id']) ?> — <?= $e(License_label($p['plan'])) ?> — دامنه <?= $e($p['domain']) ?> — انقضا <?= $e(date('Y-m-d', $p['exp'])) ?></p>
    <div class="key"><?= $e($out['key']) ?></div>
    <p class="muted">این کد را به مشتری بدهید تا در پنل مدیریت، بخش «لایسنس» وارد کند.</p>
  <?php endif ?>
<?php endif ?>
<p class="muted" style="margin-top:20px">کلید عمومی فعلی: <code style="direction:ltr"><?= $e($pub) ?></code></p>
</div></body></html>
<?php
function License_label(string $p): string { return ['trial' => 'آزمایشی ۱۳ ماهه', '1m' => 'یک ماهه', '1y' => 'یک ساله', 'custom' => 'سفارشی'][$p] ?? $p; }
