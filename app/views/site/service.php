<?php $o = fn($k, $d = '') => old($k, $pf[$k] ?? $d); ?>
<section class="svc-hero">
  <div class="tubes lit" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></div>
  <div class="container">
    <h1>نوبت سرویس و تعمیرات</h1>
    <p>زمان مراجعه را آنلاین رزرو کنید؛ بعد از تأیید کارشناس پیامک تأیید دریافت می‌کنید.</p>
    <a href="<?= url('service/track') ?>" class="btn btn-ghost-light"><?= icon('search') ?> پیگیری نوبت</a>
  </div>
</section>
<div class="container section svc">
  <?php if (!$days): ?>
    <div class="card card-pad acc-empty"><?= icon('calendar') ?><p>در حال حاضر زمان آزادی برای نوبت‌دهی آنلاین وجود ندارد. لطفاً با شماره <?= e(fa(setting('phone'))) ?> تماس بگیرید.</p></div>
  <?php else: ?>
  <form method="post" action="<?= url('service') ?>" class="svc-form" data-svc-form data-old-slot="<?= e(old('slot')) ?>">
    <?= csrf_field() ?><?= honeypot() ?>
    <div class="svc-steps">
      <section class="card card-pad">
        <h2 class="svc-h"><span>۱</span> چه خدمتی لازم دارید؟</h2>
        <div class="svc-types">
          <?php foreach ($types as $i => $t): ?>
            <label class="svc-type"><input type="radio" name="type" value="<?= $t['id'] ?>" required <?= (string)old('type') === (string)$t['id'] ? 'checked' : '' ?> data-title="<?= e($t['title']) ?>">
              <span><i><?= icon($t['icon'] ?: 'wrench') ?></i><b><?= e($t['title']) ?></b><small><?= e($t['description']) ?></small>
                <?php if ($t['price_from']): ?><em>از <?= fa(num($t['price_from'])) ?> تومان</em><?php endif ?></span></label>
          <?php endforeach ?>
        </div>
      </section>

      <section class="card card-pad">
        <h2 class="svc-h"><span>۲</span> مشخصات خودرو</h2>
        <div class="form-grid">
          <div class="field"><label>برند *</label><select name="brand_id" data-brand-select required><option value="">انتخاب برند</option>
            <?php foreach ($brands as $b): ?><option value="<?= $b['id'] ?>"<?= selected($b['id'], old('brand_id')) ?>><?= e($b['name_fa']) ?></option><?php endforeach ?>
            <option value="other"<?= selected('other', old('brand_id')) ?>>سایر (برند در فهرست نیست)</option></select></div>
          <div class="field" data-model-field><label>مدل</label><select name="model_id" data-model-select data-selected="<?= e(old('model_id')) ?>"><option value="">انتخاب مدل</option></select></div>
          <div class="field" data-car-text hidden><label>برند و مدل خودرو *</label><input name="car_text" maxlength="80" placeholder="مثلاً: سوزوکی ویتارا" value="<?= e(old('car_text')) ?>"></div>
          <div class="field"><label>سال ساخت</label><input name="year" inputmode="numeric" maxlength="4" placeholder="مثلاً ۱۴۰۰" value="<?= e(old('year')) ?>"></div>
          <div class="field"><label>کارکرد (کیلومتر)</label><input name="mileage" inputmode="numeric" data-money value="<?= e(old('mileage')) ?>"></div>
          <div class="field full"><label>شماره پلاک (اختیاری)</label><input name="plate" maxlength="30" placeholder="مثلاً ۱۲ ب ۳۴۵ ایران ۶۷" value="<?= e(old('plate')) ?>"></div>
        </div>
      </section>

      <section class="card card-pad">
        <h2 class="svc-h"><span>۳</span> روز و ساعت مراجعه</h2>
        <div class="svc-days" role="radiogroup" aria-label="روز مراجعه">
          <?php foreach ($days as $d => $info): ?>
            <label class="svc-day<?= $info['full'] ? ' full' : '' ?>"><input type="radio" name="day" value="<?= $d ?>" required <?= $info['full'] ? 'disabled' : (old('day') === $d ? 'checked' : '') ?>>
              <span><small><?= $info['today'] ? 'امروز' : ($info['tomorrow'] ? 'فردا' : $info['w']) ?></small><b><?= e(explode(' ', $info['j'])[0]) ?></b><small><?= $info['full'] ? 'تکمیل' : e(explode(' ', $info['j'])[1] ?? '') ?></small></span></label>
          <?php endforeach ?>
        </div>
        <div class="svc-slots" data-slots aria-live="polite"><p class="muted small">ابتدا روز را انتخاب کنید.</p></div>
        <p class="small muted mb-0">ساعات کاری: <?= fa($cfg['start']) ?> تا <?= fa($cfg['end']) ?><?= in_array(4, $cfg['days'], true) ? '، پنجشنبه تا ' . fa($cfg['thu_end']) : '' ?></p>
      </section>

      <section class="card card-pad">
        <h2 class="svc-h"><span>۴</span> مشخصات شما</h2>
        <div class="form-grid">
          <div class="field"><label>نام و نام خانوادگی *</label><input name="name" required maxlength="120" value="<?= e($o('name')) ?>" autocomplete="name"></div>
          <div class="field"><label>شماره موبایل *</label><input name="mobile" required inputmode="tel" placeholder="09xxxxxxxxx" class="ltr" value="<?= e($o('mobile')) ?>" autocomplete="tel"></div>
          <div class="field full"><label>توضیح مشکل یا درخواست (اختیاری)</label><textarea name="note" rows="3" maxlength="1000" placeholder="مثلاً صدای غیرعادی از جلوبندی هنگام ترمز"><?= e(old('note')) ?></textarea></div>
        </div>
        <?= Captcha::field('service') ?>
      </section>
    </div>

    <aside class="svc-sum card card-pad">
      <h2>خلاصه نوبت</h2>
      <dl>
        <dt>خدمت</dt><dd data-sum="type">—</dd>
        <dt>روز</dt><dd data-sum="day">—</dd>
        <dt>ساعت</dt><dd data-sum="slot">—</dd>
      </dl>
      <button type="submit" class="btn btn-accent btn-lg btn-block"><?= icon('calendar') ?> ثبت نوبت</button>
      <p class="small muted mb-0">ثبت نوبت رایگان است. اگر برنامه‌تان تغییر کرد، تا <?= fa($cfg['cancel_hours']) ?> ساعت قبل از نوبت می‌توانید آن را آنلاین لغو کنید.</p>
    </aside>
  </form>
  <?php endif ?>
</div>
