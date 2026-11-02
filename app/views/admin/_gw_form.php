<?php $g = $g ?? null; $cfg = $g ? (json_decode((string)$g['config'], true) ?: []) : []; ?>
<form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $g['id'] ?? '' ?>">
  <div class="form-grid">
    <div class="field"><label>عنوان نمایشی برای مشتری</label><input name="label" value="<?= e($g['label'] ?? '') ?>" placeholder="مثلاً بانک سینا"></div>
    <div class="field"><label>بانک</label><select name="bank"><?php foreach (Payment::BANKS as $k => $l): ?><option value="<?= $k ?>"<?= selected($k, $g['bank'] ?? '') ?>><?= $l ?></option><?php endforeach ?></select></div>
    <div class="field"><label>شرکت پرداخت (PSP / درایور)</label><select name="driver" data-switch><?php foreach (Payment::DRIVERS as $k => $cls): ?><option value="<?= $k ?>"<?= selected($k, $g['driver'] ?? '') ?>><?= e($cls::title()) ?></option><?php endforeach ?></select></div>
    <div class="field"><label>ترتیب نمایش</label><input name="sort" value="<?= e($g['sort'] ?? 0) ?>" inputmode="numeric"></div>
  </div>
  <?php foreach (Payment::DRIVERS as $k => $cls): ?>
    <div data-for="<?= $k ?>" class="form-grid">
      <?php foreach ($cls::fields() as $fk => [$fl, $ft, $hint]): ?>
        <div class="field"><label><?= e($fl) ?></label>
          <input name="cfg[<?= $fk ?>]" dir="ltr" type="<?= $ft === 'password' ? 'password' : 'text' ?>" autocomplete="off"
            value="<?= $ft === 'password' ? '' : e($g && $g['driver'] === $k ? ($cfg[$fk] ?? '') : '') ?>"
            placeholder="<?= $ft === 'password' && $g && !empty($cfg[$fk]) ? e(Secret::mask($cfg[$fk])) . ' (برای تغییر وارد کنید)' : '' ?>">
          <?php if ($hint): ?><div class="hint"><?= e($hint) ?></div><?php endif ?></div>
      <?php endforeach ?>
      <?php if ($cls::note()): ?><div class="full muted-box"><?= e($cls::note()) ?></div><?php endif ?>
    </div>
  <?php endforeach ?>
  <div class="flex wrap mt-2">
    <label class="check"><input type="checkbox" name="active" value="1" <?= checked($g['active'] ?? 0) ?>> فعال</label>
    <label class="check"><input type="checkbox" name="sandbox" value="1" <?= checked($g['sandbox'] ?? 0) ?>> حالت آزمایشی (Sandbox)</label>
    <button class="btn btn-accent" style="margin-inline-start:auto">ذخیره</button>
  </div>
</form>
