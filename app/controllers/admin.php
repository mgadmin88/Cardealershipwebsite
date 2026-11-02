<?php
declare(strict_types=1);

/* =========================================================
 *  Auth
 * ======================================================= */
function admin_login(): void
{
    if (Auth::check()) redirect('admin');
    $error = null;
    if (is_post()) {
        if (!rate_limit('login', 10, 900)) $error = 'تعداد تلاش‌ها بیش از حد مجاز است.';
        elseif (!Captcha::verify('login')) $error = 'کد امنیتی اشتباه است یا منقضی شده. کد جدید را وارد کنید.';
        else $error = Auth::attempt(mb_substr((string)input('username'), 0, 60), (string)($_POST['password'] ?? ''));
        if (!$error) {
            $to = $_SESSION['intended'] ?? '';
            unset($_SESSION['intended']);
            redirect(str_starts_with($to, base_path() . '/admin') ? $to : 'admin');
        }
        usleep(random_int(200000, 500000)); // slow down brute force
    }
    view('admin/login', ['error' => $error, 'title' => 'ورود به پنل مدیریت', 'bare' => true]);
}

/**
 * Password recovery
 *  1) SMS: a 6-digit code is sent to the mobile saved on the admin account (needs a working SMS panel)
 *  2) Emergency: whoever controls the host creates  storage/reset-password.txt  → a reset form unlocks for 2 hours, file is deleted after use
 */
function admin_forgot(): void
{
    if (Auth::check()) redirect('admin');
    $file = STORAGE . '/reset-password.txt';
    $emergency = is_file($file) && time() - (int)filemtime($file) < 7200;
    if (is_file($file) && !$emergency) @unlink($file);                 // stale file: ignore and remove
    if (isset($_GET['restart'])) unset($_SESSION['pwreset']);
    $st = $_SESSION['pwreset'] ?? null;
    if ($st && time() - $st['t'] > 900) { unset($_SESSION['pwreset']); $st = null; }
    $error = null; $notice = null;
    $smsReady = setting('sms_provider', 'log') !== 'log';

    if (is_post()) {
        $step = (string)input('step');
        if ($step === 'restart') { unset($_SESSION['pwreset']); redirect('admin/forgot'); }
        if (!rate_limit('forgot', 12, 900)) $error = 'تعداد تلاش‌ها بیش از حد مجاز است. ۱۵ دقیقه دیگر تلاش کنید.';
        elseif (!Captcha::verify('forgot')) $error = 'کد امنیتی اشتباه است یا منقضی شده. کد جدید را وارد کنید.';
        elseif ($step === 'request') {
            $u = DB::row('SELECT * FROM users WHERE username = ? AND active = 1 AND role IN (?, ?, ?)', [mb_substr(trim((string)input('username')), 0, 60), 'admin', 'expert', 'operator']);
            $code = (string)random_int(100000, 999999);
            $sent = $u && valid_mobile(normalize_mobile((string)$u['mobile'])) && $smsReady
                && Sms::send((string)$u['mobile'], 'کد بازیابی رمز پنل ' . setting('site_name') . ': ' . $code . "\nاین کد را به کسی ندهید.");
            // same answer whether the user exists or not (no user enumeration)
            $_SESSION['pwreset'] = ['uid' => $sent ? (int)$u['id'] : 0, 'h' => password_hash($code, PASSWORD_DEFAULT), 't' => time(), 'tries' => 0, 'ok' => false];
            if ($u) log_activity('password.reset_request', 'user #' . $u['id'] . ($sent ? ' sms sent' : ' sms not sent'));
            redirect('admin/forgot');
        } elseif ($step === 'code' && $st) {
            $st['tries']++;
            $code = preg_replace('/\D/', '', to_en_digits((string)input('code')));
            if ($st['tries'] > 5) { unset($_SESSION['pwreset']); $error = 'تعداد تلاش‌ها زیاد بود. دوباره درخواست کد بدهید.'; }
            elseif ($st['uid'] && password_verify($code, $st['h'])) { $st['ok'] = true; $_SESSION['pwreset'] = $st; redirect('admin/forgot'); }
            else { $_SESSION['pwreset'] = $st; $error = 'کد واردشده درست نیست.'; }
        } elseif ($step === 'new' && (($st['ok'] ?? false) || $emergency)) {
            $pw = (string)($_POST['password'] ?? '');
            $uid = ($st['ok'] ?? false) ? (int)$st['uid'] : (int)(DB::val('SELECT id FROM users WHERE username = ?', [mb_substr(trim((string)input('username')), 0, 60)]) ?: 0);
            if (!$uid) $error = 'نام کاربری یافت نشد.';
            elseif ($e = Auth::passwordPolicy($pw)) $error = $e;
            elseif ($pw !== ($_POST['password2'] ?? '')) $error = 'تکرار رمز مطابقت ندارد.';
            else {
                DB::update('users', ['password' => password_hash($pw, PASSWORD_DEFAULT), 'active' => 1], 'id = ?', [$uid]);
                DB::run('DELETE FROM login_attempts');
                log_activity('password.reset', 'user #' . $uid . (($st['ok'] ?? false) ? ' via sms' : ' via emergency file'));
                unset($_SESSION['pwreset']);
                if ($emergency) @unlink($file);
                flash('success', 'رمز عبور با موفقیت تغییر کرد. اکنون وارد شوید.');
                redirect('admin/login');
            }
        }
        $st = $_SESSION['pwreset'] ?? null;
    }
    $users = $emergency ? DB::all("SELECT username, name FROM users WHERE role IN ('admin','expert','operator') ORDER BY id") : [];
    view('admin/forgot', compact('error', 'st', 'emergency', 'users', 'smsReady') + ['title' => 'بازیابی رمز عبور', 'bare' => true]);
}

function admin_logout(): void
{
    log_activity('logout');
    Auth::logout();
    redirect('admin/login');
}

/* =========================================================
 *  Dashboard
 * ======================================================= */
