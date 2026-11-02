<div class="page-hero"><div class="container"><h1>مقایسه خودروها</h1><p>مشخصات حداکثر ۳ خودرو را کنار هم مقایسه کنید</p></div></div>
<div class="container">
<?php if (count($cars) < 1): ?>
  <div class="card empty" data-compare-empty><?= icon('compare') ?><h3>خودرویی برای مقایسه انتخاب نشده است</h3><p>از صفحه خودروها، دکمه مقایسه را روی کارت خودرو بزنید.</p><a class="btn btn-accent" href="<?= url('cars') ?>">مشاهده خودروها</a></div>
<?php else:
  $rows = [
    'قیمت' => fn($c) => $c['price_call'] || !$c['price'] ? 'تماس بگیرید' : fa(num($c['price'])) . ' تومان',
    'وضعیت' => fn($c) => Catalog::CONDITION[$c['car_condition']],
    'سال ساخت' => fn($c) => fa($c['year']),
    'کارکرد' => fn($c) => fa(num($c['mileage'])) . ' km',
    'گیربکس' => fn($c) => Catalog::GEARBOX[$c['gearbox']] ?? '—',
    'سوخت' => fn($c) => Catalog::FUEL[$c['fuel']] ?? '—',
    'بدنه' => fn($c) => Catalog::BODY_TYPES[$c['body_type']] ?? '—',
    'رنگ' => fn($c) => $c['color'] ?: '—',
    'قطعات رنگ‌شده' => fn($c) => ($n = count(array_filter($c['damage'], fn($s) => $s && $s !== 'ok'))) ? fa($n) . ' قطعه' : 'بدون رنگ',
    'بیمه بدنه' => fn($c) => $c['ins_body'] ? 'دارد' : 'ندارد',
    'نوع سند' => fn($c) => Catalog::DOC_TYPES[$c['doc_type']] ?? '—',
    'گارانتی' => fn($c) => $c['warranty'] ?: '—',
    'اقساطی' => fn($c) => $c['installment'] ? 'بله' : 'خیر',
    'شهر' => fn($c) => $c['city'],
  ];
  foreach (['engine' => 'موتور', 'power' => 'قدرت', 'consumption' => 'مصرف'] as $k => $l) $rows[$l] = fn($c) => $c['specs'][$k] ?? '—';
?>
  <div style="overflow-x:auto">
  <table class="compare-table">
    <tr><th></th><?php foreach ($cars as $c): ?><th><a href="<?= url('car/' . $c['id']) ?>"><img src="<?= e(upload_url($c['thumb'])) ?>" alt=""><b><?= e(Cars::title($c)) ?></b></a><br><button class="btn btn-ghost btn-sm mt-1" data-cmp-remove="<?= $c['id'] ?>"><?= icon('trash') ?> حذف</button></th><?php endforeach ?></tr>
    <?php foreach ($rows as $label => $fn): ?><tr><td><?= $label ?></td><?php foreach ($cars as $c): ?><td><?= e($fn($c)) ?></td><?php endforeach ?></tr><?php endforeach ?>
  </table>
  </div>
<?php endif ?>
</div>
