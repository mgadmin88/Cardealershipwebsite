<?php /** @var array $car */
$tags = array_filter(array_map('trim', explode(',', (string)$car['tags'])));
$link = url('car/' . $car['id'] . '/' . ($car['slug'] ?: 'car'));
$dmg = is_array($car['damage'] ?? null) ? $car['damage'] : (json_decode((string)($car['damage'] ?? ''), true) ?: []);
$dmg = array_filter($dmg, fn($s) => is_string($s) && $s !== 'ok');
$replaced = count(array_filter($dmg, fn($s) => $s === 'replaced'));
?>
<article class="car-card reveal">
  <a href="<?= $link ?>" class="car-link" aria-label="<?= e($car['brand_name'] . ' ' . $car['model_name']) ?>"></a>
  <div class="car-media">
    <img src="<?= e(upload_url($car['thumb'] ?? null)) ?>" alt="<?= e($car['brand_name'] . ' ' . $car['model_name'] . ' ' . $car['year']) ?>" loading="lazy" decoding="async">
    <div class="car-flag">
      <?php if ($car['car_condition'] === 'new'): ?><span class="flag ok">صفر کیلومتر</span><?php endif ?>
      <?php if (module('installment') && $car['installment']): ?><span class="flag"><?= icon('calc') ?> خرید اقساطی</span><?php endif ?>
      <?php if ($car['status'] === 'reserved'): ?><span class="flag accent">رزرو شده</span><?php endif ?>
    </div>
    <div class="car-tools">
      <?php if (module('favorites')): ?><button class="tool" data-fav="<?= (int)$car['id'] ?>" aria-label="افزودن به علاقه‌مندی"><?= icon('heart') ?></button><?php endif ?>
      <?php if (module('compare')): ?><button class="tool" data-cmp="<?= (int)$car['id'] ?>" aria-label="افزودن به مقایسه"><?= icon('compare') ?></button><?php endif ?>
    </div>
    <?php if ($car['status'] === 'sold'): ?><div class="car-sold">فروخته شد</div><?php endif ?>
  </div>
  <div class="car-body">
    <div class="car-title"><span><?= e($car['brand_name']) ?></span><?php if ($car['model_name']): ?><span class="sep"></span><span><?= e($car['model_name']) ?></span><?php endif ?><span class="sep"></span><span><?= fa($car['year']) ?></span></div>
    <div class="car-meta">
      <span><?= e(Catalog::GEARBOX[$car['gearbox']] ?? '') ?></span><span class="sep"></span>
      <span><?= $car['car_condition'] === 'new' ? 'صفر' : fa(num($car['mileage'])) . ' km' ?></span><span class="sep"></span>
      <span><?= e($car['city']) ?></span>
    </div>
    <?php if ($tags): ?><div class="chips"><?php foreach (array_slice($tags, 0, 3) as $t): ?><span class="chip"><?= e($t) ?></span><?php endforeach ?></div><?php endif ?>
  </div>
  <div class="car-service">
    <b><?= icon('shield') ?> کارشناسی‌شده</b>
    <?php if ($car['car_condition'] === 'new'): ?><span class="body-st ok"><?= e($car['warranty'] ? excerpt($car['warranty'], 22) : 'گارانتی رسمی') ?></span>
    <?php elseif (!$dmg): ?><span class="body-st ok">بدنه بدون رنگ</span>
    <?php else: ?><span class="body-st <?= $replaced ? 'bad' : 'warn' ?>"><?= fa(count($dmg)) ?> قطعه <?= $replaced ? 'رنگ / تعویض' : 'رنگ / صافکاری' ?></span><?php endif ?>
  </div>
  <div class="car-price">
    <span class="label">قیمت</span>
    <span>
      <?php if ($car['price_call'] || !$car['price']): ?><b class="amount">تماس بگیرید</b>
      <?php else: ?>
        <?php if ($car['old_price'] && $car['old_price'] > $car['price']): ?><s class="old"><?= fa(num($car['old_price'])) ?></s><?php endif ?>
        <b class="amount"><?= fa(num($car['price'])) ?></b> <span class="unit">تومان</span>
      <?php endif ?>
    </span>
  </div>
</article>
