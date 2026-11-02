<?php
$initial = mb_substr(trim((string)($u['name'] ?: 'م')), 0, 1);
$links = [
    'home' => ['account', 'grid', 'پیشخوان'],
    'orders' => ['account/orders', 'cart', 'سفارش‌های من'],
    'payments' => ['account/payments', 'card', 'پرداخت‌ها'],
    'requests' => ['account/requests', 'message', 'درخواست‌ها'],
    'profile' => ['account/profile', 'user', 'اطلاعات حساب'],
];
$navActive = $tab === 'order' ? 'orders' : $tab;
?>
<aside class="acc-side">
  <div class="acc-me">
    <span class="acc-avatar"><?= e($initial) ?></span>
    <div><b><?= e($u['name'] ?: 'کاربر گرامی') ?></b>
      <span class="ltr small muted"><?= fa($u['mobile']) ?></span>
      <?php if ((int)$u['verified']): ?><span class="acc-ver ok"><?= icon('check') ?> موبایل تأییدشده</span>
      <?php else: ?><span class="acc-ver warn">موبایل تأیید نشده</span><?php endif ?>
    </div>
  </div>
  <nav class="acc-nav" aria-label="بخش‌های حساب کاربری">
    <?php foreach ($links as $k => [$href, $ic, $label]): ?>
      <a href="<?= url($href) ?>" class="<?= $navActive === $k ? 'on' : '' ?>"<?= $navActive === $k ? ' aria-current="page"' : '' ?>><?= icon($ic) ?><span><?= $label ?></span>
        <?php if ($k === 'orders' && $counts['orders']): ?><em><?= fa($counts['orders']) ?></em><?php endif ?>
        <?php if ($k === 'requests' && $counts['requests']): ?><em><?= fa($counts['requests']) ?></em><?php endif ?></a>
    <?php endforeach ?>
    <form method="post" action="<?= url('account/logout') ?>"><?= csrf_field() ?><button type="submit"><?= icon('logout') ?><span>خروج</span></button></form>
  </nav>
</aside>
