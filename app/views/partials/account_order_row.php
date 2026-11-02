<?php
/** @var array $o */
$car = $car ?? null;
$title = $o['type'] === 'car' ? ($car ? Cars::title($car) : 'خرید خودرو') : 'خرید قطعات' . (!empty($o['items_count']) ? ' (' . fa($o['items_count']) . ' قلم)' : '');
$due = in_array($o['status'], ['pending', 'failed'], true);
?>
<a class="acc-row order-row" href="<?= url('account/orders/' . $o['code']) ?>">
  <span class="acc-ic"><?= icon($o['type'] === 'car' ? 'car' : 'box') ?></span>
  <div class="grow">
    <b><?= e($title) ?></b>
    <small class="muted">کد <?= fa($o['code']) ?> — <?= jdate($o['created_at']) ?></small>
  </div>
  <div class="order-amt">
    <b><?= fa(num($due ? $o['pay_amount'] : ($o['paid_amount'] ?: $o['total']))) ?> <small>تومان</small></b>
    <?= Orders::statusBadge($o['status']) ?>
  </div>
  <?= icon('chev-left', 'chev') ?>
</a>
