<div class="login-page">
  <div class="login-box forgot-box">
    <div class="logo"><span class="logo-mark"><?= icon('lock') ?></span><span>بازیابی رمز عبور<small><?= e(setting('site_name')) ?></small></span></div>
    <?php if ($error): ?><div class="alert error"><?= icon('info') ?><span><?= e($error) ?></span></div><?php endif ?>

    <?php if ($emergency && !($st['ok'] ?? false)): ?>
      <div class="alert ok"><?= icon('shield') ?><span>فایل بازیابی اضطراری پیدا شد. رمز جدید را تعیین کنید. پس از ذخیره، فایل به‌طور خودکار حذف می‌شود.</span></div>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="step" value="new">
        <div class="field"><label>نام کاربری</label><select name="username" dir="ltr"><?php foreach ($users as $u): ?><option value="<?= e($u['username']) ?>"><?= e($u['username']) ?> — <?= e($u['name']) ?></option><?php endforeach ?></select></div>
        <div class="field"><label>رمز جدید (حداقل ۱۰ کاراکتر، شامل حرف و عدد)</label><input type="password" name="password" required minlength="10" dir="ltr" autocomplete="new-password"></div>
        <div class="field"><label>تکرار رمز جدید</label><input type="password" name="password2" required dir="ltr" autocomplete="new-password"></div>
        <?= Captcha::field('forgot') ?>
        <button type="submit" class="btn btn-accent btn-lg btn-block">ذخیره رمز جدید</button>
      </form>

    <?php elseif ($st['ok'] ?? false): ?>
      <div class="alert ok"><?= icon('check') ?><span>کد تأیید شد. رمز جدید را وارد کنید.</span></div>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="step" value="new">
        <div class="field"><label>رمز جدید (حداقل ۱۰ کاراکتر، شامل حرف و عدد)</label><input type="password" name="password" required minlength="10" dir="ltr" autocomplete="new-password" autofocus></div>
        <div class="field"><label>تکرار رمز جدید</label><input type="password" name="password2" required dir="ltr" autocomplete="new-password"></div>
        <?= Captcha::field('forgot') ?>
        <button type="submit" class="btn btn-accent btn-lg btn-block">ذخیره رمز جدید</button>
      </form>

    <?php elseif ($st): ?>
      <p class="muted small">اگر نام کاربری درست باشد و برای آن شماره موبایل ثبت شده باشد، یک کد ۶ رقمی پیامک شد. کد تا ۱۵ دقیقه معتبر است.</p>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="step" value="code">
        <div class="field"><label>کد پیامک‌شده</label><input name="code" required inputmode="numeric" maxlength="8" dir="ltr" autocomplete="one-time-code" class="otp" autofocus></div>
        <?= Captcha::field('forgot') ?>
        <button type="submit" class="btn btn-accent btn-lg btn-block">تأیید کد</button>
      </form>
      <form method="post" class="mt-2"><?= csrf_field() ?><input type="hidden" name="step" value="restart"><button type="submit" class="btn btn-ghost btn-block btn-sm" formaction="<?= url('admin/forgot') ?>?restart=1">کد نرسید؟ دوباره تلاش کنید</button></form>

    <?php else: ?>
      <p class="muted small">نام کاربری خود را وارد کنید تا کد بازیابی به موبایل ثبت‌شده در حساب شما پیامک شود.</p>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="step" value="request">
        <div class="field"><label>نام کاربری</label><div class="input-icon"><?= icon('user') ?><input name="username" required dir="ltr" autocomplete="username" autofocus></div></div>
        <?= Captcha::field('forgot') ?>
        <button type="submit" class="btn btn-accent btn-lg btn-block">ارسال کد بازیابی</button>
      </form>
    <?php endif ?>

    <?php if (!$emergency): ?>
    <details class="emergency"<?= !$smsReady ? ' open' : '' ?>>
      <summary><?= icon('wrench') ?> پیامک نمی‌رسد یا موبایل ثبت نشده؟ بازیابی از طریق هاست</summary>
      <?php if (!$smsReady): ?><p class="small" style="color:var(--bad)">پنل پیامک هنوز تنظیم نشده، پس کد پیامکی ارسال نمی‌شود. از این روش استفاده کنید.</p><?php endif ?>
      <ol class="small">
        <li>وارد سی‌پنل (یا دایرکت‌ادمین) هاست شوید و File Manager را باز کنید.</li>
        <li>به پوشه <b>storage</b> سایت بروید.</li>
        <li>یک فایل خالی جدید با نام <code dir="ltr">reset-password.txt</code> بسازید.</li>
        <li>همین صفحه را دوباره باز کنید؛ فرم تعیین رمز جدید نمایش داده می‌شود.</li>
      </ol>
      <p class="small muted mb-0">این فایل فقط تا ۲ ساعت معتبر است و پس از تغییر رمز خودکار حذف می‌شود. فقط کسی که به هاست دسترسی دارد می‌تواند آن را بسازد.</p>
    </details>
    <?php endif ?>
    <div class="login-links"><a href="<?= url('admin/login') ?>">بازگشت به صفحه ورود</a></div>
  </div>
</div>
