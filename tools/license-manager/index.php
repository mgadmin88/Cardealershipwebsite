<?php
declare(strict_types=1);
/**
 * License manager — VENDOR ONLY. Run it on your own host or on your computer (php -S 127.0.0.1:9100 index.php).
 *   - ?api=check   the endpoint the customers' sites call (JSON in, signed JSON out)
 *   - everything else is the management panel (password protected)
 */
require __DIR__ . '/lib.php';

/* ---------------- API: site check-ins ---------------- */
if (($_GET['api'] ?? '') === 'check') {
    header('Cache-Control: no-store');
    $raw = (string)file_get_contents('php://input', false, null, 0, 16384);
    $req = json_decode($raw, true);
    if (!is_array($req)) { http_response_code(400); echo json_encode(['error' => 'bad request']); exit; }
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    echo json_encode(lm_check($req, $ip), JSON_UNESCAPED_SLASHES);
    exit;
}

/* ---------------- session, password, csrf ---------------- */
session_name('LM_SESS');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict', 'secure' => (($_SERVER['HTTPS'] ?? '') === 'on')]);
session_start();
header('X-Frame-Options: DENY');
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; img-src 'self' data:");
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

$hasPass = lm_get('pass_hash') !== '';
$loggedIn = !empty($_SESSION['lm']);
$csrf = $_SESSION['csrf'] ??= bin2hex(random_bytes(16));
$route = (string)($_GET['p'] ?? 'home');
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
function go(string $to): never { header('Location: ?' . $to); exit; }
function flash(string $t, string $m): void { $_SESSION['flash'] = [$t, $m]; }

if (!$hasPass) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_csrf'] ?? '') === $csrf) {
        $pw = (string)($_POST['password'] ?? '');
        if (mb_strlen($pw) < 12) flash('error', 'رمز باید حداقل ۱۲ کاراکتر باشد.');
        elseif ($pw !== (string)($_POST['password2'] ?? '')) flash('error', 'تکرار رمز مطابقت ندارد.');
        else { lm_set('pass_hash', password_hash($pw, PASSWORD_DEFAULT)); lm_set('srv_url', 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . strtok((string)$_SERVER['REQUEST_URI'], '?') . '?api=check'); $_SESSION['lm'] = 1; go('p=home'); }
        go('p=setup');
    }
    render_head('راه‌اندازی مدیریت لایسنس');
    echo '<main class="login"><form method="post" class="card"><h1>ساخت رمز مدیریت</h1><p class="muted">این رمز فقط برای ورود به این پنل است. آن را جای امنی نگه دارید.</p>';
    flash_box($flash);
    echo '<input type="hidden" name="_csrf" value="' . $csrf . '"><label>رمز عبور (حداقل ۱۲ کاراکتر)<input type="password" name="password" required minlength="12" dir="ltr"></label>'
       . '<label>تکرار رمز<input type="password" name="password2" required dir="ltr"></label><button class="btn">ذخیره و ورود</button></form></main>';
    render_foot(); exit;
}

if (!$loggedIn) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_csrf'] ?? '') === $csrf && $route === 'login') {
        usleep(random_int(250000, 500000));
        if (password_verify((string)($_POST['password'] ?? ''), lm_get('pass_hash'))) { session_regenerate_id(true); $_SESSION['lm'] = 1; go('p=home'); }
        flash('error', 'رمز عبور اشتباه است.');
        go('p=login');
    }
    render_head('ورود');
    echo '<main class="login"><form method="post" action="?p=login" class="card"><h1>مدیریت لایسنس</h1>';
    flash_box($flash);
    echo '<input type="hidden" name="_csrf" value="' . $csrf . '"><label>رمز عبور<input type="password" name="password" required autofocus dir="ltr"></label><button class="btn">ورود</button></form></main>';
    render_foot(); exit;
}