function admin_dashboard(): void
{
    Auth::require();
    $since = date('Y-m-d', strtotime('-29 days'));
    $s = [
        'cars' => (int)DB::val("SELECT COUNT(*) FROM cars WHERE status = 'available'"),
        'reserved' => (int)DB::val("SELECT COUNT(*) FROM cars WHERE status = 'reserved'"),
        'sold' => (int)DB::val("SELECT COUNT(*) FROM cars WHERE status = 'sold'"),
        'orders' => (int)DB::val('SELECT COUNT(*) FROM orders WHERE created_at >= ?', [$since]),
        'revenue' => (int)DB::val("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE status = 'success' AND created_at >= ?", [$since]),
        'requests' => (int)DB::val("SELECT COUNT(*) FROM requests WHERE status = 'new'"),
        'parts' => (int)DB::val('SELECT COUNT(*) FROM parts WHERE active = 1'),
        'low' => (int)DB::val('SELECT COUNT(*) FROM parts WHERE active = 1 AND stock < 3'),
        'views' => (int)DB::val('SELECT COALESCE(SUM(views),0) FROM cars'),
    ];
    // last 7 days revenue
    $days = [];
    for ($i = 6; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $days[] = [jdate($d), (int)DB::val("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE status = 'success' AND created_at >= ? AND created_at < ?", [$d, date('Y-m-d', strtotime($d . ' +1 day'))])];
    }
    $orders = DB::all('SELECT * FROM orders ORDER BY id DESC LIMIT 6');
    $requests = DB::all("SELECT * FROM requests ORDER BY id DESC LIMIT 6");
    $top = DB::all(Cars::SELECT . " WHERE c.status != 'draft' ORDER BY c.views DESC LIMIT 5");
    view('admin/dashboard', compact('s', 'days', 'orders', 'requests', 'top') + ['title' => 'داشبورد']);
}

/* =========================================================
 *  Cars
 * ======================================================= */
function admin_cars(): void
{
    Auth::require('cars');
    $w = ['1=1']; $p = [];
    if ($q = trim((string)input('q'))) { $w[] = '(b.name_fa LIKE ? OR m.name_fa LIKE ? OR c.code = ?)'; array_push($p, "%$q%", "%$q%", $q); }
    if (($st = input('status')) && isset(Catalog::CAR_STATUS[$st])) { $w[] = 'c.status = ?'; $p[] = $st; }
    if (($cd = input('condition')) && isset(Catalog::CONDITION[$cd])) { $w[] = 'c.car_condition = ?'; $p[] = $cd; }
    $where = implode(' AND ', $w);
    $total = (int)DB::val("SELECT COUNT(*) FROM cars c JOIN brands b ON b.id=c.brand_id LEFT JOIN models m ON m.id=c.model_id WHERE $where", $p);
    $pg = paginate($total, 20, max(1, (int)($_GET['page'] ?? 1)));
    $cars = DB::all(Cars::SELECT . " WHERE $where ORDER BY c.id DESC LIMIT {$pg['per']} OFFSET {$pg['offset']}", $p);
    view('admin/cars', compact('cars', 'pg') + ['title' => 'مدیریت خودروها',
        'actions' => '<a href="' . url('admin/cars/new') . '" class="btn btn-accent">' . icon('plus') . ' افزودن خودرو</a>']);
}

function admin_car_form(?string $id = null): void
{
    Auth::require('cars');
    $car = $id ? DB::find('cars', (int)$id) : null;
    if ($id && !$car) abort(404);

    if (is_post()) {
        $b = fn($k) => !empty($_POST[$k]) ? 1 : 0;
        $s = fn($k, $len = 120) => mb_substr(trim((string)input($k)), 0, $len);
        $brandId = input_int('brand_id');
        $modelId = input_int('model_id') ?: null;
        $errors = [];
        if (!DB::find('brands', $brandId)) $errors[] = 'برند را انتخاب کنید.';
        if ($modelId && !DB::val('SELECT 1 FROM models WHERE id = ? AND brand_id = ?', [$modelId, $brandId])) $errors[] = 'مدل با برند مطابقت ندارد.';
        $year = input_int('year');
        if ($year < 1300 || $year > jyear_now() + 1) { if ($year < 1950 || $year > (int)date('Y') + 1) $errors[] = 'سال ساخت معتبر نیست.'; }
        $cond = isset(Catalog::CONDITION[input('car_condition')]) ? input('car_condition') : 'used';
        $status = isset(Catalog::CAR_STATUS[input('status')]) ? input('status') : 'available';

        $damage = [];
        foreach ((array)($_POST['damage'] ?? []) as $part => $st) {
            if (isset(Catalog::PARTS[$part], Catalog::DAMAGE[$st]) && $st !== 'ok') $damage[$part] = $st;
        }
        $insp = [];
        foreach (Catalog::INSPECTION as $k => [, , $items]) {
            $in = $_POST['insp'][$k] ?? [];
            foreach ($items as $i => $_) {
                $st = $in['items'][$i]['s'] ?? '';
                if (in_array($st, ['ok', 'fail'], true)) $insp[$k]['items'][$i] = ['s' => $st, 'n' => mb_substr(trim((string)($in['items'][$i]['n'] ?? '')), 0, 150)];
            }
            $sc = trim((string)($in['score'] ?? ''));
            if ($sc !== '' && is_numeric(to_en_digits($sc))) $insp[$k]['score'] = max(0, min(5, (float)to_en_digits($sc)));
        }
        $specs = [];
        foreach ((array)($_POST['specs'] ?? []) as $k => $v) if (preg_match('/^[a-z_]{2,20}$/', (string)$k) && trim((string)$v) !== '') $specs[$k] = mb_substr(trim((string)$v), 0, 80);
        $tags = array_slice(array_unique(array_filter(array_map(fn($t) => mb_substr(trim(str_replace(',', ' ', (string)$t)), 0, 30),
            array_merge((array)($_POST['tags'] ?? []), explode('،', str_replace(',', '،', (string)input('tags_extra'))))))), 0, 10);

        $data = [
            'car_condition' => $cond, 'brand_id' => $brandId, 'model_id' => $modelId, 'trim_name' => $s('trim_name', 80), 'year' => $year,
            'mileage' => $cond === 'new' ? 0 : max(0, input_int('mileage')),
            'gearbox' => isset(Catalog::GEARBOX[input('gearbox')]) ? input('gearbox') : null,
            'fuel' => isset(Catalog::FUEL[input('fuel')]) ? input('fuel') : null,
            'body_type' => isset(Catalog::BODY_TYPES[input('body_type')]) ? input('body_type') : null,
            'color' => $s('color', 30), 'interior_color' => $s('interior_color', 30), 'city' => $s('city', 40),
            'price' => max(0, input_int('price')), 'old_price' => input_int('old_price') ?: null, 'price_call' => $b('price_call'),
            'installment' => $b('installment'), 'installment_note' => $s('installment_note', 255),
            'ins_third' => $b('ins_third'), 'ins_third_until' => $s('ins_third_until', 20), 'ins_third_company' => $s('ins_third_company', 40),
            'ins_discount_years' => input('ins_discount_years') === '' ? null : input_int('ins_discount_years'),
            'ins_body' => $b('ins_body'), 'ins_body_until' => $s('ins_body_until', 20), 'ins_body_company' => $s('ins_body_company', 40),
            'tech_inspection' => $b('tech_inspection'), 'fuel_card' => $b('fuel_card'),
            'doc_type' => isset(Catalog::DOC_TYPES[input('doc_type')]) ? input('doc_type') : null,
            'warranty' => $s('warranty'), 'chassis' => isset(Catalog::CHASSIS[input('chassis')]) ? input('chassis') : null,
            'engine_status' => $s('engine_status'), 'owners' => input('owners') === '' ? null : input_int('owners'),
            'tags' => implode(',', $tags), 'options' => mb_substr((string)input('options'), 0, 5000), 'description' => mb_substr((string)input('description'), 0, 10000),
            'damage' => json_encode($damage), 'inspection' => json_encode($insp, JSON_UNESCAPED_UNICODE), 'specs' => json_encode($specs, JSON_UNESCAPED_UNICODE),
            'status' => $status, 'featured' => $b('featured'), 'updated_at' => now(),
        ];
        if ($errors) { flash('error', implode(' ', $errors)); keep_old(); redirect($car ? 'admin/cars/' . $car['id'] : 'admin/cars/new'); }

        $bn = DB::row('SELECT name_en FROM brands WHERE id = ?', [$brandId]);
        $mn = $modelId ? DB::row('SELECT name_en FROM models WHERE id = ?', [$modelId]) : null;
        $data['slug'] = slugify(($bn['name_en'] ?? 'car') . ' ' . ($mn['name_en'] ?? '') . ' ' . $year);

        if ($car) {
            DB::update('cars', $data, 'id = ?', [$car['id']]);
            $cid = (int)$car['id'];
            log_activity('car.update', "#{$cid}");
        } else {
            $data['code'] = (string)max(54000, (int)DB::val('SELECT MAX(code + 0) FROM cars') + 1);
            $data['created_by'] = Auth::id();
            $data['created_at'] = now();
            $cid = DB::insert('cars', $data);
            log_activity('car.create', "#{$cid}");
        }
        // images
        $sort = (int)DB::val('SELECT COALESCE(MAX(sort), -1) FROM car_images WHERE car_id = ?', [$cid]) + 1;
        $errs = [];
        foreach (array_slice(Upload::files('images'), 0, 40) as $f) {
            $r = Upload::image($f, 'cars');
            if (is_string($r)) { $errs[] = $f['name'] . ': ' . $r; continue; }
            DB::insert('car_images', ['car_id' => $cid, 'path' => $r['path'], 'thumb' => $r['thumb'], 'sort' => $sort++]);
        }
        // body damage spots (pins on the diagram) + their photos
        $errs = array_merge($errs, admin_save_damage_points($cid));
        clear_old();
        flash($errs ? 'error' : 'success', $errs ? 'خودرو ذخیره شد اما برخی تصاویر بارگذاری نشدند: ' . implode(' | ', $errs) : 'خودرو با موفقیت ذخیره شد.');
        redirect('admin/cars/' . $cid);
    }

    $car = $car ? Cars::find((int)$car['id'], false) : null;
    $images = $car ? Cars::images((int)$car['id']) : [];
    $points = $car ? Cars::points((int)$car['id']) : [];
    $brands = DB::all('SELECT id, name_fa, name_en, origin FROM brands ORDER BY origin, sort');
    $models = $car ? DB::all('SELECT id, name_fa, body_type FROM models WHERE brand_id = ? ORDER BY name_fa', [$car['brand_id']]) : [];
    view('admin/car_form', compact('car', 'images', 'points', 'brands', 'models') + ['title' => $car ? 'ویرایش خودرو ' . fa($car['code']) : 'افزودن خودروی جدید',
        'actions' => $car ? '<a class="btn btn-ghost" target="_blank" href="' . url('car/' . $car['id']) . '">' . icon('eye') . ' مشاهده در سایت</a>' : '']);
}

