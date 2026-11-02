<div class="container section acc">
  <?php require __DIR__ . '/_nav.php'; ?>
  <div class="acc-main">
    <div class="acc-hello"><div><h1>پرداخت‌های من</h1><p>همه تراکنش‌های بانکی سفارش‌هایتان، همراه با کد رهگیری.</p></div>
      <div class="acc-total"><span>جمع پرداخت‌های موفق</span><b><?= fa(num($paid)) ?> <small>تومان</small></b></div></div>
    <section class="card card-pad"><?php partial('account_tx_table', ['tx' => $tx, 'withOrder' => true]); ?></section>
    <p class="small muted mt-2">اگر مبلغی از حسابتان کسر شده ولی تراکنش «ناموفق» ثبت شده، بانک حداکثر تا ۷۲ ساعت آن را برمی‌گرداند. در غیر این صورت با کد رهگیری با ما تماس بگیرید.</p>
  </div>
</div>
