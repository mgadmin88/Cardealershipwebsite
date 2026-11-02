<form class="filters-bar" method="get">
  <input type="search" name="q" value="<?= e(input('q')) ?>" placeholder="کد سفارش، نام یا موبایل">
  <select name="type"><option value="">همه</option><option value="car"<?= selected('car', input('type')) ?>>خودرو</option><option value="parts"<?= selected('parts', input('type')) ?>>قطعات</option></select>
  <select name="status"><option value="">همه وضعیت‌ها</option><?php foreach (Catalog::ORDER_STATUS as $k => [$l]): ?><option value="<?= $k ?>"<?= selected($k, input('status')) ?>><?= $l ?></option><?php endforeach ?></select>
  <button class="btn btn-soft">فیلتر</button>
</form>
<div class="card tbl-wrap"><table class="table">
  <thead><tr><th>کد</th><th>نوع</th><th>مشتری</th><th>موبایل</th><th>مبلغ کل</th><th>پرداختی</th><th>وضعیت</th><th>تاریخ</th></tr></thead>
  <?php foreach ($orders as $o): ?><tr>
    <td><a href="<?= url('admin/orders/' . $o['id']) ?>"><b><?= fa($o['code']) ?></b></a></td>
    <td><?= $o['type'] === 'car' ? '<span class="badge info">خودرو</span>' : '<span class="badge">قطعات</span>' ?></td>
    <td><?= e($o['customer_name']) ?></td><td class="ltr"><?= fa($o['mobile']) ?></td>
    <td><?= fa(num($o['total'])) ?></td><td><?= fa(num($o['paid_amount'])) ?></td>
    <td><?= Orders::statusBadge($o['status']) ?></td><td class="small"><?= jdate($o['created_at'], 'datetime') ?></td>
  </tr><?php endforeach ?></table>
  <?php if (!$orders): ?><div class="empty"><?= icon('cart') ?><p>سفارشی یافت نشد.</p></div><?php endif ?></div>