/** Save damage spots posted as pt[key][id|x|y|part|type|note|del_photo] + files pt_photo[key]. Returns upload errors. */
function admin_save_damage_points(int $carId): array
{
    if (!isset($_POST['pt_present'])) return [];           // body tab not on the form → leave untouched
    $existing = [];
    foreach (Cars::points($carId) as $p) $existing[(int)$p['id']] = $p;
    $files = $_FILES['pt_photo'] ?? null;
    $keep = []; $errs = []; $sort = 0;
    $damage = json_decode((string)DB::val('SELECT damage FROM cars WHERE id = ?', [$carId]), true) ?: [];

    foreach (array_slice((array)($_POST['pt'] ?? []), 0, 60, true) as $key => $p) {
        if (!is_array($p)) continue;
        $part = (string)($p['part'] ?? ''); $type = (string)($p['type'] ?? '');
        if (!isset(Catalog::PARTS[$part]) || !isset(Catalog::DAMAGE[$type]) || $type === 'ok') continue;
        $row = ['car_id' => $carId, 'part' => $part, 'type' => $type,
            'x' => max(0, min(600, (int)($p['x'] ?? 0))), 'y' => max(0, min(780, (int)($p['y'] ?? 0))),
            'note' => mb_substr(trim((string)($p['note'] ?? '')), 0, 250), 'sort' => $sort++];
        $id = (int)($p['id'] ?? 0);
        $old = $existing[$id] ?? null;

        // photo: new upload replaces old; explicit removal clears it
        if ($old && !empty($p['del_photo']) && $old['photo']) { Upload::remove($old['photo'], $old['thumb']); $row['photo'] = null; $row['thumb'] = null; }
        if ($files && isset($files['error'][$key]) && $files['error'][$key] !== UPLOAD_ERR_NO_FILE) {
            $f = ['name' => $files['name'][$key], 'type' => $files['type'][$key], 'tmp_name' => $files['tmp_name'][$key], 'error' => $files['error'][$key], 'size' => $files['size'][$key]];
            $r = Upload::image($f, 'damage', 360);
            if (is_string($r)) $errs[] = 'تصویر ' . Catalog::PARTS[$part] . ': ' . $r;
            else {
                if ($old && $old['photo'] && !array_key_exists('photo', $row)) Upload::remove($old['photo'], $old['thumb']);
                $row['photo'] = $r['path']; $row['thumb'] = $r['thumb'];
            }
        }
        if ($old) { DB::update('damage_points', $row, 'id = ?', [$id]); $keep[] = $id; }
        else $keep[] = DB::insert('damage_points', $row);

        // the panel status follows its worst spot
        if (Catalog::damageRank($type) > Catalog::damageRank($damage[$part] ?? 'ok')) $damage[$part] = $type;
    }
    foreach ($existing as $id => $old) {
        if (in_array($id, $keep, true)) continue;
        if ($old['photo']) Upload::remove($old['photo'], $old['thumb']);
        DB::delete('damage_points', 'id = ?', [$id]);
    }
    DB::update('cars', ['damage' => json_encode($damage)], 'id = ?', [$carId]);
    return $errs;
}

