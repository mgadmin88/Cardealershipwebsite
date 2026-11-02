<div class="container section acc">
  <?php require __DIR__ . '/_nav.php'; ?>
  <div class="acc-main">
    <div class="acc-hello"><div><h1>درخواست‌های من</h1><p>رزرو بازدید، درخواست اقساط، فروش خودرو و پیام‌هایی که فرستاده‌اید.</p></div></div>
    <section class="card card-pad">
      <?php if ($requests): ?><div class="acc-list">
        <?php foreach ($requests as $r): $d = json_decode((string)$r['data'], true) ?: []; $car = $r['car_id'] ? ($cars[$r['car_id']] ?? null) : null; ?>
          <div class="acc-row req-row">
            <span class="acc-ic"><?= icon($r['type'] === 'test_drive' ? 'calendar' : ($r['type'] === 'sell' ? 'car' : ($r['type'] === 'installment' ? 'calc' : 'message'))) ?></span>
            <div class="grow">
              <b><?= e(Catalog::REQUEST_TYPES[$r['type']] ?? $r['type']) ?></b>
              <small class="muted"><?= jdate($r['created_at'], 'datetime') ?><?php if ($car): ?> — <a href="<?= url('car/' . $car['id']) ?>"><?= e(Cars::title($car)) ?></a><?php endif ?></small>
              <?php $bits = [];
                if (!empty($d['date'])) $bits[] = 'تاریخ: ' . $d['date'];
                if (!empty($d['time'])) $bits[] = 'زمان: ' . $d['time'];
                if (!empty($d['down'])) $bits[] = 'پیش‌پرداخت: ' . fa(num($d['down'])) . ' تومان';
                if (!empty($d['months'])) $bits[] = fa($d['months']) . ' قسط';
                if (!empty($d['brand'])) $bits[] = $d['brand'] . ' ' . ($d['model'] ?? '') . ' ' . ($d['year'] ?? '');
                if (!empty($d['subject'])) $bits[] = $d['subject'];
              ?>
              <?php if ($bits): ?><small class="req-bits"><?= e(implode('، ', array_map('strval', $bits))) ?></small><?php endif ?>
              <?php if ($r['message']): ?><p class="req-msg"><?= e(excerpt($r['message'], 180)) ?></p><?php endif ?>
            </div>
            <?= request_badge($r['status']) ?>
          </div>
        <?php endforeach ?></div>
      <?php else: ?>
        <div class="acc-empty"><?= icon('message') ?><p>هنوز درخواستی ثبت نکرده‌اید. از صفحه هر خودرو می‌توانید بازدید حضوری یا خرید اقساطی را درخواست کنید.</p></div>
      <?php endif ?>
    </section>
  </div>
</div>
