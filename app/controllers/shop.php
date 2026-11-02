<?php
declare(strict_types=1);

function shop_parts(): void
{
    require_module('parts_shop');
    $where = ['p.active = 1']; $params = [];
    if ($cat = input_int('cat')) { $where[] = 'p.category_id = ?'; $params[] = $cat; }
    if (($q = mb_substr(trim((string)input('q')), 0, 60)) !== '') {
        $where[] = '(p.title LIKE ? OR p.brand LIKE ? OR p.compatible LIKE ? OR p.sku = ?)';
        array_push($params, "%$q%", "%$q%", "%$q%", $q);
    }
    if (input('type') === 'option') $where[] = 'p.is_option = 1';
    if (input('type') === 'part') $where[] = 'p.is_option = 0';
    if (input('stock')) $where[] = 'p.stock > 0';
    $w = implode(' AND ', $where);
    $total = (int)DB::val("SELECT COUNT(*) FROM parts p WHERE $w", $params);
    $pg = paginate($total, 16, max(1, (int)($_GET['page'] ?? 1)));
    $order = match (input('sort')) { 'cheap' => 'COALESCE(p.sale_price, p.price) ASC', 'expensive' => 'COALESCE(p.sale_price, p.price) DESC', default => 'p.featured DESC, p.id DESC' };
    $parts = DB::all("SELECT p.* FROM parts p WHERE $w ORDER BY $order LIMIT {$pg['per']} OFFSET {$pg['offset']}", $params);
    $cats = DB::all('SELECT c.*, (SELECT COUNT(*) FROM parts p WHERE p.category_id = c.id AND p.active = 1) n FROM part_categories c ORDER BY sort');
    view('site/parts', compact('parts', 'pg', 'cats') + ['title' => 'فروشگاه قطعات و لوازم جانبی']);
}

function shop_part(string $id): void
{
    require_module('parts_shop');
    $p = DB::row('SELECT p.*, c.name AS cat_name FROM parts p LEFT JOIN part_categories c ON c.id = p.category_id WHERE p.id = ? AND p.active = 1', [(int)$id]);
    if (!$p) abort(404);
    $related = DB::all('SELECT * FROM parts WHERE active = 1 AND category_id = ? AND id != ? ORDER BY ' . (DB::driver() === 'mysql' ? 'RAND()' : 'RANDOM()') . ' LIMIT 4', [$p['category_id'], $p['id']]);
    view('site/part', ['p' => $p, 'related' => $related, 'title' => $p['title'], 'metaDesc' => excerpt((string)$p['description'], 150)]);
}

