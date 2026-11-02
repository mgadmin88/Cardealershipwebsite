<?php $edit = $edit ?? null; ?>
<div class="grid3">
  <div class="card tbl-wrap"><table class="table">
  <?php if ($kind === 'pcat'): ?>
    <thead><tr><th>نام</th><th>آیکون</th><th>ترتیب</th><th>محصولات</th><th></th></tr></thead>
    <?php foreach ($rows as $r): ?><tr><td><?= e($r['name']) ?></td><td><?= icon($r['icon'] ?: 'box') ?></td><td><?= fa($r['sort']) ?></td><td><?= fa($r['n']) ?></td>
      <td><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="icon-btn" data-confirm="حذف شود؟"><?= icon('trash') ?></button></form></td></tr><?php endforeach ?>
  <?php else: ?>
    <thead><tr><th>سوال</th><th>گروه</th><th>وضعیت</th><th></th></tr></thead>
    <?php foreach ($rows as $r): ?><tr><td><?= e($r['question']) ?></td><td><?= $r['grp'] === 'car' ? 'صفحه خودرو' : 'عمومی' ?></td><td><?= $r['active'] ? '<span class="badge ok">فعال</span>' : '<span class="badge">غیرفعال</span>' ?></td>
      <td><div class="actions"><a class="icon-btn" href="?edit=<?= $r['id'] ?>"><?= icon('edit') ?></a><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="icon-btn" data-confirm="حذف شود؟"><?= icon('trash') ?></button></form></div></td></tr><?php endforeach ?>
  <?php endif ?>
  </table></div>
  <form method="post" class="card section-card"><h3><?= $edit ? 'ویرایش' : 'افزودن' ?></h3><div class="body"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
  <?php if ($kind === 'pcat'): ?>
    <div class="field"><label>نام دسته</label><input name="name" required></div>
    <div class="field"><label>آیکون</label><select name="icon"><?php foreach (['box', 'engine', 'gauge', 'bolt', 'car', 'star', 'drop', 'wheel', 'music', 'wrench', 'shield'] as $ic): ?><option><?= $ic ?></option><?php endforeach ?></select></div>
    <div class="field"><label>ترتیب</label><input name="sort" value="0" inputmode="numeric"></div>
  <?php else: ?>
    <div class="field"><label>سوال</label><input name="question" required value="<?= e($edit['question'] ?? '') ?>"></div>
    <div class="field"><label>پاسخ</label><textarea name="answer" rows="5"><?= e($edit['answer'] ?? '') ?></textarea></div>
    <div class="field"><label>محل نمایش</label><select name="grp"><option value="general"<?= selected('general', $edit['grp'] ?? '') ?>>عمومی (صفحه اول و سوالات)</option><option value="car"<?= selected('car', $edit['grp'] ?? '') ?>>صفحه خودرو + عمومی</option></select></div>
    <div class="field"><label>ترتیب</label><input name="sort" value="<?= e($edit['sort'] ?? 0) ?>"></div>
    <label class="check mb-2"><input type="checkbox" name="active" value="1" <?= checked($edit['active'] ?? 1) ?>> فعال</label>
  <?php endif ?>
    <button class="btn btn-accent btn-block">ذخیره</button>
  </div></form>
</div>
