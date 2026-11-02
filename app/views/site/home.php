<?php $heroMode = setting('theme_hero', 'slider'); ?>
<section class="hero showroom">
  <div class="stage">
    <div class="tubes" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>
    <div class="dusk" aria-hidden="true"></div>
    <canvas class="dust" data-dust aria-hidden="true"></canvas>
    <svg class="trails" viewBox="0 0 1440 300" preserveAspectRatio="none" aria-hidden="true">
      <defs>
        <linearGradient id="trA" x1="0" x2="1"><stop offset="0" stop-color="#ffb648" stop-opacity="0"/><stop offset=".6" stop-color="#ffb648"/><stop offset="1" stop-color="#fff3d6"/></linearGradient>
        <linearGradient id="trR" x1="1" x2="0"><stop offset="0" stop-color="#ff3d3d" stop-opacity="0"/><stop offset=".6" stop-color="#ff4d4d"/><stop offset="1" stop-color="#ffd0d0"/></linearGradient>
      </defs>
      <path class="tr a1" d="M-40,240 C300,215 620,250 940,205 S1300,170 1480,180"/>
      <path class="tr a2" d="M-40,262 C340,240 700,272 1020,232 S1320,214 1480,220"/>
      <path class="tr r1" d="M1480,250 C1160,268 820,236 520,262 S120,286 -40,276"/>
      <path class="tr r2" d="M1480,276 C1180,290 860,264 560,284 S160,298 -40,294"/>
    </svg>
    <div class="spot" data-spot aria-hidden="true"></div>
    <?php if ($slides && $heroMode !== 'search'): ?>
      <div class="slider" data-slider data-interval="6000" aria-roledescription="carousel">
        <?php foreach ($slides as $i => $s): ?>
          <div class="slide <?= $i === 0 ? 'active' : '' ?> <?= $s['align'] === 'left' ? 'left' : '' ?>" aria-hidden="<?= $i ? 'true' : 'false' ?>">
            <img src="<?= e(upload_url($s['image'])) ?>" alt="<?= e($s['title']) ?>" <?= $i ? 'loading="lazy"' : 'fetchpriority="high"' ?>>
            <div class="slide-body">
              <?php if ($s['title']): ?><<?= $i ? 'h2' : 'h1' ?> class="slide-title"><?= e($s['title']) ?></<?= $i ? 'h2' : 'h1' ?>><?php endif ?>
              <?php if ($s['subtitle']): ?><p><?= e($s['subtitle']) ?></p><?php endif ?>
              <?php if ($s['btn_text']): ?><a href="<?= e(str_starts_with($s['btn_link'], 'http') ? $s['btn_link'] : url($s['btn_link'])) ?>" class="btn btn-accent btn-lg"><?= e($s['btn_text']) ?> <?= icon('arrow-left') ?></a><?php endif ?>
            </div>
          </div>
        <?php endforeach ?>
        <?php if (count($slides) > 1): ?>
          <div class="slider-dots"><?php foreach ($slides as $i => $s): ?><button class="<?= $i === 0 ? 'active' : '' ?>" aria-label="اسلاید <?= $i + 1 ?>"></button><?php endforeach ?></div>
          <div class="slider-nav"><button data-prev aria-label="قبلی"><?= icon('chev-right') ?></button><button data-next aria-label="بعدی"><?= icon('chev-left') ?></button></div>
        <?php endif ?>
      </div>
    <?php else: ?>
      <div class="hero-static">
        <img class="hero-car" src="<?= asset('img/car-placeholder.svg') ?>" alt="" aria-hidden="true">
        <h1><?= e(setting('site_tagline')) ?></h1>
        <p><?= e(setting('site_description')) ?></p>
        <a href="<?= url('cars') ?>" class="btn btn-accent btn-lg">مشاهده خودروها <?= icon('arrow-left') ?></a>
      </div>
    <?php endif ?>
    <div class="floor" aria-hidden="true"></div>
  </div>
  <div class="container">
    <form class="qsearch" action="<?= url('cars') ?>" method="get" role="search">
      <div class="qtabs">
        <label><input type="radio" name="condition" value="" checked><span>همه خودروها</span></label>
        <?php if (module('used_cars')): ?><label><input type="radio" name="condition" value="used"><span>کارکرده</span></label><?php endif ?>
        <?php if (module('new_cars')): ?><label><input type="radio" name="condition" value="new"><span>صفر کیلومتر</span></label><?php endif ?>
      </div>
      <div class="qgrid">
        <select name="brand" data-brand-select aria-label="برند"><option value="">همه برندها</option>
          <?php foreach ($allBrands as $b): ?><option value="<?= $b['id'] ?>"><?= e($b['name_fa']) ?></option><?php endforeach ?></select>
        <select name="model" data-model-select aria-label="مدل"><option value="">همه مدل‌ها</option></select>
        <select name="price_max" aria-label="حداکثر قیمت"><option value="">حداکثر قیمت</option>
          <?php foreach ([500, 1000, 1500, 2000, 3000, 5000, 8000, 15000] as $m): ?><option value="<?= $m * 1000000 ?>">تا <?= fa(price_words($m * 1000000)) ?> تومان</option><?php endforeach ?></select>
        <select name="year_min" aria-label="سال ساخت"><option value="">از سال ساخت</option>
          <?php for ($y = jyear_now(); $y >= 1385; $y--): ?><option value="<?= $y ?>">از <?= fa($y) ?></option><?php endfor ?></select>
        <button class="btn btn-accent"><?= icon('search') ?> جستجو</button>
      </div>
    </form>
  </div>
