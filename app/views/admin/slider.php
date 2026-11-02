<div class="card card-pad mb-2 flex between wrap">
  <div><b>نمایش اسلایدشو در صفحه اول:</b> <?= module('slider') ? '<span class="badge ok">فعال</span>' : '<span class="badge bad">غیرفعال</span>' ?>
    <p class="small muted mb-0">در صورت غیرفعال بودن، بنر ثابت با جستجو نمایش داده می‌شود. (تنظیمات ظاهر ← نوع هدر صفحه اول)</p></div>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle_module"><button class="btn <?= module('slider') ? 'btn-ghost' : 'btn-accent' ?>"><?= module('slider') ? 'غیرفعال کردن اسلایدر' : 'فعال کردن اسلایدر' ?></button></form>
</div>
<div class="grid3">
  <div class="img-grid" style="grid-template-columns:repeat(auto-fill,minmax(260px,1fr))">
    <?php foreach ($slides as $s): ?>
      <div class="card" style="overflow:hidden">
        <img src="<?= e(upload_url($s['image'])) ?>" alt="" style="aspect-ratio:21/9;object-fit:cover;width:100%">
        <div class="card-pad" style="padding:14px"><b><?= e($s['title']) ?></b><p class="small muted mb-0"><?= e(excerpt((string)$s['subtitle'], 70)) ?></p>
          <div class="flex mt-1"><?= $s['active'] ? '<span class="badge ok">فعال</span>' : '<span class="badge">غیرفعال</span>' ?><span class="small muted">ترتیب <?= fa($s['sort']) ?></span>
            <a class="icon-btn" style="margin-inline-start:auto" href="?edit=<?= $s['id'] ?>"><?= icon('edit') ?></a>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $s['id'] ?>"><button class="icon-btn" data-confirm="اسلاید حذف شود؟"><?= icon('trash') ?></button></form></div></div>
      </div>
    <?php endforeach ?>
  </div>
  <form method="post" enctype="multipart/form-data" class="card section-card"><h3><?= $edit ? 'ویرایش اسلاید' : 'افزودن اسلاید' ?></h3><div class="body"><?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
    <div class="field"><label>تصویر (پیشنهاد: ۱۹۲۰×۷۳۰)</label><input type="file" name="image" accept="image/*" <?= $edit ? '' : 'required' ?>></div>
    <div class="field"><label>عنوان</label><input name="title" value="<?= e($edit['title'] ?? '') ?>"></div>
    <div class="field"><label>زیرعنوان</label><textarea name="subtitle" rows="2"><?= e($edit['subtitle'] ?? '') ?></textarea></div>
    <div class="form-grid"><div class="field"><label>متن دکمه</label><input name="btn_text" value="<?= e($edit['btn_text'] ?? '') ?>"></div>
    <div class="field"><label>لینک دکمه</label><input name="btn_link" dir="ltr" value="<?= e($edit['btn_link'] ?? '/cars') ?>"></div>
    <div class="field"><label>محل متن</label><select name="align"><option value="right"<?= selected('right', $edit['align'] ?? '') ?>>راست</option><option value="left"<?= selected('left', $edit['align'] ?? '') ?>>چپ</option></select></div>
    <div class="field"><label>ترتیب</label><input name="sort" value="<?= e($edit['sort'] ?? count($slides)) ?>"></div></div>
    <label class="check mb-2"><input type="checkbox" name="active" value="1" <?= checked($edit['active'] ?? 1) ?>> فعال</label>
    <button class="btn btn-accent btn-block">ذخیره اسلاید</button>
  </div></form>
</div>
