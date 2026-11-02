<div class="card tbl-wrap"><table class="table">
  <thead><tr><th>#</th><th>سفارش</th><th>مشتری</th><th>درگاه</th><th>مبلغ</th><th>وضعیت</th><th>کد رهگیری</th><th>پیام</th><th>زمان</th></tr></thead>
  <?php foreach ($tx as $t): ?><tr>
    <td><?= fa($t['id']) ?></td><td><a href="<?= url('admin/orders/' . $t['order_id']) ?>"><?= fa($t['code']) ?></a></td><td><?= e($t['customer_name']) ?></td><td><?= e($t['label']) ?></td>
    <td><?= fa(num($t['amount'])) ?></td>
    <td><?= $t['status'] === 'success' ? '<span class="badge ok">موفق</span>' : ($t['status'] === 'init' ? '<span class="badge warn">ناتمام</span>' : '<span class="badge bad">ناموفق</span>') ?></td>
    <td class="ltr"><?= e($t['ref_id']) ?></td><td class="small"><?= e(excerpt((string)$t['message'], 60)) ?></td><td class="small"><?= jdate($t['created_at'], 'datetime') ?></td>
  </tr><?php endforeach ?></table>
  <?php if (!$tx): ?><div class="empty"><?= icon('card') ?><p>تراکنشی ثبت نشده است.</p></div><?php endif ?></div>
