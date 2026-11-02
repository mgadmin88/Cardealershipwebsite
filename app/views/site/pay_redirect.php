<div class="container full-center"><div>
  <div class="big-ic warn" style="width:80px;height:80px;margin:auto;border-radius:50%;display:grid;place-items:center;background:var(--soft-2)"><?= icon('lock') ?></div>
  <h1 class="mt-2">در حال انتقال به درگاه پرداخت…</h1><p class="muted">لطفاً صبر کنید. در صورت عدم انتقال خودکار، روی دکمه زیر کلیک کنید.</p>
  <form method="<?= e($form['method'] ?? 'post') ?>" action="<?= e($form['action']) ?>" data-autosubmit-form>
    <?php foreach ($form['fields'] as $k => $v): ?><input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>"><?php endforeach ?>
    <button class="btn btn-accent btn-lg">انتقال به درگاه <?= e($label) ?></button>
  </form>
</div></div>
