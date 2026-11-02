<div class="container section acc">
  <?php require __DIR__ . '/_nav.php'; ?>
  <div class="acc-main">
    <div class="acc-hello"><div><h1>سفارش‌های من</h1><p>وضعیت هر سفارش، مبلغ پرداخت‌شده و مراحل بعدی.</p></div></div>
    <div class="acc-filter" role="tablist">
      <?php foreach (['' => 'همه', 'open' => 'در انتظار پرداخت', 'active' => 'در حال انجام', 'done' => 'تکمیل‌شده', 'cancelled' => 'لغوشده'] as $k => $l): ?>
        <a href="<?= url('account/orders', $k ? ['s' => $k] : []) ?>" class="<?= $filter === $k ? 'on' : '' ?>"><?= $l ?></a>
      <?php endforeach ?>
    </div>
    <section class="card card-pad">
      <?php if ($orders): ?>
        <div class="acc-list"><?php foreach ($orders as $o) partial('account_order_row', ['o' => $o, 'car' => $o['car_id'] ? ($cars[$o['car_id']] ?? null) : null]); ?></div>
      <?php else: ?>
        <div class="acc-empty"><?= icon('cart') ?><p><?= $filter ? 'سفارشی با این وضعیت ندارید.' : 'هنوز سفارشی ثبت نکرده‌اید.' ?></p></div>
      <?php endif ?>
    </section>
    <?php if (!(int)$u['verified']): ?>
    <form method="post" action="<?= url('account/orders/claim') ?>" class="card card-pad mt-3 acc-claim"><?= csrf_field() ?>
      <div><h2>سفارش قبلی‌تان را نمی‌بینید؟</h2><p class="muted small">اگر قبل از ساخت حساب و با همین شماره موبایل خرید کرده‌اید، کد سفارش را وارد کنید تا به حسابتان اضافه شود.</p></div>
      <div class="flex"><input name="code" required inputmode="numeric" placeholder="کد سفارش" class="ltr" maxlength="10"><button class="btn btn-soft">افزودن سفارش</button></div>
    </form>
    <?php endif ?>
  </div>
</div>
