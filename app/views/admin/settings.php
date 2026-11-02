<?php $s = fn($k) => e(setting($k)); $stats = Settings::json('stats'); ?>
<form method="post" enctype="multipart/form-data"><?= csrf_field() ?>
  <div class="tabsbar" data-tabs>
    <button type="button" class="on" data-tab="s1">اطلاعات سایت</button><button type="button" data-tab="s2">تماس و نقشه</button>
    <button type="button" data-tab="s3">فروش و پرداخت</button><button type="button" data-tab="s4">شبکه‌های اجتماعی و نماد</button>
  </div>
  <div class="tab on" id="s1"><div class="grid2">
    <div class="card section-card"><h3>هویت سایت</h3><div class="body">
      <div class="field"><label>نام سایت / نمایندگی</label><input name="site_name" value="<?= $s('site_name') ?>"></div>
      <div class="field"><label>شعار</label><input name="site_tagline" value="<?= $s('site_tagline') ?>"></div>
      <div class="field"><label>توضیحات سئو (Meta description)</label><textarea name="site_description" rows="3"><?= $s('site_description') ?></textarea></div>
      <div class="field"><label>متن درباره در فوتر</label><textarea name="footer_about" rows="3"><?= $s('footer_about') ?></textarea></div>
    </div></div>
    <div class="card section-card"><h3>لوگو و آمار</h3><div class="body">
      <div class="field"><label>لوگو</label><?php if (setting('logo')): ?><img src="<?= e(upload_url(setting('logo'))) ?>" style="max-height:60px;margin-bottom:8px" alt=""><label class="check"><input type="checkbox" name="remove_logo" value="1"> حذف لوگو</label><?php endif ?><input type="file" name="logo" accept="image/*"></div>
      <div class="field"><label>فاوآیکن</label><input type="file" name="favicon" accept="image/png,image/webp"></div>
      <label class="lbl">آمار نمایش‌داده‌شده در سایت</label>
      <?php for ($i = 0; $i < 4; $i++): ?><div class="form-grid" style="grid-template-columns:1fr 2fr"><div class="field"><input name="stats_n[]" value="<?= e($stats[$i][0] ?? '') ?>" placeholder="۱۵+"></div><div class="field"><input name="stats_l[]" value="<?= e($stats[$i][1] ?? '') ?>" placeholder="سال تجربه"></div></div><?php endfor ?>
    </div></div>
  </div></div>
  <div class="tab" id="s2"><div class="grid2">
    <div class="card section-card"><h3>اطلاعات تماس</h3><div class="body"><div class="form-grid">
      <div class="field"><label>تلفن اصلی</label><input name="phone" dir="ltr" value="<?= $s('phone') ?>"></div>
      <div class="field"><label>تلفن دوم</label><input name="phone2" dir="ltr" value="<?= $s('phone2') ?>"></div>
      <div class="field"><label>موبایل</label><input name="mobile" dir="ltr" value="<?= $s('mobile') ?>"></div>
      <div class="field"><label>واتس‌اپ (با کد کشور 98...)</label><input name="whatsapp" dir="ltr" value="<?= $s('whatsapp') ?>"></div>
      <div class="field"><label>ایمیل</label><input name="email" dir="ltr" value="<?= $s('email') ?>"></div>
      <div class="field"><label>ساعات کاری</label><input name="work_hours" value="<?= $s('work_hours') ?>"></div>
      <div class="field full"><label>آدرس</label><textarea name="address" rows="2"><?= $s('address') ?></textarea></div>
    </div></div></div>
    <div class="card section-card"><h3>لوکیشن روی نقشه</h3><div class="body">
      <div class="form-grid"><div class="field"><label>عرض جغرافیایی (Latitude)</label><input name="map_lat" dir="ltr" value="<?= $s('map_lat') ?>"></div>
      <div class="field"><label>طول جغرافیایی (Longitude)</label><input name="map_lng" dir="ltr" value="<?= $s('map_lng') ?>"></div></div>
      <p class="small muted">مختصات را از گوگل‌مپ یا نشان کپی کنید (روی محل کلیک راست ← مختصات). نقشه در صفحه تماس با لینک مسیریابی نشان، بلد و گوگل نمایش داده می‌شود.</p>
    </div></div>
  </div></div>
  <div class="tab" id="s3"><div class="grid2">
    <div class="card section-card"><h3>خرید آنلاین خودرو</h3><div class="body"><div class="form-grid">
      <div class="field"><label>نوع بیعانه</label><select name="deposit_type"><option value="percent"<?= selected('percent', setting('deposit_type')) ?>>درصد از قیمت</option><option value="fixed"<?= selected('fixed', setting('deposit_type')) ?>>مبلغ ثابت (تومان)</option></select></div>
      <div class="field"><label>مقدار بیعانه</label><input name="deposit_value" dir="ltr" value="<?= $s('deposit_value') ?>"></div>
      <div class="field full"><label class="check"><input type="checkbox" name="car_full_pay" value="1" <?= checked(setting('car_full_pay') === '1') ?>> امکان پرداخت کامل مبلغ خودرو به‌صورت آنلاین</label></div>
      <div class="field"><label>سقف پرداخت کامل آنلاین (تومان، ۰ = نامحدود)</label><input name="car_max_online" data-money value="<?= $s('car_max_online') ?>"></div>
    </div></div></div>
    <div class="card section-card"><h3>اقساط و ارسال</h3><div class="body"><div class="form-grid">
      <div class="field"><label>نرخ سود سالانه اقساط (٪)</label><input name="installment_rate" dir="ltr" value="<?= $s('installment_rate') ?>"></div>
      <div class="field"><label>حداکثر تعداد اقساط (ماه)</label><input name="installment_max_months" dir="ltr" value="<?= $s('installment_max_months') ?>"></div>
      <div class="field"><label>حداقل پیش‌پرداخت (٪)</label><input name="installment_min_down" dir="ltr" value="<?= $s('installment_min_down') ?>"></div>
      <div class="field"><label>هزینه ارسال قطعات (تومان)</label><input name="shipping_cost" data-money value="<?= $s('shipping_cost') ?>"></div>
      <div class="field"><label>ارسال رایگان برای خرید بالای (تومان)</label><input name="free_shipping_over" data-money value="<?= $s('free_shipping_over') ?>"></div>
      <div class="field full"><label>متن هزینه کارشناسی</label><input name="expert_fee_note" value="<?= $s('expert_fee_note') ?>"></div>
    </div></div></div>
  </div></div>
  <div class="tab" id="s4"><div class="card section-card"><h3>شبکه‌های اجتماعی و نماد اعتماد</h3><div class="body"><div class="form-grid">
    <?php foreach (['instagram' => 'اینستاگرام', 'telegram' => 'تلگرام', 'linkedin' => 'لینکدین', 'aparat' => 'آپارات', 'enamad_link' => 'لینک اینماد', 'enamad_img' => 'آدرس تصویر اینماد'] as $k => $l): ?>
      <div class="field"><label><?= $l ?> (https://...)</label><input name="<?= $k ?>" dir="ltr" value="<?= $s($k) ?>"></div>
    <?php endforeach ?>
  </div></div></div></div>
  <div class="save-bar"><button class="btn btn-accent btn-lg"><?= icon('check') ?> ذخیره تنظیمات</button></div>
</form>
