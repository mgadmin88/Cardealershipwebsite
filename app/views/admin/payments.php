<div class="alert info"><?= icon('info') ?><span>در ایران درگاه اینترنتی هر بانک از طریق یکی از شرکت‌های PSP ارائه می‌شود (سامان‌کیش، به‌پرداخت ملت، سداد، پارسیان، سپهر، پاسارگاد). برای بانک‌هایی مثل <b>سینا</b> و <b>سرمایه</b>، درگاه را با نام بانک تعریف کنید و PSP درج‌شده در قرارداد بانک را انتخاب کنید. اطلاعات محرمانه (رمز و کلیدها) به‌صورت رمزنگاری‌شده ذخیره می‌شوند.
<br>آدرس بازگشت (Callback) که باید به بانک اعلام شود: <code><?= e(abs_url('payment/callback/')) ?>*</code></span></div>
<?php foreach ($gateways as $g): $cls = Payment::DRIVERS[$g['driver']] ?? null; ?>
  <details class="gw-card">
    <summary><span class="dot <?= $g['active'] ? 'on' : '' ?>"></span><b><?= e($g['label']) ?></b><span class="muted small"><?= $cls ? e($cls::title()) : '' ?></span>
      <?php if ($g['sandbox']): ?><span class="badge warn">آزمایشی</span><?php endif ?>
      <span style="margin-inline-start:auto" class="badge <?= $g['active'] ? 'ok' : '' ?>"><?= $g['active'] ? 'فعال' : 'غیرفعال' ?></span><?= icon('chev-down') ?></summary>
    <div class="gw-body">
      <?php partial('../admin/_gw_form', ['g' => $g]) ?>
      <form method="post" class="mt-1"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $g['id'] ?>"><button class="btn btn-ghost btn-sm" data-confirm="درگاه حذف شود؟"><?= icon('trash') ?> حذف</button></form>
    </div>
  </details>
<?php endforeach ?>
<details class="gw-card" open><summary><?= icon('plus') ?><b>افزودن درگاه جدید</b></summary><div class="gw-body"><?php partial('../admin/_gw_form', ['g' => null]) ?></div></details>
