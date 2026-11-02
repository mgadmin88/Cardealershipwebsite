<?php $f = fn($v) => $v === null ? '—' : fa(number_format((float)$v, 1)); ?>
<div class="kpis">
  <div class="kpi"><span class="ki"><?= icon('star') ?></span><div><b><?= $f($avg['overall']) ?></b><small>میانگین رضایت کلی (از ۵)</small></div></div>
  <div class="kpi" style="--c:#2563eb"><span class="ki"><?= icon('wrench') ?></span><div><b><?= $f($avg['quality']) ?></b><small>کیفیت کار</small></div></div>
  <div class="kpi" style="--c:#16a34a"><span class="ki"><?= icon('clock') ?></span><div><b><?= $f($avg['timing']) ?></b><small>رعایت زمان</small></div></div>
  <div class="kpi" style="--c:#9333ea"><span class="ki"><?= icon('users') ?></span><div><b><?= $avg['recommend'] === null ? '—' : fa(round($avg['recommend'] * 100)) . '٪' ?></b><small>پیشنهاد به دیگران</small></div></div>
</div>
<p class="muted small"><?= fa((int)$avg['n']) ?> پاسخ از <?= fa($sent) ?> نظرسنجی ارسال‌شده</p>
<div class="card tbl-wrap"><table class="table">
  <thead><tr><th>تاریخ</th><th>نوبت</th><th>مشتری</th><th>خدمت</th><th>کلی</th><th>کیفیت</th><th>زمان</th><th>کارکنان</th><th>نظر</th></tr></thead>
  <?php foreach ($rows as $r): ?><tr>
    <td class="small"><?= jdate($r['created_at']) ?></td><td><a href="<?= url('admin/appointments/' . $r['aid']) ?>"><?= e($r['code']) ?></a></td>
    <td><?= e($r['name']) ?></td><td class="small"><?= e($r['type_title'] ?? '') ?></td>
    <td><b class="score s<?= (int)$r['overall'] ?>"><?= fa($r['overall']) ?></b></td><td><?= $r['quality'] ? fa($r['quality']) : '—' ?></td><td><?= $r['timing'] ? fa($r['timing']) : '—' ?></td><td><?= $r['staff'] ? fa($r['staff']) : '—' ?></td>
    <td class="small"><?= e(excerpt((string)$r['comment'], 90)) ?></td>
  </tr><?php endforeach ?></table>
  <?php if (!$rows): ?><div class="empty"><?= icon('star') ?><p>هنوز نظری ثبت نشده. پس از «ثبت پایان کار» در هر نوبت، لینک نظرسنجی برای مشتری پیامک می‌شود.</p></div><?php endif ?></div>
