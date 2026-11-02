<?php $has = $p['sale_price'] && $p['sale_price'] < $p['price']; $final = $has ? $p['sale_price'] : $p['price']; ?>
<div class="container">
  <nav class="crumbs"><a href="<?= url('/') ?>">خانه</a><?= icon('chev-left') ?><a href="<?= url('parts') ?>">فروشگاه</a><?= icon('chev-left') ?><?php if ($p['cat_name']): ?><a href="<?= url('parts', ['cat' => $p['category_id']]) ?>"><?= e($p['cat_name']) ?></a><?= icon('chev-left') ?><?php endif ?><span><?= e($p['title']) ?></span></nav>
  <div class="detail">
    <div class="card card-pad">
      <div class="gallery-main" style="aspect-ratio:1/1;max-width:520px;margin:auto;cursor:default"><img src="<?= e(upload_url($p['image'], 'img/part-placeholder.svg')) ?>" alt="<?= e($p['title']) ?>"></div>
      <h2 class="card-title mt-3">توضیحات محصول</h2>
      <div class="prose"><?= nl2p((string)$p['description']) ?></div>
      <table class="kv mt-2">
        <tr><td>برند</td><td><?= e($p['brand'] ?: '—') ?></td></tr>
        <tr><td>کد کالا</td><td class="ltr" style="text-align:right"><?= e($p['sku'] ?: '—') ?></td></tr>
        <tr><td>خودروهای سازگار</td><td><?= e($p['compatible'] ?: '—') ?></td></tr>
        <tr><td>نوع</td><td><?= $p['is_option'] ? 'آپشن / لوازم جانبی' : 'قطعه یدکی' ?></td></tr>
      </table>
    </div>
    <aside><div class="card side-card">
      <div class="sc-head"><h1><?= e($p['title']) ?></h1><div class="meta"><?= e($p['brand']) ?></div></div>
      <div class="svc-box"><b><?= icon('shield') ?> ضمانت اصالت کالا</b><span class="muted">ارسال به سراسر کشور<?php if ((int)setting('free_shipping_over')): ?> — ارسال رایگان برای خریدهای بالای <?= fa(price_words((int)setting('free_shipping_over'))) ?> تومان<?php endif ?></span></div>
      <div class="price-row"><span class="muted">قیمت</span><span><?php if ($has): ?><s class="muted small"><?= fa(num($p['price'])) ?></s> <?php endif ?><b class="amount"><?= fa(num($final)) ?></b> <span class="unit">تومان</span></span></div>
      <div class="side-actions">
        <?php if ($p['stock'] > 0): ?>
        <form method="post" action="<?= url('cart/add') ?>" class="flex"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $p['id'] ?>">
          <div class="qty" data-qty><button type="button" data-dec><?= icon('minus') ?></button><input name="qty" value="1" inputmode="numeric" aria-label="تعداد" data-max="<?= min(20, $p['stock']) ?>"><button type="button" data-inc><?= icon('plus') ?></button></div>
          <button class="btn btn-accent grow"><?= icon('cart') ?> افزودن به سبد</button></form>
        <p class="small muted center mb-0"><?= $p['stock'] < 5 ? 'تنها ' . fa($p['stock']) . ' عدد در انبار باقی مانده' : 'موجود در انبار' ?></p>
        <?php else: ?><div class="alert bad">ناموجود</div><?php endif ?>
      </div>
    </div></aside>
  </div>
  <?php if ($related): ?><section class="section"><div class="section-head"><div><h2>محصولات مرتبط</h2></div></div><div class="grid-parts"><?php foreach ($related as $r) partial('part_card', ['p' => $r]); ?></div></section><?php endif ?>
</div>
