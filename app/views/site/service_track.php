<div class="container section">
  <form method="post" class="card card-pad" style="max-width:480px;margin:auto"><?= csrf_field() ?><?= honeypot() ?>
    <h1 class="acc-h2" style="font-size:1.35em">پیگیری نوبت سرویس</h1>
    <p class="muted small">کد پیگیری نوبت (مثل S123456) و شماره موبایلی که با آن نوبت گرفته‌اید را وارد کنید.</p>
    <div class="field"><label>کد پیگیری</label><input name="code" required class="ltr" placeholder="S123456" value="<?= e(old('code')) ?>"></div>
    <div class="field"><label>شماره موبایل</label><input name="mobile" required inputmode="tel" class="ltr" placeholder="09xxxxxxxxx" value="<?= e(old('mobile')) ?>"></div>
    <?= Captcha::field('strack') ?>
    <button type="submit" class="btn btn-accent btn-block">پیگیری</button>
    <p class="small muted center mt-2"><a href="<?= url('service') ?>">ثبت نوبت جدید</a></p>
  </form>
</div>
