<form class="filters-bar" method="get">
  <input type="search" name="q" value="<?= e(input('q')) ?>" placeholder="نام، کد یا برند">
  <select name="cat"><option value="">همه دسته‌ها</option><?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>"<?= selected($c['id'], input('cat')) ?>><?= e($c['name']) ?></option><?php endforeach ?></select>
  <button class="btn btn-soft">فیلتر</button>
</form>
<div class="card tbl-wrap"><table class="table">
  <thead><tr><th></th><th>محصول</th><th>دسته</th><th>قیمت</th><th>موجودی</th><th>وضعیت</th><th></th></tr></thead>
  <?php foreach ($parts as $p): ?><tr>
    <td><img class="th" src="<?= e(upload_url($p['thumb'] ?: $p['image'], 'img/part-placeholder.svg')) ?>" alt=""></td>
    <td><a href="<?= url('admin/parts/' . $p['id']) ?>"><b><?= e($p['title']) ?></b></a><div class="small muted"><?= e($p['brand']) ?> — <?= e($p['sku']) ?></div></td>
    <td><?= e($p['cat']) ?><?= $p['is_option'] ? ' <span class="badge info">آپشن</span>' : '' ?></td>
    <td><?php if ($p['sale_price']): ?><s class="muted small"><?= fa(num($p['price'])) ?></s><br><?= fa(num($p['sale_price'])) ?><?php else: ?><?= fa(num($p['price'])) ?><?php endif ?></td>
    <td><?= $p['stock'] < 3 ? '<span class="badge bad">' . fa($p['stock']) . '</span>' : fa($p['stock']) ?></td>
    <td><?= $p['active'] ? '<span class="badge ok">فعال</span>' : '<span class="badge">غیرفعال</span>' ?></td>
    <td><a class="icon-btn" href="<?= url('admin/parts/' . $p['id']) ?>"><?= icon('edit') ?></a></td>
  </tr><?php endforeach ?></table></div>
