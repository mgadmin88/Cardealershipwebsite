<?php
declare(strict_types=1);

function site_home(): void
{
    $slides = module('slider') ? DB::all('SELECT * FROM slides WHERE active = 1 ORDER BY sort, id') : [];
    $brands = module('brands_strip') ? DB::all('SELECT b.*, (SELECT COUNT(*) FROM cars c WHERE c.brand_id = b.id AND c.status = ?) AS cnt FROM brands b WHERE b.popular = 1 ORDER BY b.sort', ['available']) : [];
    $used = module('used_cars') ? Cars::latest(8, ['condition' => 'used']) : [];
    $new = module('new_cars') ? Cars::latest(4, ['condition' => 'new']) : [];
    $parts = module('parts_shop') ? DB::all('SELECT * FROM parts WHERE active = 1 ORDER BY featured DESC, id DESC LIMIT 5') : [];
    $faqs = module('faq') ? DB::all('SELECT * FROM faqs WHERE active = 1 ORDER BY sort LIMIT 5') : [];
    $allBrands = DB::all('SELECT id, name_fa FROM brands ORDER BY origin, sort');
    view('site/home', compact('slides', 'brands', 'used', 'new', 'parts', 'faqs', 'allBrands'));
}

function site_cars(): void
{
    $f = $_GET;
    if (empty(Cars::allowedConditions()) || Cars::allowedConditions() === ['__none__']) abort(404);
    [$cars, $pg] = Cars::search($f, max(1, (int)($_GET['page'] ?? 1)), 12);
    $brands = DB::all('SELECT id, name_fa, origin FROM brands ORDER BY origin, sort');
    $models = !empty($f['brand']) ? DB::all('SELECT id, name_fa FROM models WHERE brand_id = ? ORDER BY name_fa', [(int)$f['brand']]) : [];
    $cities = DB::all("SELECT city, COUNT(*) n FROM cars WHERE status = 'available' AND city IS NOT NULL GROUP BY city ORDER BY n DESC");
    $cond = $f['condition'] ?? '';
    $title = $cond === 'new' ? 'خرید خودروی صفر کیلومتر' : ($cond === 'used' ? 'خرید خودروی کارکرده' : 'خرید خودرو');
    view('site/cars', compact('cars', 'pg', 'brands', 'models', 'cities', 'f', 'title'));
}

function site_car(string $id): void
{
    $car = Cars::find((int)$id);
    if (!$car) abort(404);
    // count unique views per session
    $_SESSION['viewed'] ??= [];
    if (!in_array($car['id'], $_SESSION['viewed'], true)) {
        DB::run('UPDATE cars SET views = views + 1 WHERE id = ?', [$car['id']]);
        $_SESSION['viewed'][] = $car['id'];
    }
    $images = Cars::images((int)$car['id']);
    $points = Cars::points((int)$car['id']);
    [$where, $p] = Cars::where(['available' => 1]);
    $similar = DB::all(Cars::SELECT . " WHERE $where AND c.id != ? AND (c.brand_id = ? OR c.body_type = ?) ORDER BY ABS(c.price - ?) LIMIT 4",
        [...$p, $car['id'], $car['brand_id'], $car['body_type'], $car['price']]);
    $faqs = module('faq') ? DB::all("SELECT * FROM faqs WHERE active = 1 AND grp = 'car' ORDER BY sort") : [];
    $title = Cars::title($car);
    $metaDesc = $title . ' — ' . excerpt((string)$car['description'], 120);
    $ogImage = $images ? abs_url('uploads/' . $images[0]['path']) : null;
    view('site/car', compact('car', 'images', 'points', 'similar', 'faqs', 'title', 'metaDesc', 'ogImage'));
}

function site_compare(): void
{
    require_module('compare');
    $ids = array_slice(explode(',', (string)($_GET['ids'] ?? '')), 0, 3);
    $cars = Cars::byIds($ids);
    foreach ($cars as &$c) {
        foreach (['damage', 'inspection', 'specs'] as $j) $c[$j] = json_decode((string)$c[$j], true) ?: [];
    }
    view('site/compare', ['cars' => $cars, 'title' => 'مقایسه خودروها']);
}

function site_favorites(): void
{
    require_module('favorites');
    view('site/favorites', ['title' => 'علاقه‌مندی‌های من']);
}

function site_about(): void
{
    require_module('about');
    $page = DB::row("SELECT * FROM pages WHERE slug = 'about'");
    view('site/about', ['page' => $page, 'title' => $page['title'] ?? 'درباره ما']);
}

function site_page(string $slug): void
{
    $page = DB::row('SELECT * FROM pages WHERE slug = ? AND active = 1', [$slug]);
    if (!$page) abort(404);
    if ($slug === 'about') redirect('about', 301);
    view('site/page', ['page' => $page, 'title' => $page['title']]);
}

function site_faq(): void
{
    require_module('faq');
    view('site/faq', ['faqs' => DB::all('SELECT * FROM faqs WHERE active = 1 ORDER BY grp, sort'), 'title' => 'سوالات متداول']);
}

