<?php $txLabels = ['success' => ['موفق', 'ok'], 'failed' => ['ناموفق', 'bad'], 'init' => ['ناتمام', 'warn'], 'verifying' => ['در حال تأیید', 'info']]; ?>
<?php if ($tx): ?>
<div class="table-wrap"><table class="acc-table">
  <thead><tr><th>تاریخ</th><?php if ($withOrder): ?><th>سفارش</th><?php endif ?><th>درگاه</th><th>مبلغ (تومان)</th><th>کد رهگیری</th><th>وضعیت</th></tr></thead>
  <tbody><?php foreach ($tx as $t): [$tl, $tc] = $txLabels[$t['status']] ?? [$t['status'], 'info']; ?>
    <tr><td data-l="تاریخ"><?= jdate($t['created_at'], 'datetime') ?></td>
      <?php if ($withOrder): ?><td data-l="سفارش"><a href="<?= url('account/orders/' . $t['code']) ?>"><?= fa($t['code']) ?></a></td><?php endif ?>
      <td data-l="درگاه"><?= e($t['label'] ?? '—') ?></td>
      <td data-l="مبلغ"><b><?= fa(num($t['amount'])) ?></b></td>
      <td data-l="کد رهگیری" class="ltr"><?= $t['ref_id'] ? e(fa($t['ref_id'])) : '—' ?></td>
      <td data-l="وضعیت"><span class="badge <?= $tc ?>"><?= $tl ?></span></td></tr>
  <?php endforeach ?></tbody>
</table></div>
<?php else: ?><p class="muted mb-0">هنوز تراکنشی ثبت نشده است.</p><?php endif ?>