function shop_cart_add(): void
{
    require_module('parts_shop');
    $id = input_int('id');
    $p = DB::row('SELECT id, stock FROM parts WHERE id = ? AND active = 1', [$id]);
    if (!$p || $p['stock'] < 1) { flash('error', 'این کالا موجود نیست.'); back(); }
    $qty = max(1, input_int('qty', 1));
    $_SESSION['cart'][$id] = min(($_SESSION['cart'][$id] ?? 0) + $qty, (int)$p['stock'], 20);
    if (($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json') json_out(['ok' => true, 'count' => array_sum($_SESSION['cart'])]);
    flash('success', 'به سبد خرید اضافه شد.');
    back();
}

function shop_cart_update(): void
{
    foreach ((array)($_POST['qty'] ?? []) as $id => $q) {
        $id = (int)$id; $q = (int)$q;
        if ($q <= 0) unset($_SESSION['cart'][$id]); elseif (isset($_SESSION['cart'][$id])) $_SESSION['cart'][$id] = min($q, 20);
    }
    if ($rm = input_int('remove')) unset($_SESSION['cart'][$rm]);
    redirect('cart');
}

function shop_cart(): void
{
    require_module('parts_shop');
    [$items, $sum] = Orders::cartItems();
    view('site/cart', ['items' => $items, 'sum' => $sum, 'shipping' => $items ? Orders::shipping($sum) : 0, 'title' => 'سبد خرید']);
}

/** validates the shared customer fields; returns [data, error] */
function customer_input(bool $needAddress): array
{
    $d = [
        'customer_name' => mb_substr(trim((string)input('name')), 0, 120),
        'mobile' => normalize_mobile((string)input('mobile')),
        'national_code' => to_en_digits((string)input('national_code')),
        'email' => filter_var(input('email'), FILTER_VALIDATE_EMAIL) ?: null,
        'province' => mb_substr((string)input('province'), 0, 40),
        'city' => mb_substr((string)input('city'), 0, 60),
        'address' => mb_substr((string)input('address'), 0, 500),
        'postal_code' => preg_replace('/\D/', '', to_en_digits((string)input('postal_code'))),
        'note' => mb_substr((string)input('note'), 0, 1000),
    ];
    if (mb_strlen($d['customer_name']) < 3) return [$d, 'نام و نام خانوادگی را کامل وارد کنید.'];
    if (!valid_mobile($d['mobile'])) return [$d, 'شماره موبایل معتبر نیست.'];
    if ($d['national_code'] !== '' && !valid_national_code($d['national_code'])) return [$d, 'کد ملی معتبر نیست.'];
    if ($needAddress && (mb_strlen($d['address']) < 10 || $d['city'] === '')) return [$d, 'آدرس کامل و شهر را وارد کنید.'];
    if ($needAddress && $d['postal_code'] !== '' && strlen($d['postal_code']) !== 10) return [$d, 'کد پستی باید ۱۰ رقم باشد.'];
    if (empty($_POST['terms'])) return [$d, 'لطفاً قوانین و مقررات را بپذیرید.'];
    return [$d, null];
}

function chosen_gateway(): ?int
{
    if (!module('online_payment')) return null;
    $gid = input_int('gateway');
    return $gid && DB::val('SELECT 1 FROM gateways WHERE id = ? AND active = 1', [$gid]) ? $gid : null;
}

function shop_checkout(): void
{
    require_module('parts_shop');
    [$items, $sum] = Orders::cartItems();
    if (!$items) { flash('error', 'سبد خرید شما خالی است.'); redirect('cart'); }
    $shipping = Orders::shipping($sum);
    $gateways = module('online_payment') ? DB::all('SELECT id, label, bank FROM gateways WHERE active = 1 ORDER BY sort') : [];

    if (is_post()) {
        if (!rate_limit('checkout', 10, 600)) { flash('error', 'تعداد درخواست‌ها زیاد است.'); redirect('checkout'); }
        [$d, $err] = customer_input(true);
        $gid = chosen_gateway();
        if (!$err && $gateways && !$gid) $err = 'لطفاً درگاه پرداخت را انتخاب کنید.';
        if ($err) { keep_old(); flash('error', $err); redirect('checkout'); }
        $order = DB::tx(function () use ($d, $items, $sum, $shipping) {
            $code = Orders::newCode();
            $total = $sum + $shipping;
            $id = DB::insert('orders', $d + ['code' => $code, 'type' => 'parts', 'subtotal' => $sum, 'shipping' => $shipping, 'total' => $total,
                'pay_amount' => $total, 'pay_type' => 'full', 'status' => 'pending', 'track_token' => bin2hex(random_bytes(16)), 'created_at' => now(), 'updated_at' => now()]);
            foreach ($items as $it) DB::insert('order_items', ['order_id' => $id, 'part_id' => $it['part']['id'], 'title' => $it['part']['title'], 'price' => $it['price'], 'qty' => $it['qty']]);
            return DB::find('orders', $id);
        });
        clear_old();
        unset($_SESSION['cart']);
        Orders::grant($order);
        Notify::orderCreated($order);
        if ($gid) redirect('pay/' . $order['code'] . '?gw=' . $gid);
        redirect(Orders::link($order));
    }
    view('site/checkout', compact('items', 'sum', 'shipping', 'gateways') + ['title' => 'تکمیل خرید', 'car' => null]);
}

function shop_buy_car(string $id): void
{
    require_module('car_reserve');
    $car = Cars::find((int)$id);
    if (!$car || $car['status'] !== 'available' || $car['price_call'] || !$car['price']) { flash('error', 'این خودرو در حال حاضر قابل خرید آنلاین نیست.'); redirect('car/' . (int)$id); }
    $deposit = Cars::depositFor($car);
    $maxOnline = (int)setting('car_max_online', 0);
    $fullAllowed = setting('car_full_pay', '1') === '1' && (!$maxOnline || $car['price'] <= $maxOnline);
    $gateways = module('online_payment') ? DB::all('SELECT id, label, bank FROM gateways WHERE active = 1 ORDER BY sort') : [];
    $images = Cars::images((int)$car['id']);

    if (is_post()) {
        if (!rate_limit('checkout', 10, 600)) { flash('error', 'تعداد درخواست‌ها زیاد است.'); redirect('buy/' . $car['id']); }
        [$d, $err] = customer_input(false);
        if (!$err && $d['national_code'] === '') $err = 'وارد کردن کد ملی برای تنظیم قرارداد خودرو الزامی است.';
        $payType = input('pay_type') === 'full' && $fullAllowed ? 'full' : 'deposit';
        $gid = chosen_gateway();
        if (!$err && $gateways && !$gid) $err = 'لطفاً درگاه پرداخت را انتخاب کنید.';
        if ($err) { keep_old(); flash('error', $err); redirect('buy/' . $car['id']); }
        $amount = $payType === 'full' ? (int)$car['price'] : $deposit;
        $oid = DB::insert('orders', $d + ['code' => Orders::newCode(), 'type' => 'car', 'car_id' => $car['id'], 'subtotal' => $car['price'], 'shipping' => 0,
            'total' => $car['price'], 'pay_amount' => $amount, 'pay_type' => $payType, 'status' => 'pending',
            'track_token' => bin2hex(random_bytes(16)), 'created_at' => now(), 'updated_at' => now()]);
        $order = DB::find('orders', $oid);
        clear_old();
        Orders::grant($order);
        Notify::orderCreated($order);
        if ($gid) redirect('pay/' . $order['code'] . '?gw=' . $gid);
        redirect(Orders::link($order));
    }
    view('site/checkout', ['car' => $car, 'images' => $images, 'deposit' => $deposit, 'fullAllowed' => $fullAllowed, 'gateways' => $gateways,
        'items' => [], 'sum' => $car['price'], 'shipping' => 0, 'title' => 'خرید آنلاین ' . Cars::title($car)]);
}

function shop_order(string $code): void
{
    $order = DB::row('SELECT * FROM orders WHERE code = ?', [$code]);
    if (!$order || !Orders::canView($order)) abort(404);
    Orders::grant($order);
    $items = DB::all('SELECT * FROM order_items WHERE order_id = ?', [$order['id']]);
    $car = $order['car_id'] ? Cars::find((int)$order['car_id'], false) : null;
    $tx = DB::all('SELECT * FROM transactions WHERE order_id = ? ORDER BY id DESC', [$order['id']]);
    $gateways = module('online_payment') && in_array($order['status'], ['pending', 'failed'], true)
        ? DB::all('SELECT id, label, bank FROM gateways WHERE active = 1 ORDER BY sort') : [];
    view('site/order', compact('order', 'items', 'car', 'tx', 'gateways') + ['title' => 'سفارش ' . $order['code']]);
}

function shop_track(): void
{
    require_module('order_tracking');
    if (is_post()) {
        if (!rate_limit('track', 8, 600)) { flash('error', 'تعداد تلاش‌ها زیاد است. بعداً تلاش کنید.'); redirect('track'); }
        $order = DB::row('SELECT * FROM orders WHERE code = ? AND mobile = ?', [to_en_digits((string)input('code')), normalize_mobile((string)input('mobile'))]);
        if (!$order) { flash('error', 'سفارشی با این مشخصات یافت نشد.'); redirect('track'); }
        Orders::grant($order);
        redirect('order/' . $order['code']);
    }
    view('site/track', ['title' => 'پیگیری سفارش']);
}
