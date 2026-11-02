<?php
declare(strict_types=1);

/* =========================================================
 *  Buyer account ("حساب کاربری من")
 * ======================================================= */

function account_guard(): array
{
    require_module('customer_accounts');
    $u = Customer::user();
    if (!$u) { $_SESSION['c_intended'] = $_SERVER['REQUEST_URI'] ?? ''; redirect('account/login'); }
    return $u;
}

function account_view(string $tpl, array $data, string $title): void
{
    $u = Customer::user();
    [$w, $p] = Customer::scope();
    $data['counts'] = [
        'orders' => (int)DB::val("SELECT COUNT(*) FROM orders WHERE $w", $p),
        'requests' => (int)DB::val("SELECT COUNT(*) FROM requests WHERE $w", $p),
    ];
    view('account/' . $tpl, $data + ['u' => $u, 'title' => $title, 'tab' => $tpl]);
}

function account_login(): void
{
    require_module('customer_accounts');
    if (Customer::check()) redirect('account');
    $sms = Customer::smsReady();
    $mode = (string)($_GET['mode'] ?? ($sms ? 'otp' : 'password'));
    if (!in_array($mode, ['otp', 'password', 'register'], true) || ($mode === 'otp' && !$sms)) $mode = 'password';
    if (isset($_GET['change'])) unset($_SESSION['cotp']);
    $error = null;

    if (is_post()) {
        $step = (string)input('step');
        $mobile = normalize_mobile((string)input('mobile'));
        $done = function (array $c) {
            Customer::login($c);
            $to = (string)($_SESSION['c_intended'] ?? '');
            unset($_SESSION['c_intended']);
            flash('success', 'خوش آمدید' . ($c['name'] ? '، ' . $c['name'] : '') . '.');
            redirect(str_starts_with($to, base_path() . '/account') ? $to : 'account');
        };
        if (!rate_limit('c_login', 15, 900)) $error = 'تعداد تلاش‌ها بیش از حد مجاز است. ۱۵ دقیقه دیگر تلاش کنید.';
        elseif ($step !== 'otp_verify' && !Captcha::verify('account')) $error = 'کد امنیتی اشتباه است یا منقضی شده. کد جدید را وارد کنید.';
        elseif ($step === 'otp_send' && $sms) {
            if (!valid_mobile($mobile)) $error = 'شماره موبایل معتبر نیست.';
            elseif (!rate_limit('c_otp', 5, 900)) $error = 'تعداد درخواست کد زیاد است. کمی بعد دوباره تلاش کنید.';
            elseif (!Customer::sendOtp($mobile)) $error = 'ارسال پیامک ممکن نشد. لطفاً با رمز عبور وارد شوید یا بعداً تلاش کنید.';
            else redirect('account/login?mode=otp');
        } elseif ($step === 'otp_verify' && $sms) {
            $m = Customer::pendingOtpMobile();
            if (!$m) { $error = 'زمان کد به پایان رسید. دوباره درخواست کد بدهید.'; }
            elseif (!Customer::verifyOtp($m, (string)input('code'))) $error = 'کد واردشده درست نیست.';
            else {
                $c = DB::row('SELECT * FROM customers WHERE mobile = ?', [$m]);
                if ($c && !(int)$c['active']) $error = 'حساب کاربری شما غیرفعال شده است. با پشتیبانی تماس بگیرید.';
                else {
                    if (!$c) { $id = DB::insert('customers', ['mobile' => $m, 'verified' => 1, 'active' => 1, 'created_at' => now()]); $c = DB::find('customers', $id); }
                    // the real owner of the number proved it: an unverified account (maybe created by someone else) is taken over,
                    // its password cleared so any other session/login on it stops working
                    elseif (!(int)$c['verified']) DB::update('customers', ['verified' => 1, 'password' => null], 'id = ?', [$c['id']]);
                    $done(DB::find('customers', (int)$c['id']));
                }
            }
        } elseif ($step === 'password') {
            $c = valid_mobile($mobile) ? DB::row('SELECT * FROM customers WHERE mobile = ?', [$mobile]) : null;
            $ok = password_verify((string)($_POST['password'] ?? ''), (string)($c['password'] ?? '') ?: '$2y$12$abcdefghijklmnopqrstuuJ8n4Jz8B1jQ3s8bQ0J0Fv9gU5Q0nX9y');
            if (!$c || !$c['password'] || !$ok) { usleep(random_int(200000, 400000)); $error = 'شماره موبایل یا رمز عبور اشتباه است.'; }
            elseif (!(int)$c['active']) $error = 'حساب کاربری شما غیرفعال شده است. با پشتیبانی تماس بگیرید.';
            else $done($c);
        } elseif ($step === 'register') {
            $name = mb_substr(trim((string)input('name')), 0, 120);
            $pw = (string)($_POST['password'] ?? '');
            if (mb_strlen($name) < 3) $error = 'نام و نام خانوادگی را کامل وارد کنید.';
            elseif (!valid_mobile($mobile)) $error = 'شماره موبایل معتبر نیست.';
            elseif ($e = Customer::passwordPolicy($pw)) $error = $e;
            elseif ($pw !== ($_POST['password2'] ?? '')) $error = 'تکرار رمز عبور مطابقت ندارد.';
            elseif (DB::val('SELECT 1 FROM customers WHERE mobile = ?', [$mobile])) $error = 'با این شماره قبلاً حساب ساخته شده است. وارد شوید.';
            else {
                $id = DB::insert('customers', ['mobile' => $mobile, 'name' => $name, 'password' => password_hash($pw, PASSWORD_DEFAULT),
                    'verified' => 0, 'active' => 1, 'created_at' => now()]);
                $done(DB::find('customers', $id));
            }
        }
        // remember what was typed for redisplay — never the password
        if ($error) $_SESSION['_old'] = ['mobile' => (string)input('mobile'), 'name' => (string)input('name')];
        else clear_old();
    }
    $pending = $mode === 'otp' ? Customer::pendingOtpMobile() : null;
    view('account/login', compact('mode', 'error', 'sms', 'pending') + ['title' => 'ورود به حساب کاربری']);
}

