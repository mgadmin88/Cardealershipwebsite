<?php
/** @var string $content */
$siteName = setting('site_name');
$pageTitle = isset($title) ? $title . ' | ' . $siteName : $siteName . ' | ' . setting('site_tagline');
$desc = $metaDesc ?? setting('site_description');
$path = '/' . trim(substr(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), strlen(base_path())), '/');
$isActive = fn(string $p) => ($p === '/' ? $path === '/' : str_starts_with($path, $p)) ? ' active' : '';
$cartCount = array_sum($_SESSION['cart'] ?? []);
$mode = Theme::mode();
$headerStyle = setting('theme_header', 'light');
?><!doctype html>
<html lang="fa" dir="rtl" data-theme="<?= $mode === 'dark' ? 'dark' : 'light' ?>" data-card="<?= e(setting('theme_card', 'elevated')) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e(excerpt($desc, 160)) ?>">
<link rel="canonical" href="<?= e(abs_url(ltrim($path, '/'))) ?>">
<meta property="og:type" content="website"><meta property="og:site_name" content="<?= e($siteName) ?>">
<meta property="og:title" content="<?= e($pageTitle) ?>"><meta property="og:description" content="<?= e(excerpt($desc, 160)) ?>">
<?php if (!empty($ogImage)): ?><meta property="og:image" content="<?= e($ogImage) ?>"><?php endif ?>
<meta name="theme-color" content="<?= e(Theme::colors()['primary']) ?>">
<link rel="icon" href="<?= setting('favicon') ? e(upload_url(setting('favicon'))) : asset('img/favicon.svg') ?>">
<link rel="preload" href="<?= asset('fonts/Vazirmatn-FD-Regular.woff2') ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= asset('css/site.css') ?>"><link rel="stylesheet" href="<?= asset('css/fx.css') ?>">
<style><?= Theme::css() ?></style>
<script nonce="<?= nonce() ?>">(function(){try{var m=<?= json_encode($mode) ?>,s=localStorage.getItem('theme');if(<?= module('dark_toggle') ? 'true' : 'false' ?>&&s)m=s;if(m==='auto')m=matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light';document.documentElement.dataset.theme=m}catch(e){}})()</script>
<script type="application/ld+json"><?= json_encode(['@context' => 'https://schema.org', '@type' => 'AutoDealer', 'name' => $siteName, 'telephone' => setting('phone'),
    'address' => setting('address'), 'url' => base_url(), 'geo' => ['@type' => 'GeoCoordinates', 'latitude' => setting('map_lat'), 'longitude' => setting('map_lng')]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
<?= $headExtra ?? '' ?>
</head>
<body>
<a href="#main" class="sr-only">رفتن به محتوای اصلی</a>
<div class="topbar">
  <div class="container">
    <div class="flex">
      <a href="tel:<?= e(preg_replace('/\D/', '', setting('phone'))) ?>" class="flex"><?= icon('phone') ?> <span class="ltr"><?= e(fa(setting('phone'))) ?></span></a>
      <span class="flex hide-s"><?= icon('clock') ?> <?= e(setting('work_hours')) ?></span>
    </div>
    <div class="flex">
      <?php if (module('order_tracking')): ?><a href="<?= url('track') ?>" class="hide-s">پیگیری سفارش</a><?php endif ?>
      <?php if (setting('instagram')): ?><a href="<?= e(setting('instagram')) ?>" target="_blank" rel="noopener" aria-label="اینستاگرام"><?= icon('instagram') ?></a><?php endif ?>
      <?php if (setting('telegram')): ?><a href="<?= e(setting('telegram')) ?>" target="_blank" rel="noopener" aria-label="تلگرام"><?= icon('telegram') ?></a><?php endif ?>
    </div>
  </div>
</div>
<header class="header" data-style="<?= e($headerStyle) ?>" id="header">
  <div class="container">
    <button class="icon-btn menu-btn" data-toggle="nav" aria-label="منو"><?= icon('menu') ?></button>
    <a href="<?= url('/') ?>" class="logo" aria-label="<?= e($siteName) ?>">
      <?php if (setting('logo')): ?><img src="<?= e(upload_url(setting('logo'))) ?>" alt="<?= e($siteName) ?>">
      <?php else: ?><span class="logo-mark"><?= icon('car') ?></span><span><?= e($siteName) ?><small><?= e(setting('site_tagline')) ?></small></span><?php endif ?>
    </a>
    <nav class="nav" id="nav" aria-label="منوی اصلی">
      <a href="<?= url('/') ?>" class="<?= $isActive('/') ?>">خانه</a>
      <?php if (module('used_cars')): ?><a href="<?= url('cars', ['condition' => 'used']) ?>" class="<?= ($_GET['condition'] ?? '') === 'used' ? 'active' : '' ?>">خودروی کارکرده</a><?php endif ?>
      <?php if (module('new_cars')): ?><a href="<?= url('cars', ['condition' => 'new']) ?>" class="<?= ($_GET['condition'] ?? '') === 'new' ? 'active' : '' ?>">خودروی صفر</a><?php endif ?>
      <?php if (module('parts_shop')): ?><a href="<?= url('parts') ?>" class="<?= $isActive('/part') ?>">قطعات و آپشن</a><?php endif ?>
      <?php if (module('sell_request')): ?><a href="<?= url('sell') ?>" class="<?= $isActive('/sell') ?>">فروش خودروی شما</a><?php endif ?>
      <?php if (module('about')): ?><a href="<?= url('about') ?>" class="<?= $isActive('/about') ?>">درباره ما</a><?php endif ?>
      <?php if (module('contact')): ?><a href="<?= url('contact') ?>" class="<?= $isActive('/contact') ?>">تماس با ما</a><?php endif ?>
    </nav>
    <div class="header-actions">
      <?php if (module('dark_toggle')): ?><button class="icon-btn" data-theme-toggle aria-label="تغییر تم"><?= icon('moon') ?></button><?php endif ?>
      <?php if (module('favorites')): ?><a href="<?= url('favorites') ?>" class="icon-btn hide-m" aria-label="علاقه‌مندی‌ها"><?= icon('heart') ?><span class="count" data-fav-count></span></a><?php endif ?>
      <?php if (module('compare')): ?><a href="<?= url('compare') ?>" class="icon-btn hide-m" aria-label="مقایسه" data-compare-link><?= icon('compare') ?><span class="count" data-cmp-count></span></a><?php endif ?>
      <?php if (module('parts_shop')): ?><a href="<?= url('cart') ?>" class="icon-btn" aria-label="سبد خرید"><?= icon('cart') ?><span class="count" data-n="<?= $cartCount ?>"><?= $cartCount ?: '' ?></span></a><?php endif ?>
      <a href="tel:<?= e(preg_replace('/\D/', '', setting('phone'))) ?>" class="btn btn-accent hide-m"><?= icon('phone') ?> مشاوره خرید</a>
    </div>
  </div>
</header>
<div class="backdrop" id="backdrop"></div>

<main id="main">
<?php $fl = flashes(); if ($fl): ?><div class="toast-wrap"><?php foreach ($fl as [$t, $m]): ?><div class="toast<?= $t === 'error' ? ' toast-error' : '' ?>" role="<?= $t === 'error' ? 'alert' : 'status' ?>"><?= icon($t === 'error' ? 'info' : 'check') ?><?= e($m) ?></div><?php endforeach ?></div><?php endif ?>
<?= $content ?>
</main>

<footer class="footer">
  <div class="container footer-grid">
    <div>
      <a href="<?= url('/') ?>" class="logo"><span class="logo-mark"><?= icon('car') ?></span><span><?= e($siteName) ?></span></a>
      <p class="small"><?= e(setting('footer_about')) ?></p>
      <div class="socials">
        <?php foreach (['instagram', 'telegram', 'linkedin'] as $s): if (setting($s)): ?><a href="<?= e(setting($s)) ?>" target="_blank" rel="noopener" aria-label="<?= $s ?>"><?= icon($s) ?></a><?php endif; endforeach ?>
        <?php if (setting('whatsapp')): ?><a href="https://wa.me/<?= e(preg_replace('/\D/', '', setting('whatsapp'))) ?>" target="_blank" rel="noopener" aria-label="واتس‌اپ"><?= icon('chat') ?></a><?php endif ?>
      </div>
    </div>
    <div>
      <h4>دسترسی سریع</h4>
      <ul>
        <?php if (module('used_cars')): ?><li><a href="<?= url('cars', ['condition' => 'used']) ?>">خرید خودروی کارکرده</a></li><?php endif ?>
        <?php if (module('new_cars')): ?><li><a href="<?= url('cars', ['condition' => 'new']) ?>">خرید خودروی صفر</a></li><?php endif ?>
        <?php if (module('installment')): ?><li><a href="<?= url('cars', ['installment' => 1]) ?>">خرید اقساطی</a></li><?php endif ?>
        <?php if (module('parts_shop')): ?><li><a href="<?= url('parts') ?>">فروشگاه قطعات</a></li><?php endif ?>
        <?php if (module('sell_request')): ?><li><a href="<?= url('sell') ?>">فروش خودروی شما</a></li><?php endif ?>
      </ul>
    </div>
    <div>
      <h4>راهنما</h4>
      <ul>
        <?php if (module('faq')): ?><li><a href="<?= url('faq') ?>">سوالات متداول</a></li><?php endif ?>
        <?php if (module('order_tracking')): ?><li><a href="<?= url('track') ?>">پیگیری سفارش</a></li><?php endif ?>
        <?php foreach (DB::all('SELECT slug, title FROM pages WHERE active = 1 AND in_footer = 1 AND slug != ? ORDER BY id', ['about']) as $pg): ?>
          <li><a href="<?= url('page/' . $pg['slug']) ?>"><?= e($pg['title']) ?></a></li>
        <?php endforeach ?>
        <?php if (module('about')): ?><li><a href="<?= url('about') ?>">درباره ما</a></li><?php endif ?>
        <?php if (module('contact')): ?><li><a href="<?= url('contact') ?>">تماس با ما</a></li><?php endif ?>
      </ul>
    </div>
    <div>
      <h4>ارتباط با ما</h4>
      <div class="contact-line"><?= icon('pin') ?><span><?= e(setting('address')) ?></span></div>
      <div class="contact-line"><?= icon('phone') ?><a href="tel:<?= e(preg_replace('/\D/', '', setting('phone'))) ?>" class="ltr"><?= e(fa(setting('phone'))) ?></a></div>
      <div class="contact-line"><?= icon('mail') ?><a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a></div>
      <div class="trust">
        <?php if (setting('enamad_img')): ?><a href="<?= e(setting('enamad_link')) ?>" target="_blank" rel="noopener" referrerpolicy="origin"><img src="<?= e(setting('enamad_img')) ?>" alt="نماد اعتماد" referrerpolicy="origin"></a><?php endif ?>
      </div>
    </div>
  </div>
  <div class="container copy">
    <span>© <?= jdate(time(), 'year') ?> — تمامی حقوق برای <?= e($siteName) ?> محفوظ است.</span>
    <span>پرداخت امن از طریق درگاه‌های شاپرک <?= icon('lock') ?></span>
  </div>
</footer>

<?php if (module('floating_contact')): ?>
<div class="fab" id="fab">
  <button aria-label="تماس با ما" data-fab><?= icon('chat') ?></button>
  <a href="tel:<?= e(preg_replace('/\D/', '', setting('phone'))) ?>" aria-label="تماس تلفنی"><?= icon('phone') ?></a>
  <?php if (setting('whatsapp')): ?><a href="https://wa.me/<?= e(preg_replace('/\D/', '', setting('whatsapp'))) ?>" target="_blank" rel="noopener" aria-label="واتس‌اپ"><?= icon('chat') ?></a><?php endif ?>
  <?php if (setting('telegram')): ?><a href="<?= e(setting('telegram')) ?>" target="_blank" rel="noopener" aria-label="تلگرام"><?= icon('telegram') ?></a><?php endif ?>
</div>
<?php endif ?>
<button class="to-top" id="toTop" aria-label="بازگشت به بالا"><svg class="ic" style="transform:rotate(90deg)"><use href="<?= asset('img/icons.svg') ?>#chev-right"></use></svg></button>
<?php if (module('compare')): ?>
<div class="compare-bar" id="compareBar"><?= icon('compare') ?> <span><b data-cmp-count>0</b> خودرو برای مقایسه</span>
  <a href="<?= url('compare') ?>" class="btn btn-accent btn-sm" data-compare-link>مقایسه</a>
  <button class="tool" data-cmp-clear aria-label="پاک کردن"><?= icon('x') ?></button></div>
<?php endif ?>
<script id="app-config" type="application/json"><?= json_encode(['base' => base_path(), 'icons' => asset('img/icons.svg'), 'csrf' => csrf_token(),
    'fav' => module('favorites'), 'cmp' => module('compare'), 'toggle' => module('dark_toggle')], JSON_HEX_TAG | JSON_UNESCAPED_SLASHES) ?></script>
<script src="<?= asset('js/site.js') ?>" defer></script>
<script src="<?= asset('js/fx.js') ?>" defer></script>
</body>
</html>
