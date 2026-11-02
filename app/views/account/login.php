<div class="container section">
  <div class="acc-login">
    <div class="acc-login-art" aria-hidden="true">
      <div class="tubes lit"><i></i><i></i><i></i><i></i></div>
      <img src="<?= asset('img/car-placeholder.svg') ?>" alt="">
      <ul>
        <li><?= icon('check') ?> وضعیت سفارش‌ها و رزرو خودرو</li>
        <li><?= icon('check') ?> پرداخت‌ها و کدهای رهگیری</li>
        <li><?= icon('check') ?> پیگیری بازدید، اقساط و فروش خودرو</li>
      </ul>
    </div>
    <div class="acc-login-box card card-pad">
      <h1>ورود به حساب کاربری</h1>
      <?php if ($error): ?><div class="alert error"><?= icon('info') ?><span><?= e($error) ?></span></div><?php endif ?>

      <?php if ($sms || $mode !== 'register'): ?>
      <div class="acc-tabs" role="tablist">
        <?php if ($sms): ?><a href="<?= url('account/login?mode=otp') ?>" class="<?= $mode === 'otp' ? 'on' : '' ?>">ورود با کد پیامکی</a><?php endif ?>
        <a href="<?= url('account/login?mode=password') ?>" class="<?= $mode === 'password' ? 'on' : '' ?>">ورود با رمز عبور</a>
        <?php if (!$sms): ?><a href="<?= url('account/login?mode=register') ?>" class="<?= $mode === 'register' ? 'on' : '' ?>">ثبت‌نام</a><?php endif ?>
      </div>
      <?php endif ?>

      <?php if ($mode === 'otp' && $pending): ?>
        <p class="muted small">کد ۵ رقمی به شماره <b class="ltr"><?= fa($pending) ?></b> پیامک شد. <a href="<?= url('account/login?mode=otp&change=1') ?>">تغییر شماره</a></p>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="step" value="otp_verify">
          <div class="field"><label>کد تأیید</label><input name="code" required inputmode="numeric" maxlength="6" dir="ltr" autocomplete="one-time-code" class="otp" autofocus></div>
          <button type="submit" class="btn btn-accent btn-lg btn-block">ورود</button>
        </form>
      <?php elseif ($mode === 'otp'): ?>
        <p class="muted small">شماره موبایل خود را وارد کنید. اگر حساب ندارید، با همین کد ساخته می‌شود.</p>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="step" value="otp_send">
          <div class="field"><label>شماره موبایل</label><input name="mobile" required inputmode="tel" placeholder="09xxxxxxxxx" class="ltr" value="<?= e(old('mobile')) ?>" autocomplete="tel" autofocus></div>
          <?= Captcha::field('account') ?>
          <button type="submit" class="btn btn-accent btn-lg btn-block">دریافت کد ورود</button>
        </form>
      <?php elseif ($mode === 'register'): ?>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="step" value="register">
          <div class="field"><label>نام و نام خانوادگی</label><input name="name" required maxlength="120" value="<?= e(old('name')) ?>" autocomplete="name"></div>
          <div class="field"><label>شماره موبایل</label><input name="mobile" required inputmode="tel" placeholder="09xxxxxxxxx" class="ltr" value="<?= e(old('mobile')) ?>" autocomplete="tel"></div>
          <div class="field"><label>رمز عبور (حداقل ۸ کاراکتر، حرف و عدد)</label><input type="password" name="password" required minlength="8" dir="ltr" autocomplete="new-password"></div>
          <div class="field"><label>تکرار رمز عبور</label><input type="password" name="password2" required dir="ltr" autocomplete="new-password"></div>
          <?= Captcha::field('account') ?>
          <button type="submit" class="btn btn-accent btn-lg btn-block">ساخت حساب</button>
        </form>
      <?php else: ?>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="step" value="password">
          <div class="field"><label>شماره موبایل</label><input name="mobile" required inputmode="tel" placeholder="09xxxxxxxxx" class="ltr" value="<?= e(old('mobile')) ?>" autocomplete="username"></div>
          <div class="field"><label>رمز عبور</label><input type="password" name="password" required dir="ltr" autocomplete="current-password"></div>
          <?= Captcha::field('account') ?>
          <button type="submit" class="btn btn-accent btn-lg btn-block">ورود</button>
        </form>
        <?php if ($sms): ?><p class="small muted center mt-2">رمز ندارید یا فراموش کرده‌اید؟ <a href="<?= url('account/login?mode=otp') ?>">با کد پیامکی وارد شوید</a></p>
        <?php else: ?><p class="small muted center mt-2">حساب ندارید؟ <a href="<?= url('account/login?mode=register') ?>">ثبت‌نام کنید</a></p><?php endif ?>
      <?php endif ?>
    </div>
  </div>
</div>
