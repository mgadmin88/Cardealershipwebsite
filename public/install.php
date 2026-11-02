<?php
/**
 * نصب‌کننده
 *  وب:  /install.php
 *  CLI: php public/install.php admin "StrongPass123" [--mysql host db user pass] [--no-demo]
 */
declare(strict_types=1);

$root = dirname(__DIR__);
define('PUB', __DIR__);
$lock = $root . '/storage/installed.lock';
$cli = PHP_SAPI === 'cli';

if (is_file($lock)) {
    if ($cli) exit("Already installed. Delete storage/installed.lock to reinstall.\n");
    http_response_code(403);
    exit('<meta charset="utf-8"><p style="font-family:tahoma;direction:rtl">سایت قبلاً نصب شده است.</p>');
}

foreach (['/storage', '/storage/logs', '/storage/cache', '/storage/sessions'] as $d) {
    if (!is_dir($root . $d)) @mkdir($root . $d, 0755, true);
}
if (!is_dir(PUB . '/uploads')) @mkdir(PUB . '/uploads', 0755, true);

function write_config(string $root, array $db): void
{
    $tpl = is_file($root . '/config.sample.php') ? (string)file_get_contents($root . '/config.sample.php') : '';
    if (!str_contains($tpl, '__APP_KEY__')) $tpl = default_config_template();   // sample missing or already edited
    $tpl = str_replace('__APP_KEY__', bin2hex(random_bytes(32)), $tpl);
    if ($db['driver'] === 'mysql') {
        $tpl = str_replace("'driver'  => 'sqlite'", "'driver'  => 'mysql'", $tpl);
        $tpl = preg_replace("/'host'    => '[^']*'/", "'host'    => " . var_export($db['host'], true), $tpl);
        $tpl = preg_replace("/'name'    => '[^']*'/", "'name'    => " . var_export($db['name'], true), $tpl);
        $tpl = preg_replace("/'user'    => '[^']*'/", "'user'    => " . var_export($db['user'], true), $tpl);
        $tpl = preg_replace("/'pass'    => '[^']*'/", "'pass'    => " . var_export($db['pass'], true), $tpl);
    }
    if (file_put_contents($root . '/config.php', $tpl) === false) {
        throw new RuntimeException('امکان ساخت فایل config.php نیست. دسترسی نوشتن (755) پوشه ' . $root . ' را بررسی کنید.');
    }
    @chmod($root . '/config.php', 0640);
}

function default_config_template(): string
{
    return <<<'CFG'
<?php
/**
 * فایل پیکربندی — این فایل را با نام config.php کپی کنید (نصب‌کننده خودکار این کار را انجام می‌دهد).
 * هرگز این فایل را در پوشه public قرار ندهید.
 */
return [
    'debug'    => false,                 // در محیط واقعی حتماً false
    'timezone' => 'Asia/Tehran',
    'base_url' => '',                    // مثال: https://example.com  (خالی = تشخیص خودکار)
    'force_https' => false,              // پس از نصب SSL روی true بگذارید
    'app_key'  => '__APP_KEY__',         // کلید تصادفی امنیتی (توسط نصب‌کننده ساخته می‌شود)

    'db' => [
        'driver'  => 'sqlite',           // sqlite یا mysql
        'path'    => __DIR__ . '/storage/database.sqlite',
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'autosite',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],

    'upload' => [
        'max_size_mb' => 8,
        'max_width'   => 1920,           // تصاویر بزرگ‌تر به این عرض کوچک می‌شوند
    ],
];
CFG;
}

$error = null;
$done = false;

if ($cli) {
    $args = array_slice($argv, 1);
    $user = $args[0] ?? 'admin';
    $pass = $args[1] ?? '';
    $db = ['driver' => 'sqlite'];
    if (($i = array_search('--mysql', $args, true)) !== false) {
        $db = ['driver' => 'mysql', 'host' => $args[$i + 1], 'name' => $args[$i + 2], 'user' => $args[$i + 3], 'pass' => $args[$i + 4] ?? ''];
    }
    $demo = !in_array('--no-demo', $args, true);
    if (strlen($pass) < 10) exit("Password must be at least 10 characters.\n");
    if (!is_file($root . '/config.php')) write_config($root, $db);
    require $root . '/app/bootstrap.php';
    Schema::install();
    Seeder::run($user, $pass, $demo);
    Settings::set('schema_version', (string)Schema::VERSION);
    file_put_contents($lock, date('c'));
    exit("Installed successfully. Admin: /admin  user: $user\n");
}