function admin_car_delete(string $id): void
{
    Auth::require('cars');
    $imgs = Cars::images((int)$id);
    if (DB::val('SELECT 1 FROM orders WHERE car_id = ?', [(int)$id])) {
        DB::update('cars', ['status' => 'draft'], 'id = ?', [(int)$id]);
        flash('success', 'این خودرو دارای سفارش است؛ به‌جای حذف به پیش‌نویس منتقل شد.');
    } else {
        foreach ($imgs as $i) Upload::remove($i['path'], $i['thumb']);
        foreach (Cars::points((int)$id) as $pt) if ($pt['photo']) Upload::remove($pt['photo'], $pt['thumb']);
        DB::delete('damage_points', 'car_id = ?', [(int)$id]);
        DB::delete('cars', 'id = ?', [(int)$id]);
        flash('success', 'خودرو حذف شد.');
    }
    log_activity('car.delete', "#$id");
    redirect('admin/cars');
}

function admin_car_status(string $id): void
{
    Auth::require('cars');
    $st = (string)input('status');
    if (isset(Catalog::CAR_STATUS[$st])) DB::update('cars', ['status' => $st, 'updated_at' => now()], 'id = ?', [(int)$id]);
    if (input('featured') !== '') DB::update('cars', ['featured' => input_int('featured') ? 1 : 0], 'id = ?', [(int)$id]);
    back();
}

