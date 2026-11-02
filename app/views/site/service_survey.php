<?php $q = ['quality' => 'کیفیت انجام کار', 'timing' => 'رعایت زمان نوبت و تحویل', 'staff' => 'برخورد و راهنمایی کارکنان']; ?>
<div class="container section" style="max-width:640px">
  <form method="post" class="card card-pad svc-survey"><?= csrf_field() ?>
    <h1 class="acc-h2" style="font-size:1.35em">نظرسنجی خدمات</h1>
    <p class="muted small">نوبت <?= e($a['code']) ?> — <?= e($a['type_title'] ?? '') ?></p>
    <?php if ($done): ?><p>شما قبلاً در این نظرسنجی شرکت کرده‌اید. سپاسگزاریم.</p>
    <?php else: ?>
      <fieldset class="stars-q"><legend>رضایت کلی شما *</legend><?= star_input('overall', true) ?></fieldset>
      <?php foreach ($q as $k => $l): ?><fieldset class="stars-q"><legend><?= $l ?></legend><?= star_input($k) ?></fieldset><?php endforeach ?>
      <fieldset class="stars-q"><legend>ما را به دوستانتان پیشنهاد می‌کنید؟</legend>
        <div class="seg-yn"><label><input type="radio" name="recommend" value="1"><span>بله</span></label><label><input type="radio" name="recommend" value="0"><span>خیر</span></label></div></fieldset>
      <div class="field"><label>پیشنهاد یا انتقاد (اختیاری)</label><textarea name="comment" rows="3" maxlength="2000"></textarea></div>
      <button type="submit" class="btn btn-accent btn-block">ثبت نظر</button>
    <?php endif ?>
  </form>
</div>