// ---------------- web installer ----------------
session_start();
if (empty($_SESSION['inst_csrf'])) $_SESSION['inst_csrf'] = bin2hex(random_bytes(16));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['inst_csrf'], $_POST['_t'] ?? '')) $error = 'توکن نامعتبر است.';
    $user = trim($_POST['user'] ?? '');
    $pass = $_POST['pass'] ?? '';
    if (!$error && !preg_match('/^[a-zA-Z0-9_.]{4,30}$/', $user)) $error = 'نام کاربری فقط حروف انگلیسی و عدد (۴ تا ۳۰ کاراکتر).';
    if (!$error && (strlen($pass) < 10 || !preg_match('/\d/', $pass) || !preg_match('/[a-zA-Z]/', $pass))) $error = 'رمز عبور حداقل ۱۰ کاراکتر و شامل حرف و عدد باشد.';
    $db = ['driver' => $_POST['driver'] === 'mysql' ? 'mysql' : 'sqlite', 'host' => $_POST['host'] ?? '', 'name' => $_POST['dbname'] ?? '', 'user' => $_POST['dbuser'] ?? '', 'pass' => $_POST['dbpass'] ?? ''];
    if (!$error && $db['driver'] === 'mysql') {
        try { new PDO("mysql:host={$db['host']};dbname={$db['name']};charset=utf8mb4", $db['user'], $db['pass']); }
        catch (Throwable $e) { $error = 'اتصال به MySQL ناموفق بود: ' . $e->getMessage(); }
    }
    if (!$error) {
        try {
            write_config($root, $db);
            require $root . '/app/bootstrap.php';
            Schema::install();
            Seeder::run($user, $pass, !empty($_POST['demo']));
            Settings::set('schema_version', (string)Schema::VERSION);
            file_put_contents($lock, date('c'));
            $done = true;
        } catch (Throwable $e) {
            @unlink($root . '/config.php');
            $error = 'خطا در نصب: ' . $e->getMessage();
        }
    }
}
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
?><!doctype html>
<html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>نصب سایت</title>
<style>
@font-face{font-family:V;src:url(assets/fonts/Vazirmatn-FD-Regular.woff2)}
body{font-family:V,tahoma;background:linear-gradient(135deg,#0f172a,#1e3a5f);min-height:100vh;margin:0;display:grid;place-items:center;color:#0f172a}
.box{background:#fff;border-radius:20px;padding:32px;width:min(460px,92vw);box-shadow:0 30px 80px rgba(0,0,0,.35)}
h1{font-size:22px;margin:0 0 6px}p{color:#64748b;font-size:14px}label{display:block;font-size:13px;margin:14px 0 6px;color:#334155}
input,select{width:100%;box-sizing:border-box;padding:11px 13px;border:1px solid #cbd5e1;border-radius:10px;font:inherit}
button{margin-top:22px;width:100%;padding:13px;border:0;border-radius:12px;background:#f7931e;color:#fff;font:inherit;font-weight:700;cursor:pointer}
.err{background:#fee2e2;color:#991b1b;padding:10px 14px;border-radius:10px;font-size:13px}.ok{background:#dcfce7;color:#166534;padding:14px;border-radius:10px}
.my{display:none}.row{display:flex;gap:10px}.row>*{flex:1}
</style></head><body><div class="box">
<?php if ($done): ?>
  <h1>✅ نصب با موفقیت انجام شد</h1>
  <div class="ok">برای امنیت بیشتر، فایل <b>public/install.php</b> را از سرور حذف کنید.</div>
  <p><a href="./">مشاهده سایت</a> — <a href="admin">ورود به پنل مدیریت</a></p>
<?php else: ?>
  <h1>نصب سایت نمایندگی خودرو</h1><p>اطلاعات زیر را وارد کنید. نسخه PHP: <?= PHP_VERSION ?></p>
  <?php if ($error): ?><div class="err"><?= $h($error) ?></div><?php endif ?>
  <form method="post">
    <input type="hidden" name="_t" value="<?= $h($_SESSION['inst_csrf']) ?>">
    <label>پایگاه داده</label>
    <select name="driver" id="drv"><option value="sqlite">SQLite (بدون نیاز به تنظیم)</option><option value="mysql">MySQL / MariaDB</option></select>
    <div class="my" id="my">
      <div class="row"><div><label>هاست</label><input name="host" value="127.0.0.1" dir="ltr"></div><div><label>نام دیتابیس</label><input name="dbname" dir="ltr"></div></div>
      <div class="row"><div><label>کاربر</label><input name="dbuser" dir="ltr"></div><div><label>رمز</label><input name="dbpass" type="password" dir="ltr"></div></div>
    </div>
    <label>نام کاربری مدیر</label><input name="user" value="admin" dir="ltr" required>
    <label>رمز عبور مدیر (حداقل ۱۰ کاراکتر)</label><input name="pass" type="password" dir="ltr" required minlength="10">
    <label><input type="checkbox" name="demo" value="1" checked style="width:auto"> نصب داده‌های نمونه (خودرو، قطعه، اسلاید)</label>
    <button>نصب</button>
  </form>
  <script>document.getElementById('drv').onchange=e=>document.getElementById('my').style.display=e.target.value==='mysql'?'block':'none'</script>
<?php endif ?>
</div></body></html>
