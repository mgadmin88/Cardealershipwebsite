<div class="page-hero"><div class="container"><h1>سوالات متداول</h1><p>پاسخ پرسش‌های رایج درباره خرید، فروش و پرداخت</p></div></div>
<div class="container">
  <div class="faq"><?php foreach ($faqs as $q): ?><details><summary><?= e($q['question']) ?><span class="tg"><?= icon('chev-down') ?></span></summary><div class="ans"><?= nl2p((string)$q['answer']) ?></div></details><?php endforeach ?></div>
</div>
