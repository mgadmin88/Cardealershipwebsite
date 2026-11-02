<?php $lat = (float)setting('map_lat'); $lng = (float)setting('map_lng'); $d = 0.006; ?>
<div class="page-hero"><div class="container"><h1>تماس با ما</h1><p>کارشناسان ما آماده پاسخگویی به شما هستند</p></div></div>
<div class="container">
  <div class="contact-grid">
    <div class="contact-cards">
      <a class="ccard" href="tel:<?= e(preg_replace('/\D/', '', setting('phone'))) ?>"><span class="ci"><?= icon('phone') ?></span><div><small>تلفن تماس</small><b class="ltr"><?= e(fa(setting('phone'))) ?></b><?php if (setting('phone2')): ?> — <b class="ltr"><?= e(fa(setting('phone2'))) ?></b><?php endif ?></div></a>
      <?php if (setting('mobile')): ?><a class="ccard" href="tel:<?= e(setting('mobile')) ?>"><span class="ci"><?= icon('sms') ?></span><div><small>موبایل / واتس‌اپ</small><b class="ltr"><?= e(fa(setting('mobile'))) ?></b></div></a><?php endif ?>
      <a class="ccard" href="mailto:<?= e(setting('email')) ?>"><span class="ci"><?= icon('mail') ?></span><div><small>ایمیل</small><b><?= e(setting('email')) ?></b></div></a>
      <div class="ccard"><span class="ci"><?= icon('clock') ?></span><div><small>ساعات کاری</small><b><?= e(setting('work_hours')) ?></b></div></div>
      <div class="ccard"><span class="ci"><?= icon('pin') ?></span><div><small>آدرس نمایشگاه</small><b><?= e(setting('address')) ?></b></div></div>
    </div>
    <form class="card card-pad" method="post" action="<?= url('contact') ?>">
      <h2 class="card-title">ارسال پیام</h2>
      <?= csrf_field() ?><?= honeypot() ?>
      <div class="form-grid">
        <div class="field"><label for="c-name">نام و نام خانوادگی</label><input id="c-name" name="name" required maxlength="120" value="<?= e(old('name')) ?>" autocomplete="name"></div>
        <div class="field"><label for="c-mob">شماره موبایل</label><input id="c-mob" name="mobile" required inputmode="tel" class="ltr" value="<?= e(old('mobile')) ?>" placeholder="09xxxxxxxxx" autocomplete="tel"></div>
        <div class="field"><label for="c-email">ایمیل (اختیاری)</label><input id="c-email" type="email" name="email" class="ltr" value="<?= e(old('email')) ?>"></div>
        <div class="field"><label for="c-sub">موضوع</label><select id="c-sub" name="subject"><?php foreach (['مشاوره خرید خودرو', 'فروش خودرو', 'خرید اقساطی', 'قطعات و آپشن', 'پیگیری سفارش', 'انتقادات و پیشنهادات'] as $s): ?><option<?= selected($s, old('subject')) ?>><?= $s ?></option><?php endforeach ?></select></div>
        <div class="field full"><label for="c-msg">متن پیام</label><textarea id="c-msg" name="message" required maxlength="2000"><?= e(old('message')) ?></textarea></div>
      </div>
      <?= Captcha::field('contact') ?>
      <button class="btn btn-accent btn-lg">ارسال پیام <?= icon('arrow-left') ?></button>
    </form>
  </div>
  <?php if ($lat && $lng): ?>
  <div class="section-head mt-4"><div><h2>موقعیت ما روی نقشه</h2></div></div>
  <div class="map-box">
    <iframe loading="lazy" title="نقشه" referrerpolicy="no-referrer"
      src="https://www.openstreetmap.org/export/embed.html?bbox=<?= $lng - $d ?>%2C<?= $lat - $d ?>%2C<?= $lng + $d ?>%2C<?= $lat + $d ?>&amp;layer=mapnik&amp;marker=<?= $lat ?>%2C<?= $lng ?>"></iframe>
  </div>
  <div class="map-links">
    <a class="btn btn-ghost btn-sm" target="_blank" rel="noopener" href="https://neshan.org/maps/@<?= $lat ?>,<?= $lng ?>,16z"><?= icon('map') ?> مسیریابی با نشان</a>
    <a class="btn btn-ghost btn-sm" target="_blank" rel="noopener" href="https://balad.ir/location?latitude=<?= $lat ?>&longitude=<?= $lng ?>&zoom=16"><?= icon('map') ?> مسیریابی با بلد</a>
    <a class="btn btn-ghost btn-sm" target="_blank" rel="noopener" href="https://www.google.com/maps/search/?api=1&query=<?= $lat ?>,<?= $lng ?>"><?= icon('map') ?> Google Maps</a>
    <a class="btn btn-ghost btn-sm" href="geo:<?= $lat ?>,<?= $lng ?>"><?= icon('pin') ?> باز کردن در اپ نقشه</a>
  </div>
  <?php endif ?>
</div>
