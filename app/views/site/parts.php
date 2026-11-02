<div class="page-hero"><div class="container"><h1>قطعات و لوازم جانبی خودرو</h1><p>قطعات اصلی، آپشن‌ها و لوازم جانبی با ضمانت اصالت و ارسال به سراسر کشور</p></div></div>
<div class="container">
  <div class="listing">
    <aside class="card filters" id="filters">
      <div class="fh"><h3>دسته‌بندی‌ها</h3><button type="button" class="icon-btn filters-btn" data-close-filters aria-label="بستن"><?= icon('x') ?></button></div>
      <div class="fbody" style="padding:6px 14px 18px;display:grid;gap:4px">
        <a href="<?= url('parts') ?>" class="btn <?= !input_int('cat') ? 'btn-soft' : 'btn-ghost' ?>" style="justify-content:flex-start;border:0">همه محصولات</a>
        <?php foreach ($cats as $c): ?>
          <a href="<?= url('parts', ['cat' => $c['id']]) ?>" class="btn <?= input_int('cat') === (int)$c['id'] ? 'btn-soft' : 'btn-ghost' ?>" style="justify-content:space-between;border:0"><span class="flex"><?= icon($c['icon'] ?: 'box') ?><?= e($c['name']) ?></span><small class="muted"><?= fa($c['n']) ?></small></a>
        <?php endforeach ?>
      </div>
    </aside>
    <div>
      <form class="toolbar" method="get">
        <?php if (input_int('cat')): ?><input type="hidden" name="cat" value="<?= input_int('cat') ?>"><?php endif ?>
        <button type="button" class="btn btn-ghost filters-btn" data-open-filters><?= icon('filter') ?> دسته‌ها</button>
        <div class="input-icon search"><?= icon('search') ?><input type="search" name="q" value="<?= e(input('q')) ?>" placeholder="نام قطعه، برند، خودروی سازگار یا کد کالا…"></div>
        <select name="type" data-autosubmit><option value="">همه</option><option value="part"<?= selected('part', input('type')) ?>>قطعات یدکی</option><option value="option"<?= selected('option', input('type')) ?>>آپشن و لوازم جانبی</option></select>
        <select name="sort" data-autosubmit><option value="">پیشنهادی</option><option value="cheap"<?= selected('cheap', input('sort')) ?>>ارزان‌ترین</option><option value="expensive"<?= selected('expensive', input('sort')) ?>>گران‌ترین</option></select>
      </form>
      <p class="result-count"><?= fa($pg['total']) ?> محصول</p>
      <?php if ($parts): ?>
        <div class="grid-parts"><?php foreach ($parts as $p) partial('part_card', ['p' => $p]); ?></div>
        <?php if ($pg['pages'] > 1): ?><nav class="pagination"><?php for ($i = 1; $i <= $pg['pages']; $i++): ?><?= $i === $pg['page'] ? '<span class="cur">' . fa($i) . '</span>' : '<a href="' . e(query_with(['page' => $i])) . '">' . fa($i) . '</a>' ?><?php endfor ?></nav><?php endif ?>
      <?php else: ?><div class="card empty"><?= icon('box') ?><h3>محصولی یافت نشد</h3></div><?php endif ?>
    </div>
  </div>
</div>
