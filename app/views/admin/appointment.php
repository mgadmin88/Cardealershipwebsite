<?php $locked = in_array($a['status'], ['done', 'rejected', 'cancelled', 'no_show'], true); ?>
<div class="grid2" style="align-items:start">
  <div>
    <div class="card section-card"><h3><?= icon('calendar') ?> نوبت <?= e($a['code']) ?> <span style="margin-inline-start:auto"><?= Service::badge($a['status']) ?></span></h3><div class="body">
      <table class="kv">
        <tr><td>زمان</td><td><b><?= e(Service::when($a)) ?></b></td></tr>
        <tr><td>خدمت</td><td><?= e($a['type_title'] ?? '—') ?></td></tr>
        <tr><td>مشتری</td><td><?= e($a['name']) ?> — <a class="ltr" href="tel:<?= e($a['mobile']) ?>"><?= fa($a['mobile']) ?></a></td></tr>
        <tr><td>خودرو</td><td><?= e($a['car_text']) ?><?= $a['year'] ? ' — مدل ' . fa($a['year']) : '' ?></td></tr>
        <?php if ($a['plate']): ?><tr><td>پلاک</td><td><?= e($a['plate']) ?></td></tr><?php endif ?>
        <?php if ($a['mileage'] !== null): ?><tr><td>کارکرد</td><td><?= fa(num($a['mileage'])) ?> کیلومتر</td></tr><?php endif ?>
        <?php if ($a['note']): ?><tr><td>توضیح مشتری</td><td><?= e($a['note']) ?></td></tr><?php endif ?>
        <tr><td>ثبت</td><td><?= jdate($a['created_at'], 'datetime') ?></td></tr>
        <?php if ($a['done_at']): ?><tr><td>انجام</td><td><?= jdate($a['done_at'], 'datetime') ?></td></tr><?php endif ?>
        <?php if ($a['report']): ?><tr><td>گزارش کار</td><td style="white-space:pre-line"><?= e($a['report']) ?></td></tr><?php endif ?>
        <?php if ($a['cost'] !== null): ?><tr><td>هزینه</td><td><?= fa(num($a['cost'])) ?> تومان</td></tr><?php endif ?>
      </table>
    </div></div>
    <?php if ($survey): ?>
    <div class="card section-card mt-2"><h3><?= icon('star') ?> نظر مشتری</h3><div class="body">
      <div class="survey-scores">
        <?php foreach (['overall' => 'کلی', 'quality' => 'کیفیت کار', 'timing' => 'زمان', 'staff' => 'کارکنان'] as $k => $l): if ($survey[$k] === null) continue; ?>
          <div><b><?= fa($survey[$k]) ?><small>/۵</small></b><span><?= $l ?></span></div><?php endforeach ?>
        <?php if ($survey['recommend'] !== null): ?><div><b><?= (int)$survey['recommend'] ? 'بله' : 'خیر' ?></b><span>پیشنهاد به دیگران</span></div><?php endif ?>
      </div>
      <?php if ($survey['comment']): ?><p class="mt-2 mb-0">«<?= e($survey['comment']) ?>»</p><?php endif ?>
    </div></div>
    <?php endif ?>
    <?php if ($history): ?>
    <div class="card section-card mt-2"><h3><?= icon('list') ?> سابقه این مشتری</h3><div class="body">
      <?php foreach ($history as $h): ?><div class="flex" style="justify-content:space-between;padding:6px 0;border-bottom:1px dashed var(--line)"><a href="<?= url('admin/appointments/' . $h['id']) ?>"><?= e($h['code']) ?> — <?= e($h['type_title'] ?? '') ?></a><span class="small"><?= jdate($h['day']) ?> <?= Service::badge($h['status']) ?></span></div><?php endforeach ?>
    </div></div>
    <?php endif ?>
  </div>

  <div>
    <form method="post" class="card section-card"><h3><?= icon('check') ?> اقدام</h3><div class="body"><?= csrf_field() ?>
      <div class="field"><label>پیام برای مشتری / یادداشت (در صفحه پیگیری نوبت نمایش داده می‌شود)</label><textarea name="admin_note" rows="2" maxlength="1000"><?= e($a['admin_note']) ?></textarea></div>
      <label class="check mb-2"><input type="checkbox" name="notify" value="1" checked> اطلاع‌رسانی پیامکی به مشتری<?= module('sms') ? '' : ' (ماژول پیامک خاموش است)' ?></label>
      <div class="appt-actions">
        <?php if ($a['status'] === 'pending'): ?><button name="action" value="confirm" class="btn btn-accent"><?= icon('check') ?> تأیید نوبت</button><button name="action" value="reject" class="btn btn-danger-ghost"><?= icon('x') ?> رد نوبت</button><?php endif ?>
        <?php if ($a['status'] === 'confirmed'): ?><button name="action" value="start" class="btn btn-accent"><?= icon('wrench') ?> پذیرش خودرو (شروع کار)</button><button name="action" value="no_show" class="btn btn-ghost">مراجعه نکرد</button><?php endif ?>
        <?php if (!$locked): ?><button name="action" value="cancel" class="btn btn-ghost">لغو نوبت</button><?php endif ?>
        <button name="action" value="note" class="btn btn-ghost">فقط ذخیره یادداشت</button>
      </div>
    </div></form>

    <?php if (in_array($a['status'], ['confirmed', 'in_progress'], true)): ?>
    <form method="post" class="card section-card mt-2"><h3><?= icon('award') ?> پایان کار و تحویل</h3><div class="body"><?= csrf_field() ?><input type="hidden" name="action" value="done"><input type="hidden" name="admin_note" value="<?= e($a['admin_note']) ?>">
      <div class="field"><label>گزارش کارهای انجام‌شده (برای مشتری نمایش داده می‌شود)</label><textarea name="report" rows="4" maxlength="2000" placeholder="مثلاً: تعویض روغن ۵W30، فیلتر روغن و هوا، بازدید ترمزها"><?= e($a['report']) ?></textarea></div>
      <div class="field"><label>هزینه (تومان)</label><input name="cost" inputmode="numeric" value="<?= e((string)$a['cost']) ?>"></div>
      <label class="check mb-2"><input type="checkbox" name="notify" value="1" checked> پیامک «آماده تحویل»<?= module('surveys') ? ' و لینک نظرسنجی' : '' ?> به مشتری</label>
      <button class="btn btn-accent"><?= icon('check') ?> ثبت پایان کار</button>
    </div></form>
    <?php endif ?>

    <?php if ($a['status'] !== 'done'): ?>
    <form method="post" class="card section-card mt-2" data-move-form><h3><?= icon('clock') ?> تغییر زمان نوبت</h3><div class="body"><?= csrf_field() ?><input type="hidden" name="action" value="move"><input type="hidden" name="admin_note" value="<?= e($a['admin_note']) ?>">
      <div class="form-grid">
        <div class="field"><label>روز</label><select name="day"><?php for ($i = 0; $i <= 30; $i++): $ts = strtotime("+$i day", strtotime('today')); $d = date('Y-m-d', $ts); ?><option value="<?= $d ?>"<?= selected($d, $a['day']) ?>><?= Service::WEEKDAYS[(int)date('w', $ts)] ?> <?= jdate($ts) ?></option><?php endfor ?></select></div>
        <div class="field"><label>ساعت (مثلاً ۱۰:۳۰)</label><input name="slot" value="<?= e($a['slot']) ?>" class="ltr" required pattern="[0-9۰-۹]{2}:[0-9۰-۹]{2}"></div>
      </div>
      <label class="check mb-2"><input type="checkbox" name="notify" value="1" checked> پیامک زمان جدید به مشتری</label>
      <button class="btn btn-soft">تغییر زمان</button>
    </div></form>
    <?php endif ?>
  </div>
</div>