function admin_image_delete(string $id): void
{
    Auth::require('cars');
    $img = DB::find('car_images', (int)$id);
    if ($img) { Upload::remove($img['path'], $img['thumb']); DB::delete('car_images', 'id = ?', [$img['id']]); }
    if (($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json') json_out(['ok' => true]);
    back();
}

function admin_image_sort(): void
{
    Auth::require('cars');
    foreach (array_values(array_map('intval', (array)($_POST['ids'] ?? []))) as $i => $id) DB::update('car_images', ['sort' => $i], 'id = ?', [$id]);
    json_out(['ok' => true]);
}

/* =========================================================
 *  Brands & models
 * ======================================================= */
function admin_brands(): void
{
    Auth::require('cars');
    if (is_post()) {
        $a = input('action');
        if ($a === 'brand') {
            $d = ['name_fa' => mb_substr((string)input('name_fa'), 0, 80), 'name_en' => mb_substr((string)input('name_en'), 0, 80),
                'origin' => input('origin') === 'foreign' ? 'foreign' : 'domestic', 'popular' => !empty($_POST['popular']) ? 1 : 0];
            if ($d['name_fa'] === '') { flash('error', 'نام برند الزامی است.'); back(); }
            $f = Upload::files('logo');
            if ($f) { $r = Upload::image($f[0], 'brands', 160); if (is_array($r)) $d['logo'] = $r['thumb']; }
            if ($bid = input_int('id')) DB::update('brands', $d, 'id = ?', [$bid]); else { $d['sort'] = 999; DB::insert('brands', $d); }
            flash('success', 'برند ذخیره شد.');
        } elseif ($a === 'model') {
            $d = ['brand_id' => input_int('brand_id'), 'name_fa' => mb_substr((string)input('name_fa'), 0, 80), 'name_en' => mb_substr((string)input('name_en'), 0, 80),
                'body_type' => isset(Catalog::BODY_TYPES[input('body_type')]) ? input('body_type') : null];
            if ($d['name_fa'] === '' || !DB::find('brands', $d['brand_id'])) { flash('error', 'اطلاعات مدل ناقص است.'); back(); }
            if ($mid = input_int('id')) DB::update('models', $d, 'id = ?', [$mid]); else DB::insert('models', $d);
            flash('success', 'مدل ذخیره شد.');
        } elseif ($a === 'del_model') {
            if (DB::val('SELECT 1 FROM cars WHERE model_id = ?', [input_int('id')])) flash('error', 'این مدل در خودروها استفاده شده است.');
            else DB::delete('models', 'id = ?', [input_int('id')]);
        } elseif ($a === 'del_brand') {
            if (DB::val('SELECT 1 FROM cars WHERE brand_id = ?', [input_int('id')])) flash('error', 'این برند در خودروها استفاده شده است.');
            else DB::delete('brands', 'id = ?', [input_int('id')]);
        }
        redirect('admin/brands' . (input_int('brand_id') ? '?b=' . input_int('brand_id') : ''));
    }
    $brands = DB::all('SELECT b.*, (SELECT COUNT(*) FROM models m WHERE m.brand_id = b.id) mc, (SELECT COUNT(*) FROM cars c WHERE c.brand_id = b.id) cc FROM brands b ORDER BY origin, sort, name_fa');
    $sel = input_int('b') ?: ($brands[0]['id'] ?? 0);
    $models = DB::all('SELECT * FROM models WHERE brand_id = ? ORDER BY name_fa', [$sel]);
    view('admin/brands', compact('brands', 'models', 'sel') + ['title' => 'برندها و مدل‌ها']);
}

/* =========================================================
 *  Parts
 * ======================================================= */
function admin_parts(): void
{
    Auth::require('parts');
    $w = ['1=1']; $p = [];
    if ($q = trim((string)input('q'))) { $w[] = '(p.title LIKE ? OR p.sku = ? OR p.brand LIKE ?)'; array_push($p, "%$q%", $q, "%$q%"); }
    if ($c = input_int('cat')) { $w[] = 'p.category_id = ?'; $p[] = $c; }
    $parts = DB::all('SELECT p.*, c.name cat FROM parts p LEFT JOIN part_categories c ON c.id = p.category_id WHERE ' . implode(' AND ', $w) . ' ORDER BY p.id DESC LIMIT 300', $p);
    view('admin/parts', ['parts' => $parts, 'cats' => DB::all('SELECT * FROM part_categories ORDER BY sort'), 'title' => 'قطعات و آپشن‌ها',
        'actions' => '<a href="' . url('admin/parts/new') . '" class="btn btn-accent">' . icon('plus') . ' افزودن محصول</a>']);
}

function admin_part_form(?string $id = null): void
{
    Auth::require('parts');
    $p = $id ? DB::find('parts', (int)$id) : null;
    if ($id && !$p) abort(404);
    if (is_post()) {
        $d = [
            'category_id' => input_int('category_id') ?: null, 'title' => mb_substr((string)input('title'), 0, 200), 'sku' => mb_substr((string)input('sku'), 0, 60),
            'brand' => mb_substr((string)input('brand'), 0, 80), 'compatible' => mb_substr((string)input('compatible'), 0, 255),
            'price' => max(0, input_int('price')), 'sale_price' => input_int('sale_price') ?: null, 'stock' => max(0, input_int('stock')),
            'description' => mb_substr((string)input('description'), 0, 10000), 'is_option' => !empty($_POST['is_option']) ? 1 : 0,
            'active' => !empty($_POST['active']) ? 1 : 0, 'featured' => !empty($_POST['featured']) ? 1 : 0,
        ];
        if ($d['title'] === '' || $d['price'] <= 0) { keep_old(); flash('error', 'عنوان و قیمت الزامی است.'); back(); }
        $f = Upload::files('image');
        if ($f) {
            $r = Upload::image($f[0], 'parts', 400);
            if (is_string($r)) { flash('error', $r); back(); }
            if ($p) Upload::remove($p['image'], $p['thumb']);
            $d['image'] = $r['path']; $d['thumb'] = $r['thumb'];
        }
        if ($p) DB::update('parts', $d, 'id = ?', [$p['id']]); else { $d['created_at'] = now(); $p = ['id' => DB::insert('parts', $d)]; }
        log_activity('part.save', '#' . $p['id']);
        flash('success', 'محصول ذخیره شد.');
        redirect('admin/parts/' . $p['id']);
    }
    view('admin/part_form', ['p' => $p, 'cats' => DB::all('SELECT * FROM part_categories ORDER BY sort'), 'title' => $p ? 'ویرایش محصول' : 'افزودن محصول']);
}

function admin_part_delete(string $id): void
{
    Auth::require('parts');
    $p = DB::find('parts', (int)$id);
    if ($p) {
        if (DB::val('SELECT 1 FROM order_items WHERE part_id = ?', [$p['id']])) { DB::update('parts', ['active' => 0], 'id = ?', [$p['id']]); flash('success', 'محصول دارای سفارش بود و غیرفعال شد.'); }
        else { Upload::remove($p['image'], $p['thumb']); DB::delete('parts', 'id = ?', [$p['id']]); flash('success', 'محصول حذف شد.'); }
    }
    redirect('admin/parts');
}

function admin_part_categories(): void
{
    Auth::require('parts');
    if (is_post()) {
        if (input('action') === 'delete') {
            DB::update('parts', ['category_id' => null], 'category_id = ?', [input_int('id')]);
            DB::delete('part_categories', 'id = ?', [input_int('id')]);
        } else {
            $d = ['name' => mb_substr((string)input('name'), 0, 80), 'icon' => preg_replace('/[^a-z\-]/', '', (string)input('icon')) ?: 'box', 'sort' => input_int('sort')];
            $d['slug'] = slugify($d['name']);
            if ($d['name'] !== '') { if ($id = input_int('id')) DB::update('part_categories', $d, 'id = ?', [$id]); else DB::insert('part_categories', $d); }
        }
        flash('success', 'ذخیره شد.');
        redirect('admin/part-categories');
    }
    view('admin/simple_list', ['title' => 'دسته‌بندی قطعات', 'kind' => 'pcat', 'rows' => DB::all('SELECT c.*, (SELECT COUNT(*) FROM parts p WHERE p.category_id=c.id) n FROM part_categories c ORDER BY sort')]);
}

/* =========================================================
 *  Orders, transactions, requests
 * ======================================================= */
function admin_orders(): void
{
    Auth::require('orders');
    $w = ['1=1']; $p = [];
    if ($q = trim(to_en_digits((string)input('q')))) { $w[] = '(o.code = ? OR o.mobile LIKE ? OR o.customer_name LIKE ?)'; array_push($p, $q, "%$q%", "%$q%"); }
    if (($st = input('status')) && isset(Catalog::ORDER_STATUS[$st])) { $w[] = 'o.status = ?'; $p[] = $st; }
    if (in_array($t = input('type'), ['car', 'parts'], true)) { $w[] = 'o.type = ?'; $p[] = $t; }
    $orders = DB::all('SELECT o.* FROM orders o WHERE ' . implode(' AND ', $w) . ' ORDER BY o.id DESC LIMIT 300', $p);
    view('admin/orders', compact('orders') + ['title' => 'سفارش‌ها']);
}

function admin_order(string $id): void
{
    Auth::require('orders');
    $o = DB::find('orders', (int)$id);
    if (!$o) abort(404);
    if (is_post()) {
        $st = (string)input('status');
        $d = ['admin_note' => mb_substr((string)input('admin_note'), 0, 1000), 'updated_at' => now()];
        if (isset(Catalog::ORDER_STATUS[$st])) $d['status'] = $st;
        DB::update('orders', $d, 'id = ?', [$o['id']]);
        if ($o['type'] === 'car' && $o['car_id']) {
            if ($st === 'completed') DB::update('cars', ['status' => 'sold'], 'id = ?', [$o['car_id']]);
            if ($st === 'cancelled') DB::update('cars', ['status' => 'available'], "id = ? AND status = 'reserved'", [$o['car_id']]);
        }
        if (!empty($_POST['notify']) && module('sms')) Sms::send($o['mobile'], 'وضعیت سفارش ' . $o['code'] . ' در ' . setting('site_name') . ': ' . (Catalog::ORDER_STATUS[$st][0] ?? ''));
        log_activity('order.update', $o['code'] . ' → ' . $st);
        flash('success', 'سفارش به‌روزرسانی شد.');
        redirect('admin/orders/' . $o['id']);
    }
    $items = DB::all('SELECT * FROM order_items WHERE order_id = ?', [$o['id']]);
    $car = $o['car_id'] ? Cars::find((int)$o['car_id'], false) : null;
    $tx = DB::all('SELECT t.*, g.label FROM transactions t LEFT JOIN gateways g ON g.id = t.gateway_id WHERE order_id = ? ORDER BY t.id DESC', [$o['id']]);
    view('admin/order', compact('o', 'items', 'car', 'tx') + ['title' => 'سفارش ' . fa($o['code'])]);
}

function admin_transactions(): void
{
    Auth::require('orders');
    $tx = DB::all('SELECT t.*, g.label, o.code, o.customer_name FROM transactions t LEFT JOIN gateways g ON g.id = t.gateway_id LEFT JOIN orders o ON o.id = t.order_id ORDER BY t.id DESC LIMIT 300');
    view('admin/transactions', ['tx' => $tx, 'title' => 'تراکنش‌های بانکی']);
}

function admin_requests(): void
{
    Auth::require('requests');
    $w = ['1=1']; $p = [];
    if (($t = input('type')) && isset(Catalog::REQUEST_TYPES[$t])) { $w[] = 'r.type = ?'; $p[] = $t; }
    if (in_array($s = input('status'), ['new', 'seen', 'done'], true)) { $w[] = 'r.status = ?'; $p[] = $s; }
    $rows = DB::all('SELECT r.*, c.code car_code FROM requests r LEFT JOIN cars c ON c.id = r.car_id WHERE ' . implode(' AND ', $w) . ' ORDER BY r.id DESC LIMIT 300', $p);
    view('admin/requests', ['rows' => $rows, 'title' => 'درخواست‌ها و پیام‌ها']);
}

function admin_request_update(string $id): void
{
    Auth::require('requests');
    if (input('action') === 'delete' && Auth::can('settings')) DB::delete('requests', 'id = ?', [(int)$id]);
    elseif (in_array($s = input('status'), ['new', 'seen', 'done'], true)) DB::update('requests', ['status' => $s], 'id = ?', [(int)$id]);
    back();
}

/* =========================================================
 *  Content: slider, pages, faqs
 * ======================================================= */
function admin_slider(): void
{
    Auth::require('content');
    if (is_post()) {
        $id = input_int('id');
        if (input('action') === 'toggle_module') {
            Settings::set('module_slider', module('slider') ? '0' : '1');
            redirect('admin/slider');
        }
        if (input('action') === 'delete') {
            $s = DB::find('slides', $id);
            if ($s) { if (!str_starts_with((string)$s['image'], 'demo/')) Upload::remove($s['image']); DB::delete('slides', 'id = ?', [$id]); }
        } else {
            $link = (string)input('btn_link');
            if ($link !== '' && !preg_match('~^(/|https?://)~', $link)) $link = '/' . $link;
            $d = ['title' => mb_substr((string)input('title'), 0, 160), 'subtitle' => mb_substr((string)input('subtitle'), 0, 255), 'btn_text' => mb_substr((string)input('btn_text'), 0, 60),
                'btn_link' => mb_substr($link, 0, 255), 'align' => input('align') === 'left' ? 'left' : 'right', 'sort' => input_int('sort'), 'active' => !empty($_POST['active']) ? 1 : 0];
            $f = Upload::files('image');
            if ($f) { $r = Upload::image($f[0], 'slides', 600); if (is_string($r)) { flash('error', $r); back(); } $d['image'] = $r['path']; }
            if (!$id && empty($d['image'])) { flash('error', 'تصویر اسلاید الزامی است.'); back(); }
            if ($id) DB::update('slides', $d, 'id = ?', [$id]); else DB::insert('slides', $d);
        }
        flash('success', 'ذخیره شد.');
        redirect('admin/slider');
    }
    view('admin/slider', ['slides' => DB::all('SELECT * FROM slides ORDER BY sort, id'), 'edit' => input_int('edit') ? DB::find('slides', input_int('edit')) : null, 'title' => 'اسلایدر صفحه اول']);
}

function admin_pages(): void
{
    Auth::require('content');
    if (is_post()) {
        $id = input_int('id');
        if (input('action') === 'delete') { if (!in_array(DB::val('SELECT slug FROM pages WHERE id = ?', [$id]), ['about', 'terms'], true)) DB::delete('pages', 'id = ?', [$id]); }
        else {
            $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower((string)input('slug')));
            $d = ['title' => mb_substr((string)input('title'), 0, 160), 'content' => clean_html((string)($_POST['content'] ?? '')), 'active' => !empty($_POST['active']) ? 1 : 0, 'in_footer' => !empty($_POST['in_footer']) ? 1 : 0];
            if (!$id) { if (!$slug || DB::val('SELECT 1 FROM pages WHERE slug = ?', [$slug])) { flash('error', 'نامک (slug) تکراری یا نامعتبر است.'); back(); } $d['slug'] = $slug; DB::insert('pages', $d); }
            else DB::update('pages', $d, 'id = ?', [$id]);
        }
        flash('success', 'ذخیره شد.');
        redirect('admin/pages');
    }
    view('admin/pages', ['pages' => DB::all('SELECT * FROM pages ORDER BY id'), 'edit' => input_int('edit') ? DB::find('pages', input_int('edit')) : null, 'title' => 'صفحات سایت']);
}

function admin_faqs(): void
{
    Auth::require('content');
    if (is_post()) {
        $id = input_int('id');
        if (input('action') === 'delete') DB::delete('faqs', 'id = ?', [$id]);
        else {
            $d = ['question' => mb_substr((string)input('question'), 0, 255), 'answer' => mb_substr((string)input('answer'), 0, 5000), 'grp' => input('grp') === 'car' ? 'car' : 'general', 'sort' => input_int('sort'), 'active' => !empty($_POST['active']) ? 1 : 0];
            if ($d['question'] !== '') { if ($id) DB::update('faqs', $d, 'id = ?', [$id]); else DB::insert('faqs', $d); }
        }
        flash('success', 'ذخیره شد.');
        redirect('admin/faqs');
    }
    view('admin/simple_list', ['title' => 'سوالات متداول', 'kind' => 'faq', 'rows' => DB::all('SELECT * FROM faqs ORDER BY grp, sort'), 'edit' => input_int('edit') ? DB::find('faqs', input_int('edit')) : null]);
}

/* =========================================================
 *  Settings: modules, theme, general, payments, sms
 * ======================================================= */
function admin_modules(): void
{
    Auth::require('settings');
    if (is_post()) {
        $on = (array)($_POST['m'] ?? []);
        $pairs = [];
        foreach (Catalog::MODULES as $k => $_) $pairs['module_' . $k] = isset($on[$k]) ? '1' : '0';
        Settings::setMany($pairs);
        log_activity('modules.update');
        flash('success', 'وضعیت ماژول‌ها ذخیره شد.');
        redirect('admin/modules');
    }
    view('admin/modules', ['title' => 'مدیریت ماژول‌ها']);
}

function admin_theme(): void
{
    Auth::require('settings');
    if (is_post()) {
        $preset = isset(Catalog::THEMES[input('theme_preset')]) ? input('theme_preset') : 'navy';
        $pairs = ['theme_preset' => $preset];
        if (input('apply_preset')) {
            foreach (Catalog::THEMES[$preset][1] as $k => $v) $pairs['theme_' . $k] = $v;
        } else {
            foreach (array_keys(Catalog::THEMES['navy'][1]) as $k) {
                $v = (string)input('theme_' . $k);
                if (preg_match('/^#[0-9a-f]{6}$/i', $v)) $pairs['theme_' . $k] = $v;
            }
        }
        $pairs += [
            'theme_radius' => (string)max(0, min(28, input_int('theme_radius', 14))),
            'theme_container' => (string)max(960, min(1600, input_int('theme_container', 1280))),
            'theme_font_scale' => (string)max(85, min(120, input_int('theme_font_scale', 100))),
            'theme_mode' => in_array(input('theme_mode'), ['light', 'dark', 'auto'], true) ? input('theme_mode') : 'light',
            'theme_header' => in_array(input('theme_header'), ['light', 'dark', 'primary'], true) ? input('theme_header') : 'light',
            'theme_hero' => in_array(input('theme_hero'), ['slider', 'search'], true) ? input('theme_hero') : 'slider',
            'theme_card' => in_array(input('theme_card'), ['elevated', 'outlined', 'flat'], true) ? input('theme_card') : 'elevated',
            'theme_custom_css' => mb_substr((string)($_POST['theme_custom_css'] ?? ''), 0, 20000),
        ];
        Settings::setMany($pairs);
        log_activity('theme.update', $preset);
        flash('success', 'تنظیمات ظاهری ذخیره شد.');
        redirect('admin/theme');
    }
    view('admin/theme', ['title' => 'تم و ظاهر سایت', 'actions' => '<a class="btn btn-ghost" target="_blank" href="' . url('/') . '">' . icon('eye') . ' پیش‌نمایش سایت</a>']);
}

function admin_settings(): void
{
    Auth::require('settings');
    $text = ['site_name', 'site_tagline', 'site_description', 'phone', 'phone2', 'mobile', 'whatsapp', 'email', 'address', 'work_hours', 'map_lat', 'map_lng',
        'instagram', 'telegram', 'linkedin', 'aparat', 'enamad_link', 'enamad_img', 'footer_about', 'deposit_type', 'deposit_value', 'car_full_pay', 'car_max_online',
        'shipping_cost', 'free_shipping_over', 'installment_rate', 'installment_max_months', 'installment_min_down', 'expert_fee_note'];
    if (is_post()) {
        $pairs = [];
        foreach ($text as $k) $pairs[$k] = mb_substr(trim((string)input($k)), 0, 2000);
        foreach (['instagram', 'telegram', 'linkedin', 'aparat', 'enamad_link', 'enamad_img'] as $k) if ($pairs[$k] !== '' && !preg_match('~^https://~', $pairs[$k])) $pairs[$k] = '';
        foreach (['map_lat', 'map_lng', 'deposit_value', 'installment_rate'] as $k) $pairs[$k] = (string)(float)to_en_digits($pairs[$k]);
        foreach (['car_max_online', 'shipping_cost', 'free_shipping_over', 'installment_max_months', 'installment_min_down'] as $k) $pairs[$k] = (string)(int)preg_replace('/\D/', '', to_en_digits($pairs[$k]));
        $pairs['deposit_type'] = $pairs['deposit_type'] === 'fixed' ? 'fixed' : 'percent';
        $pairs['car_full_pay'] = !empty($_POST['car_full_pay']) ? '1' : '0';
        $stats = [];
        foreach ((array)($_POST['stats_n'] ?? []) as $i => $n) if (trim((string)$n) !== '') $stats[] = [mb_substr((string)$n, 0, 20), mb_substr((string)($_POST['stats_l'][$i] ?? ''), 0, 40)];
        $pairs['stats'] = json_encode($stats, JSON_UNESCAPED_UNICODE);
        foreach (['logo' => 300, 'favicon' => 64] as $k => $w) {
            if (!empty($_POST['remove_' . $k])) $pairs[$k] = '';
            $f = Upload::files($k);
            if ($f) { $r = Upload::image($f[0], 'brand', $w); if (is_array($r)) $pairs[$k] = $r['thumb']; else flash('error', $r); }
        }
        Settings::setMany($pairs);
        log_activity('settings.update');
        flash('success', 'تنظیمات ذخیره شد.');
        redirect('admin/settings');
    }
    view('admin/settings', ['title' => 'تنظیمات عمومی']);
}

function admin_payments(): void
{
    Auth::require('settings');
    if (is_post()) {
        $id = input_int('id');
        if (input('action') === 'delete') { if (!DB::val('SELECT 1 FROM transactions WHERE gateway_id = ?', [$id])) DB::delete('gateways', 'id = ?', [$id]); else DB::update('gateways', ['active' => 0], 'id = ?', [$id]); }
        else {
            $driver = isset(Payment::DRIVERS[input('driver')]) ? input('driver') : 'test';
            $old = $id ? (json_decode((string)DB::val('SELECT config FROM gateways WHERE id = ?', [$id]), true) ?: []) : [];
            $cls = Payment::DRIVERS[$driver];
            $cfg = [];
            foreach ($cls::fields() as $k => [$label, $type]) {
                $v = trim((string)($_POST['cfg'][$k] ?? ''));
                if ($type === 'password') $cfg[$k] = $v === '' ? ($old[$k] ?? '') : Secret::encrypt($v); // keep secret if left empty
                else $cfg[$k] = mb_substr($v, 0, 200);
            }
            $d = ['label' => mb_substr((string)input('label'), 0, 80) ?: $cls::title(), 'bank' => isset(Payment::BANKS[input('bank')]) ? input('bank') : 'other', 'driver' => $driver,
                'config' => json_encode($cfg), 'sandbox' => !empty($_POST['sandbox']) ? 1 : 0, 'active' => !empty($_POST['active']) ? 1 : 0, 'sort' => input_int('sort')];
            if ($id) DB::update('gateways', $d, 'id = ?', [$id]); else DB::insert('gateways', $d);
            log_activity('gateway.save', $d['label']);
        }
        flash('success', 'درگاه ذخیره شد.');
        redirect('admin/payments');
    }
    view('admin/payments', ['gateways' => DB::all('SELECT * FROM gateways ORDER BY sort, id'), 'title' => 'درگاه‌های پرداخت']);
}

function admin_sms(): void
{
    Auth::require('settings');
    if (is_post()) {
        if (input('action') === 'test') {
            $ok = Sms::send((string)input('test_mobile'), 'پیامک آزمایشی از ' . setting('site_name'));
            flash($ok ? 'success' : 'error', $ok ? 'پیامک آزمایشی ارسال شد.' : 'ارسال ناموفق بود. جزئیات در گزارش ارسال‌ها.');
            redirect('admin/sms');
        }
        $provider = isset(Sms::PROVIDERS[input('sms_provider')]) ? input('sms_provider') : 'log';
        $all = Settings::json('sms_config');
        $cfg = [];
        foreach (Sms::PROVIDERS[$provider][1] as $k => [$l, $type]) {
            $v = trim((string)($_POST['cfg'][$k] ?? ''));
            $cfg[$k] = $type === 'password' ? ($v === '' ? ($all[$provider][$k] ?? '') : Secret::encrypt($v)) : mb_substr($v, 0, 500);
        }
        $all[$provider] = $cfg;
        $pairs = ['sms_provider' => $provider, 'sms_config' => json_encode($all), 'sms_admin_mobiles' => mb_substr((string)input('sms_admin_mobiles'), 0, 300)];
        foreach (['order', 'paid', 'request', 'admin'] as $t) $pairs['sms_tpl_' . $t] = mb_substr((string)input('sms_tpl_' . $t), 0, 500);
        $pairs['module_sms'] = !empty($_POST['enabled']) ? '1' : '0';
        Settings::setMany($pairs);
        flash('success', 'تنظیمات پیامک ذخیره شد.');
        redirect('admin/sms?p=' . $provider);
    }
    view('admin/sms', ['log' => DB::all('SELECT * FROM sms_log ORDER BY id DESC LIMIT 30'), 'title' => 'پنل پیامک']);
}

/* =========================================================
 *  Users
 * ======================================================= */
function admin_users(): void
{
    Auth::require('users');
    if (is_post()) {
        $id = input_int('id');
        if (input('action') === 'delete') {
            if ($id !== Auth::id()) DB::update('users', ['active' => 0], 'id = ?', [$id]);
            flash('success', 'کاربر غیرفعال شد.'); redirect('admin/users');
        }
        $username = strtolower((string)input('username'));
        if (!preg_match('/^[a-z0-9_.]{4,30}$/', $username)) { flash('error', 'نام کاربری فقط حروف انگلیسی و عدد (۴ تا ۳۰).'); back(); }
        if (DB::val('SELECT 1 FROM users WHERE username = ? AND id != ?', [$username, $id])) { flash('error', 'این نام کاربری قبلاً ثبت شده است.'); back(); }
        $d = ['name' => mb_substr((string)input('name'), 0, 120), 'username' => $username, 'role' => isset(Auth::ROLES[input('role')]) ? input('role') : 'expert',
            'mobile' => normalize_mobile((string)input('mobile')), 'active' => !empty($_POST['active']) || $id === Auth::id() ? 1 : 0];
        if ($id === Auth::id()) $d['role'] = 'admin';
        $pw = (string)($_POST['password'] ?? '');
        if ($pw !== '' || !$id) {
            if ($err = Auth::passwordPolicy($pw)) { flash('error', $err); back(); }
            $d['password'] = password_hash($pw, PASSWORD_DEFAULT);
        }
        if ($id) DB::update('users', $d, 'id = ?', [$id]); else { $d['created_at'] = now(); DB::insert('users', $d); }
        log_activity('user.save', $username);
        flash('success', 'کاربر ذخیره شد.');
        redirect('admin/users');
    }
    view('admin/users', ['users' => DB::all('SELECT * FROM users ORDER BY id'), 'edit' => input_int('edit') ? DB::find('users', input_int('edit')) : null, 'title' => 'کاربران و سطح دسترسی']);
}

function admin_profile(): void
{
    Auth::require();
    $u = Auth::user();
    if (is_post() && input('action') === 'mobile') {
        $m = normalize_mobile((string)input('mobile'));
        if ($m !== '' && !valid_mobile($m)) { flash('error', 'شماره موبایل معتبر نیست.'); back(); }
        DB::update('users', ['mobile' => $m ?: null], 'id = ?', [$u['id']]);
        log_activity('profile.mobile');
        flash('success', 'شماره موبایل ذخیره شد.');
        redirect('admin/profile');
    }
    if (is_post()) {
        if (!password_verify((string)($_POST['current'] ?? ''), $u['password'])) { flash('error', 'رمز فعلی اشتباه است.'); back(); }
        $pw = (string)($_POST['password'] ?? '');
        if ($err = Auth::passwordPolicy($pw)) { flash('error', $err); back(); }
        if ($pw !== ($_POST['password2'] ?? '')) { flash('error', 'تکرار رمز مطابقت ندارد.'); back(); }
        $hash = password_hash($pw, PASSWORD_DEFAULT);
        DB::update('users', ['password' => $hash], 'id = ?', [$u['id']]);
        $_SESSION['pw'] = substr(hash('sha256', $hash), 0, 16);
        log_activity('password.change');
        flash('success', 'رمز عبور تغییر کرد.');
        redirect('admin/profile');
    }
    view('admin/profile', ['u' => $u, 'title' => 'حساب کاربری من']);
}

function admin_logs(): void
{
    Auth::require('users');
    view('admin/logs', ['rows' => DB::all('SELECT l.*, u.username FROM activity_log l LEFT JOIN users u ON u.id = l.user_id ORDER BY l.id DESC LIMIT 300'), 'title' => 'گزارش فعالیت‌ها']);
}


/* ---------- buyer accounts ---------- */
function admin_customers(): void
{
    Auth::require('orders');
    $w = ['1 = 1']; $p = [];
    if (($q = trim((string)input('q'))) !== '') {
        $w[] = '(c.name LIKE ? OR c.mobile LIKE ? OR c.national_code LIKE ?)';
        $like = '%' . to_en_digits($q) . '%'; array_push($p, $like, $like, $like);
    }
    $rows = DB::all('SELECT c.*, (SELECT COUNT(*) FROM orders o WHERE o.customer_id = c.id OR (c.verified = 1 AND o.mobile = c.mobile)) AS orders_count,
        (SELECT COALESCE(SUM(o.paid_amount),0) FROM orders o WHERE o.customer_id = c.id OR (c.verified = 1 AND o.mobile = c.mobile)) AS paid
        FROM customers c WHERE ' . implode(' AND ', $w) . ' ORDER BY c.id DESC LIMIT 300', $p);
    view('admin/customers', ['rows' => $rows, 'title' => 'مشتریان']);
}

function admin_customer(string $id): void
{
    Auth::require('orders');
    $c = DB::find('customers', (int)$id);
    if (!$c) abort(404);
    if (is_post()) {
        $act = (string)input('action');
        if ($act === 'toggle') DB::update('customers', ['active' => (int)$c['active'] ? 0 : 1], 'id = ?', [$c['id']]);
        if ($act === 'verify') DB::update('customers', ['verified' => 1], 'id = ?', [$c['id']]);
        log_activity('customer.' . $act, '#' . $c['id']);
        flash('success', 'حساب مشتری به‌روزرسانی شد.');
        redirect('admin/customers/' . $c['id']);
    }
    $sc = (int)$c['verified'] ? ['(customer_id = ? OR mobile = ?)', [$c['id'], $c['mobile']]] : ['customer_id = ?', [$c['id']]];
    $orders = DB::all("SELECT * FROM orders WHERE {$sc[0]} ORDER BY id DESC LIMIT 100", $sc[1]);
    $requests = DB::all("SELECT * FROM requests WHERE {$sc[0]} ORDER BY id DESC LIMIT 100", $sc[1]);
    view('admin/customer', compact('c', 'orders', 'requests') + ['title' => 'مشتری: ' . ($c['name'] ?: fa($c['mobile']))]);
}
