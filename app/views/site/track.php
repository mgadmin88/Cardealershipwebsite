<div class="page-hero"><div class="container"><h1>پیگیری سفارش</h1><p>با وارد کردن کد سفارش و شماره موبایل، وضعیت سفارش خود را ببینید</p></div></div>
<div class="container"><form method="post" class="card card-pad" style="max-width:480px;margin:auto"><?= csrf_field() ?>
  <div class="field"><label>کد سفارش</label><input name="code" required class="ltr" inputmode="numeric"></div>
  <div class="field"><label>شماره موبایل</label><input name="mobile" required class="ltr" inputmode="tel" placeholder="09xxxxxxxxx"></div>
  <?= honeypot() ?><?= Captcha::field('track') ?>
  <button class="btn btn-accent btn-block">پیگیری</button></form></div>
