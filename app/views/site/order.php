<?php $st = $order['status']; $paid = in_array($st, ['paid', 'processing', 'shipped', 'completed'], true); $res = $_GET['r'] ?? ''; ?>
<div class="container section">
  <div class="receipt card card-pad">
    <?php if ($res === 'ok' || ($paid && !$res)): ?>
      <div class="big-ic ok"><?= icon('check') ?></div><h1>پرداخت با موفقیت انجام شد</h1>
      <p class="muted"><?= $order['type'] === 'car' ? 'خودرو برای شما رزرو شد. کارشناس فروش به‌زودی با شما تماس می‌گیرد.' : 'سفارش شما ثبت شد و به‌زودی ارسال می‌شود.' ?></p>
    <?php elseif ($res === 'fail' || $st === 'failed'): ?>
      <div class="big-ic bad"><?= icon('x') ?></div><h1>پرداخت ناموفق بود</h1>
      <p class="muted">در صورت کسر وجه، مبلغ حداکثر تا ۷۲ ساعت به حساب شما بازمی‌گردد. می‌توانید دوباره پرداخت کنید.</p>
    <?php else: ?>
      <div class="big-ic warn"><?= icon('clock') ?></div><h1>سفارش ثبت شد</h1>
      <p class="muted"><?= $gateways ? 'سفارش شما در انتظار پرداخت است.' : 'همکاران ما برای نهایی کردن سفارش با شما تماس می‌گیرند.' ?></p>
    <?php endif ?>

    <table class="kv" style="text-align:right">
      <tr><td>کد سفارش</td><td><b><?= fa($order['code']) ?></b></td></tr>
      <tr><td>وضعیت</td><td><?= Orders::statusBadge($st) ?></td></tr>
      <tr><td>تاریخ ثبت</td><td><?= jdate($order['created_at'], 'datetime') ?></td></tr>
      <tr><td>خریدار</td><td><?= e($order['customer_name']) ?> — <span class="ltr"><?= fa($order['mobile']) ?></span></td></tr>
      <?php if ($car): ?><tr><td>خودرو</td><td><a href="<?= url('car/' . $car['id']) ?>"><?= e(Cars::title($car)) ?></a></td></tr><?php endif ?>
      <?php foreach ($items as $it): ?><tr><td><?= e($it['title']) ?> × <?= fa($it['qty']) ?></td><td><?= fa(num($it['price'] * $it['qty'])) ?> تومان</td></tr><?php endforeach ?>
      <?php if ($order['shipping']): ?><tr><td>هزینه ارسال</td><td><?= fa(num($order['shipping'])) ?> تومان</td></tr><?php endif ?>
      <tr><td>مبلغ کل</td><td><b><?= fa(num($order['total'])) ?> تومان</b></td></tr>
      <tr><td><?= $order['pay_type'] === 'deposit' ? 'مبلغ بیعانه' : 'مبلغ قابل پرداخت' ?></td><td><?= fa(num($order['pay_amount'])) ?> تومان</td></tr>
      <tr><td>پرداخت شده</td><td><?= fa(num($order['paid_amount'])) ?> تومان</td></tr>
      <?php foreach ($tx as $t) if ($t['status'] === 'success'): ?><tr><td>کد رهگیری بانکی</td><td class="ltr" style="text-align:right"><?= e(fa($t['ref_id'])) ?></td></tr><?php endif ?>
    </table>

    <?php if ($gateways): ?>
      <form method="get" action="<?= url('pay/' . $order['code']) ?>" class="mt-3">
        <div class="gateways"><?php foreach ($gateways as $i => $g): ?><label class="gw"><input type="radio" name="gw" value="<?= $g['id'] ?>" <?= $i ? '' : 'checked' ?>><span><i><?= e(mb_substr($g['label'], 0, 1)) ?></i><?= e($g['label']) ?></span></label><?php endforeach ?></div>
        <button class="btn btn-accent btn-lg mt-2"><?= icon('lock') ?> پرداخت <?= fa(num($order['pay_amount'])) ?> تومان</button>
      </form>
    <?php endif ?>
    <p class="small muted mt-3">لینک پیگیری این سفارش را نگه دارید یا از بخش «پیگیری سفارش» با کد سفارش و موبایل استفاده کنید.</p>
    <a href="<?= url('/') ?>" class="btn btn-ghost">بازگشت به صفحه اصلی</a>
  </div>
</div>
