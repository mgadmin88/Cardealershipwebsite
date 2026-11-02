<?php $isCar = !empty($car); $bankInitial = fn($b) => mb_substr($b, 0, 1); ?>
<div class="container">
  <nav class="crumbs"><a href="<?= url('/') ?>">خانه</a><?= icon('chev-left') ?><?php if ($isCar): ?><a href="<?= url('car/' . $car['id']) ?>"><?= e(Cars::title($car)) ?></a><?php else: ?><a href="<?= url('cart') ?>">سبد خرید</a><?php endif ?><?= icon('chev-left') ?><span>تکمیل خرید</span></nav>
  <ol class="steps"><li class="done"><?= $isCar ? 'انتخاب خودرو' : 'سبد خرید' ?></li><li class="cur">اطلاعات خریدار</li><li>پرداخت</li><li>تکمیل</li></ol>
  <form class="cart-grid" method="post">
    <?= csrf_field() ?>
    <div>
      <section class="card card-pad mb-2">
        <h2 class="card-title">اطلاعات خریدار</h2>
        <div class="form-grid">
          <div class="field"><label>نام و نام خانوادگی *</label><input name="name" required value="<?= e(old('name')) ?>" autocomplete="name"></div>
          <div class="field"><label>شماره موبایل *</label><input name="mobile" required class="ltr" inputmode="tel" placeholder="09xxxxxxxxx" value="<?= e(old('mobile')) ?>" autocomplete="tel"></div>
          <div class="field"><label>کد ملی <?= $isCar ? '*' : '' ?></label><input name="national_code" class="ltr" inputmode="numeric" maxlength="10" value="<?= e(old('national_code')) ?>" <?= $isCar ? 'required' : '' ?>></div>
          <div class="field"><label>ایمیل</label><input type="email" name="email" class="ltr" value="<?= e(old('email')) ?>" autocomplete="email"></div>
          <?php if (!$isCar): ?>
            <div class="field"><label>استان</label><input name="province" value="<?= e(old('province')) ?>"></div>
            <div class="field"><label>شهر *</label><input name="city" required value="<?= e(old('city')) ?>"></div>
            <div class="field full"><label>آدرس کامل *</label><textarea name="address" required rows="2"><?= e(old('address')) ?></textarea></div>
            <div class="field"><label>کد پستی</label><input name="postal_code" class="ltr" inputmode="numeric" maxlength="10" value="<?= e(old('postal_code')) ?>"></div>
          <?php endif ?>
          <div class="field full"><label>توضیحات سفارش</label><textarea name="note" rows="2"><?= e(old('note')) ?></textarea></div>
        </div>
      </section>

      <?php if ($isCar): ?>
      <section class="card card-pad mb-2">
        <h2 class="card-title">نحوه پرداخت</h2>
        <div class="gateways" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr))">
          <label class="gw"><input type="radio" name="pay_type" value="deposit" checked><span><i><?= icon('lock') ?></i><b>رزرو با پرداخت بیعانه</b><small class="muted"><?= fa(num($deposit)) ?> تومان — مابقی هنگام تنظیم قرارداد</small></span></label>
          <?php if ($fullAllowed): ?><label class="gw"><input type="radio" name="pay_type" value="full"><span><i><?= icon('card') ?></i><b>پرداخت کامل آنلاین</b><small class="muted"><?= fa(num($car['price'])) ?> تومان</small></span></label><?php endif ?>
        </div>
        <p class="small muted mt-2">پس از پرداخت، خودرو برای شما رزرو می‌شود و کارشناس فروش برای هماهنگی بازدید نهایی و تنظیم قرارداد با شما تماس می‌گیرد. توجه: سقف تراکنش روزانه کارت‌های بانکی ممکن است محدود باشد.</p>
      </section>
      <?php endif ?>

      <section class="card card-pad">
        <h2 class="card-title">انتخاب درگاه پرداخت</h2>
        <?php if ($gateways): ?>
          <div class="gateways">
            <?php foreach ($gateways as $i => $g): ?>
              <label class="gw"><input type="radio" name="gateway" value="<?= $g['id'] ?>" <?= $i === 0 ? 'checked' : '' ?>><span><i><?= e($bankInitial($g['label'])) ?></i><?= e($g['label']) ?></span></label>
            <?php endforeach ?>
          </div>
        <?php else: ?>
          <div class="alert info"><?= icon('info') ?> پرداخت آنلاین در حال حاضر فعال نیست. سفارش شما ثبت می‌شود و همکاران ما برای نهایی کردن آن تماس می‌گیرند.</div>
        <?php endif ?>
        <label class="check mt-2"><input type="checkbox" name="terms" value="1" required> <span><a href="<?= url('page/terms') ?>" target="_blank">قوانین و مقررات</a> را مطالعه کرده و می‌پذیرم.</span></label>
      </section>
    </div>

    <aside class="card card-pad summary">
      <h2 class="card-title">خلاصه سفارش</h2>
      <?php if ($isCar): ?>
        <img src="<?= e(upload_url($images[0]['thumb'] ?? null)) ?>" alt="" style="border-radius:12px;aspect-ratio:16/10;object-fit:cover;width:100%;margin-bottom:12px">
        <b><?= e(Cars::title($car)) ?></b>
        <div class="muted small mb-2">کد خودرو <?= fa($car['code']) ?> — <?= e($car['city']) ?></div>
        <div class="row"><span>قیمت خودرو</span><b><?= fa(num($car['price'])) ?> تومان</b></div>
        <div class="row total"><span>پرداخت آنلاین (بیعانه)</span><span data-pay-amount><?= fa(num($deposit)) ?> تومان</span></div>
      <?php else: ?>
        <?php foreach ($items as $it): ?><div class="row"><span><?= e($it['part']['title']) ?> × <?= fa($it['qty']) ?></span><span><?= fa(num($it['line'])) ?></span></div><?php endforeach ?>
        <div class="row"><span>هزینه ارسال</span><b><?= $shipping ? fa(num($shipping)) . ' تومان' : 'رایگان' ?></b></div>
        <div class="row total"><span>مبلغ قابل پرداخت</span><span><?= fa(num($sum + $shipping)) ?> تومان</span></div>
      <?php endif ?>
      <?= honeypot() ?><?= Captcha::field(isset($car) ? 'buy' : 'checkout') ?>
      <button class="btn btn-accent btn-lg btn-block mt-2"><?= icon('lock') ?> <?= $gateways ? 'پرداخت امن' : 'ثبت سفارش' ?></button>
      <p class="small muted center mt-1 mb-0">پرداخت از طریق درگاه امن شاپرک</p>
    </aside>
  </form>
</div>
