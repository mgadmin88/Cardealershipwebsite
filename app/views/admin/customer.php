<div class="grid2" style="align-items:start">
  <div class="card section-card"><h3><?= icon('user') ?> مشخصات</h3><div class="body">
    <table class="kv">
      <tr><td>نام</td><td><?= e($c['name'] ?: '—') ?></td></tr>
      <tr><td>موبایل</td><td class="ltr" style="text-align:right"><?= fa($c['mobile']) ?> <?= (int)$c['verified'] ? '<span class="badge ok">تأییدشده</span>' : '<span class="badge warn">تأیید نشده</span>' ?></td></tr>
      <tr><td>کد ملی</td><td><?= $c['national_code'] ? fa($c['national_code']) : '—' ?></td></tr>
      <tr><td>ایمیل</td><td class="ltr" style="text-align:right"><?= e($c['email'] ?: '—') ?></td></tr>
      <tr><td>آدرس</td><td><?= e(trim($c['province'] . ' ' . $c['city'] . ' ' . $c['address'])) ?: '—' ?></td></tr>
      <tr><td>عضویت</td><td><?= jdate($c['created_at'], 'datetime') ?></td></tr>
      <tr><td>آخرین ورود</td><td><?= jdate($c['last_login'], 'datetime') ?></td></tr>
    </table>
    <div class="flex mt-2">
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><button class="btn <?= (int)$c['active'] ? 'btn-ghost' : 'btn-soft' ?>"><?= (int)$c['active'] ? 'غیرفعال کردن حساب' : 'فعال کردن حساب' ?></button></form>
      <?php if (!(int)$c['verified']): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="verify"><button class="btn btn-ghost" title="فقط اگر هویت و مالکیت شماره را خودتان بررسی کرده‌اید">تأیید دستی موبایل</button></form><?php endif ?>
    </div>
    <?php if (!(int)$c['verified']): ?><p class="small muted mt-2">موبایل تأیید نشده یعنی مشتری فقط سفارش‌هایی را می‌بیند که بعد از ورود ثبت کرده یا با کد سفارش اضافه کرده است. تأیید دستی، همه سفارش‌های این شماره را به او نشان می‌دهد.</p><?php endif ?>
  </div></div>
  <div>
    <div class="card section-card"><h3><?= icon('cart') ?> سفارش‌ها (<?= fa(count($orders)) ?>)</h3><div class="body">
      <?php foreach ($orders as $o): ?><div class="flex" style="justify-content:space-between;padding:8px 0;border-bottom:1px dashed var(--line)">
        <a href="<?= url('admin/orders/' . $o['id']) ?>"><b><?= fa($o['code']) ?></b> <span class="small muted"><?= $o['type'] === 'car' ? 'خودرو' : 'قطعات' ?> — <?= jdate($o['created_at']) ?></span></a>
        <span><?= fa(num($o['total'])) ?> <?= Orders::statusBadge($o['status']) ?></span></div><?php endforeach ?>
      <?php if (!$orders): ?><p class="muted mb-0">سفارشی ندارد.</p><?php endif ?>
    </div></div>
    <div class="card section-card mt-2"><h3><?= icon('message') ?> درخواست‌ها (<?= fa(count($requests)) ?>)</h3><div class="body">
      <?php foreach ($requests as $r): ?><div class="flex" style="justify-content:space-between;padding:8px 0;border-bottom:1px dashed var(--line)">
        <span><?= e(Catalog::REQUEST_TYPES[$r['type']] ?? $r['type']) ?> <span class="small muted"><?= jdate($r['created_at']) ?></span></span><?= request_badge($r['status']) ?></div><?php endforeach ?>
      <?php if (!$requests): ?><p class="muted mb-0">درخواستی ندارد.</p><?php endif ?>
    </div></div>
  </div>
</div>