</section>

<?php if ($brands): ?>
<section class="section tight">
  <div class="container">
    <div class="section-head"><div><h2>برندهای محبوب</h2></div><a class="more" href="<?= url('cars') ?>">همه برندها <?= icon('chev-left') ?></a></div>
    <div class="marquee" data-marquee>
      <div class="marquee-track">
      <?php foreach ($brands as $b): ?>
        <a class="brand-chip" href="<?= url('cars', ['brand' => $b['id']]) ?>">
          <span class="bm"><?php if ($b['logo']): ?><img src="<?= e(upload_url($b['logo'])) ?>" alt=""><?php else: ?><?= e(mb_substr($b['name_en'] ?: $b['name_fa'], 0, 2)) ?><?php endif ?></span>
          <b><?= e($b['name_fa']) ?></b><small><?= fa((int)$b['cnt']) ?> آگهی</small>
        </a>
      <?php endforeach ?>
      </div>
    </div>
  </div>
</section>
<?php endif ?>

<section class="section tight">
  <div class="container cats">
    <?php if (module('used_cars')): ?><a class="cat reveal" style="--c1:var(--primary);--c2:color-mix(in oklab,var(--primary) 60%,#000)" href="<?= url('cars', ['condition' => 'used']) ?>"><?= icon('car') ?><h3>خودروی کارکرده</h3><p>کارشناسی‌شده با گزارش کامل</p></a><?php endif ?>
    <?php if (module('new_cars')): ?><a class="cat reveal" style="--c1:#0f766e;--c2:#064e3b" href="<?= url('cars', ['condition' => 'new']) ?>"><?= icon('sparkle') ?><h3>خودروی صفر</h3><p>تحویل فوری با گارانتی</p></a><?php endif ?>
    <?php if (module('installment')): ?><a class="cat reveal" style="--c1:var(--accent);--c2:color-mix(in oklab,var(--accent) 60%,#7c2d12)" href="<?= url('cars', ['installment' => 1]) ?>"><?= icon('calc') ?><h3>خرید اقساطی</h3><p>تا <?= fa(setting('installment_max_months')) ?> ماه بازپرداخت</p></a><?php endif ?>
    <?php if (module('parts_shop')): ?><a class="cat reveal" style="--c1:#334155;--c2:#0f172a" href="<?= url('parts') ?>"><?= icon('wrench') ?><h3>قطعات و آپشن</h3><p>اصل و با ضمانت اصالت</p></a><?php endif ?>
  </div>
</section>

<?php if ($used): ?>
<section class="section">
  <div class="container">
    <div class="section-head"><div><h2>جدیدترین خودروهای کارکرده</h2><p>همه خودروها توسط کارشناسان ما بررسی شده‌اند</p></div><a class="more" href="<?= url('cars', ['condition' => 'used']) ?>">مشاهده همه <?= icon('chev-left') ?></a></div>
    <div class="grid-cars"><?php foreach ($used as $car) partial('car_card', ['car' => $car]); ?></div>
  </div>
</section>
<?php endif ?>

