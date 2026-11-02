<div class="card tbl-wrap"><table class="table"><thead><tr><th>زمان</th><th>کاربر</th><th>عملیات</th><th>جزئیات</th><th>IP</th></tr></thead>
<?php foreach ($rows as $r): ?><tr><td class="small"><?= jdate($r['created_at'], 'datetime') ?></td><td class="ltr"><?= e($r['username'] ?? '—') ?></td><td><code><?= e($r['action']) ?></code></td><td class="small"><?= e($r['details']) ?></td><td class="ltr small"><?= e($r['ip']) ?></td></tr><?php endforeach ?>
</table></div>
