<form method="post"><?= csrf_field() ?>
  <div class="alert info"><?= icon('info') ?><span>هر ماژول را می‌توانید فعال یا غیرفعال کنید. با غیرفعال کردن، بخش مربوطه از منو، صفحات و صفحه اول سایت حذف می‌شود و آدرس‌های آن نیز در دسترس نخواهد بود.</span></div>
  <div class="mod-list">
    <?php foreach (Catalog::MODULES as $k => [$t, $d]): ?>
      <label class="mod"><span class="t"><b><?= e($t) ?></b><small><?= e($d) ?></small></span><span class="switch"><input type="checkbox" name="m[<?= $k ?>]" value="1" <?= checked(module($k)) ?>><i></i></span></label>
    <?php endforeach ?>
  </div>
  <div class="save-bar"><button class="btn btn-accent btn-lg"><?= icon('check') ?> ذخیره تغییرات</button></div>
</form>
