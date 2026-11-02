<?php /** @var array $p */
$has = $p['sale_price'] && $p['sale_price'] < $p['price'];
$final = $has ? $p['sale_price'] : $p['price'];
?>
<article class="part-card reveal">
  <a href="<?= url('part/' . $p['id']) ?>" class="pm">
    <img src="<?= e(upload_url($p['thumb'] ?: $p['image'], 'img/part-placeholder.svg')) ?>" alt="<?= e($p['title']) ?>" loading="lazy">
    <?php if ($has): ?><span class="off"><?= fa(round(100 - $p['sale_price'] * 100 / $p['price'])) ?>٪</span><?php endif ?>
  </a>
  <div class="pb">
    <small class="muted"><?= e($p['brand']) ?></small>
    <h3><a href="<?= url('part/' . $p['id']) ?>"><?= e($p['title']) ?></a></h3>
    <div class="pp">
      <div><?php if ($has): ?><s><?= fa(num($p['price'])) ?></s><?php endif ?><b><?= fa(num($final)) ?></b> <span class="unit">تومان</span></div>
      <?php if ($p['stock'] > 0): ?>
      <form method="post" action="<?= url('cart/add') ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
        <button class="icon-btn" aria-label="افزودن به سبد"><?= icon('plus') ?></button></form>
      <?php else: ?><span class="badge bad">ناموجود</span><?php endif ?>
    </div>
  </div>
</article>
