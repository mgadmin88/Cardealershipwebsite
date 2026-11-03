<?php $site = setting('site_name', 'وب‌سایت'); ?><!doctype html>
<html lang="fa" dir="rtl"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>سایت موقتاً در دسترس نیست — <?= e($site) ?></title>
<style>
:root{color-scheme:dark}
*{box-sizing:border-box}
body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;
  font-family:Tahoma,system-ui,sans-serif;line-height:1.9;color:#e8ecf4;
  background:radial-gradient(80% 60% at 50% 0%,#2d1b3d,#141829 60%,#0d1020)}
.box{max-width:520px;width:100%;text-align:center;background:#19202f;border:1px solid #2a3346;
  border-radius:22px;padding:44px 32px;box-shadow:0 40px 80px -30px #000}
.ic{width:72px;height:72px;margin:0 auto 18px;border-radius:50%;display:grid;place-items:center;
  background:color-mix(in oklab,#ff9a2e 18%,transparent);color:#ff9a2e}
.ic svg{width:38px;height:38px;fill:none;stroke:currentColor;stroke-width:1.8}
h1{font-size:1.4rem;margin:0 0 10px}
p{color:#9aa6bd;margin:0 0 6px}
.site{color:#ff9a2e;font-weight:800}
a.admin{display:inline-block;margin-top:22px;color:#7d8aa3;font-size:.82rem;text-decoration:none}
a.admin:hover{color:#e8ecf4}
</style></head><body>
<div class="box">
  <div class="ic"><svg viewBox="0 0 24 24"><path d="M12 3l7 4v5c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V7z" stroke-linejoin="round"/><path d="M9.5 12l2 2 3.5-4" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
  <h1>دسترسی به سایت موقتاً مقدور نیست</h1>
  <p>لایسنس <span class="site"><?= e($site) ?></span> فعال نیست یا منقضی شده است.</p>
  <p>لطفاً برای فعال‌سازی یا تمدید، با پشتیبانی تماس بگیرید.</p>
  <a class="admin" href="<?= url('admin/license') ?>">ورود مدیر و ثبت لایسنس</a>
</div>
</body></html>
