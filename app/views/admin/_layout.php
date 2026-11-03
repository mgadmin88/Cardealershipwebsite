<?php
$u = Auth::user();
$p = '/' . trim(substr(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), strlen(base_path())), '/');
if (License::enabled() && !License::ok() && !in_array($p, ['/admin/license', '/admin/login', '/admin/forgot'], true)) { header('Location: ' . url('admin/license')); return; }
$on = fn($x) => ($x === '/admin' ? $p === '/admin' : str_starts_with($p, $x)) ? ' class="on"' : '';
$newReq = (int)DB::val("SELECT COUNT(*) FROM requests WHERE status = 'new'");
$newOrd = (int)DB::val("SELECT COUNT(*) FROM orders WHERE status = 'paid'");
$newAppt = 0;
try { $newAppt = (int)DB::val("SELECT COUNT(*) FROM appointments WHERE status = 'pending' AND day >= ?", [date('Y-m-d')]); } catch (Throwable) {}
$menu = [
    ['', [['/admin', 'grid', 'داشبورد', '']]],
    ['خودروها', [['/admin/cars', 'car', 'مدیریت خودروها', 'cars'], ['/admin/brands', 'tag', 'برندها و مدل‌ها', 'cars']]],
    ['فروشگاه', [['/admin/orders', 'cart', 'سفارش‌ها', 'orders', $newOrd], ['/admin/customers', 'users', 'مشتریان', 'orders'], ['/admin/transactions', 'card', 'تراکنش‌ها', 'orders'], ['/admin/parts', 'box', 'قطعات و آپشن‌ها', 'parts'], ['/admin/part-categories', 'layers', 'دسته‌بندی قطعات', 'parts']]],
    ['ارتباط با مشتری', [['/admin/requests', 'message', 'درخواست‌ها و پیام‌ها', 'requests', $newReq]]],
    ['نوبت‌دهی و خدمات', module('service_booking') ? [['/admin/appointments', 'calendar', 'نوبت‌های سرویس', 'service', $newAppt], ['/admin/surveys', 'star', 'نظرسنجی مشتریان', 'service'], ['/admin/service-settings', 'wrench', 'تنظیمات نوبت‌دهی', 'settings']] : []],
    ['محتوا', [['/admin/slider', 'image', 'اسلایدر صفحه اول', 'content'], ['/admin/pages', 'doc', 'صفحات (درباره ما، قوانین)', 'content'], ['/admin/faqs', 'info', 'سوالات متداول', 'content']]],
    ['تنظیمات', [['/admin/modules', 'toggle', 'ماژول‌ها', 'settings'], ['/admin/theme', 'palette', 'تم و ظاهر سایت', 'settings'], ['/admin/settings', 'gear', 'تنظیمات عمومی', 'settings'],
        ['/admin/payments', 'card', 'درگاه‌های پرداخت', 'settings'], ['/admin/sms', 'sms', 'پنل پیامک', 'settings'], ['/admin/users', 'users', 'کاربران و دسترسی‌ها', 'users'], ['/admin/backup', 'upload', 'پشتیبان‌گیری و بازیابی', 'backup'], ['/admin/license', 'shield', 'لایسنس', 'settings'], ['/admin/logs', 'list', 'گزارش فعالیت‌ها', 'users']]],
];
?><!doctype html>
<html lang="fa" dir="rtl" data-theme="light">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow">
<title><?= e($title ?? 'پنل مدیریت') ?> | مدیریت <?= e(setting('site_name')) ?></title>
<link rel="icon" href="<?= asset('img/favicon.svg') ?>">
<link rel="stylesheet" href="<?= asset('css/site.css') ?>"><link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
<style><?= Theme::css() ?></style>
<script nonce="<?= nonce() ?>">try{var t=localStorage.getItem('theme')||localStorage.getItem('adm-theme');if(t==='auto')t=matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light';if(t==='dark')document.documentElement.dataset.theme='dark'}catch(e){}</script>
</head>
<body class="admin">
<?php if (empty($bare)): ?>
<div class="adm">
  <aside class="side" id="side">
    <a href="<?= url('admin') ?>" class="logo"><span class="logo-mark"><?= icon('car') ?></span><span><?= e(setting('site_name')) ?><small style="color:#64748b">پنل مدیریت</small></span></a>
    <?php foreach ($menu as [$g, $items]): $items = array_filter($items, fn($i) => !$i[3] || Auth::can($i[3])); if (!$items) continue; ?>
      <?php if ($g): ?><div class="grp"><?= $g ?></div><?php endif ?>
      <?php foreach ($items as $i): ?><a href="<?= url(ltrim($i[0], '/')) ?>"<?= $on($i[0]) ?>><?= icon($i[1]) ?><?= $i[2] ?><?php if (!empty($i[4])): ?><span class="badge"><?= fa($i[4]) ?></span><?php endif ?></a><?php endforeach ?>
    <?php endforeach ?>
    <div class="grp">حساب</div>
    <a href="<?= url('admin/profile') ?>"<?= $on('/admin/profile') ?>><?= icon('user') ?><?= e($u['name']) ?></a>
    <a href="<?= url('/') ?>" target="_blank"><?= icon('external') ?>مشاهده سایت</a>
    <form method="post" action="<?= url('admin/logout') ?>"><?= csrf_field() ?><button class="btn btn-ghost btn-block mt-1" style="color:#cbd5e1;border-color:#ffffff1a"><?= icon('logout') ?> خروج</button></form>
  </aside>
  <div class="amain">
    <header class="atop">
      <button class="icon-btn amenu" data-side aria-label="منو"><?= icon('menu') ?></button>
      <h1><?= e($title ?? '') ?></h1>
      <?= $actions ?? '' ?>
      <button class="icon-btn" data-adm-theme aria-label="حالت شب"><?= icon('moon') ?></button>
    </header>
    <div class="acontent">
      <?php foreach (flashes() as [$t, $m]): ?><div class="alert <?= $t === 'error' ? 'error' : 'ok' ?>"><?= icon($t === 'error' ? 'info' : 'check') ?><span><?= e($m) ?></span></div><?php endforeach ?>
      <?= $content ?>
    </div>
  </div>
</div>
<div class="backdrop" id="backdrop"></div>
<?php else: ?>
<?= $content ?>
<?php endif ?>
<script id="app-config" type="application/json"><?= json_encode(['base' => base_path(), 'icons' => asset('img/icons.svg'), 'csrf' => csrf_token(), 'damage' => Catalog::DAMAGE], JSON_HEX_TAG | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
<script src="<?= asset('js/admin.js') ?>" defer></script>
</body></html>
