<?php $v = fn($k, $d = '') => old($k, $p[$k] ?? $d); ?>
<form method="post" enctype="multipart/form-data" class="grid3"><?= csrf_field() ?>
  <div class="card section-card"><h3>اطلاعات محصول</h3><div class="body"><div class="form-grid">
    <div class="field full"><label>عنوان *</label><input name="title" required value="<?= e($v('title')) ?>"></div>
    <div class="field"><label>دسته‌بندی</label><select name="category_id"><option value="">—</option><?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>"<?= selected($c['id'], $v('category_id')) ?>><?= e($c['name']) ?></option><?php endforeach ?></select></div>
    <div class="field"><label>برند سازنده</label><input name="brand" value="<?= e($v('brand')) ?>"></div>
    <div class="field"><label>کد کالا (SKU)</label><input name="sku" dir="ltr" value="<?= e($v('sku')) ?>"></div>
    <div class="field"><label>خودروهای سازگار</label><input name="compatible" value="<?= e($v('compatible')) ?>"></div>
    <div class="field"><label>قیمت (تومان) *</label><input name="price" data-money required value="<?= e($v('price')) ?>"></div>
    <div class="field"><label>قیمت با تخفیف</label><input name="sale_price" data-money value="<?= e($v('sale_price')) ?>"></div>
    <div class="field"><label>موجودی انبار</label><input name="stock" inputmode="numeric" value="<?= e($v('stock', 0)) ?>"></div>
    <div class="field full"><label>توضیحات</label><textarea name="description" rows="6"><?= e($v('description')) ?></textarea></div>
  </div></div></div>
  <div>
    <div class="card section-card"><h3>تصویر و وضعیت</h3><div class="body">
      <img id="pimg" src="<?= e(upload_url($p['image'] ?? null, 'img/part-placeholder.svg')) ?>" alt="" style="border-radius:12px;aspect-ratio:1;object-fit:cover;width:100%;margin-bottom:10px">
      <input type="file" name="image" accept="image/*" data-single-preview="pimg">
      <div class="mt-2" style="display:grid;gap:10px">
        <label class="check"><input type="checkbox" name="active" value="1" <?= checked($v('active', 1)) ?>> نمایش در فروشگاه</label>
        <label class="check"><input type="checkbox" name="is_option" value="1" <?= checked($v('is_option')) ?>> آپشن / لوازم جانبی</label>
        <label class="check"><input type="checkbox" name="featured" value="1" <?= checked($v('featured')) ?>> نمایش در صفحه اول</label>
      </div>
      <button class="btn btn-accent btn-block mt-2">ذخیره</button>
    </div></div>
  </div>
</form>
<?php if ($p): ?><form method="post" action="<?= url('admin/parts/' . $p['id'] . '/delete') ?>"><?= csrf_field() ?><button class="btn btn-ghost btn-sm" data-confirm="محصول حذف شود؟"><?= icon('trash') ?> حذف محصول</button></form><?php endif ?>
