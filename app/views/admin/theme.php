<?php $cols = Theme::colors(); $cl = ['primary' => 'رنگ اصلی', 'accent' => 'رنگ تاکیدی (دکمه‌ها)', 'bg' => 'پس‌زمینه', 'surface' => 'کارت‌ها', 'text' => 'متن', 'dark_bg' => 'پس‌زمینه حالت شب', 'dark_surface' => 'کارت‌ها در حالت شب']; ?>
<form method="post"><?= csrf_field() ?>
  <div class="card section-card"><h3><?= icon('palette') ?> قالب‌های رنگی آماده</h3><div class="body">
    <div class="presets">
      <?php foreach (Catalog::THEMES as $k => [$name, $c]): ?>
        <label class="preset"><input type="radio" name="theme_preset" value="<?= $k ?>" <?= checked(setting('theme_preset') === $k) ?>><span>
          <span class="sw"><i style="background:<?= $c['primary'] ?>"></i><i style="background:<?= $c['accent'] ?>"></i><i style="background:<?= $c['bg'] ?>"></i><i style="background:<?= $c['dark_bg'] ?>"></i></span><?= $name ?></span></label>
      <?php endforeach ?>
    </div>
    <button class="btn btn-soft mt-2" name="apply_preset" value="1"><?= icon('refresh') ?> اعمال رنگ‌های قالب انتخابی</button>
  </div></div>

  <div class="card section-card"><h3>رنگ‌های سفارشی</h3><div class="body"><div class="colors">
    <?php foreach ($cl as $k => $l): ?><div><label class="lbl"><?= $l ?></label><div class="color-field" data-var="<?= str_replace('_', '-', $k) ?>"><input type="color" value="<?= e($cols[$k]) ?>"><input type="text" name="theme_<?= $k ?>" value="<?= e($cols[$k]) ?>"></div></div><?php endforeach ?>
  </div></div></div>

  <div class="grid2">
    <div class="card section-card"><h3>چیدمان و ظاهر</h3><div class="body"><div class="form-grid">
      <div class="field"><label>حالت پیش‌فرض</label><select name="theme_mode"><option value="light"<?= selected('light', setting('theme_mode')) ?>>روشن</option><option value="dark"<?= selected('dark', setting('theme_mode')) ?>>تیره</option><option value="auto"<?= selected('auto', setting('theme_mode')) ?>>خودکار (مطابق سیستم کاربر)</option></select></div>
      <div class="field"><label>سبک هدر</label><select name="theme_header"><option value="light"<?= selected('light', setting('theme_header')) ?>>روشن شیشه‌ای</option><option value="dark"<?= selected('dark', setting('theme_header')) ?>>تیره</option><option value="primary"<?= selected('primary', setting('theme_header')) ?>>رنگ اصلی</option></select></div>
      <div class="field"><label>بالای صفحه اول</label><select name="theme_hero"><option value="slider"<?= selected('slider', setting('theme_hero')) ?>>اسلایدشو (در صورت فعال بودن)</option><option value="search"<?= selected('search', setting('theme_hero')) ?>>بنر ثابت + جستجو</option></select></div>
      <div class="field"><label>سبک کارت‌ها</label><select name="theme_card"><option value="elevated"<?= selected('elevated', setting('theme_card')) ?>>سایه‌دار</option><option value="outlined"<?= selected('outlined', setting('theme_card')) ?>>حاشیه‌دار</option><option value="flat"<?= selected('flat', setting('theme_card')) ?>>تخت</option></select></div>
      <div class="field"><label>گردی گوشه‌ها: <b data-out="r"><?= fa(setting('theme_radius')) ?></b>px</label><input type="range" name="theme_radius" min="0" max="28" value="<?= e(setting('theme_radius')) ?>"></div>
      <div class="field"><label>عرض محتوا (px)</label><input name="theme_container" inputmode="numeric" value="<?= e(setting('theme_container')) ?>"></div>
      <div class="field"><label>اندازه فونت (٪)</label><input name="theme_font_scale" inputmode="numeric" value="<?= e(setting('theme_font_scale', 100)) ?>"></div>
    </div></div></div>
    <div class="card section-card"><h3>CSS سفارشی (پیشرفته)</h3><div class="body">
      <textarea name="theme_custom_css" rows="10" class="mono" dir="ltr" placeholder=".car-card{border-radius:24px}"><?= e(setting('theme_custom_css')) ?></textarea>
    </div></div>
  </div>
  <div class="save-bar"><button class="btn btn-accent btn-lg"><?= icon('check') ?> ذخیره ظاهر سایت</button></div>
</form>
