<form class="filters-bar" method="get">
  <input type="search" name="q" value="<?= e(input('q')) ?>" placeholder="جستجوی برند، مدل یا کد">
  <select name="condition"><option value="">همه</option><?php foreach (Catalog::CONDITION as $k => $l): ?><option value="<?= $k ?>"<?= selected($k, input('condition')) ?>><?= $l ?></option><?php endforeach ?></select>
  <select name="status"><option value="">همه وضعیت‌ها</option><?php foreach (Catalog::CAR_STATUS as $k => $l): ?><option value="<?= $k ?>"<?= selected($k, input('status')) ?>><?= $l ?></option><?php endforeach ?></select>
  <button class="btn btn-soft"><?= icon('filter') ?> فیلتر</button>
  <span class="muted" style="align-self:center"><?= fa($pg['total']) ?> خودرو</span>
</form>
<div class="card tbl-wrap"><table class="table">
  <thead><tr><th></th><th>کد</th><th>خودرو</th><th>وضعیت</th><th>کارکرد</th><th>قیمت (تومان)</th><th>بازدید</th><th>انتشار</th><th></th></tr></thead>
  <tbody><?php foreach ($cars as $c): ?>
    <tr>
      <td><img class="th" src="<?= e(upload_url($c['thumb'])) ?>" alt=""></td>
      <td><?= fa($c['code']) ?></td>
      <td><a href="<?= url('admin/cars/' . $c['id']) ?>"><b><?= e(Cars::title($c)) ?></b></a><?php if ($c['featured']): ?> <span class="badge warn">ویژه</span><?php endif ?><?php if ($c['installment']): ?> <span class="badge info">اقساطی</span><?php endif ?></td>
      <td><?= Catalog::CONDITION[$c['car_condition']] ?></td>
      <td><?= fa(num($c['mileage'])) ?></td>
      <td><?= $c['price_call'] ? 'تماس' : fa(num($c['price'])) ?></td>
      <td><?= fa($c['views']) ?></td>
      <td><form method="post" action="<?= url('admin/cars/' . $c['id'] . '/status') ?>"><?= csrf_field() ?><select name="status" data-autosubmit style="min-height:34px;padding:.2em .6em;padding-left:28px;width:auto"><?php foreach (Catalog::CAR_STATUS as $k => $l): ?><option value="<?= $k ?>"<?= selected($k, $c['status']) ?>><?= $l ?></option><?php endforeach ?></select></form></td>
      <td><div class="actions"><a class="icon-btn" href="<?= url('car/' . $c['id']) ?>" target="_blank" aria-label="مشاهده"><?= icon('eye') ?></a><a class="icon-btn" href="<?= url('admin/cars/' . $c['id']) ?>" aria-label="ویرایش"><?= icon('edit') ?></a></div></td>
    </tr>
  <?php endforeach ?></tbody></table>
  <?php if (!$cars): ?><div class="empty"><?= icon('car') ?><p>خودرویی یافت نشد.</p><a class="btn btn-accent" href="<?= url('admin/cars/new') ?>">افزودن اولین خودرو</a></div><?php endif ?>
</div>
<?php if ($pg['pages'] > 1): ?><nav class="pagination"><?php for ($i = 1; $i <= $pg['pages']; $i++): ?><?= $i === $pg['page'] ? '<span class="cur">' . fa($i) . '</span>' : '<a href="' . e(query_with(['page' => $i])) . '">' . fa($i) . '</a>' ?><?php endfor ?></nav><?php endif ?>