/** shared validation + storing for all public lead forms */
function store_request(string $type, array $extra = [], ?int $carId = null): ?string
{
    if (honeypot_failed()) return null; // silently drop bots
    if (!rate_limit('req', 6, 600)) return 'تعداد درخواست‌ها بیش از حد مجاز است. لطفاً چند دقیقه بعد تلاش کنید.';
    $name = mb_substr(trim((string)input('name')), 0, 120);
    $mobile = normalize_mobile((string)input('mobile'));
    if (mb_strlen($name) < 2) return 'لطفاً نام خود را وارد کنید.';
    if (!valid_mobile($mobile)) return 'شماره موبایل معتبر نیست.';
    DB::insert('requests', [
        'type' => $type, 'name' => $name, 'mobile' => $mobile, 'car_id' => $carId,
        'data' => json_encode($extra, JSON_UNESCAPED_UNICODE), 'message' => mb_substr((string)input('message'), 0, 2000),
        'status' => 'new', 'ip' => client_ip(), 'created_at' => now(),
    ]);
    Notify::request($type, $name, $mobile);
    return '';
}

function site_contact(): void
{
    require_module('contact');
    if (is_post()) {
        $err = store_request('contact', ['subject' => mb_substr((string)input('subject'), 0, 150), 'email' => filter_var(input('email'), FILTER_VALIDATE_EMAIL) ?: '']);
        if ($err) { keep_old(); flash('error', $err); redirect('contact'); }
        clear_old();
        flash('success', 'پیام شما با موفقیت ارسال شد. به‌زودی با شما تماس می‌گیریم.');
        redirect('contact');
    }
    view('site/contact', ['title' => 'تماس با ما']);
}

function site_sell(): void
{
    require_module('sell_request');
    if (is_post()) {
        $brand = DB::row('SELECT name_fa FROM brands WHERE id = ?', [input_int('brand')]);
        $model = DB::row('SELECT name_fa FROM models WHERE id = ?', [input_int('model')]);
        $err = store_request('sell', [
            'brand' => $brand['name_fa'] ?? '', 'model' => $model['name_fa'] ?? '', 'year' => input_int('year'),
            'mileage' => input_int('mileage'), 'city' => mb_substr((string)input('city'), 0, 40), 'price' => input_int('price'),
        ]);
        if ($err) { keep_old(); flash('error', $err); redirect('sell'); }
        clear_old();
        flash('success', 'درخواست فروش خودروی شما ثبت شد. کارشناسان ما برای هماهنگی کارشناسی رایگان تماس می‌گیرند.');
        redirect('sell');
    }
    view('site/sell', ['title' => 'فروش خودروی شما', 'brands' => DB::all('SELECT id, name_fa FROM brands ORDER BY origin, sort')]);
}

/** generic AJAX/POST lead: test drive, installment request, callback */
function site_request(): void
{
    $type = (string)input('type');
    $map = ['test_drive' => 'test_drive', 'installment' => 'installment', 'callback' => 'callback'];
    if (!isset($map[$type])) abort(400);
    if ($type === 'test_drive') require_module('test_drive');
    if ($type === 'installment') require_module('installment');
    $carId = input_int('car_id') ?: null;
    if ($carId && !DB::find('cars', $carId)) $carId = null;
    $err = store_request($type, ['date' => mb_substr((string)input('date'), 0, 30), 'time' => mb_substr((string)input('time'), 0, 30),
        'down' => input_int('down'), 'months' => input_int('months')], $carId);
    $ajax = ($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json';
    if ($ajax) json_out($err ? ['ok' => false, 'msg' => $err] : ['ok' => true, 'msg' => 'درخواست شما ثبت شد. به‌زودی با شما تماس می‌گیریم.']);
    flash($err ? 'error' : 'success', $err ?: 'درخواست شما ثبت شد. به‌زودی با شما تماس می‌گیریم.');
    back();
}

function api_models(): void
{
    $brand = input_int('brand');
    header('Cache-Control: public, max-age=600');
    json_out($brand ? DB::all('SELECT id, name_fa AS name, body_type FROM models WHERE brand_id = ? ORDER BY name_fa', [$brand]) : []);
}

function api_cars(): void
{
    $ids = explode(',', (string)($_GET['ids'] ?? ''));
    $out = [];
    foreach (Cars::byIds($ids) as $c) {
        ob_start();
        partial('car_card', ['car' => $c]);
        $out[] = ob_get_clean();
    }
    json_out(['html' => $out]);
}

function site_sitemap(): void
{
    header('Content-Type: application/xml; charset=utf-8');
    $u = fn($p, $d = null) => '<url><loc>' . e(abs_url($p)) . '</loc>' . ($d ? '<lastmod>' . date('Y-m-d', strtotime($d)) . '</lastmod>' : '') . '</url>';
    echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    echo $u(''), $u('cars');
    foreach (['about', 'contact', 'parts', 'faq', 'sell'] as $p) echo $u($p);
    [$where, $p] = Cars::where([]);
    foreach (DB::all("SELECT c.id, c.slug, c.updated_at FROM cars c JOIN brands b ON b.id=c.brand_id LEFT JOIN models m ON m.id=c.model_id WHERE $where", $p) as $c) echo $u('car/' . $c['id'] . '/' . $c['slug'], $c['updated_at']);
    if (module('parts_shop')) foreach (DB::all('SELECT id FROM parts WHERE active = 1') as $pt) echo $u('part/' . $pt['id']);
    echo '</urlset>';
}

function site_robots(): void
{
    header('Content-Type: text/plain');
    echo "User-agent: *\nDisallow: /admin\nDisallow: /cart\nDisallow: /checkout\nDisallow: /pay/\nDisallow: /order/\nSitemap: " . abs_url('sitemap.xml') . "\n";
}

function site_captcha(string $form): void
{
    Captcha::image($form);
}
