<?php
$cond = $f['condition'] ?? '';
$arr = fn($k) => (array)($f[$k] ?? []);
$chips = [];
$labels = ['q' => 'جستجو', 'price_min' => 'از قیمت', 'price_max' => 'تا قیمت', 'year_min' => 'از سال', 'year_max' => 'تا سال', 'km_max' => 'تا کارکرد'];
foreach ($labels as $k => $l) if (!empty($f[$k])) $chips[$k] = $l . ': ' . (str_starts_with($k, 'price') ? price_words((int)$f[$k]) : $f[$k]);
if (!empty($f['brand'])) foreach ($brands as $b) if ($b['id'] == $f['brand']) $chips['brand'] = $b['name_fa'];
if (!empty($f['installment'])) $chips['installment'] = 'اقساطی';
if (!empty($f['ins_body'])) $chips['ins_body'] = 'بیمه بدنه';
if (!empty($f['no_paint'])) $chips['no_paint'] = 'بدون رنگ';
if ($cond) $chips['condition'] = Catalog::CONDITION[$cond] ?? '';
?>
<div class="container">
  <nav class="crumbs" aria-label="مسیر"><a href="<?= url('/') ?>">خانه</a><?= icon('chev-left') ?><span><?= e($title) ?></span></nav>

  <div class="listing">
    <form class="card filters" id="filters" method="get" action="<?= url('cars') ?>">
      <div class="fh"><h3>فیلترها</h3><button type="button" class="icon-btn filters-btn" data-close-filters aria-label="بستن"><?= icon('x') ?></button></div>
      <?php if (!empty($f['q'])): ?><input type="hidden" name="q" value="<?= e($f['q']) ?>"><?php endif ?>
      <?php if (!empty($f['sort'])): ?><input type="hidden" name="sort" value="<?= e($f['sort']) ?>"><?php endif ?>

      <?php if (module('used_cars') && module('new_cars')): ?>
      <details class="fgroup" open><summary>وضعیت خودرو <?= icon('chev-down') ?></summary>
        <div class="fbody fopts">
          <label><input type="radio" name="condition" value="" <?= checked(!$cond) ?>><span>همه</span></label>
          <?php foreach (Catalog::CONDITION as $k => $v): ?><label><input type="radio" name="condition" value="<?= $k ?>" <?= checked($cond === $k) ?>><span><?= $v ?></span></label><?php endforeach ?>
        </div></details>
      <?php endif ?>

      <details class="fgroup" <?= !empty($f['brand']) ? 'open' : '' ?>><summary>برند و مدل <?= icon('chev-down') ?></summary>
        <div class="fbody">
          <select name="brand" data-brand-select><option value="">همه برندها</option>
            <optgroup label="داخلی"><?php foreach ($brands as $b) if ($b['origin'] === 'domestic'): ?><option value="<?= $b['id'] ?>"<?= selected($b['id'], $f['brand'] ?? '') ?>><?= e($b['name_fa']) ?></option><?php endif ?></optgroup>
            <optgroup label="خارجی"><?php foreach ($brands as $b) if ($b['origin'] === 'foreign'): ?><option value="<?= $b['id'] ?>"<?= selected($b['id'], $f['brand'] ?? '') ?>><?= e($b['name_fa']) ?></option><?php endif ?></optgroup>
          </select>
          <select name="model" data-model-select data-selected="<?= e($f['model'] ?? '') ?>"><option value="">همه مدل‌ها</option>
            <?php foreach ($models as $m): ?><option value="<?= $m['id'] ?>"<?= selected($m['id'], $f['model'] ?? '') ?>><?= e($m['name_fa']) ?></option><?php endforeach ?></select>
          <div class="fopts">
            <label><input type="radio" name="origin" value="" <?= checked(empty($f['origin'])) ?>><span>همه</span></label>
            <label><input type="radio" name="origin" value="domestic" <?= checked(($f['origin'] ?? '') === 'domestic') ?>><span>داخلی</span></label>
            <label><input type="radio" name="origin" value="foreign" <?= checked(($f['origin'] ?? '') === 'foreign') ?>><span>خارجی</span></label>
          </div>
        </div></details>

      <?php if ($cities): ?>
      <details class="fgroup" <?= $arr('city') ? 'open' : '' ?>><summary>شهر <?= icon('chev-down') ?></summary>
        <div class="fbody filter-scroll"><?php foreach ($cities as $c): ?>
          <label class="check"><input type="checkbox" name="city[]" value="<?= e($c['city']) ?>" <?= checked(in_array($c['city'], $arr('city'), true)) ?>> <?= e($c['city']) ?> <small class="muted">(<?= fa($c['n']) ?>)</small></label>
        <?php endforeach ?></div></details>
      <?php endif ?>

      <?php if (module('installment')): ?>
      <div class="frow"><label for="f-inst">امکان خرید اقساطی</label><label class="switch"><input id="f-inst" type="checkbox" name="installment" value="1" <?= checked(!empty($f['installment'])) ?> data-autosubmit><i></i></label></div>
      <?php endif ?>
      <div class="frow"><label for="f-avail">فقط خودروهای موجود</label><label class="switch"><input id="f-avail" type="checkbox" name="available" value="1" <?= checked(!empty($f['available'])) ?> data-autosubmit><i></i></label></div>

      <details class="fgroup" <?= !empty($f['price_min']) || !empty($f['price_max']) ? 'open' : '' ?>><summary>بازه قیمت (تومان) <?= icon('chev-down') ?></summary>
        <div class="fbody frange">
          <input type="text" inputmode="numeric" name="price_min" placeholder="از" value="<?= e($f['price_min'] ?? '') ?>" data-money>
          <input type="text" inputmode="numeric" name="price_max" placeholder="تا" value="<?= e($f['price_max'] ?? '') ?>" data-money>
        </div></details>

      <details class="fgroup" <?= !empty($f['year_min']) || !empty($f['year_max']) ? 'open' : '' ?>><summary>سال ساخت <?= icon('chev-down') ?></summary>
        <div class="fbody frange">
          <select name="year_min"><option value="">از</option><?php for ($y = jyear_now(); $y >= 1370; $y--): ?><option value="<?= $y ?>"<?= selected($y, $f['year_min'] ?? '') ?>><?= fa($y) ?></option><?php endfor ?></select>
          <select name="year_max"><option value="">تا</option><?php for ($y = jyear_now(); $y >= 1370; $y--): ?><option value="<?= $y ?>"<?= selected($y, $f['year_max'] ?? '') ?>><?= fa($y) ?></option><?php endfor ?></select>
        </div></details>

      <details class="fgroup" <?= !empty($f['km_max']) ? 'open' : '' ?>><summary>کارکرد (کیلومتر) <?= icon('chev-down') ?></summary>
        <div class="fbody fopts">
          <?php foreach ([0 => 'همه', 20000 => 'تا ۲۰ هزار', 50000 => 'تا ۵۰ هزار', 100000 => 'تا ۱۰۰ هزار', 150000 => 'تا ۱۵۰ هزار'] as $v => $l): ?>
            <label><input type="radio" name="km_max" value="<?= $v ?: '' ?>" <?= checked((int)($f['km_max'] ?? 0) === $v) ?>><span><?= $l ?></span></label>
          <?php endforeach ?></div></details>

      <details class="fgroup" <?= $arr('body') ? 'open' : '' ?>><summary>نوع بدنه <?= icon('chev-down') ?></summary>
        <div class="fbody fopts"><?php foreach (Catalog::BODY_TYPES as $k => $v): ?>
          <label><input type="checkbox" name="body[]" value="<?= $k ?>" <?= checked(in_array($k, $arr('body'), true)) ?>><span><?= $v ?></span></label>
        <?php endforeach ?></div></details>

      <details class="fgroup" <?= $arr('gearbox') ? 'open' : '' ?>><summary>گیربکس <?= icon('chev-down') ?></summary>
        <div class="fbody fopts"><?php foreach (Catalog::GEARBOX as $k => $v): ?>
          <label><input type="checkbox" name="gearbox[]" value="<?= $k ?>" <?= checked(in_array($k, $arr('gearbox'), true)) ?>><span><?= $v ?></span></label>
        <?php endforeach ?></div></details>

      <details class="fgroup" <?= $arr('fuel') ? 'open' : '' ?>><summary>نوع سوخت <?= icon('chev-down') ?></summary>
        <div class="fbody fopts"><?php foreach (Catalog::FUEL as $k => $v): ?>
          <label><input type="checkbox" name="fuel[]" value="<?= $k ?>" <?= checked(in_array($k, $arr('fuel'), true)) ?>><span><?= $v ?></span></label>
        <?php endforeach ?></div></details>

      <details class="fgroup" <?= $arr('color') ? 'open' : '' ?>><summary>رنگ <?= icon('chev-down') ?></summary>
        <div class="fbody fopts"><?php foreach (Catalog::COLORS as $k => $hex): ?>
          <label><input type="checkbox" name="color[]" value="<?= e($k) ?>" <?= checked(in_array($k, $arr('color'), true)) ?>><span><i style="display:inline-block;width:10px;height:10px;border-radius:50%;background:<?= $hex ?>;border:1px solid #0003"></i> <?= e($k) ?></span></label>
        <?php endforeach ?></div></details>

      <div class="frow"><label for="f-np">بدون رنگ‌شدگی</label><label class="switch"><input id="f-np" type="checkbox" name="no_paint" value="1" <?= checked(!empty($f['no_paint'])) ?> data-autosubmit><i></i></label></div>
      <div class="frow"><label for="f-ib">دارای بیمه بدنه</label><label class="switch"><input id="f-ib" type="checkbox" name="ins_body" value="1" <?= checked(!empty($f['ins_body'])) ?> data-autosubmit><i></i></label></div>

      <div style="padding:16px 20px;display:grid;gap:8px;grid-template-columns:1fr auto">
        <button class="btn btn-accent">اعمال فیلتر</button>
        <a href="<?= url('cars', $cond ? ['condition' => $cond] : []) ?>" class="btn btn-ghost">حذف</a>
      </div>
    </form>

    <div>
      <form class="toolbar" method="get" action="<?= url('cars') ?>">
        <?php foreach ($f as $k => $v) if (!in_array($k, ['q', 'sort', 'page'], true)): foreach ((array)$v as $vv): ?><input type="hidden" name="<?= e($k) . (is_array($v) ? '[]' : '') ?>" value="<?= e($vv) ?>"><?php endforeach; endif ?>
        <button type="button" class="btn btn-ghost filters-btn" data-open-filters><?= icon('filter') ?> فیلترها</button>
        <div class="input-icon search"><?= icon('search') ?><input type="search" name="q" value="<?= e($f['q'] ?? '') ?>" placeholder="جستجوی برند، مدل، سال یا کد خودرو…" aria-label="جستجو"></div>
        <select name="sort" data-autosubmit aria-label="مرتب‌سازی"><?php foreach (Cars::SORTS as $k => [$l]): ?><option value="<?= $k ?>"<?= selected($k, $f['sort'] ?? 'newest') ?>><?= $l ?></option><?php endforeach ?></select>
      </form>

      <div class="flex between wrap mb-2">
        <h1 style="font-size:1.25em;margin:0"><?= e($title) ?></h1>
        <span class="result-count"><?= fa(num($pg['total'])) ?> خودرو یافت شد</span>
      </div>
      <?php if ($chips): ?><div class="active-filters"><?php foreach ($chips as $k => $l): ?><a href="<?= e(query_with([$k => null, 'page' => null] + ($k === 'brand' ? ['model' => null] : []))) ?>"><?= e(fa($l)) ?> <?= icon('x') ?></a><?php endforeach ?></div><?php endif ?>

      <?php if ($cars): ?>
        <div class="grid-cars"><?php foreach ($cars as $car) partial('car_card', ['car' => $car]); ?></div>
        <?php if ($pg['pages'] > 1): ?>
        <nav class="pagination" aria-label="صفحه‌بندی">
          <?php if ($pg['page'] > 1): ?><a href="<?= e(query_with(['page' => $pg['page'] - 1])) ?>" aria-label="قبلی"><?= icon('chev-right') ?></a><?php endif ?>
          <?php for ($i = max(1, $pg['page'] - 2); $i <= min($pg['pages'], $pg['page'] + 2); $i++): ?>
            <?= $i === $pg['page'] ? '<span class="cur">' . fa($i) . '</span>' : '<a href="' . e(query_with(['page' => $i])) . '">' . fa($i) . '</a>' ?>
          <?php endfor ?>
          <?php if ($pg['page'] < $pg['pages']): ?><a href="<?= e(query_with(['page' => $pg['page'] + 1])) ?>" aria-label="بعدی"><?= icon('chev-left') ?></a><?php endif ?>
        </nav>
        <?php endif ?>
      <?php else: ?>
        <div class="card empty"><?= icon('car') ?><h3>خودرویی با این مشخصات یافت نشد</h3><p>فیلترها را تغییر دهید یا درخواست خود را برای کارشناسان ما ثبت کنید.</p>
          <a href="<?= url('cars') ?>" class="btn btn-soft">حذف همه فیلترها</a></div>
      <?php endif ?>
    </div>
  </div>
</div>
