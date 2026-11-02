<?php $icons = ['wrench', 'drop', 'engine', 'bolt', 'paint', 'wheel', 'shield', 'gauge', 'snow', 'car', 'gear']; ?>
<form method="post" class="card section-card"><h3><?= icon('clock') ?> برنامه کاری و ظرفیت</h3><div class="body"><?= csrf_field() ?><input type="hidden" name="action" value="schedule">
  <div class="field"><label>روزهای کاری</label><div class="fopts"><?php foreach (Service::WEEKDAYS as $n => $l): ?><label><input type="checkbox" name="days[]" value="<?= $n ?>" <?= checked(in_array($n, $cfg['days'], true)) ?>><span><?= $l ?></span></label><?php endforeach ?></div></div>
  <div class="form-grid">
    <div class="field"><label>شروع کار</label><input name="start" value="<?= e($cfg['start']) ?>" class="ltr" placeholder="08:00"></div>
    <div class="field"><label>پایان کار</label><input name="end" value="<?= e($cfg['end']) ?>" class="ltr" placeholder="17:00"></div>
    <div class="field"><label>پایان کار پنجشنبه</label><input name="thu_end" value="<?= e($cfg['thu_end']) ?>" class="ltr" placeholder="13:00"></div>
    <div class="field"><label>طول هر نوبت (دقیقه)</label><input name="slot" type="number" min="15" max="240" value="<?= $cfg['slot'] ?>"></div>
    <div class="field"><label>ظرفیت هر نوبت (تعداد خودرو هم‌زمان)</label><input name="capacity" type="number" min="1" max="50" value="<?= $cfg['capacity'] ?>"></div>
    <div class="field"><label>نوبت‌دهی تا چند روز آینده</label><input name="ahead" type="number" min="1" max="60" value="<?= $cfg['ahead'] ?>"></div>
    <div class="field"><label>حداقل فاصله ثبت تا نوبت (ساعت)</label><input name="notice" type="number" min="0" max="72" value="<?= $cfg['notice'] ?>"></div>
    <div class="field"><label>لغو آنلاین تا چند ساعت قبل</label><input name="cancel_hours" type="number" min="0" max="72" value="<?= $cfg['cancel_hours'] ?>"></div>
  </div>
  <div class="field"><label>روزهای تعطیل (تاریخ شمسی، هر خط یک روز — مثلاً ۱۴۰۵/۰۹/۱۰)</label><textarea name="holidays" rows="4" class="ltr" style="text-align:right"><?= e((string)setting('svc_holidays', '')) ?></textarea></div>
  <button class="btn btn-accent">ذخیره برنامه</button>
</div></form>

<div class="card section-card mt-2"><h3><?= icon('wrench') ?> خدمات قابل رزرو</h3><div class="body">
  <?php foreach ([...$types, ['id' => 0, 'title' => '', 'description' => '', 'icon' => 'wrench', 'price_from' => null, 'duration' => 60, 'active' => 1, 'sort' => count($types)]] as $t): ?>
    <form method="post" class="svc-type-row<?= $t['id'] ? '' : ' new' ?>"><?= csrf_field() ?><input type="hidden" name="action" value="type"><input type="hidden" name="id" value="<?= $t['id'] ?>">
      <?php if (!$t['id']): ?><b class="small">افزودن خدمت جدید</b><?php endif ?>
      <div class="form-grid">
        <div class="field"><label>عنوان</label><input name="title" value="<?= e($t['title']) ?>" <?= $t['id'] ? 'required' : '' ?>></div>
        <div class="field"><label>آیکن</label><select name="icon"><?php foreach ($icons as $ic): ?><option value="<?= $ic ?>"<?= selected($ic, $t['icon']) ?>><?= $ic ?></option><?php endforeach ?></select></div>
        <div class="field full"><label>توضیح کوتاه</label><input name="description" value="<?= e($t['description']) ?>" maxlength="500"></div>
        <div class="field"><label>قیمت از (تومان، اختیاری)</label><input name="price_from" inputmode="numeric" value="<?= e((string)$t['price_from']) ?>"></div>
        <div class="field"><label>ترتیب</label><input name="sort" type="number" value="<?= (int)$t['sort'] ?>"></div>
      </div>
      <div class="flex"><label class="check"><input type="checkbox" name="active" value="1" <?= checked($t['active']) ?>> فعال</label>
        <button class="btn btn-soft btn-sm"><?= $t['id'] ? 'ذخیره' : 'افزودن' ?></button>
        <?php if ($t['id']): ?><button class="btn btn-ghost btn-sm" name="action" value="delete_type" formnovalidate data-confirm="این خدمت حذف شود؟">حذف</button><?php endif ?></div>
    </form>
  <?php endforeach ?>
</div></div>
