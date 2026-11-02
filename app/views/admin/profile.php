<div class="grid2" style="align-items:start">
<form method="post" class="card section-card"><h3><?= icon('lock') ?> تغییر رمز عبور</h3><div class="body"><?= csrf_field() ?>
  <p class="muted"><?= e($u['name']) ?> — <?= e(Auth::ROLES[$u['role']]) ?></p>
  <div class="field"><label>رمز فعلی</label><input type="password" name="current" required dir="ltr" autocomplete="current-password"></div>
  <div class="field"><label>رمز جدید (حداقل ۱۰ کاراکتر، شامل حرف و عدد)</label><input type="password" name="password" required minlength="10" dir="ltr" autocomplete="new-password"></div>
  <div class="field"><label>تکرار رمز جدید</label><input type="password" name="password2" required dir="ltr" autocomplete="new-password"></div>
  <button class="btn btn-accent">تغییر رمز</button>
</div></form>
<form method="post" class="card section-card"><h3><?= icon('phone') ?> موبایل بازیابی رمز</h3><div class="body"><?= csrf_field() ?><input type="hidden" name="action" value="mobile">
  <p class="muted small">اگر رمز را فراموش کنید، کد بازیابی به این شماره پیامک می‌شود (پنل پیامک باید تنظیم شده باشد).</p>
  <div class="field"><label>شماره موبایل</label><input name="mobile" value="<?= e((string)($u['mobile'] ?? '')) ?>" inputmode="tel" placeholder="09xxxxxxxxx" dir="ltr"></div>
  <button class="btn btn-soft">ذخیره موبایل</button>
</div></form>
</div>
