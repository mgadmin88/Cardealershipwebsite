<div class="container">
  <nav class="crumbs"><a href="<?= url('/') ?>">خانه</a><?= icon('chev-left') ?><span>سبد خرید</span></nav>
  <ol class="steps"><li class="cur">سبد خرید</li><li>اطلاعات ارسال</li><li>پرداخت</li><li>تکمیل سفارش</li></ol>
  <?php if (!$items): ?>
    <div class="card empty"><?= icon('cart') ?><h3>سبد خرید شما خالی است</h3><a href="<?= url('parts') ?>" class="btn btn-accent">رفتن به فروشگاه</a></div>
  <?php else: ?>
  <div class="cart-grid">
    <form class="card card-pad" method="post" action="<?= url('cart/update') ?>"><?= csrf_field() ?>
      <h2 class="card-title">سبد خرید (<?= fa(count($items)) ?> کالا)</h2>
      <?php foreach ($items as $it): $p = $it['part']; ?>
        <div class="cart-item">
          <img src="<?= e(upload_url($p['thumb'] ?: $p['image'], 'img/part-placeholder.svg')) ?>" alt="">
          <div><a href="<?= url('part/' . $p['id']) ?>"><b><?= e($p['title']) ?></b></a><div class="muted small"><?= e($p['brand']) ?> — <?= fa(num($it['price'])) ?> تومان</div></div>
          <div class="flex">
            <div class="qty" data-qty><button type="button" data-dec><?= icon('minus') ?></button><input name="qty[<?= $p['id'] ?>]" value="<?= $it['qty'] ?>" data-max="<?= min(20, $p['stock']) ?>" aria-label="تعداد"><button type="button" data-inc><?= icon('plus') ?></button></div>
            <b class="nowrap"><?= fa(num($it['line'])) ?></b>
            <button class="icon-btn" name="remove" value="<?= $p['id'] ?>" aria-label="حذف"><?= icon('trash') ?></button>
          </div>
        </div>
      <?php endforeach ?>
      <div class="flex between mt-2"><a href="<?= url('parts') ?>" class="btn btn-ghost">ادامه خرید</a><button class="btn btn-soft"><?= icon('refresh') ?> بروزرسانی سبد</button></div>
    </form>
    <aside class="card card-pad summary">
      <div class="row"><span>جمع کالاها</span><b><?= fa(num($sum)) ?> تومان</b></div>
      <div class="row"><span>هزینه ارسال</span><b><?= $shipping ? fa(num($shipping)) . ' تومان' : 'رایگان' ?></b></div>
      <div class="row total"><span>مبلغ قابل پرداخت</span><span><?= fa(num($sum + $shipping)) ?> تومان</span></div>
      <a href="<?= url('checkout') ?>" class="btn btn-accent btn-lg btn-block mt-2">ادامه فرایند خرید <?= icon('arrow-left') ?></a>
    </aside>
  </div>
  <?php endif ?>
</div>
