<div class="grid3">
  <div class="card tbl-wrap"><table class="table"><thead><tr><th>عنوان</th><th>آدرس</th><th>فوتر</th><th></th></tr></thead>
    <?php foreach ($pages as $p): ?><tr><td><?= e($p['title']) ?></td><td class="ltr small"><?= $p['slug'] === 'about' ? '/about' : '/page/' . e($p['slug']) ?></td><td><?= $p['in_footer'] ? '✓' : '' ?></td>
      <td><div class="actions"><a class="icon-btn" href="?edit=<?= $p['id'] ?>"><?= icon('edit') ?></a><?php if (!in_array($p['slug'], ['about', 'terms'], true)): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $p['id'] ?>"><button class="icon-btn" data-confirm="حذف شود؟"><?= icon('trash') ?></button></form><?php endif ?></div></td></tr><?php endforeach ?>
  </table></div>
  <form method="post" class="card section-card"><h3><?= $edit ? 'ویرایش «' . e($edit['title']) . '»' : 'صفحه جدید' ?></h3><div class="body"><?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
    <div class="field"><label>عنوان</label><input name="title" required value="<?= e($edit['title'] ?? '') ?>"></div>
    <?php if (!$edit): ?><div class="field"><label>نامک انگلیسی (slug)</label><input name="slug" dir="ltr" required pattern="[a-z0-9\-]+" placeholder="warranty"></div><?php endif ?>
    <div class="field"><label>محتوا (HTML ساده مجاز است: p, h2, h3, ul, li, b, a, table)</label><textarea name="content" rows="14" dir="auto" class="mono"><?= e($edit['content'] ?? '') ?></textarea></div>
    <label class="check"><input type="checkbox" name="active" value="1" <?= checked($edit['active'] ?? 1) ?>> فعال</label>
    <label class="check mb-2"><input type="checkbox" name="in_footer" value="1" <?= checked($edit['in_footer'] ?? 1) ?>> نمایش لینک در فوتر</label>
    <button class="btn btn-accent btn-block">ذخیره</button>
  </div></form>
</div>
