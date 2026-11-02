<?php $cur = null; foreach ($brands as $b) if ($b['id'] == $sel) $cur = $b; ?>
<div class="grid3" style="grid-template-columns:1fr 1.3fr">
  <div class="card section-card"><h3><?= icon('tag') ?> برندها (<?= fa(count($brands)) ?>)</h3><div class="body" style="max-height:70vh;overflow:auto">
    <table class="table"><?php foreach ($brands as $b): ?>
      <tr<?= $b['id'] == $sel ? ' style="background:var(--soft-2)"' : '' ?>><td><a href="?b=<?= $b['id'] ?>"><b><?= e($b['name_fa']) ?></b> <small class="muted"><?= e($b['name_en']) ?></small></a></td>
      <td><span class="badge <?= $b['origin'] === 'domestic' ? 'info' : 'ok' ?>"><?= $b['origin'] === 'domestic' ? 'داخلی' : 'خارجی' ?></span><?= $b['popular'] ? ' ⭐' : '' ?></td><td class="muted small"><?= fa($b['mc']) ?> مدل</td></tr>
    <?php endforeach ?></table>
  </div></div>
  <div>
    <div class="card section-card"><h3><?= $cur ? 'ویرایش برند «' . e($cur['name_fa']) . '»' : 'برند' ?></h3><div class="body">
      <form method="post" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="action" value="brand"><input type="hidden" name="id" value="<?= $cur['id'] ?? '' ?>" data-brand-id>
        <div class="form-grid">
          <div class="field"><label>نام فارسی</label><input name="name_fa" required value="<?= e($cur['name_fa'] ?? '') ?>"></div>
          <div class="field"><label>نام انگلیسی</label><input name="name_en" dir="ltr" value="<?= e($cur['name_en'] ?? '') ?>"></div>
          <div class="field"><label>نوع</label><select name="origin"><option value="domestic"<?= selected('domestic', $cur['origin'] ?? '') ?>>داخلی</option><option value="foreign"<?= selected('foreign', $cur['origin'] ?? '') ?>>خارجی</option></select></div>
          <div class="field"><label>لوگو (اختیاری)</label><input type="file" name="logo" accept="image/*"></div>
        </div>
        <label class="check mb-2"><input type="checkbox" name="popular" value="1" <?= checked($cur['popular'] ?? 0) ?>> نمایش در نوار برندهای محبوب صفحه اول</label>
        <div class="flex"><button class="btn btn-accent">ذخیره برند</button><a href="?b=0" class="btn btn-ghost">+ برند جدید</a></div>
      </form>
      <?php if ($cur && !$cur['cc']): ?><form method="post" class="mt-2"><?= csrf_field() ?><input type="hidden" name="action" value="del_brand"><input type="hidden" name="id" value="<?= $cur['id'] ?>"><button class="btn btn-ghost btn-sm" data-confirm="برند و همه مدل‌هایش حذف شود؟"><?= icon('trash') ?> حذف برند</button></form><?php endif ?>
    </div></div>
    <?php if ($cur): ?>
    <div class="card section-card"><h3>مدل‌های <?= e($cur['name_fa']) ?></h3><div class="body">
      <form method="post" class="inline-form mb-2"><?= csrf_field() ?><input type="hidden" name="action" value="model"><input type="hidden" name="brand_id" value="<?= $cur['id'] ?>">
        <div class="field"><label>نام فارسی</label><input name="name_fa" required></div>
        <div class="field"><label>نام انگلیسی</label><input name="name_en" dir="ltr"></div>
        <div class="field"><label>بدنه</label><select name="body_type"><?php foreach (Catalog::BODY_TYPES as $k => $l): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach ?></select></div>
        <button class="btn btn-soft"><?= icon('plus') ?> افزودن</button></form>
      <table class="table"><?php foreach ($models as $m): ?><tr>
        <td><?= e($m['name_fa']) ?></td><td class="ltr"><?= e($m['name_en']) ?></td><td><?= e(Catalog::BODY_TYPES[$m['body_type']] ?? '') ?></td>
        <td><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="del_model"><input type="hidden" name="brand_id" value="<?= $cur['id'] ?>"><input type="hidden" name="id" value="<?= $m['id'] ?>"><button class="icon-btn" data-confirm="مدل حذف شود؟" aria-label="حذف"><?= icon('trash') ?></button></form></td>
      </tr><?php endforeach ?></table>
    </div></div>
    <?php endif ?>
  </div>
</div>
