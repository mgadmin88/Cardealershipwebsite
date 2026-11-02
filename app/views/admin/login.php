<div class="login-page">
  <form class="login-box" method="post" autocomplete="on">
    <div class="logo"><span class="logo-mark"><?= icon('car') ?></span><span><?= e(setting('site_name')) ?><small>ورود به پنل مدیریت</small></span></div>
    <?php foreach (flashes() as [$t, $m]): ?><div class="alert <?= $t === 'success' ? 'ok' : 'error' ?>"><?= icon($t === 'success' ? 'check' : 'info') ?><span><?= e($m) ?></span></div><?php endforeach ?>
    <?php if ($error): ?><div class="alert error"><?= icon('info') ?><span><?= e($error) ?></span></div><?php endif ?>
    <?= csrf_field() ?>
    <div class="field"><label>نام کاربری</label><div class="input-icon"><?= icon('user') ?><input name="username" required autofocus autocomplete="username" dir="ltr" value="<?= e((string)($_POST['username'] ?? '')) ?>"></div></div>
    <div class="field"><label>رمز عبور</label><div class="input-icon"><?= icon('lock') ?><input type="password" name="password" required autocomplete="current-password" dir="ltr"></div></div>
    <?= Captcha::field('login') ?>
    <button type="submit" class="btn btn-accent btn-lg btn-block">ورود</button>
    <div class="login-links"><a href="<?= url('admin/forgot') ?>">رمز عبور را فراموش کرده‌اید؟</a><a href="<?= url('/') ?>">بازگشت به سایت</a></div>
  </form>
</div>
