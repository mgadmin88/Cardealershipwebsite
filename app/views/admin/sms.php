<?php $cur = setting('sms_provider', 'log'); $all = Settings::json('sms_config'); ?>
<div class="grid3">
  <form method="post" class="card section-card"><h3><?= icon('sms') ?> سامانه پیامک</h3><div class="body"><?= csrf_field() ?>
    <label class="check mb-2"><span class="switch"><input type="checkbox" name="enabled" value="1" <?= checked(module('sms')) ?>><i></i></span> ارسال پیامک‌های خودکار فعال باشد</label>
    <div class="field"><label>سرویس‌دهنده</label><select name="sms_provider" data-switch><?php foreach (Sms::PROVIDERS as $k => [$l]): ?><option value="<?= $k ?>"<?= selected($k, $cur) ?>><?= e($l) ?></option><?php endforeach ?></select></div>
    <?php foreach (Sms::PROVIDERS as $k => [$l, $fields]): ?>
      <div data-for="<?= $k ?>" class="form-grid">
        <?php foreach ($fields as $fk => [$fl, $ft]): $val = $all[$k][$fk] ?? ''; ?>
          <div class="field <?= in_array($fk, ['url', 'body'], true) ? 'full' : '' ?>"><label><?= e($fl) ?></label>
            <input name="cfg[<?= $fk ?>]" dir="ltr" type="<?= $ft === 'password' ? 'password' : 'text' ?>" autocomplete="off" value="<?= $ft === 'password' ? '' : e($val) ?>" placeholder="<?= $ft === 'password' && $val ? e(Secret::mask($val)) : '' ?>"></div>
        <?php endforeach ?>
        <?php if ($k === 'generic'): ?><div class="full muted-box">مثال GET: <code>https://panel.example.ir/send?key={key}&to={to}&from={from}&text={text}</code></div><?php endif ?>
      </div>
    <?php endforeach ?>
    <div class="field"><label>شماره موبایل مدیران برای اطلاع‌رسانی (با ویرگول جدا کنید)</label><input name="sms_admin_mobiles" dir="ltr" value="<?= e(setting('sms_admin_mobiles')) ?>"></div>
    <h4>قالب پیامک‌ها <small class="muted">متغیرها: {name} {code} {amount} {ref} {site} {event} {mobile}</small></h4>
    <?php foreach (['order' => 'ثبت سفارش (مشتری)', 'paid' => 'پرداخت موفق (مشتری)', 'request' => 'ثبت درخواست (مشتری)', 'admin' => 'اطلاع‌رسانی به مدیر'] as $k => $l): ?>
      <div class="field"><label><?= $l ?></label><textarea name="sms_tpl_<?= $k ?>" rows="2"><?= e(setting('sms_tpl_' . $k)) ?></textarea></div>
    <?php endforeach ?>
    <button class="btn btn-accent btn-block">ذخیره</button>
  </div></form>
  <div>
    <form method="post" class="card section-card"><h3>ارسال آزمایشی</h3><div class="body"><?= csrf_field() ?><input type="hidden" name="action" value="test">
      <div class="inline-form"><div class="field grow"><input name="test_mobile" dir="ltr" placeholder="09xxxxxxxxx" required></div><button class="btn btn-soft">ارسال</button></div></div></form>
    <div class="card section-card"><h3>آخرین ارسال‌ها</h3><div class="body tbl-wrap"><table class="table">
      <?php foreach ($log as $l): ?><tr><td class="ltr small"><?= fa($l['mobile']) ?></td><td class="small"><?= e(excerpt((string)$l['message'], 40)) ?></td><td><?= $l['ok'] ? '<span class="badge ok">✓</span>' : '<span class="badge bad" title="' . e($l['response']) . '">✗</span>' ?></td></tr><?php endforeach ?>
      <?php if (!$log): ?><tr><td class="muted">هنوز پیامکی ارسال نشده.</td></tr><?php endif ?>
    </table></div></div>
  </div>
</div>
