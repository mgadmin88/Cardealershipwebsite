<?php $due = in_array($o['status'], ['pending', 'failed'], true); $remaining = max(0, (int)$o['total'] - (int)$o['paid_amount']); ?>
<div class="container section acc">
  <?php require __DIR__ . '/_nav.php'; ?>
  <div class="acc-main">
    <a href="<?= url('account/orders') ?>" class="acc-back"><?= icon('chev-right') ?> بازگشت به سفارش‌ها</a>
    <div class="acc-hello">
      <div><h1>سفارش <?= fa($o['code']) ?></h1><p><?= jdate($o['created_at'], 'datetime') ?> — <?= $o['type'] === 'car' ? 'خرید خودرو' : 'خرید قطعات' ?></p></div>
      <?= Orders::statusBadge($o['status']) ?>
    </div>

    <section class="card card-pad">
      <ol class="o-timeline">
        <?php foreach (Customer::timeline($o) as [$label, $state]): ?>
          <li class="<?= $state ?>"><span class="dot"><?= $state === 'done' ? icon('check') : ($state === 'bad' ? icon('x') : '') ?></span><span><?= e($label) ?></span></li>
        <?php endforeach ?>
      </ol>
      <?php if ($o['admin_note']): ?><div class="alert info mt-2"><?= icon('info') ?><span><b>پیام کارشناس:</b> <?= e($o['admin_note']) ?></span></div><?php endif ?>
    </section>

    <?php if ($due && $gateways): ?>
    <form method="get" action="<?= url('pay/' . $o['code']) ?>" class="card card-pad mt-3 acc-pay">
      <div><h2><?= $o['status'] === 'failed' ? 'پرداخت قبلی ناموفق بود' : 'این سفارش در انتظار پرداخت است' ?></h2>
        <p class="muted small"><?= $o['type'] === 'car' ? 'تا پرداخت انجام نشود خودرو برای شما رزرو نمی‌شود.' : 'سفارش پس از پرداخت آماده ارسال می‌شود.' ?></p></div>
      <div class="gateways"><?php foreach ($gateways as $i => $g): ?><label class="gw"><input type="radio" name="gw" value="<?= $g['id'] ?>" <?= $i ? '' : 'checked' ?>><span><i><?= e(mb_substr($g['label'], 0, 1)) ?></i><?= e($g['label']) ?></span></label><?php endforeach ?></div>
      <button class="btn btn-accent btn-lg"><?= icon('lock') ?> پرداخت <?= fa(num($o['pay_amount'])) ?> تومان</button>
    </form>
    <?php endif ?>

    <div class="acc-grid mt-3">
      <section class="card card-pad">
        <h2 class="acc-h2"><?= $o['type'] === 'car' ? 'خودرو' : 'اقلام سفارش' ?></h2>
        <?php if ($car): ?>
          <a class="acc-car" href="<?= url('car/' . $car['id']) ?>"><img src="<?= e(upload_url($carImg)) ?>" alt="" loading="lazy">
            <div><b><?= e(Cars::title($car)) ?></b><small class="muted">کد خودرو <?= fa($car['code']) ?> — <?= e($car['city']) ?></small></div></a>
        <?php endif ?>
        <?php foreach ($items as $it): ?>
          <div class="acc-item"><img src="<?= e(upload_url($it['thumb'] ?? null, 'img/part-placeholder.svg')) ?>" alt="" loading="lazy"><span class="grow"><?= e($it['title']) ?> <small class="muted">× <?= fa($it['qty']) ?></small></span><b><?= fa(num($it['price'] * $it['qty'])) ?></b></div>
        <?php endforeach ?>
        <table class="kv mt-2">
          <?php if ($o['shipping']): ?><tr><td>هزینه ارسال</td><td><?= fa(num($o['shipping'])) ?> تومان</td></tr><?php endif ?>
          <tr><td>مبلغ کل</td><td><b><?= fa(num($o['total'])) ?> تومان</b></td></tr>
          <?php if ($o['pay_type'] === 'deposit'): ?><tr><td>بیعانه آنلاین</td><td><?= fa(num($o['pay_amount'])) ?> تومان</td></tr><?php endif ?>
          <tr><td>پرداخت‌شده</td><td class="ok-t"><?= fa(num($o['paid_amount'])) ?> تومان</td></tr>
          <?php if ($o['paid_amount'] && $remaining): ?><tr><td>باقی‌مانده (هنگام قرارداد)</td><td><?= fa(num($remaining)) ?> تومان</td></tr><?php endif ?>
        </table>
      </section>
      <section class="card card-pad">
        <h2 class="acc-h2">مشخصات خریدار</h2>
        <table class="kv">
          <tr><td>نام</td><td><?= e($o['customer_name']) ?></td></tr>
          <tr><td>موبایل</td><td class="ltr" style="text-align:right"><?= fa($o['mobile']) ?></td></tr>
          <?php if ($o['national_code']): ?><tr><td>کد ملی</td><td><?= fa($o['national_code']) ?></td></tr><?php endif ?>
          <?php if ($o['address']): ?><tr><td>آدرس ارسال</td><td><?= e(trim($o['province'] . ' ' . $o['city'] . '، ' . $o['address'])) ?><?= $o['postal_code'] ? ' — کد پستی ' . fa($o['postal_code']) : '' ?></td></tr><?php endif ?>
          <?php if ($o['note']): ?><tr><td>توضیحات شما</td><td><?= e($o['note']) ?></td></tr><?php endif ?>
        </table>
      </section>
    </div>

    <section class="card card-pad mt-3">
      <h2 class="acc-h2">تراکنش‌های این سفارش</h2>
      <?php partial('account_tx_table', ['tx' => $tx, 'withOrder' => false]); ?>
    </section>
  </div>
</div>
