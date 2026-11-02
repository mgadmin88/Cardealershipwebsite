<?php $tsDay = strtotime($day); $prev = date('Y-m-d', strtotime('-1 day', $tsDay)); $next = date('Y-m-d', strtotime('+1 day', $tsDay)); ?>
<div class="appt-top">
  <div class="seg-tabs">
    <a href="<?= url('admin/appointments', ['view' => 'day', 'day' => $day]) ?>" class="<?= $view === 'day' ? 'on' : '' ?>">برنامه روز</a>
    <a href="<?= url('admin/appointments', ['view' => 'upcoming']) ?>" class="<?= $view === 'upcoming' ? 'on' : '' ?>">نوبت‌های آینده</a>
    <a href="<?= url('admin/appointments', ['view' => 'all']) ?>" class="<?= $view === 'all' ? 'on' : '' ?>">همه</a>
    <a href="<?= url('admin/appointments', ['view' => 'upcoming', 'status' => 'pending']) ?>" class="<?= $st === 'pending' ? 'on' : '' ?>">منتظر تأیید <?php if ($pending): ?><span class="badge warn"><?= fa($pending) ?></span><?php endif ?></a>
  </div>
  <form method="get" class="filters-bar" style="margin:0"><input type="hidden" name="view" value="<?= e($view) ?>"><input type="search" name="q" value="<?= e(input('q')) ?>" placeholder="کد، نام، موبایل یا پلاک"><button class="btn btn-soft">جستجو</button></form>
</div>

<?php if ($view === 'day'): ?>
<div class="day-nav card">
  <a class="icon-btn" href="<?= url('admin/appointments', ['day' => $prev]) ?>" aria-label="روز قبل"><?= icon('chev-right') ?></a>
  <div><b><?= Service::WEEKDAYS[(int)date('w', $tsDay)] ?> <?= jdate($tsDay, 'long') ?></b>
    <small class="muted"><?= $day === date('Y-m-d') ? 'امروز — ' : '' ?><?= fa(count($rows)) ?> نوبت</small></div>
  <a class="icon-btn" href="<?= url('admin/appointments', ['day' => $next]) ?>" aria-label="روز بعد"><?= icon('chev-left') ?></a>
  <?php if ($day !== date('Y-m-d')): ?><a class="btn btn-ghost btn-sm" href="<?= url('admin/appointments') ?>">امروز</a><?php endif ?>
</div>
<?php if ($slots): ?><div class="slot-strip"><?php foreach ($slots as $hm => $left): ?><span class="<?= $left ? '' : 'full' ?>"><?= fa($hm) ?><small><?= $left ? fa($left) . ' جای خالی' : 'تکمیل' ?></small></span><?php endforeach ?></div><?php endif ?>
<?php endif ?>

<div class="card tbl-wrap mt-2"><table class="table">
  <thead><tr><th>زمان</th><th>کد</th><th>خدمت</th><th>مشتری</th><th>خودرو</th><th>وضعیت</th><th></th></tr></thead>
  <?php foreach ($rows as $a): ?><tr class="<?= $a['status'] === 'pending' ? 'row-warn' : '' ?>">
    <td><b><?= fa($a['slot']) ?></b><?php if ($view !== 'day'): ?><br><small class="muted"><?= jdate($a['day']) ?></small><?php endif ?></td>
    <td><a href="<?= url('admin/appointments/' . $a['id']) ?>"><b><?= e($a['code']) ?></b></a></td>
    <td><?= e($a['type_title'] ?? '—') ?></td>
    <td><?= e($a['name']) ?><br><small class="ltr muted"><?= fa($a['mobile']) ?></small></td>
    <td><?= e($a['car_text']) ?><?= $a['plate'] ? '<br><small class="muted">' . e($a['plate']) . '</small>' : '' ?></td>
    <td><?= Service::badge($a['status']) ?></td>
    <td><?php if ($a['status'] === 'pending'): ?><form method="post" action="<?= url('admin/appointments/' . $a['id']) ?>" style="display:inline"><?= csrf_field() ?><input type="hidden" name="action" value="confirm"><input type="hidden" name="notify" value="1"><button class="btn btn-soft btn-sm"><?= icon('check') ?> تأیید</button></form><?php endif ?>
      <a class="btn btn-ghost btn-sm" href="<?= url('admin/appointments/' . $a['id']) ?>">جزئیات</a></td>
  </tr><?php endforeach ?></table>
  <?php if (!$rows): ?><div class="empty"><?= icon('calendar') ?><p><?= $view === 'day' ? 'برای این روز نوبتی ثبت نشده است.' : 'نوبتی یافت نشد.' ?></p></div><?php endif ?></div>
