<?php $max = max(1, ...array_column($days, 1)); ?>
<?php
// server health: PHP extensions this site relies on
$need = [
    'curl'     => [extension_loaded('curl'), 'پرداخت آنلاین و ارسال پیامک کار نمی‌کند.'],
    'openssl'  => [extension_loaded('openssl'), 'رمزنگاری کلیدهای درگاه و درگاه سداد کار نمی‌کند.'],
    'gd'       => [extension_loaded('gd'), 'کپچا و بهینه‌سازی تصاویر کار نمی‌کند.'],
    'fileinfo' => [extension_loaded('fileinfo'), 'مشکلی ایجاد نمی‌کند (روش جایگزین فعال است)، ولی فعال بودنش بهتر است.'],
    'mbstring' => [extension_loaded('mbstring'), 'متن‌های فارسی درست پردازش نمی‌شوند.'],
];
$missing = array_filter($need, fn($x) => !$x[0]);
if ($missing && Auth::user()['role'] === 'admin'): ?>
<div class="alert error" style="margin-bottom:18px"><?= icon('info') ?><div>
  <b>چند افزونه PHP روی هاست فعال نیست:</b>
  <ul style="margin:6px 0 4px;padding-inline-start:18px"><?php foreach ($missing as $ext => [, $why]): ?><li><b dir="ltr"><?= $ext ?></b> — <?= $why ?></li><?php endforeach ?></ul>
  <small>برای فعال‌سازی در سی‌پنل: بخش Select PHP Version ← تب Extensions ← تیک افزونه‌ها را بزنید (یا از پشتیبانی هاست بخواهید).</small>
</div></div>
<?php endif ?>
<?php if (License::enabled() && Auth::user()['role'] === 'admin') { $ls = License::status();
  if ($ls['state'] === 'ok' && ($ls['days_left'] ?? 99) <= 15): ?>
  <a class="alert warn" style="margin-bottom:18px;text-decoration:none" href="<?= url('admin/license') ?>"><?= icon('shield') ?><span>لایسنس سایت تا <?= fa($ls['days_left']) ?> روز دیگر منقضی می‌شود. برای تمدید اقدام کنید.</span></a>
<?php endif; } ?>
<div class="kpis">
  <div class="kpi"><span class="ki"><?= icon('car') ?></span><div><b><?= fa($s['cars']) ?></b><small>خودروی موجود</small></div></div>
  <div class="kpi" style="--c:#2563eb"><span class="ki"><?= icon('lock') ?></span><div><b><?= fa($s['reserved']) ?></b><small>رزرو شده</small></div></div>
  <div class="kpi" style="--c:#16a34a"><span class="ki"><?= icon('check') ?></span><div><b><?= fa($s['sold']) ?></b><small>فروخته شده</small></div></div>
  <div class="kpi" style="--c:#7c3aed"><span class="ki"><?= icon('card') ?></span><div><b><?= fa(price_words($s['revenue'])) ?></b><small>دریافتی ۳۰ روز (تومان)</small></div></div>
  <div class="kpi" style="--c:#0891b2"><span class="ki"><?= icon('cart') ?></span><div><b><?= fa($s['orders']) ?></b><small>سفارش ۳۰ روز اخیر</small></div></div>
  <div class="kpi" style="--c:#dc2626"><span class="ki"><?= icon('message') ?></span><div><b><?= fa($s['requests']) ?></b><small>درخواست بررسی‌نشده</small></div></div>
  <div class="kpi" style="--c:#d97706"><span class="ki"><?= icon('box') ?></span><div><b><?= fa($s['parts']) ?></b><small>محصول فعال (<?= fa($s['low']) ?> کم‌موجود)</small></div></div>
  <div class="kpi" style="--c:#475569"><span class="ki"><?= icon('eye') ?></span><div><b><?= fa(num($s['views'])) ?></b><small>بازدید آگهی‌ها</small></div></div>
</div>
<div class="grid2">
  <div class="card card-pad"><h3 class="card-title">دریافتی ۷ روز اخیر</h3>
    <div class="mini-bar" style="margin-bottom:30px"><?php foreach ($days as [$d, $v]): ?><div style="height:<?= max(2, round($v / $max * 100)) ?>%" title="<?= e(num($v)) ?>"><?php if ($v): ?><b><?= fa(price_words($v)) ?></b><?php endif ?><span><?= mb_substr($d, 5) ?></span></div><?php endforeach ?></div></div>
  <div class="card card-pad"><h3 class="card-title">پربازدیدترین خودروها</h3>
    <table class="table"><?php foreach ($top as $c): ?><tr><td><a href="<?= url('admin/cars/' . $c['id']) ?>"><?= e(Cars::title($c)) ?></a></td><td><?= fa($c['views']) ?> بازدید</td></tr><?php endforeach ?></table></div>
  <div class="card card-pad"><div class="flex between"><h3 class="card-title">آخرین سفارش‌ها</h3><a href="<?= url('admin/orders') ?>" class="small">همه</a></div>
    <div class="tbl-wrap"><table class="table"><?php foreach ($orders as $o): ?><tr><td><a href="<?= url('admin/orders/' . $o['id']) ?>"><?= fa($o['code']) ?></a></td><td><?= e($o['customer_name']) ?></td><td><?= fa(num($o['pay_amount'])) ?></td><td><?= Orders::statusBadge($o['status']) ?></td></tr><?php endforeach ?>
    <?php if (!$orders): ?><tr><td class="muted">سفارشی ثبت نشده است.</td></tr><?php endif ?></table></div></div>
  <div class="card card-pad"><div class="flex between"><h3 class="card-title">آخرین درخواست‌ها</h3><a href="<?= url('admin/requests') ?>" class="small">همه</a></div>
    <div class="tbl-wrap"><table class="table"><?php foreach ($requests as $r): ?><tr><td><?= e(Catalog::REQUEST_TYPES[$r['type']] ?? $r['type']) ?></td><td><?= e($r['name']) ?></td><td class="ltr"><?= fa($r['mobile']) ?></td><td><?= $r['status'] === 'new' ? '<span class="badge warn">جدید</span>' : '<span class="badge">' . ($r['status'] === 'done' ? 'انجام شد' : 'دیده شد') . '</span>' ?></td></tr><?php endforeach ?>
    <?php if (!$requests): ?><tr><td class="muted">درخواستی ثبت نشده است.</td></tr><?php endif ?></table></div></div>
</div>
