<div class="container section" style="max-width:860px">
  <?php if ($isNew): ?>
    <div class="svc-ok card card-pad"><div class="big-ic ok"><?= icon('check') ?></div>
      <h1>نوبت شما ثبت شد</h1><p class="muted">کد پیگیری: <b class="ltr"><?= e($a['code']) ?></b> — بعد از بررسی کارشناس، تأیید نوبت را پیامک می‌کنیم.</p></div>
  <?php endif ?>
  <section class="card card-pad mt-3">
    <div class="acc-hello">
      <div><h1>نوبت <?= e($a['code']) ?></h1><p><?= e($a['type_title'] ?? 'خدمات') ?> — <?= e(Service::when($a)) ?></p></div>
      <?= Service::badge($a['status']) ?>
    </div>
    <ol class="o-timeline">
      <?php foreach (Service::timeline($a) as [$label, $state]): ?>
        <li class="<?= $state ?>"><span class="dot"><?= $state === 'done' ? icon('check') : ($state === 'bad' ? icon('x') : '') ?></span><span><?= e($label) ?></span></li>
      <?php endforeach ?>
    </ol>
    <?php if ($a['admin_note'] && !str_contains($a['admin_note'], '[لغو توسط مشتری]')): ?><div class="alert info mt-2"><?= icon('info') ?><span><b>پیام کارشناس:</b> <?= e($a['admin_note']) ?></span></div><?php endif ?>
  </section>

  <div class="acc-grid mt-3">
    <section class="card card-pad"><h2 class="acc-h2">جزئیات</h2>
      <table class="kv">
        <tr><td>زمان</td><td><?= e(Service::when($a)) ?></td></tr>
        <tr><td>خودرو</td><td><?= e($a['car_text']) ?><?= $a['year'] ? ' ' . fa($a['year']) : '' ?></td></tr>
        <?php if ($a['plate']): ?><tr><td>پلاک</td><td><?= e($a['plate']) ?></td></tr><?php endif ?>
        <?php if ($a['mileage'] !== null): ?><tr><td>کارکرد</td><td><?= fa(num($a['mileage'])) ?> کیلومتر</td></tr><?php endif ?>
        <tr><td>نام</td><td><?= e($a['name']) ?></td></tr>
        <?php if ($a['note']): ?><tr><td>توضیح شما</td><td><?= e($a['note']) ?></td></tr><?php endif ?>
      </table>
    </section>
    <section class="card card-pad"><h2 class="acc-h2">محل مراجعه</h2>
      <p class="mb-0"><?= e(setting('address')) ?></p>
      <p class="muted small">تلفن: <span class="ltr"><?= e(fa(setting('phone'))) ?></span></p>
      <p class="small muted mb-0">لطفاً ۱۰ دقیقه زودتر مراجعه کنید و کارت خودرو را همراه داشته باشید.</p>
    </section>
  </div>

  <?php if ($a['status'] === 'done' && ($a['report'] || $a['cost'])): ?>
    <section class="card card-pad mt-3"><h2 class="acc-h2">گزارش کار</h2>
      <?php if ($a['report']): ?><p style="white-space:pre-line"><?= e($a['report']) ?></p><?php endif ?>
      <?php if ($a['cost']): ?><p class="mb-0">هزینه: <b><?= fa(num($a['cost'])) ?> تومان</b></p><?php endif ?></section>
  <?php endif ?>

  <?php if ($a['status'] === 'done' && module('surveys')): ?>
    <section class="card card-pad mt-3 svc-survey-cta">
      <?php if ($survey): ?><p class="mb-0"><?= icon('star') ?> امتیاز شما: <b><?= fa($survey['overall']) ?> از ۵</b> — از نظرتان متشکریم.</p>
      <?php else: ?><div><h2 class="acc-h2">از خدمات ما راضی بودید؟</h2><p class="muted small mb-0">نظر شما در یک دقیقه، به بهتر شدن خدمات کمک می‌کند.</p></div>
        <a class="btn btn-accent" href="<?= url('service/survey/' . $a['code']) ?>">ثبت نظر</a><?php endif ?>
    </section>
  <?php endif ?>

  <div class="flex mt-3" style="justify-content:space-between;flex-wrap:wrap;gap:10px">
    <a href="<?= url('service') ?>" class="btn btn-ghost"><?= icon('calendar') ?> نوبت جدید</a>
    <?php if (Service::canCancel($a)): ?>
      <form method="post" action="<?= url('service/a/' . $a['code'] . '/cancel') ?>" data-confirm="نوبت لغو شود؟"><?= csrf_field() ?><button class="btn btn-danger-ghost"><?= icon('x') ?> لغو نوبت</button></form>
    <?php endif ?>
  </div>
</div>