// logged in: every state-changing request needs the csrf token
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['_csrf'] ?? '') !== $csrf) { http_response_code(403); exit('CSRF'); }
    try {
        if ($route === 'create') {
            $r = lm_create($_POST);
            flash('success', 'لایسنس صادر شد. کد را کپی و برای مشتری بفرستید.');
            go('p=view&id=' . $r['id']);
        }
        if ($route === 'act') {
            $id = (string)($_POST['id'] ?? '');
            $op = (string)($_POST['op'] ?? '');
            if ($op === 'renew') { $days = max(1, min(3650, (int)($_POST['days'] ?? 0))); $exp = lm_renew($id, $days); flash('success', 'تمدید شد؛ انقضای جدید: ' . lm_date($exp) . '. سایت در بررسی بعدی‌اش آن را می‌گیرد.'); }
            elseif (in_array($op, ['suspend', 'resume', 'revoke'], true)) {
                $st = ['suspend' => 'suspended', 'resume' => 'active', 'revoke' => 'revoked'][$op];
                lm_run('UPDATE licenses SET status = ? WHERE id = ?', [$st, $id]);
                lm_event($id, ['suspend' => 'تعلیق لایسنس', 'resume' => 'فعال‌سازی مجدد', 'revoke' => 'باطل‌سازی لایسنس'][$op]);
                flash('success', 'وضعیت لایسنس تغییر کرد.');
            } elseif ($op === 'meta') {
                lm_run('UPDATE licenses SET name = ?, notes = ?, chk = ?, lock_on_tamper = ? WHERE id = ?',
                    [mb_substr(trim((string)$_POST['name']), 0, 120), mb_substr(trim((string)$_POST['notes']), 0, 1000),
                     max(1, min(90, (int)$_POST['chk'])), empty($_POST['lock_on_tamper']) ? 0 : 1, $id]);
                flash('success', 'اطلاعات ذخیره شد. (تغییر زمان بررسی و قفل تغییر فایل، در بررسی بعدی سایت اعمال می‌شود.)');
            }
            go('p=view&id=' . $id);
        }
        if ($route === 'settings') {
            lm_set('srv_url', trim((string)($_POST['srv_url'] ?? '')));
            flash('success', 'تنظیمات ذخیره شد. لایسنس‌های جدید با این آدرس ساخته می‌شوند.');
            go('p=settings');
        }
        if ($route === 'password') {
            if (!password_verify((string)($_POST['current'] ?? ''), lm_get('pass_hash'))) flash('error', 'رمز فعلی اشتباه است.');
            elseif (mb_strlen((string)$_POST['password']) < 12 || $_POST['password'] !== $_POST['password2']) flash('error', 'رمز جدید حداقل ۱۲ کاراکتر و یکسان باشد.');
            else { lm_set('pass_hash', password_hash((string)$_POST['password'], PASSWORD_DEFAULT)); flash('success', 'رمز تغییر کرد.'); }
            go('p=settings');
        }
        if ($route === 'logout') { session_destroy(); go('p=login'); }
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
        go($route === 'create' ? 'p=new' : 'p=home');
    }
    go('p=home');
}

/* ---------------- pages ---------------- */
render_head('مدیریت لایسنس');
echo '<nav class="top"><b>مدیریت لایسنس</b><a href="?p=home">لایسنس‌ها</a><a href="?p=new">لایسنس جدید</a><a href="?p=releases">نسخه‌های مجاز</a><a href="?p=settings">تنظیمات</a>'
   . '<form method="post" action="?p=logout" class="inline"><input type="hidden" name="_csrf" value="' . $csrf . '"><button class="link">خروج</button></form></nav><main>';
flash_box($flash);

