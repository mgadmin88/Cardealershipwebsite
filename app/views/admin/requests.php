<?php $labels = ['brand' => 'برند', 'model' => 'مدل', 'year' => 'سال', 'mileage' => 'کارکرد', 'city' => 'شهر', 'price' => 'قیمت پیشنهادی', 'date' => 'تاریخ', 'time' => 'زمان', 'down' => 'پیش‌پرداخت', 'months' => 'اقساط (ماه)', 'subject' => 'موضوع', 'email' => 'ایمیل']; ?>
<form class="filters-bar" method="get">
  <select name="type" data-autosubmit><option value="">همه انواع</option><?php foreach (Catalog::REQUEST_TYPES as $k => $l): ?><option value="<?= $k ?>"<?= selected($k, input('type')) ?>><?= $l ?></option><?php endforeach ?></select>
  <select name="status" data-autosubmit><option value="">همه</option><option value="new"<?= selected('new', input('status')) ?>>جدید</option><option value="seen"<?= selected('seen', input('status')) ?>>در حال پیگیری</option><option value="done"<?= selected('done', input('status')) ?>>انجام شده</option></select>
</form>
<div class="card tbl-wrap"><table class="table">
  <thead><tr><th>نوع</th><th>نام</th><th>موبایل</th><th>جزئیات</th><th>پیام</th><th>تاریخ</th><th>وضعیت</th></tr></thead>
  <?php foreach ($rows as $r): $d = array_filter(json_decode((string)$r['data'], true) ?: []); ?><tr>
    <td><?= e(Catalog::REQUEST_TYPES[$r['type']] ?? $r['type']) ?><?php if ($r['car_code']): ?><br><a class="small" href="<?= url('admin/cars/' . $r['car_id']) ?>">خودرو <?= fa($r['car_code']) ?></a><?php endif ?></td>
    <td><?= e($r['name']) ?></td><td><a class="ltr" href="tel:<?= e($r['mobile']) ?>"><?= fa($r['mobile']) ?></a></td>
    <td class="small"><?php foreach ($d as $k => $v): ?><?= e($labels[$k] ?? $k) ?>: <?= e(is_numeric($v) && $v > 9999 ? num($v) : $v) ?><br><?php endforeach ?></td>
    <td class="small" style="max-width:280px"><?= nl2br(e($r['message'])) ?></td>
    <td class="small"><?= jdate($r['created_at'], 'datetime') ?></td>
    <td><form method="post" action="<?= url('admin/requests/' . $r['id']) ?>"><?= csrf_field() ?><select name="status" data-autosubmit style="min-height:34px;padding:.2em .5em;padding-left:28px;width:auto">
      <option value="new"<?= selected('new', $r['status']) ?>>جدید</option><option value="seen"<?= selected('seen', $r['status']) ?>>در حال پیگیری</option><option value="done"<?= selected('done', $r['status']) ?>>انجام شد</option></select></form></td>
  </tr><?php endforeach ?></table>
  <?php if (!$rows): ?><div class="empty"><?= icon('message') ?><p>درخواستی وجود ندارد.</p></div><?php endif ?></div>
