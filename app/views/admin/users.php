<div class="grid3">
  <div class="card tbl-wrap"><table class="table"><thead><tr><th>نام</th><th>نام کاربری</th><th>نقش</th><th>آخرین ورود</th><th>وضعیت</th><th></th></tr></thead>
    <?php foreach ($users as $x): ?><tr><td><?= e($x['name']) ?></td><td class="ltr"><?= e($x['username']) ?></td><td><?= e(Auth::ROLES[$x['role']] ?? $x['role']) ?></td>
      <td class="small"><?= jdate($x['last_login'], 'datetime') ?></td><td><?= $x['active'] ? '<span class="badge ok">فعال</span>' : '<span class="badge bad">غیرفعال</span>' ?></td>
      <td><a class="icon-btn" href="?edit=<?= $x['id'] ?>"><?= icon('edit') ?></a></td></tr><?php endforeach ?></table>
    <div class="card-pad small muted"><b>سطوح دسترسی:</b> مدیر کل = همه بخش‌ها • کارشناس خودرو = افزودن/ویرایش خودرو و درخواست‌ها • اپراتور فروش = سفارش‌ها، قطعات و درخواست‌ها</div></div>
  <form method="post" class="card section-card"><h3><?= $edit ? 'ویرایش کاربر' : 'کاربر جدید' ?></h3><div class="body"><?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
    <div class="field"><label>نام و نام خانوادگی</label><input name="name" required value="<?= e($edit['name'] ?? '') ?>"></div>
    <div class="field"><label>نام کاربری</label><input name="username" dir="ltr" required value="<?= e($edit['username'] ?? '') ?>"></div>
    <div class="field"><label>موبایل</label><input name="mobile" dir="ltr" value="<?= e($edit['mobile'] ?? '') ?>"></div>
    <div class="field"><label>نقش</label><select name="role"><?php foreach (Auth::ROLES as $k => $l): ?><option value="<?= $k ?>"<?= selected($k, $edit['role'] ?? 'expert') ?>><?= $l ?></option><?php endforeach ?></select></div>
    <div class="field"><label>رمز عبور <?= $edit ? '(برای تغییر وارد کنید)' : '' ?></label><input type="password" name="password" dir="ltr" autocomplete="new-password" <?= $edit ? '' : 'required' ?> minlength="10"></div>
    <label class="check mb-2"><input type="checkbox" name="active" value="1" <?= checked($edit['active'] ?? 1) ?>> فعال</label>
    <button class="btn btn-accent btn-block">ذخیره</button>
  </div></form>
</div>