function account_logout(): void
{
    Customer::logout();
    flash('success', 'از حساب کاربری خارج شدید.');
    redirect('/');
}

function account_home(): void
{
    $u = account_guard();
    [$w, $p] = Customer::scope();
    $orders = DB::all("SELECT * FROM orders WHERE $w ORDER BY id DESC LIMIT 5", $p);
    $active = (int)DB::val("SELECT COUNT(*) FROM orders WHERE $w AND status IN ('paid','processing','shipped')", $p);
    $unpaid = (int)DB::val("SELECT COUNT(*) FROM orders WHERE $w AND status IN ('pending','failed')", $p);
    $spent = (int)DB::val("SELECT COALESCE(SUM(paid_amount),0) FROM orders WHERE $w", $p);
    [$wr, $pr] = Customer::scope('r');
    $requests = DB::all("SELECT r.*, c.trim_name FROM requests r LEFT JOIN cars c ON c.id = r.car_id WHERE $wr ORDER BY r.id DESC LIMIT 4", $pr);
    account_view('home', compact('orders', 'active', 'unpaid', 'spent', 'requests'), 'حساب کاربری من');
}

function account_orders(): void
{
    account_guard();
    [$w, $p] = Customer::scope('o');
    $filter = (string)($_GET['s'] ?? '');
    $groups = ['open' => ['pending', 'failed'], 'active' => ['paid', 'processing', 'shipped'], 'done' => ['completed'], 'cancelled' => ['cancelled']];
    if (isset($groups[$filter])) { $w .= ' AND o.status IN ' . DB::in($groups[$filter]); $p = [...$p, ...$groups[$filter]]; }
    $orders = DB::all("SELECT o.*, (SELECT COUNT(*) FROM order_items i WHERE i.order_id = o.id) AS items_count FROM orders o WHERE $w ORDER BY o.id DESC LIMIT 200", $p);
    $cars = [];
    foreach ($orders as $o) if ($o['car_id']) $cars[$o['car_id']] = null;
    if ($cars) foreach (Cars::byIds(array_keys($cars)) as $c) $cars[$c['id']] = $c;
    account_view('orders', compact('orders', 'cars', 'filter'), 'سفارش‌های من');
}

function account_order(string $code): void
{
    account_guard();
    $o = DB::row('SELECT * FROM orders WHERE code = ?', [$code]);
    if (!$o || !Customer::owns($o)) abort(404);
    Orders::grant($o);
    $items = DB::all('SELECT i.*, p.thumb FROM order_items i LEFT JOIN parts p ON p.id = i.part_id WHERE i.order_id = ?', [$o['id']]);
    $car = $o['car_id'] ? Cars::find((int)$o['car_id'], false) : null;
    $carImg = $car ? (Cars::images((int)$car['id'])[0]['thumb'] ?? null) : null;
    $tx = DB::all('SELECT t.*, g.label FROM transactions t LEFT JOIN gateways g ON g.id = t.gateway_id WHERE t.order_id = ? ORDER BY t.id DESC', [$o['id']]);
    $gateways = module('online_payment') && in_array($o['status'], ['pending', 'failed'], true)
        ? DB::all('SELECT id, label, bank FROM gateways WHERE active = 1 ORDER BY sort') : [];
    account_view('order', compact('o', 'items', 'car', 'carImg', 'tx', 'gateways'), 'سفارش ' . fa($o['code']));
}

