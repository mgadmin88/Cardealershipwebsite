<?php
$tags = array_filter(array_map('trim', explode(',', (string)$car['tags'])));
$options = array_filter(array_map('trim', preg_split('/\r?\n|,/', (string)$car['options'])));
$damage = array_filter($car['damage'], fn($s) => $s && $s !== 'ok');
$dcount = Catalog::partsCount($car['damage']);
$insp = Catalog::inspectionSummary($car['inspection']);
$isNew = $car['car_condition'] === 'new';
$canBuy = $car['status'] === 'available' && module('car_reserve') && module('online_payment') && !$car['price_call'] && $car['price'] > 0;
$yn = fn($v) => $v ? 'دارد' : 'ندارد';
$specs = array_filter($car['specs']);
$specLabels = ['engine' => 'موتور', 'power' => 'قدرت', 'torque' => 'گشتاور', 'consumption' => 'مصرف سوخت', 'drive' => 'سیستم انتقال قدرت', 'seats' => 'ظرفیت', 'accel' => 'شتاب ۰ تا ۱۰۰', 'tank' => 'حجم باک'];
?>
<div class="container">
  <nav class="crumbs" aria-label="مسیر"><a href="<?= url('/') ?>">خانه</a><?= icon('chev-left') ?>
    <a href="<?= url('cars', ['condition' => $car['car_condition']]) ?>"><?= Catalog::CONDITION[$car['car_condition']] ?></a><?= icon('chev-left') ?>
    <a href="<?= url('cars', ['brand' => $car['brand_id']]) ?>"><?= e($car['brand_name']) ?></a><?= icon('chev-left') ?><span><?= e($car['model_name']) ?></span></nav>

  <div class="detail">
    <div>
      <!-- gallery -->
      <div class="gallery" data-gallery>
        <div class="gallery-main" data-open-lightbox>
          <img src="<?= e(upload_url($images[0]['path'] ?? null)) ?>" alt="<?= e($title) ?>" data-main fetchpriority="high">
          <span class="code">کد خودرو: <?= fa($car['code']) ?></span>
          <?php if (count($images) > 1): ?>
          <button class="gnav gprev" data-gprev aria-label="قبلی"><?= icon('chev-right') ?></button>
          <button class="gnav gnext" data-gnext aria-label="بعدی"><?= icon('chev-left') ?></button>
          <?php endif ?>
        </div>
        <?php if (count($images) > 1): ?>
        <div class="thumbs">
          <?php foreach (array_slice($images, 0, 4) as $i => $img): ?>
            <button class="<?= $i === 0 ? 'on' : '' ?>" data-thumb="<?= $i ?>" aria-label="تصویر <?= $i + 1 ?>">
              <img src="<?= e(upload_url($img['thumb'] ?: $img['path'])) ?>" alt="" loading="lazy">
              <?php if ($i === 3 && count($images) > 4): ?><span class="more"><?= icon('images') ?>&nbsp;<?= fa(count($images) - 4) ?>+</span><?php endif ?>
            </button>
          <?php endforeach ?>
        </div>
        <?php endif ?>
        <script type="application/json" data-images><?= json_encode(array_map(fn($i) => upload_url($i['path']), $images), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
      </div>

      <nav class="detail-tabs" data-spy>
        <a href="#details" class="on">جزئیات خودرو</a>
        <?php if (module('damage_map') && !$isNew): ?><a href="#body">وضعیت بدنه</a><?php endif ?>
        <?php if (module('inspection') && $insp): ?><a href="#inspection">گزارش کارشناسی</a><?php endif ?>
        <?php if ($options): ?><a href="#options">امکانات</a><?php endif ?>
        <?php if ($specs): ?><a href="#specs">مشخصات فنی</a><?php endif ?>
        <?php if (module('installment') && $car['installment'] && $car['price']): ?><a href="#installment">محاسبه اقساط</a><?php endif ?>
        <?php if ($faqs): ?><a href="#faq">سوالات متداول</a><?php endif ?>
      </nav>

      <section class="card card-pad dsec" id="details">
        <h2 class="card-title">جزئیات خودرو</h2>
        <div class="specs-grid">
          <div class="spec"><span class="si"><?= icon('gauge') ?></span><div><small>کارکرد</small><b><?= $isNew ? 'صفر کیلومتر' : fa(num($car['mileage'])) . ' کیلومتر' ?></b></div></div>
          <div class="spec"><span class="si"><?= icon('calendar') ?></span><div><small>سال ساخت</small><b><?= fa($car['year']) ?></b></div></div>
          <div class="spec"><span class="si"><?= icon('gearbox') ?></span><div><small>گیربکس</small><b><?= e(Catalog::GEARBOX[$car['gearbox']] ?? '—') ?></b></div></div>
          <div class="spec"><span class="si"><?= icon('drop') ?></span><div><small>سوخت</small><b><?= e(Catalog::FUEL[$car['fuel']] ?? '—') ?></b></div></div>
          <div class="spec"><span class="si"><?= icon('color') ?></span><div><small>رنگ بدنه / داخلی</small><b><?= e($car['color'] ?: '—') ?> / <?= e($car['interior_color'] ?: '—') ?></b></div></div>
          <div class="spec"><span class="si"><?= icon('car') ?></span><div><small>نوع بدنه</small><b><?= e(Catalog::BODY_TYPES[$car['body_type']] ?? '—') ?></b></div></div>
          <div class="spec"><span class="si"><?= icon('wrench') ?></span><div><small>معاینه فنی</small><b><?= $yn($car['tech_inspection']) ?></b></div></div>
          <div class="spec"><span class="si"><?= icon('doc') ?></span><div><small>بیمه شخص ثالث</small><b><?= $car['ins_third'] ? 'دارد' . ($car['ins_third_until'] ? ' (' . fa($car['ins_third_until']) . ')' : '') : 'ندارد' ?></b></div></div>
          <div class="spec"><span class="si"><?= icon('shield') ?></span><div><small>بیمه بدنه</small><b><?= $car['ins_body'] ? 'دارد' . ($car['ins_body_until'] ? ' (' . fa($car['ins_body_until']) . ')' : '') : 'ندارد' ?></b></div></div>
          <?php if ($car['ins_discount_years'] !== null): ?><div class="spec"><span class="si"><?= icon('tag') ?></span><div><small>تخفیف بیمه</small><b><?= $car['ins_discount_years'] ? fa($car['ins_discount_years']) . ' سال' : 'ندارد' ?></b></div></div><?php endif ?>
          <div class="spec"><span class="si"><?= icon('doc') ?></span><div><small>نوع سند</small><b><?= e(Catalog::DOC_TYPES[$car['doc_type']] ?? '—') ?></b></div></div>
          <div class="spec"><span class="si"><?= icon('card') ?></span><div><small>کارت سوخت</small><b><?= $yn($car['fuel_card']) ?></b></div></div>
          <div class="spec"><span class="si"><?= icon('award') ?></span><div><small>گارانتی</small><b><?= e($car['warranty'] ?: 'ندارد') ?></b></div></div>
          <?php if (!$isNew): ?>
          <div class="spec"><span class="si"><?= icon('shield') ?></span><div><small>وضعیت شاسی</small><b><?= e(Catalog::CHASSIS[$car['chassis']] ?? '—') ?></b></div></div>
          <div class="spec"><span class="si"><?= icon('engine') ?></span><div><small>وضعیت موتور</small><b><?= e($car['engine_status'] ?: '—') ?></b></div></div>
          <?php endif ?>
          <div class="spec"><span class="si"><?= icon('pin') ?></span><div><small>محل بازدید</small><b><?= e($car['city']) ?></b></div></div>
        </div>
        <?php if ($car['description']): ?><div class="prose mt-3"><?= nl2p((string)$car['description']) ?></div><?php endif ?>
      </section>

      <?php if (module('damage_map') && !$isNew):
        $pointsJs = array_map(fn($p) => ['part' => Catalog::PARTS[$p['part']] ?? $p['part'], 'type' => $p['type'], 'label' => Catalog::DAMAGE[$p['type']][0] ?? '',
            'color' => Catalog::DAMAGE[$p['type']][1] ?? '#999', 'note' => (string)$p['note'], 'photo' => $p['photo'] ? url('uploads/' . $p['photo']) : ''], $points);
        $tally = [];
        foreach ($points as $p) $tally[$p['type']] = ($tally[$p['type']] ?? 0) + 1;
        foreach ($damage as $pp => $st) if (!array_filter($points, fn($x) => $x['part'] === $pp)) $tally[$st] = ($tally[$st] ?? 0) + 1;
      ?>
      <section class="card card-pad dsec" id="body">
        <h2 class="card-title">وضعیت بدنه و رنگ</h2>
        <div class="damage-wrap" data-damage-view data-points="<?= e(json_encode($pointsJs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>">
          <div class="damage-stage">
            <?php partial('damage_map', ['damage' => $car['damage'], 'points' => $points]) ?>
            <?php if ($points): ?><p class="damage-hint"><?= icon('info') ?> روی هر نقطه کلیک کنید تا تصویر و توضیح آن نمایش داده شود.</p><?php endif ?>
          </div>
          <div class="damage-side">
            <ul class="legend">
              <?php foreach (Catalog::DAMAGE as $k => [$l, $c]): if ($k === 'ok') continue; ?>
                <li class="<?= empty($tally[$k]) ? 'zero' : '' ?>"><?= Catalog::damageIcon($k, 22) ?><span><?= $l ?></span><?php if (!empty($tally[$k])): ?><b><?= fa($tally[$k]) ?></b><?php endif ?></li>
              <?php endforeach ?>
            </ul>
            <?php if (!$damage && !$points): ?><p class="badge ok mt-2"><?= icon('check') ?> بدنه بدون رنگ‌شدگی و آسیب</p><?php endif ?>
          </div>
        </div>
        <?php if ($points): ?>
          <h3 class="dmg-sub">نقاط ثبت‌شده توسط کارشناس <span class="muted">(<?= fa(count($points)) ?> مورد)</span></h3>
          <div class="dmg-cards">
            <?php foreach ($points as $i => $p): ?>
              <button type="button" class="dmg-card" data-open-pin="<?= $i ?>">
                <span class="dmg-thumb"><?php if ($p['thumb'] || $p['photo']): ?><img src="<?= e(url('uploads/' . ($p['thumb'] ?: $p['photo']))) ?>" alt="" loading="lazy"><?php else: ?><?= Catalog::damageIcon($p['type'], 34) ?><?php endif ?></span>
                <span class="dmg-meta"><b><?= e(Catalog::PARTS[$p['part']] ?? '') ?></b><span class="dmg-type" style="--c:<?= Catalog::DAMAGE[$p['type']][1] ?>"><?= e(Catalog::DAMAGE[$p['type']][0]) ?></span>
                  <?php if ($p['note']): ?><small><?= e($p['note']) ?></small><?php endif ?></span>
              </button>
            <?php endforeach ?>
          </div>
        <?php elseif ($damage): ?>
          <div class="damage-list"><?php foreach ($damage as $pp => $st): ?><span class="chip"><?= Catalog::damageIcon($st, 18) ?><?= e(Catalog::PARTS[$pp] ?? $pp) ?>: <?= e(Catalog::DAMAGE[$st][0] ?? '') ?></span><?php endforeach ?></div>
        <?php endif ?>
      </section>

      <div class="dmg-modal" id="dmgModal" hidden role="dialog" aria-modal="true" aria-label="جزئیات نقص بدنه">
        <div class="dmg-modal-box">
          <button type="button" class="dmg-x" data-dmg-close aria-label="بستن"><?= icon('x') ?></button>
          <div class="dmg-photo"><img alt=""><div class="dmg-nophoto"><?= icon('image') ?><span>برای این نقطه تصویری ثبت نشده است</span></div></div>
          <div class="dmg-info">
            <div class="dmg-head"><span class="dmg-ico-slot"></span><div><b class="dmg-part"></b><span class="dmg-label"></span></div></div>
            <p class="dmg-note"></p>
            <div class="dmg-nav"><button type="button" class="btn btn-ghost btn-sm" data-dmg-prev><?= icon('chev-right') ?> قبلی</button><span class="dmg-count muted small"></span><button type="button" class="btn btn-ghost btn-sm" data-dmg-next>بعدی <?= icon('chev-left') ?></button></div>
          </div>
        </div>
      </div>
      <?php endif ?>

      <?php if (module('inspection') && $insp): ?>
      <section class="card card-pad dsec" id="inspection">
        <h2 class="card-title">گزارش کارشناسی</h2>
        <div class="insp-legend"><span><span class="dot-ok"><?= icon('check') ?></span> مورد تایید</span><span><span class="dot-bad"><?= icon('x') ?></span> دارای نقص</span></div>
        <div class="insp-grid">
          <?php foreach ($insp as $sec): ?>
          <details class="insp">
            <summary>
              <div class="it"><b><?= e($sec['title']) ?></b>
                <span class="cnt"><span><span class="dot-ok"><?= icon('check') ?></span> <?= fa($sec['ok']) ?></span><?php if ($sec['fail']): ?><span><span class="dot-bad"><?= icon('x') ?></span> <?= fa($sec['fail']) ?></span><?php endif ?></span></div>
              <span class="score"><?= icon('star') ?><?= fa(number_format($sec['score'], 1)) ?></span>
              <span class="tg"><?= icon('chev-down') ?></span>
            </summary>
            <ul><?php foreach ($sec['rows'] as $r): ?>
              <li><?= $r['s'] === 'ok' ? '<span class="dot-ok">' . icon('check') . '</span>' : ($r['s'] === 'fail' ? '<span class="dot-bad">' . icon('x') . '</span>' : '<span class="dot-ok" style="opacity:.4">–</span>') ?>
                <?= e($r['label']) ?><?php if ($r['n']): ?><small><?= e($r['n']) ?></small><?php endif ?></li>
            <?php endforeach ?></ul>
          </details>
          <?php endforeach ?>
        </div>
      </section>
      <?php endif ?>

      <?php if ($options): ?>
      <section class="card card-pad dsec" id="options">
        <h2 class="card-title">امکانات و آپشن‌ها</h2>
        <ul class="options-list"><?php foreach ($options as $o): ?><li><?= icon('check') ?><?= e($o) ?></li><?php endforeach ?></ul>
      </section>
      <?php endif ?>

      <?php if ($specs): ?>
      <section class="card card-pad dsec" id="specs">
        <h2 class="card-title">مشخصات فنی</h2>
        <table class="kv"><?php foreach ($specs as $k => $v): ?><tr><td><?= e($specLabels[$k] ?? $k) ?></td><td><?= e($v) ?></td></tr><?php endforeach ?></table>
      </section>
      <?php endif ?>

      <?php if (module('installment') && $car['installment'] && $car['price']): ?>
      <section class="card card-pad dsec" id="installment">
        <h2 class="card-title">ماشین‌حساب اقساط</h2>
        <?php if ($car['installment_note']): ?><p class="muted"><?= e($car['installment_note']) ?></p><?php endif ?>
        <div class="calc" data-calc data-price="<?= (int)$car['price'] ?>" data-rate="<?= (float)setting('installment_rate', 23) ?>" data-min-down="<?= (int)setting('installment_min_down', 30) ?>">
          <div>
            <div class="field"><label>پیش‌پرداخت: <b data-down-pct></b>٪</label><input type="range" min="<?= (int)setting('installment_min_down', 30) ?>" max="90" step="5" value="<?= max(40, (int)setting('installment_min_down', 30)) ?>" data-down></div>
            <div class="field"><label>تعداد اقساط: <b data-months-v></b> ماه</label><input type="range" min="6" max="<?= (int)setting('installment_max_months', 36) ?>" step="6" value="12" data-months></div>
            <p class="small muted">نرخ سود سالانه: <?= fa(setting('installment_rate')) ?>٪ — محاسبه تقریبی است و شرایط نهایی توسط کارشناس اعلام می‌شود.</p>
          </div>
          <div class="calc-out">
            <div><span>مبلغ پیش‌پرداخت</span><b data-o-down></b></div>
            <div><span>مبلغ تسهیلات</span><b data-o-loan></b></div>
            <div class="big"><span>مبلغ هر قسط</span><b data-o-inst></b></div>
            <div><span>مجموع پرداختی</span><b data-o-total></b></div>
            <button class="btn btn-accent btn-block" data-modal-open="m-installment">ثبت درخواست خرید اقساطی</button>
          </div>
        </div>
      </section>
      <?php endif ?>

      <?php if ($faqs): ?>
      <section class="faq-box dsec" id="faq">
        <div class="faq-head">سوالات متداول</div>
        <div class="faq"><?php foreach ($faqs as $q): ?><details><summary><?= e($q['question']) ?><span class="tg"><?= icon('chev-down') ?></span></summary><div class="ans"><?= nl2p((string)$q['answer']) ?></div></details><?php endforeach ?></div>
      </section>
      <?php endif ?>
    </div>

    <!-- sticky side -->
    <aside>
      <div class="card side-card">
        <div class="sc-head">
          <div class="flex between">
            <h1><?= e($car['brand_name']) ?><span class="sep"></span><?= e($car['model_name']) ?><span class="sep"></span><?= fa($car['year']) ?></h1>
            <button class="icon-btn" data-share aria-label="اشتراک‌گذاری"><?= icon('share') ?></button>
          </div>
          <div class="meta"><span><?= e(Catalog::GEARBOX[$car['gearbox']] ?? '') ?></span>|<span><?= $isNew ? 'صفر کیلومتر' : fa(num($car['mileage'])) . ' کیلومتر' ?></span>|<span><?= e($car['city']) ?></span><?php if ($car['trim_name']): ?>|<span><?= e($car['trim_name']) ?></span><?php endif ?></div>
          <?php if ($tags): ?><div class="chips mt-1"><?php foreach ($tags as $t): ?><span class="chip"><?= e($t) ?></span><?php endforeach ?></div><?php endif ?>
        </div>
        <div class="svc-box">
          <b><?= icon('shield') ?> خدمات <?= e(setting('site_name')) ?></b>
          <span class="muted"><?= e($car['warranty'] ?: 'کارشناسی کامل فنی و بدنه') ?> | گزارش کارشناسی</span>
        </div>
        <div class="price-row">
          <span class="muted">قیمت</span>
          <?php if ($car['price_call'] || !$car['price']): ?><b class="amount">تماس بگیرید</b>
          <?php else: ?><span><b class="amount"><?= fa(num($car['price'])) ?></b> <span class="unit">تومان</span></span><?php endif ?>
        </div>
        <div class="side-actions">
          <?php if ($car['status'] === 'sold'): ?><div class="alert bad">این خودرو فروخته شده است.</div>
          <?php elseif ($car['status'] === 'reserved'): ?><div class="alert info">این خودرو در حال حاضر رزرو شده است.</div><?php endif ?>
          <?php if ($canBuy): ?><a href="<?= url('buy/' . $car['id']) ?>" class="btn btn-accent btn-lg btn-block"><?= icon('card') ?> خرید / رزرو آنلاین</a><?php endif ?>
          <?php if (module('test_drive') && $car['status'] !== 'sold'): ?><button class="btn btn-soft btn-block" data-modal-open="m-visit"><?= icon('calendar') ?> رزرو بازدید حضوری</button><?php endif ?>
          <?php if (module('installment') && $car['installment'] && $car['status'] === 'available'): ?><button class="btn btn-ghost btn-block" data-modal-open="m-installment"><?= icon('calc') ?> درخواست خرید اقساطی</button><?php endif ?>
          <a href="tel:<?= e(preg_replace('/\D/', '', setting('phone'))) ?>" class="btn btn-ghost btn-block"><?= icon('phone') ?> تماس با کارشناس فروش</a>
        </div>
        <div class="share-row">
          <?php if (module('favorites')): ?><button class="btn btn-ghost btn-sm" data-fav="<?= $car['id'] ?>"><?= icon('heart') ?> علاقه‌مندی</button><?php endif ?>
          <?php if (module('compare')): ?><button class="btn btn-ghost btn-sm" data-cmp="<?= $car['id'] ?>"><?= icon('compare') ?> مقایسه</button><?php endif ?>
        </div>
      </div>
    </aside>
  </div>

  <?php if ($similar): ?>
  <section class="section">
    <div class="section-head"><div><h2>خودروهای مشابه</h2></div></div>
    <div class="grid-cars"><?php foreach ($similar as $s) partial('car_card', ['car' => $s]); ?></div>
  </section>
  <?php endif ?>
</div>

<!-- lightbox -->
<div class="lightbox" id="lightbox" role="dialog" aria-modal="true" aria-label="گالری تصاویر">
  <button class="lb-btn lb-close" data-lb-close aria-label="بستن"><?= icon('x') ?></button>
  <button class="lb-btn lb-prev" data-lb-prev aria-label="قبلی"><?= icon('chev-right') ?></button>
  <img src="" alt="">
  <button class="lb-btn lb-next" data-lb-next aria-label="بعدی"><?= icon('chev-left') ?></button>
  <span class="lb-count"></span>
</div>

<?php foreach (['m-visit' => ['test_drive', 'رزرو بازدید حضوری'], 'm-installment' => ['installment', 'درخواست خرید اقساطی']] as $mid => [$type, $mt]): ?>
<div class="modal" id="<?= $mid ?>" role="dialog" aria-modal="true" aria-labelledby="<?= $mid ?>-t">
  <div class="modal-box">
    <div class="modal-head"><h3 id="<?= $mid ?>-t"><?= $mt ?></h3><button class="icon-btn" data-modal-close aria-label="بستن"><?= icon('x') ?></button></div>
    <form class="modal-body" method="post" action="<?= url('request') ?>" data-ajax-form>
      <?= csrf_field() ?><?= honeypot() ?>
      <input type="hidden" name="type" value="<?= $type ?>"><input type="hidden" name="car_id" value="<?= $car['id'] ?>">
      <p class="muted small"><?= e($title) ?> — کد <?= fa($car['code']) ?></p>
      <div class="form-grid">
        <div class="field"><label>نام و نام خانوادگی</label><input name="name" required maxlength="120" autocomplete="name"></div>
        <div class="field"><label>شماره موبایل</label><input name="mobile" required inputmode="tel" placeholder="09xxxxxxxxx" class="ltr" autocomplete="tel"></div>
        <?php if ($type === 'test_drive'): ?>
          <div class="field"><label>تاریخ پیشنهادی</label><input name="date" placeholder="مثلاً ۱۴۰۵/۰۸/۱۵"></div>
          <div class="field"><label>بازه زمانی</label><select name="time"><option>صبح (۹ تا ۱۲)</option><option>ظهر (۱۲ تا ۱۶)</option><option>عصر (۱۶ تا ۲۰)</option></select></div>
        <?php else: ?>
          <div class="field"><label>مبلغ پیش‌پرداخت (تومان)</label><input name="down" inputmode="numeric" data-money data-down-input></div>
          <div class="field"><label>تعداد اقساط</label><select name="months"><?php for ($m = 6; $m <= (int)setting('installment_max_months', 36); $m += 6): ?><option value="<?= $m ?>"><?= fa($m) ?> ماه</option><?php endfor ?></select></div>
        <?php endif ?>
        <div class="field full"><label>توضیحات (اختیاری)</label><textarea name="message" maxlength="1000" rows="3"></textarea></div>
      </div>
      <?= Captcha::field('request', true) ?>
      <div class="form-msg"></div>
      <button class="btn btn-accent btn-block">ثبت درخواست</button>
    </form>
  </div>
</div>
<?php endforeach ?>
