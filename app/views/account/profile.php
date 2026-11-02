<?php $v = fn($k) => old($k, (string)($u[$k] ?? '')); ?>
<div class="container section acc">
  <?php require __DIR__ . '/_nav.php'; ?>
  <div class="acc-main">
    <div class="acc-hello"><div><h1>اطلاعات حساب</h1><p>این اطلاعات هنگام خرید به‌صورت خودکار در فرم‌ها قرار می‌گیرد.</p></div></div>
    <form method="post" class="card card-pad"><?= csrf_field() ?><input type="hidden" name="action" value="profile">
      <div class="form-grid">
        <div class="field"><label>نام و نام خانوادگی *</label><input name="name" required maxlength="120" value="<?= e($v('name')) ?>" autocomplete="name"></div>
        <div class="field"><label>شماره موبایل</label><input value="<?= e(fa($u['mobile'])) ?>" disabled class="ltr"><small class="muted">شماره موبایل همان نام کاربری شماست و قابل تغییر نیست.</small></div>
        <div class="field"><label>کد ملی</label><input name="national_code" inputmode="numeric" maxlength="10" class="ltr" value="<?= e($v('national_code')) ?>"></div>
        <div class="field"><label>ایمیل</label><input name="email" type="email" class="ltr" value="<?= e($v('email')) ?>" autocomplete="email"></div>
        <div class="field"><label>استان</label><input name="province" value="<?= e($v('province')) ?>"></div>
        <div class="field"><label>شهر</label><input name="city" value="<?= e($v('city')) ?>"></div>
        <div class="field full"><label>آدرس</label><textarea name="address" rows="2" maxlength="500"><?= e($v('address')) ?></textarea></div>
        <div class="field"><label>کد پستی</label><input name="postal_code" inputmode="numeric" maxlength="10" class="ltr" value="<?= e($v('postal_code')) ?>"></div>
      </div>
      <button class="btn btn-accent mt-2">ذخیره اطلاعات</button>
    </form>

    <form method="post" class="card card-pad mt-3"><?= csrf_field() ?><input type="hidden" name="action" value="password">
      <h2 class="acc-h2"><?= $u['password'] ? 'تغییر رمز عبور' : 'تعیین رمز عبور' ?></h2>
      <?php if (!$u['password']): ?><p class="muted small">با تعیین رمز، علاوه بر کد پیامکی می‌توانید با شماره موبایل و رمز هم وارد شوید.</p><?php endif ?>
      <div class="form-grid">
        <?php if ($u['password']): ?><div class="field"><label>رمز فعلی</label><input type="password" name="current" required dir="ltr" autocomplete="current-password"></div><?php endif ?>
        <div class="field"><label>رمز جدید (حداقل ۸ کاراکتر، حرف و عدد)</label><input type="password" name="password" required minlength="8" dir="ltr" autocomplete="new-password"></div>
        <div class="field"><label>تکرار رمز جدید</label><input type="password" name="password2" required dir="ltr" autocomplete="new-password"></div>
      </div>
      <button class="btn btn-soft mt-2"><?= $u['password'] ? 'تغییر رمز' : 'تعیین رمز' ?></button>
    </form>
  </div>
</div>
