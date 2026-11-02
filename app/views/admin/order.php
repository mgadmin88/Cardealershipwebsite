<div class="grid3">
  <div>
    <div class="card section-card"><h3>جزئیات سفارش</h3><div class="body">
      <table class="kv">
        <tr><td>کد سفارش</td><td><b><?= fa($o['code']) ?></b></td></tr>
        <tr><td>وضعیت</td><td><?= Orders::statusBadge($o['status']) ?></td></tr>
        <tr><td>تاریخ</td><td><?= jdate($o['created_at'], 'datetime') ?></td></tr>
        <tr><td>مشتری</td><td><?= e($o['customer_name']) ?></td></tr>
        <tr><td>موبایل</td><td><a class="ltr" href="tel:<?= e($o['mobile']) ?>"><?= fa($o['mobile']) ?></a></td></tr>
        <tr><td>کد ملی</td><td><?= e(fa($o['national_code'] ?: '—')) ?></td></tr>
        <?php if ($o['email']): ?><tr><td>ایمیل</td><td><?= e($o['email']) ?></td></tr><?php endif ?>
        <?php if ($o['address']): ?><tr><td>آدرس</td><td><?= e($o['province'] . ' ' . $o['city'] . ' — ' . $o['address']) ?> <?= $o['postal_code'] ? '— کد پستی ' . fa($o['postal_code']) : '' ?></td></tr><?php endif ?>
        <?php if ($o['note']): ?><tr><td>توضیحات مشتری</td><td><?= e($o['note']) ?></td></tr><?php endif ?>
        <?php if ($car): ?><tr><td>خودرو</td><td><a href="<?= url('admin/cars/' . $car['id']) ?>"><?= e(Cars::title($car)) ?></a> (کد <?= fa($car['code']) ?> — <?= Catalog::CAR_STATUS[$car['status']] ?>)</td></tr><?php endif ?>
        <?php foreach ($items as $it): ?><tr><td><?= e($it['title']) ?> × <?= fa($it['qty']) ?></td><td><?= fa(num($it['price'] * $it['qty'])) ?> تومان</td></tr><?php endforeach ?>
        <tr><td>هزینه ارسال</td><td><?= fa(num($o['shipping'])) ?></td></tr>
        <tr><td>مبلغ کل</td><td><b><?= fa(num($o['total'])) ?> تومان</b></td></tr>
        <tr><td>نوع پرداخت</td><td><?= $o['pay_type'] === 'deposit' ? 'بیعانه' : 'کامل' ?> — <?= fa(num($o['pay_amount'])) ?> تومان</td></tr>
        <tr><td>پرداخت شده</td><td><b><?= fa(num($o['paid_amount'])) ?></b> — مانده: <?= fa(num(max(0, $o['total'] - $o['paid_amount']))) ?></td></tr>
      </table>
    </div></div>
    <div class="card section-card"><h3>تراکنش‌ها</h3><div class="body tbl-wrap"><table class="table">
      <tr><th>درگاه</th><th>مبلغ</th><th>وضعیت</th><th>کد رهگیری</th><th>کارت</th><th>پیام</th><th>زمان</th></tr>
      <?php foreach ($tx as $t): ?><tr><td><?= e($t['label']) ?></td><td><?= fa(num($t['amount'])) ?></td><td><?= $t['status'] === 'success' ? '<span class="badge ok">موفق</span>' : ($t['status'] === 'init' ? '<span class="badge warn">ناتمام</span>' : '<span class="badge bad">ناموفق</span>') ?></td><td class="ltr"><?= e($t['ref_id']) ?></td><td class="ltr small"><?= e($t['card_pan']) ?></td><td class="small"><?= e($t['message']) ?></td><td class="small"><?= jdate($t['created_at'], 'datetime') ?></td></tr><?php endforeach ?>
    </table></div></div>
  </div>
  <form method="post" class="card section-card"><h3>تغییر وضعیت</h3><div class="body"><?= csrf_field() ?>
    <div class="field"><label>وضعیت</label><select name="status"><?php foreach (Catalog::ORDER_STATUS as $k => [$l]): ?><option value="<?= $k ?>"<?= selected($k, $o['status']) ?>><?= $l ?></option><?php endforeach ?></select>
      <div class="hint">برای سفارش خودرو: «تکمیل شده» خودرو را فروخته‌شده و «لغو شده» آن را دوباره موجود می‌کند.</div></div>
    <div class="field"><label>یادداشت داخلی</label><textarea name="admin_note" rows="4"><?= e($o['admin_note']) ?></textarea></div>
    <label class="check mb-2"><input type="checkbox" name="notify" value="1"> اطلاع‌رسانی پیامکی به مشتری</label>
    <button class="btn btn-accent btn-block">ذخیره</button>
  </div></form>
</div>
