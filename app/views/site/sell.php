<div class="page-hero"><div class="container"><h1>ماشینت رو با خیال راحت بفروش</h1><p>کارشناسی رایگان • قیمت‌گذاری منصفانه • پرداخت نقدی در کمتر از ۲۴ ساعت</p></div></div>
<div class="container">
  <ol class="steps"><li class="cur">ثبت درخواست</li><li>تماس کارشناس</li><li>کارشناسی رایگان</li><li>دریافت وجه</li></ol>
  <div class="cart-grid">
    <form class="card card-pad" method="post" action="<?= url('sell') ?>">
      <h2 class="card-title">مشخصات خودروی شما</h2>
      <?= csrf_field() ?><?= honeypot() ?>
      <div class="form-grid">
        <div class="field"><label>برند</label><select name="brand" data-brand-select required><option value="">انتخاب برند</option><?php foreach ($brands as $b): ?><option value="<?= $b['id'] ?>"<?= selected($b['id'], old('brand')) ?>><?= e($b['name_fa']) ?></option><?php endforeach ?></select></div>
        <div class="field"><label>مدل</label><select name="model" data-model-select><option value="">انتخاب مدل</option></select></div>
        <div class="field"><label>سال ساخت</label><select name="year"><?php for ($y = jyear_now(); $y >= 1370; $y--): ?><option value="<?= $y ?>"><?= fa($y) ?></option><?php endfor ?></select></div>
        <div class="field"><label>کارکرد (کیلومتر)</label><input name="mileage" inputmode="numeric" data-money value="<?= e(old('mileage')) ?>"></div>
        <div class="field"><label>شهر</label><select name="city"><?php foreach (Catalog::CITIES as $c): ?><option><?= $c ?></option><?php endforeach ?></select></div>
        <div class="field"><label>قیمت پیشنهادی شما (تومان)</label><input name="price" inputmode="numeric" data-money value="<?= e(old('price')) ?>"></div>
        <div class="field"><label>نام و نام خانوادگی</label><input name="name" required value="<?= e(old('name')) ?>"></div>
        <div class="field"><label>شماره موبایل</label><input name="mobile" required class="ltr" inputmode="tel" placeholder="09xxxxxxxxx" value="<?= e(old('mobile')) ?>"></div>
        <div class="field full"><label>توضیحات (وضعیت بدنه، آپشن‌ها، …)</label><textarea name="message"><?= e(old('message')) ?></textarea></div>
      </div>
      <?= Captcha::field('sell') ?>
      <button class="btn btn-accent btn-lg">ثبت درخواست فروش</button>
    </form>
    <aside class="card card-pad">
      <h2 class="card-title">چرا به ما بفروشید؟</h2>
      <div class="features" style="grid-template-columns:1fr">
        <div class="flex" style="align-items:flex-start"><span class="si spec"><span class="si"><?= icon('shield') ?></span></span><div><b>کارشناسی رایگان</b><p class="muted small">در محل نمایشگاه یا محل شما</p></div></div>
        <div class="flex" style="align-items:flex-start"><span class="spec"><span class="si"><?= icon('card') ?></span></span><div><b>پرداخت سریع</b><p class="muted small">تسویه نقدی پس از توافق و انتقال سند</p></div></div>
        <div class="flex" style="align-items:flex-start"><span class="spec"><span class="si"><?= icon('doc') ?></span></span><div><b>انجام امور اداری</b><p class="muted small">تنظیم قرارداد و پیگیری انتقال سند توسط ما</p></div></div>
      </div>
    </aside>
  </div>
</div>
