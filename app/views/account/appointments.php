<div class="container section acc">
  <?php require __DIR__ . '/_nav.php'; ?>
  <div class="acc-main">
    <div class="acc-hello"><div><h1>نوبت‌های سرویس</h1><p>نوبت‌های سرویس دوره‌ای، تعمیر و کارشناسی که گرفته‌اید.</p></div>
      <a href="<?= url('service') ?>" class="btn btn-accent"><?= icon('calendar') ?> نوبت جدید</a></div>
    <section class="card card-pad">
      <?php if ($rows): ?><div class="acc-list">
        <?php foreach ($rows as $a): ?>
          <a class="acc-row order-row" href="<?= url('service/a/' . $a['code']) ?>">
            <span class="acc-ic"><?= icon($a['type_icon'] ?: 'wrench') ?></span>
            <div class="grow"><b><?= e($a['type_title'] ?? 'خدمات') ?> — <?= e($a['car_text']) ?></b><small class="muted"><?= e(Service::when($a)) ?> — کد <?= e($a['code']) ?></small></div>
            <div class="order-amt"><?= Service::badge($a['status']) ?>
              <?php if ($a['status'] === 'done' && module('surveys') && !$a['surveyed']): ?><small class="survey-due"><?= icon('star') ?> منتظر نظر شما</small><?php endif ?></div>
            <?= icon('chev-left', 'chev') ?>
          </a>
        <?php endforeach ?></div>
      <?php else: ?>
        <div class="acc-empty"><?= icon('wrench') ?><p>هنوز نوبتی نگرفته‌اید.</p><a href="<?= url('service') ?>" class="btn btn-soft">گرفتن نوبت سرویس</a></div>
      <?php endif ?>
    </section>
  </div>
</div>
