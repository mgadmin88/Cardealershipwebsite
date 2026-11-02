<div class="container section acc">
  <?php require __DIR__ . '/_nav.php'; ?>
  <div class="acc-main">
    <div class="acc-hello">
      <div><h1>سلام<?= $u['name'] ? '، ' . e(explode(' ', $u['name'])[0]) : '' ?></h1><p>سفارش‌ها، پرداخت‌ها و درخواست‌هایتان را از اینجا دنبال کنید.</p></div>
      <a href="<?= url('cars') ?>" class="btn btn-accent"><?= icon('car') ?> مشاهده خودروها</a>
    </div>
    <?php if (!$u['name'] || !$u['national_code']): ?>
      <a class="acc-nudge" href="<?= url('account/profile') ?>"><?= icon('user') ?><span>اطلاعات حساب را کامل کنید تا هنگام خرید فرم‌ها خودکار پر شوند.</span><?= icon('chev-left') ?></a>
    <?php endif ?>
    <div class="acc-stats">
      <a href="<?= url('account/orders') ?>"><b><?= fa($counts['orders']) ?></b><span>کل سفارش‌ها</span></a>
      <a href="<?= url('account/orders', ['s' => 'active']) ?>"><b><?= fa($active) ?></b><span>در حال انجام</span></a>
      <a href="<?= url('account/orders', ['s' => 'open']) ?>" class="<?= $unpaid ? 'warn' : '' ?>"><b><?= fa($unpaid) ?></b><span>در انتظار پرداخت</span></a>
      <a href="<?= url('account/payments') ?>"><b><?= fa(num($spent)) ?></b><span>تومان پرداخت‌شده</span></a>
    </div>

    <section class="card card-pad">
      <div class="acc-head"><h2>آخرین سفارش‌ها</h2><?php if ($orders): ?><a href="<?= url('account/orders') ?>" class="more">همه سفارش‌ها <?= icon('chev-left') ?></a><?php endif ?></div>
      <?php if ($orders): ?>
        <div class="acc-list"><?php foreach ($orders as $o) partial('account_order_row', ['o' => $o]); ?></div>
      <?php else: ?>
        <div class="acc-empty"><?= icon('cart') ?><p>هنوز سفارشی ثبت نکرده‌اید.</p><a href="<?= url('cars') ?>" class="btn btn-soft">انتخاب خودرو</a>
          <?php if (module('parts_shop')): ?><a href="<?= url('parts') ?>" class="btn btn-ghost">فروشگاه قطعات</a><?php endif ?></div>
      <?php endif ?>
    </section>

    <?php if ($requests): ?>
    <section class="card card-pad mt-3">
      <div class="acc-head"><h2>آخرین درخواست‌ها</h2><a href="<?= url('account/requests') ?>" class="more">همه درخواست‌ها <?= icon('chev-left') ?></a></div>
      <div class="acc-list"><?php foreach ($requests as $r): ?>
        <div class="acc-row"><span class="acc-ic"><?= icon($r['type'] === 'test_drive' ? 'calendar' : ($r['type'] === 'sell' ? 'car' : ($r['type'] === 'installment' ? 'calc' : 'message'))) ?></span>
          <div class="grow"><b><?= e(Catalog::REQUEST_TYPES[$r['type']] ?? $r['type']) ?></b><small class="muted"><?= jdate($r['created_at'], 'datetime') ?></small></div>
          <?= request_badge($r['status']) ?></div>
      <?php endforeach ?></div>
    </section>
    <?php endif ?>
  </div>
</div>
