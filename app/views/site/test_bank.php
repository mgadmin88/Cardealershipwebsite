<div class="container section"><div class="card card-pad" style="max-width:460px;margin:auto;text-align:center">
  <span class="badge warn">درگاه آزمایشی — بدون کسر وجه واقعی</span>
  <h1 class="mt-2" style="font-size:1.3em">شبیه‌ساز درگاه بانکی</h1>
  <p class="muted">پذیرنده: <?= e(setting('site_name')) ?></p>
  <div class="price-row" style="border-radius:12px"><span>مبلغ</span><b class="amount"><?= fa(num($tx['amount'])) ?> تومان</b></div>
  <form method="post" action="<?= url('payment/callback/' . $tx['id']) ?>" class="mt-3" style="display:grid;gap:10px">
    <input type="hidden" name="authority" value="<?= e($tx['authority']) ?>">
    <input type="hidden" name="sig" value="<?= e($sig) ?>">
    <button class="btn btn-ok btn-lg" name="status" value="OK"><?= icon('check') ?> پرداخت موفق</button>
    <button class="btn btn-danger" name="status" value="NOK"><?= icon('x') ?> انصراف از پرداخت</button>
  </form>
</div></div>