function account_payments(): void
{
    account_guard();
    [$w, $p] = Customer::scope('o');
    $tx = DB::all("SELECT t.*, o.code, o.type, g.label FROM transactions t JOIN orders o ON o.id = t.order_id LEFT JOIN gateways g ON g.id = t.gateway_id
        WHERE $w AND t.status IN ('success','failed','verifying','init') ORDER BY t.id DESC LIMIT 300", $p);
    $paid = array_sum(array_map(fn($t) => $t['status'] === 'success' ? (int)$t['amount'] : 0, $tx));
    account_view('payments', compact('tx', 'paid'), 'پرداخت‌های من');
}

function account_requests(): void
{
    account_guard();
    [$w, $p] = Customer::scope('r');
    $requests = DB::all("SELECT r.* FROM requests r WHERE $w ORDER BY r.id DESC LIMIT 200", $p);
    $cars = [];
    foreach ($requests as $r) if ($r['car_id']) $cars[$r['car_id']] = null;
    if ($cars) foreach (Cars::byIds(array_keys($cars)) as $c) $cars[$c['id']] = $c;
    account_view('requests', compact('requests', 'cars'), 'درخواست‌های من');
}

function account_profile(): void
{
    $u = account_guard();
    if (is_post()) {
        $act = (string)input('action');
        if ($act === 'profile') {
            $d = [
                'name' => mb_substr(trim((string)input('name')), 0, 120),
                'email' => filter_var(input('email'), FILTER_VALIDATE_EMAIL) ?: null,
                'national_code' => to_en_digits((string)input('national_code')),
                'province' => mb_substr(trim((string)input('province')), 0, 40),
                'city' => mb_substr(trim((string)input('city')), 0, 60),
                'address' => mb_substr(trim((string)input('address')), 0, 500),
                'postal_code' => preg_replace('/\D/', '', to_en_digits((string)input('postal_code'))),
            ];
            $err = null;
            if (mb_strlen($d['name']) < 3) $err = 'نام و نام خانوادگی را کامل وارد کنید.';
            elseif ($d['national_code'] !== '' && !valid_national_code($d['national_code'])) $err = 'کد ملی معتبر نیست.';
            elseif ($d['postal_code'] !== '' && strlen($d['postal_code']) !== 10) $err = 'کد پستی باید ۱۰ رقم باشد.';
            if ($err) { keep_old(); flash('error', $err); redirect('account/profile'); }
            DB::update('customers', $d, 'id = ?', [$u['id']]);
            clear_old();
            flash('success', 'اطلاعات شما ذخیره شد.');
        } elseif ($act === 'password') {
            $pw = (string)($_POST['password'] ?? '');
            if ($u['password'] && !password_verify((string)($_POST['current'] ?? ''), $u['password'])) flash('error', 'رمز فعلی اشتباه است.');
            elseif ($e = Customer::passwordPolicy($pw)) flash('error', $e);
            elseif ($pw !== ($_POST['password2'] ?? '')) flash('error', 'تکرار رمز عبور مطابقت ندارد.');
            else {
                DB::update('customers', ['password' => password_hash($pw, PASSWORD_DEFAULT)], 'id = ?', [$u['id']]);
                Customer::login(DB::find('customers', (int)$u['id']));    // refresh session binding
                flash('success', $u['password'] ? 'رمز عبور تغییر کرد.' : 'رمز عبور تعیین شد؛ از این به بعد می‌توانید با رمز هم وارد شوید.');
            }
        }
        redirect('account/profile');
    }
    account_view('profile', [], 'اطلاعات حساب');
}

/** attach an order placed without logging in (unverified accounts): order code + same mobile */
function account_claim(): void
{
    $u = account_guard();
    if (!rate_limit('c_claim', 8, 900)) { flash('error', 'تعداد تلاش‌ها زیاد است. کمی بعد تلاش کنید.'); redirect('account/orders'); }
    $code = preg_replace('/\D/', '', to_en_digits((string)input('code')));
    $o = $code !== '' ? DB::row('SELECT * FROM orders WHERE code = ?', [$code]) : null;
    if (!$o || $o['mobile'] !== $u['mobile']) { flash('error', 'سفارشی با این کد برای شماره موبایل شما پیدا نشد.'); redirect('account/orders'); }
    if ($o['customer_id'] && (int)$o['customer_id'] !== (int)$u['id']) { flash('error', 'این سفارش به حساب دیگری متصل است.'); redirect('account/orders'); }
    DB::update('orders', ['customer_id' => $u['id']], 'id = ?', [$o['id']]);
    flash('success', 'سفارش ' . fa($o['code']) . ' به حساب شما اضافه شد.');
    redirect('account/orders/' . $o['code']);
}
