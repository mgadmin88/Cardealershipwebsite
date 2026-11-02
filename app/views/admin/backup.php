<?php $kinds = ['manual' => ['دستی', 'info'], 'auto' => ['خودکار روزانه', 'ok'], 'safety' => ['پیش از بازیابی', 'warn']];
$size = fn($b) => $b > 1048576 ? fa(number_format($b / 1048576, 1)) . ' مگابایت' : fa(number_format(max(1, $b / 1024))) . ' کیلوبایت'; ?>
<div class="grid2" style="align-items:start">
  <form method="post" class="card section-card"><h3><?= icon('upload') ?> ساخت نسخه پشتیبان</h3><div class="body"><?= csrf_field() ?><input type="hidden" name="action" value="create">
    <p class="muted small">کل اطلاعات سایت (خودروها، سفارش‌ها، مشتریان، نوبت‌ها، تنظیمات و…) در یک فایل ذخیره می‌شود.</p>
    <?php if ($zip): ?><label class="check mb-2"><input type="checkbox" name="uploads" value="1" checked> عکس‌ها هم در پشتیبان باشند (حجم بیشتر)</label>
    <?php else: ?><div class="alert info"><?= icon('info') ?><span>افزونه zip روی هاست فعال نیست؛ فقط از دیتابیس پشتیبان گرفته می‌شود. از پوشه public/uploads جداگانه از طریق هاست نسخه بگیرید.</span></div><?php endif ?>
    <button class="btn btn-accent"><?= icon('upload') ?> ساخت پشتیبان</button>
  </div></form>

  <div class="card section-card"><h3><?= icon('refresh') ?> بازیابی از فایل</h3><div class="body">
    <form method="post" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="action" value="restore_upload">
      <p class="muted small">فایل پشتیبانی که قبلاً دانلود کرده‌اید را انتخاب کنید (حداکثر حجم مجاز هاست: <span class="ltr"><?= e((string)$uploadMax) ?></span>).</p>
      <div class="field"><input type="file" name="file" accept=".zip,.gz,.json" required></div>
      <?php if ($zip): ?><label class="check mb-2"><input type="checkbox" name="uploads" value="1" checked> عکس‌های داخل فایل هم بازگردانده شوند</label><?php endif ?>
      <div class="field"><label>رمز عبور مدیر برای تأیید</label><input type="password" name="password" required dir="ltr" autocomplete="current-password"></div>
      <button class="btn btn-danger-ghost" data-confirm="همه اطلاعات فعلی سایت با محتوای این فایل جایگزین می‌شود. ادامه می‌دهید؟"><?= icon('refresh') ?> بازیابی</button>
    </form>
  </div></div>
</div>

<div class="alert info mt-2"><?= icon('shield') ?><span>قبل از هر بازیابی، خودکار یک نسخه «پیش از بازیابی» ساخته می‌شود تا در صورت اشتباه بتوانید برگردید. بازیابی، کاربران و رمزهای پنل را هم به حالت همان نسخه برمی‌گرداند. فایل‌ها در پوشه storage/backups نگهداری می‌شوند و از اینترنت قابل دسترسی نیستند؛ برای امنیت بیشتر هر چند وقت یک نسخه دانلود و جای دیگری نگه دارید.</span></div>

<form method="post" class="card mt-2 flex" style="padding:14px 18px;justify-content:space-between;flex-wrap:wrap;gap:10px"><?= csrf_field() ?><input type="hidden" name="action" value="auto">
  <label class="check"><input type="checkbox" name="auto" value="1" <?= checked($auto) ?>> پشتیبان خودکار روزانه از دیتابیس (۷ نسخه آخر نگه داشته می‌شود)</label>
  <button class="btn btn-soft btn-sm">ذخیره</button>
</form>

<div class="card tbl-wrap mt-2"><table class="table">
  <thead><tr><th>فایل</th><th>نوع</th><th>محتوا</th><th>تاریخ</th><th>حجم</th><th></th></tr></thead>
  <?php foreach ($files as $f): [$kl, $kc] = $kinds[$f['kind']] ?? ['دستی', 'info']; if (str_contains($f['name'], '-uploaded-')) [$kl, $kc] = ['بارگذاری‌شده', 'info']; ?><tr>
    <td class="ltr small" style="text-align:right"><?= e($f['name']) ?></td>
    <td><span class="badge <?= $kc ?>"><?= $kl ?></span></td>
    <td class="small"><?= $f['uploads'] ? 'دیتابیس + عکس‌ها' : 'دیتابیس' ?></td>
    <td class="small"><?= jdate($f['time'], 'datetime') ?></td><td class="small"><?= $size($f['size']) ?></td>
    <td class="backup-acts">
      <a class="btn btn-ghost btn-sm" href="<?= url('admin/backup/download', ['f' => $f['name']]) ?>"><?= icon('external') ?> دانلود</a>
      <details><summary class="btn btn-ghost btn-sm">بازیابی / حذف</summary>
        <form method="post" class="backup-pop"><?= csrf_field() ?><input type="hidden" name="name" value="<?= e($f['name']) ?>">
          <input type="password" name="password" required placeholder="رمز عبور مدیر" dir="ltr" autocomplete="current-password">
          <?php if ($f['uploads'] && $zip): ?><label class="check small"><input type="checkbox" name="uploads" value="1" checked> بازگرداندن عکس‌ها</label><?php endif ?>
          <div class="flex"><button name="action" value="restore" class="btn btn-danger-ghost btn-sm" data-confirm="اطلاعات فعلی سایت با این نسخه جایگزین شود؟">بازیابی این نسخه</button>
            <button name="action" value="delete" class="btn btn-ghost btn-sm" data-confirm="این فایل پشتیبان حذف شود؟">حذف</button></div>
        </form></details>
    </td>
  </tr><?php endforeach ?></table>
  <?php if (!$files): ?><div class="empty"><?= icon('upload') ?><p>هنوز نسخه پشتیبانی ساخته نشده است.</p></div><?php endif ?></div>