if ($route === 'home') {
    $rows = lm_q('SELECT l.*, (SELECT COUNT(*) FROM activations a WHERE a.license_id = l.id) AS sites, (SELECT MAX(last_seen) FROM activations a WHERE a.license_id = l.id) AS seen,
        (SELECT COUNT(*) FROM activations a WHERE a.license_id = l.id AND a.tampered = 1) AS tampered FROM licenses l ORDER BY l.created DESC');
    $q = trim((string)($_GET['q'] ?? ''));
    echo '<h1>لایسنس‌ها <small class="muted">(' . lm_fa(count($rows)) . ')</small></h1>';
    if (!lm_val('SELECT 1 FROM releases LIMIT 1')) echo '<div class="alert warn">هنوز نسخه‌های مجاز ثبت نشده‌اند؛ تغییر فایل‌ها بررسی نمی‌شود. بعد از هر نسخه، دستور «bin/add-release.php» را اجرا کنید.</div>';
    echo '<table><thead><tr><th>مشتری / توضیح</th><th>دامنه</th><th>نوع</th><th>وضعیت</th><th>انقضا</th><th>سایت‌ها</th><th>آخرین بررسی</th></tr></thead><tbody>';
    foreach ($rows as $l) {
        if ($q !== '' && !str_contains(mb_strtolower($l['name'] . ' ' . $l['domain'] . ' ' . $l['id']), mb_strtolower($q))) continue;
        [$stl, $cls] = lm_status($l);
        echo '<tr><td><a href="?p=view&id=' . lm_h($l['id']) . '"><b>' . lm_h($l['name'] ?: '—') . '</b></a><small class="muted ltr"> ' . lm_h($l['id']) . '</small></td>'
           . '<td class="ltr">' . lm_h($l['domain']) . '</td><td>' . lm_h(LM_PLANS[$l['plan']] ?? $l['plan']) . '</td>'
           . '<td><span class="badge ' . $cls . '">' . lm_h($stl) . '</span>' . ((int)$l['tampered'] ? ' <span class="badge bad">تغییر فایل</span>' : '') . '</td>'
           . '<td>' . lm_date((int)$l['exp']) . '</td><td>' . lm_fa($l['sites']) . '</td><td>' . ($l['seen'] ? lm_date((int)$l['seen']) : '—') . '</td></tr>';
    }
    echo '</tbody></table>';
    if (!$rows) echo '<p class="muted">هنوز لایسنسی صادر نشده است. از «لایسنس جدید» شروع کنید.</p>';
}

elseif ($route === 'new') {
    echo '<h1>صدور لایسنس جدید</h1><form method="post" action="?p=create" class="card wide">';
    echo '<input type="hidden" name="_csrf" value="' . $csrf . '">';
    echo '<label>نام مشتری / نمایندگی<input name="name" required maxlength="120" placeholder="مثلاً نمایندگی اکباتان"></label>';
    echo '<label>دامنه سایت مشتری<input name="domain" required dir="ltr" placeholder="example.com یا * برای هر دامنه"><small class="muted">فقط همین دامنه کار می‌کند. کپی سایت روی دامنه دیگر کار نمی‌کند.</small></label>';
    echo '<label>نوع لایسنس<select name="plan" id="plan">';
    foreach (LM_PLANS as $k => $l) echo '<option value="' . $k . '"' . ($k === 'trial' ? ' selected' : '') . '>' . lm_h($l) . '</option>';
    echo '</select><small class="muted">آزمایشی: ۱ سال و ۱ ماه. یک‌ساله: ۱۲ ماه. یک‌ماهه: ۱ ماه.</small></label>';
    echo '<label>روز (فقط برای «سفارشی»)<input name="days" type="number" min="1" max="3650" value="90"></label>';
    echo '<label>بررسی آنلاین هر چند روز؟<input name="chk" type="number" min="1" max="90" value="3"><small class="muted">اگر سایت این مدت به سرور شما وصل نشود، قفل می‌شود. برای جلوگیری از قطع ناخواسته، ۳ روز پیش‌فرض امن‌تر است؛ برای میزبانی مطمئن ۷ روز هم مناسب است.</small></label>';
    echo '<label class="check"><input type="checkbox" name="lock_on_tamper" value="1" checked> اگر فایل‌های سایت تغییر کرده باشد، سایت قفل شود</label>';
    echo '<label>یادداشت داخلی<textarea name="notes" rows="2"></textarea></label>';
    echo '<button class="btn">صدور لایسنس</button></form>';
}

elseif ($route === 'view') {
    $id = (string)($_GET['id'] ?? '');
    $l = lm_row('SELECT * FROM licenses WHERE id = ?', [$id]);
    if (!$l) { echo '<p>لایسنس پیدا نشد.</p>'; }
    else {
        [$stl, $cls] = lm_status($l);
        echo '<h1>' . lm_h($l['name'] ?: 'لایسنس') . ' <span class="badge ' . $cls . '">' . lm_h($stl) . '</span></h1>';
        echo '<div class="grid"><section class="card"><h2>مشخصات</h2><table class="kv">'
           . '<tr><td>شناسه</td><td class="ltr">' . lm_h($l['id']) . '</td></tr><tr><td>دامنه</td><td class="ltr">' . lm_h($l['domain']) . '</td></tr>'
           . '<tr><td>نوع</td><td>' . lm_h(LM_PLANS[$l['plan']] ?? $l['plan']) . '</td></tr><tr><td>صدور</td><td>' . lm_date((int)$l['iat']) . '</td></tr>'
           . '<tr><td>انقضا</td><td>' . lm_date((int)$l['exp']) . '</td></tr><tr><td>بررسی هر</td><td>' . lm_fa($l['chk']) . ' روز</td></tr></table>';
        echo '<h3>ویرایش</h3><form method="post" action="?p=act"><input type="hidden" name="_csrf" value="' . $csrf . '"><input type="hidden" name="id" value="' . lm_h($l['id']) . '"><input type="hidden" name="op" value="meta">'
           . '<label>نام<input name="name" value="' . lm_h($l['name']) . '"></label><label>یادداشت<textarea name="notes" rows="2">' . lm_h($l['notes']) . '</textarea></label>'
           . '<label>بررسی هر چند روز<input name="chk" type="number" min="1" max="90" value="' . lm_h($l['chk']) . '"></label>'
           . '<label class="check"><input type="checkbox" name="lock_on_tamper" value="1"' . ((int)$l['lock_on_tamper'] ? ' checked' : '') . '> قفل در صورت تغییر فایل</label><button class="btn btn-ghost">ذخیره</button></form></section>';

        echo '<section class="card"><h2>عملیات</h2>';
        echo '<form method="post" action="?p=act" class="row"><input type="hidden" name="_csrf" value="' . $csrf . '"><input type="hidden" name="id" value="' . lm_h($l['id']) . '"><input type="hidden" name="op" value="renew">'
           . '<label>تمدید (روز)<input name="days" type="number" min="1" max="3650" value="365"></label><button class="btn">تمدید</button></form>';
        echo '<div class="row">';
        foreach ([['suspend', 'تعلیق', 'btn-ghost'], ['resume', 'فعال‌سازی', 'btn-ghost'], ['revoke', 'باطل‌سازی', 'btn-danger']] as [$op, $lbl, $c]) {
            echo '<form method="post" action="?p=act" data-confirm="' . $lbl . ' این لایسنس؟"><input type="hidden" name="_csrf" value="' . $csrf . '"><input type="hidden" name="id" value="' . lm_h($l['id']) . '"><input type="hidden" name="op" value="' . $op . '"><button class="btn ' . $c . '">' . $lbl . '</button></form>';
        }
        echo '</div><p class="muted small">تعلیق و باطل‌سازی در بررسی بعدی سایت اعمال می‌شود (معمولاً ظرف یک روز). سایت پاسخ‌های امضاشده را ذخیره می‌کند و آن‌ها را نمی‌تواند تغییر دهد.</p>';
        echo '<h3>کد لایسنس</h3><textarea readonly rows="4" class="ltr key" onclick="this.select()">' . lm_h($l['key_text']) . '</textarea><small class="muted">این کد را فقط برای مشتری همان دامنه بفرستید. برای نسخه‌های آفلاین (بدون بررسی آنلاین) کافی است.</small>';
        echo '</section></div>';

        echo '<h2>سایت‌های فعال</h2><table><thead><tr><th>نصب</th><th>دامنه</th><th>نسخه</th><th>آخرین بررسی</th><th>دفعات</th><th>وضعیت فایل‌ها</th></tr></thead><tbody>';
        foreach (lm_q('SELECT * FROM activations WHERE license_id = ? ORDER BY last_seen DESC', [$id]) as $a) {
            echo '<tr><td class="ltr">' . lm_h(substr($a['install'], 0, 8)) . '</td><td class="ltr">' . lm_h($a['domain']) . '</td><td class="ltr">' . lm_h($a['ver']) . ' / PHP ' . lm_h($a['php']) . '</td>'
               . '<td>' . lm_date((int)$a['last_seen']) . '</td><td>' . lm_fa($a['checks']) . '</td><td>'
               . ((int)$a['tampered'] ? '<span class="badge bad">تغییر یافته: ' . lm_h($a['modified']) . '</span>' : '<span class="badge ok">سالم</span>') . '</td></tr>';
        }
        echo '</tbody></table>';
        if (!lm_val('SELECT 1 FROM activations WHERE license_id = ?', [$id])) echo '<p class="muted">هنوز هیچ سایتی بررسی نکرده است.</p>';

        echo '<h2>رویدادها</h2><ul class="events">';
        foreach (lm_q('SELECT * FROM events WHERE license_id = ? ORDER BY id DESC LIMIT 60', [$id]) as $e) echo '<li><span class="muted">' . lm_date((int)$e['at']) . '</span> ' . lm_h($e['msg']) . '</li>';
        echo '</ul>';
    }
}

elseif ($route === 'releases') {
    echo '<h1>نسخه‌های مجاز</h1><p>فایل‌های اصلی سایت که نسخه‌های رسمی آن‌ها ثبت شده است. اگر فایلی از این نسخه‌ها تغییر کرده باشد، لایسنس آن سایت («قفل در صورت تغییر») بسته می‌شود.</p>';
    $rel = lm_q('SELECT label, COUNT(*) files, MIN(added) added FROM releases GROUP BY label ORDER BY added DESC');
    echo '<table><thead><tr><th>نسخه</th><th>تعداد فایل</th><th>ثبت</th></tr></thead><tbody>';
    foreach ($rel as $r) echo '<tr><td>' . lm_h($r['label']) . '</td><td>' . lm_fa($r['files']) . '</td><td>' . lm_date((int)$r['added']) . '</td></tr>';
    echo '</tbody></table>';
    echo '<p class="muted">برای ثبت نسخه جدید، بعد از ساختن فایل آپدیت، روی کامپیوتر خودتان اجرا کنید:<br><code class="ltr">php tools/license-manager/bin/add-release.php ./ "نسخه ۶"</code></p>';
}

elseif ($route === 'settings') {
    echo '<h1>تنظیمات</h1><div class="grid"><section class="card"><h2>آدرس بررسی آنلاین</h2><form method="post" action="?p=settings"><input type="hidden" name="_csrf" value="' . $csrf . '">'
       . '<label>آدرس این مدیر (با ?api=check)<input name="srv_url" dir="ltr" value="' . lm_h(lm_get('srv_url')) . '"><small class="muted">این آدرس داخل هر لایسنس جدید نوشته می‌شود؛ باید از اینترنت در دسترس سایت‌های مشتری باشد.</small></label>'
       . '<button class="btn">ذخیره</button></form></section>';
    echo '<section class="card"><h2>تغییر رمز</h2><form method="post" action="?p=password"><input type="hidden" name="_csrf" value="' . $csrf . '">'
       . '<label>رمز فعلی<input type="password" name="current" required></label><label>رمز جدید (حداقل ۱۲ کاراکتر)<input type="password" name="password" required minlength="12"></label>'
       . '<label>تکرار رمز جدید<input type="password" name="password2" required minlength="12"></label><button class="btn">تغییر رمز</button></form></section></div>';
}

else { echo '<p>صفحه پیدا نشد.</p>'; }

echo '</main>';
render_foot();

/* ---------------- layout ---------------- */
function flash_box(?array $f): void
{
    if (!$f) return;
    [$t, $m] = $f;
    echo '<div class="alert ' . ($t === 'success' ? 'ok' : 'error') . '">' . lm_h($m) . '</div>';
}

function render_head(string $title): void
{
    echo '<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow">'
       . '<title>' . lm_h($title) . '</title><style>' . LM_CSS . '</style></head><body>';
}

function render_foot(): void
{
    echo '<script>document.querySelectorAll("form[data-confirm]").forEach(f=>f.addEventListener("submit",e=>{if(!confirm(f.dataset.confirm))e.preventDefault();}));</script></body></html>';
}

