<div class="page-hero"><div class="container"><h1><?= e($page['title'] ?? 'درباره ما') ?></h1><p><?= e(setting('site_tagline')) ?></p></div></div>
<div class="container">
  <div class="card card-pad prose"><?= clean_html((string)($page['content'] ?? '')) ?></div>
  <?php if (module('stats') && ($stats = Settings::json('stats'))): ?><div class="stats mt-3"><?php foreach ($stats as [$n, $l]): ?><div><b><?= e($n) ?></b><span><?= e($l) ?></span></div><?php endforeach ?></div><?php endif ?>
  <div class="features mt-3">
    <div class="feature"><div class="fi"><?= icon('shield') ?></div><h3>شفافیت کامل</h3><p>گزارش کارشناسی و نقشه بدنه هر خودرو به‌صورت عمومی منتشر می‌شود.</p></div>
    <div class="feature"><div class="fi"><?= icon('users') ?></div><h3>تیم متخصص</h3><p>کارشناسان باتجربه در حوزه فنی، بدنه و حقوقی خرید و فروش خودرو.</p></div>
    <div class="feature"><div class="fi"><?= icon('truck') ?></div><h3>خدمات پس از فروش</h3><p>پشتیبانی، تامین قطعات اصلی و آپشن پس از خرید.</p></div>
  </div>
</div>