<?php if (module('sell_request')): ?>
<section class="section tight">
  <div class="container">
    <div class="cta-banner reveal">
      <div>
        <h3>مسیر ساده‌تر <em>فروش خودرو</em></h3>
        <p>درخواستتان را ثبت کنید؛ کارشناسی رایگان، قیمت منصفانه و پرداخت نقدی در کمتر از ۲۴ ساعت</p>
      </div>
      <div class="road" aria-hidden="true">
        <div class="lane"></div>
        <svg class="road-car" viewBox="0 0 620 170"><ellipse cx="310" cy="158" rx="250" ry="10" fill="#000" opacity=".35"/>
          <path d="M40,118 C40,95 56,84 86,80 L170,70 C204,44 240,20 296,16 L398,15 C440,17 474,42 502,68 L556,78 C576,82 584,98 582,118 L578,130 L46,130 Z" fill="currentColor"/>
          <path d="M190,70 C218,48 248,28 296,26 L340,26 L340,70 Z M352,26 L396,25 C428,28 456,48 478,68 L352,70 Z" fill="#0f172a" opacity=".75"/>
          <rect x="50" y="88" width="34" height="10" rx="5" fill="#fff6c9"/><rect x="552" y="86" width="26" height="12" rx="5" fill="#ff3b3b"/>
          <g class="wh"><circle cx="150" cy="130" r="34" fill="#111"/><circle cx="150" cy="130" r="18" fill="#9aa3ad"/><rect x="146" y="112" width="8" height="36" rx="3" fill="#5b6470"/></g>
          <g class="wh"><circle cx="470" cy="130" r="34" fill="#111"/><circle cx="470" cy="130" r="18" fill="#9aa3ad"/><rect x="466" y="112" width="8" height="36" rx="3" fill="#5b6470"/></g>
          <path class="beam" d="M50,93 L-200,60 L-200,130 Z" fill="url(#beamG)"/>
          <defs><linearGradient id="beamG" x1="1" x2="0"><stop offset="0" stop-color="#fff3c4" stop-opacity=".55"/><stop offset="1" stop-color="#fff3c4" stop-opacity="0"/></linearGradient></defs>
        </svg>
      </div>
      <a href="<?= url('sell') ?>" class="btn btn-accent btn-lg">ثبت درخواست فروش</a>
    </div>
  </div>
</section>
<?php endif ?>

<?php if ($new): ?>
<section class="section">
  <div class="container">
    <div class="section-head"><div><h2>خودروهای صفر کیلومتر</h2><p>با گارانتی رسمی و تحویل فوری</p></div><a class="more" href="<?= url('cars', ['condition' => 'new']) ?>">مشاهده همه <?= icon('chev-left') ?></a></div>
    <div class="grid-cars"><?php foreach ($new as $car) partial('car_card', ['car' => $car]); ?></div>
  </div>
</section>
<?php endif ?>

<section class="section process-sec">
  <div class="container">
    <div class="section-head"><div><h2>خرید خودرو در <?= e(setting('site_name')) ?></h2><p>از انتخاب تا تحویل، هر مرحله شفاف و قابل پیگیری است</p></div></div>
    <ol class="process">
      <li><span class="pn">۱</span><div class="pi"><?= icon('search') ?></div><h3>انتخاب و بررسی</h3><p>گزارش کارشناسی ۱۲۰ نقطه‌ای و نقشه رنگ‌شدگی هر خودرو را با عکس ببینید.</p></li>
      <li><span class="pn">۲</span><div class="pi"><?= icon('calendar') ?></div><h3>بازدید و تست درایو</h3><p>زمان بازدید حضوری را آنلاین رزرو کنید و خودرو را از نزدیک امتحان کنید.</p></li>
      <li><span class="pn">۳</span><div class="pi"><?= icon('card') ?></div><h3>رزرو و پرداخت</h3><p>بیعانه یا مبلغ کامل را از درگاه امن بانکی بپردازید<?= module('installment') ? '، یا خرید اقساطی تا ' . fa(setting('installment_max_months')) . ' ماه' : '' ?>.</p></li>
      <li><span class="pn">۴</span><div class="pi"><?= icon('award') ?></div><h3>انتقال سند و تحویل</h3><p>انتقال سند در دفترخانه و تحویل خودرو با ضمانت اصالت کارشناسی.</p></li>
    </ol>
  </div>
</section>

<?php if (module('stats')): $stats = Settings::json('stats'); if ($stats): ?>
<section class="section tight">
  <div class="container"><div class="stats showroom-band"><div class="tubes lit" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></div><?php foreach ($stats as [$n, $l]): ?><div><b data-count="<?= e($n) ?>"><?= e($n) ?></b><span><?= e($l) ?></span></div><?php endforeach ?></div></div>
</section>
<?php endif; endif ?>

<?php if ($parts): ?>
<section class="section">
  <div class="container">
    <div class="section-head"><div><h2>قطعات و لوازم جانبی</h2><p>قطعات اصلی و آپشن‌های کاربردی با ارسال به سراسر کشور</p></div><a class="more" href="<?= url('parts') ?>">فروشگاه <?= icon('chev-left') ?></a></div>
    <div class="grid-parts"><?php foreach ($parts as $p) partial('part_card', ['p' => $p]); ?></div>
  </div>
</section>
<?php endif ?>

<?php if ($faqs): ?>
<section class="section">
  <div class="container">
    <div class="faq-box reveal">
      <div class="faq-head">سوالات متداول</div>
      <div class="faq"><?php foreach ($faqs as $q): ?>
        <details><summary><?= e($q['question']) ?><span class="tg"><?= icon('chev-down') ?></span></summary><div class="ans"><?= nl2p((string)$q['answer']) ?></div></details>
      <?php endforeach ?></div>
    </div>
  </div>
</section>
<?php endif ?>
