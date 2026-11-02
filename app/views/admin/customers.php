<form class="filters-bar" method="get">
  <input type="search" name="q" value="<?= e(input('q')) ?>" placeholder="نام، موبایل یا کد ملی">
  <button class="btn btn-soft">جستجو</button>
</form>
<div class="card tbl-wrap"><table class="table">
  <thead><tr><th>نام</th><th>موبایل</th><th>تأیید موبایل</th><th>سفارش‌ها</th><th>پرداختی (تومان)</th><th>عضویت</th><th>آخرین ورود</th><th>وضعیت</th></tr></thead>
  <?php foreach ($rows as $c): ?><tr>
    <td><a href="<?= url('admin/customers/' . $c['id']) ?>"><b><?= e($c['name'] ?: '—') ?></b></a></td>
    <td class="ltr"><?= fa($c['mobile']) ?></td>
    <td><?= (int)$c['verified'] ? '<span class="badge ok">تأییدشده</span>' : '<span class="badge warn">تأیید نشده</span>' ?></td>
    <td><?= fa($c['orders_count']) ?></td><td><?= fa(num($c['paid'])) ?></td>
    <td class="small"><?= jdate($c['created_at']) ?></td><td class="small"><?= jdate($c['last_login'], 'datetime') ?></td>
    <td><?= (int)$c['active'] ? '<span class="badge ok">فعال</span>' : '<span class="badge bad">غیرفعال</span>' ?></td>
  </tr><?php endforeach ?></table>
  <?php if (!$rows): ?><div class="empty"><?= icon('users') ?><p>هنوز مشتری‌ای در سایت حساب نساخته است.</p></div><?php endif ?></div>
