<?php
$state = $st['state'];
$badge = ['ok' => 'ok', 'expired' => 'bad', 'invalid' => 'bad', 'domain' => 'bad', 'missing' => 'warn', 'unsupported' => 'bad'][$state] ?? 'info';
?>
<div class="grid2" style="align-items:start">
  <div class="card section-card"><h3><?= icon('shield') ?> وضعیت لایسنس</h3><div class="body">
    <?php if (!$enabled): ?>
      <div class="alert info"><?= icon('info') ?><span>بررسی لایسنس در فایل config.php غیرفعال است (حالت توسعه).</span></div>
    <?php endif ?>
    <table class="kv">
      <tr><td>وضعیت</td><td><span class="badge <?= $badge ?>"><?= e(License::stateLabel($state)) ?></span></td></tr>
      <?php if (!empty($st['plan'])): ?><tr><td>نوع</td><td><?= e(License::planLabel($st['plan'])) ?></td></tr><?php endif ?>
      <?php if (!empty($st['name'])): ?><tr><td>نام مشتری</td><td><?= e($st['name']) ?></td></tr><?php endif ?>
      <?php if (!empty($st['id'])): ?><tr><td>کد لایسنس</td><td class="ltr" style="text-align:right"><?= e($st['id']) ?></td></tr><?php endif ?>
      <?php if (!empty($st['domain'])): ?><tr><td>دامنه مجاز</td><td class="ltr" style="text-align:right"><?= $st['domain'] === '*' ? 'همه دامنه‌ها' : e($st['domain']) ?></td></tr><?php endif ?>
      <?php if (!empty($st['exp'])): ?><tr><td>تاریخ انقضا</td><td><?= jdate((int)$st['exp'], 'datetime') ?><?php if ($state === 'ok' && $st['days_left'] !== null): ?> <span class="badge <?= $st['days_left'] <= 15 ? 'warn' : 'ok' ?>"><?= fa($st['days_left']) ?> روز مانده</span><?php endif ?></td></tr><?php endif ?>
      <tr><td>دامنه این سایت</td><td class="ltr" style="text-align:right"><?= e($host) ?></td></tr>
    </table>
    <?php if ($state === 'missing'): ?><div class="alert warn mt-2"><?= icon('info') ?><span>هنوز لایسنسی ثبت نشده است. کد لایسنس را از فروشنده بگیرید و وارد کنید.</span></div>
    <?php elseif ($state === 'expired'): ?><div class="alert error mt-2"><?= icon('info') ?><span>لایسنس منقضی شده و سایت برای بازدیدکنندگان بسته است. برای تمدید با فروشنده تماس بگیرید.</span></div>
    <?php elseif ($state === 'domain'): ?><div class="alert error mt-2"><?= icon('info') ?><span>این لایسنس برای دامنه دیگری صادر شده است.</span></div>
    <?php elseif ($state === 'unsupported'): ?><div class="alert error mt-2"><?= icon('info') ?><span><?= e($st['reason'] ?? '') ?></span></div><?php endif ?>
  </div></div>

  <form method="post" class="card section-card"><h3><?= icon('upload') ?> ثبت / تمدید لایسنس</h3><div class="body"><?= csrf_field() ?>
    <p class="muted small">کد لایسنس دریافتی از فروشنده را اینجا بچسبانید. این کد با امضای دیجیتال محافظت می‌شود و قابل جعل نیست.</p>
    <div class="field"><label>کد لایسنس</label><textarea name="license_key" rows="5" dir="ltr" style="font-family:monospace;font-size:.82em" placeholder="xxxxx.xxxxx" required></textarea></div>
    <button class="btn btn-accent"><?= icon('check') ?> ثبت لایسنس</button>
  </div></form>
</div>
