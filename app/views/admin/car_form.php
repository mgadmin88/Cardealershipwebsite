<?php
$c = $car ?? [];
$v = fn($k, $d = '') => old($k, $c[$k] ?? $d);
$tags = array_filter(explode(',', (string)($c['tags'] ?? '')));
$damage = $c['damage'] ?? [];
$points = $points ?? [];
// panel-level status only (spots are edited separately; a panel tinted only by its worst spot shows "سالم" here)
$worstPt = [];
foreach ($points as $pt) if (Catalog::damageRank($pt['type']) > Catalog::damageRank($worstPt[$pt['part']] ?? 'ok')) $worstPt[$pt['part']] = $pt['type'];
foreach ($damage as $pk => $st) if (($worstPt[$pk] ?? null) === $st) unset($damage[$pk]);
$glyphs = []; foreach (Catalog::DAMAGE as $dk => [$dl, $dc]) if ($dk !== 'ok') $glyphs[$dk] = ['l' => $dl, 'c' => $dc, 'g' => Catalog::damageGlyph($dk)];
$insp = $c['inspection'] ?? [];
$specs = $c['specs'] ?? [];
$specLabels = ['engine' => 'حجم / نوع موتور', 'power' => 'قدرت', 'torque' => 'گشتاور', 'consumption' => 'مصرف ترکیبی', 'drive' => 'سیستم انتقال قدرت', 'seats' => 'ظرفیت سرنشین', 'accel' => 'شتاب ۰-۱۰۰', 'tank' => 'حجم باک'];
?>
<form method="post" enctype="multipart/form-data" id="carForm">
  <?= csrf_field() ?>
  <div class="tabsbar" data-tabs>
    <button type="button" class="on" data-tab="t-main"><?= icon('car') ?> مشخصات اصلی</button>
    <button type="button" data-tab="t-docs"><?= icon('doc') ?> بیمه و مدارک</button>
    <button type="button" data-tab="t-body"><?= icon('paint') ?> وضعیت بدنه</button>
    <button type="button" data-tab="t-insp"><?= icon('wrench') ?> گزارش کارشناسی</button>
    <button type="button" data-tab="t-img"><?= icon('camera') ?> تصاویر</button>
    <button type="button" data-tab="t-more"><?= icon('list') ?> امکانات و توضیحات</button>
  </div>

  <!-- MAIN -->
  <div class="tab on" id="t-main">
    <div class="grid3">
      <div class="card section-card"><h3>اطلاعات خودرو</h3><div class="body">
        <div class="form-grid">
          <div class="field"><label>وضعیت خودرو</label><select name="car_condition" data-cond><?php foreach (Catalog::CONDITION as $k => $l): ?><option value="<?= $k ?>"<?= selected($k, $v('car_condition', 'used')) ?>><?= $l ?></option><?php endforeach ?></select></div>
          <div class="field"><label>برند *</label>
            <select name="brand_id" data-brand-select required><option value="">انتخاب برند از لیست</option>
              <optgroup label="برندهای داخلی"><?php foreach ($brands as $b) if ($b['origin'] === 'domestic'): ?><option value="<?= $b['id'] ?>"<?= selected($b['id'], $v('brand_id')) ?>><?= e($b['name_fa']) ?> — <?= e($b['name_en']) ?></option><?php endif ?></optgroup>
              <optgroup label="برندهای خارجی"><?php foreach ($brands as $b) if ($b['origin'] === 'foreign'): ?><option value="<?= $b['id'] ?>"<?= selected($b['id'], $v('brand_id')) ?>><?= e($b['name_fa']) ?> — <?= e($b['name_en']) ?></option><?php endif ?></optgroup>
            </select>
            <div class="hint">برند در لیست نیست؟ <a href="<?= url('admin/brands') ?>" target="_blank">افزودن برند/مدل</a></div></div>
          <div class="field"><label>مدل</label><select name="model_id" data-model-select data-selected="<?= e($v('model_id')) ?>"><option value="">انتخاب مدل</option>
            <?php foreach ($models as $m): ?><option value="<?= $m['id'] ?>" data-body="<?= e($m['body_type']) ?>"<?= selected($m['id'], $v('model_id')) ?>><?= e($m['name_fa']) ?></option><?php endforeach ?></select></div>
          <div class="field"><label>تیپ / تریم</label><input name="trim_name" value="<?= e($v('trim_name')) ?>" placeholder="مثلاً GLX، فول، اتوماتیک"></div>
          <div class="field"><label>سال ساخت (شمسی یا میلادی) *</label><input name="year" required inputmode="numeric" value="<?= e($v('year', jyear_now())) ?>"></div>
          <div class="field" data-used-only><label>کارکرد (کیلومتر)</label><input name="mileage" inputmode="numeric" data-money value="<?= e($v('mileage', 0)) ?>"></div>
          <div class="field"><label>گیربکس</label><select name="gearbox"><?php foreach (Catalog::GEARBOX as $k => $l): ?><option value="<?= $k ?>"<?= selected($k, $v('gearbox')) ?>><?= $l ?></option><?php endforeach ?></select></div>
          <div class="field"><label>نوع سوخت</label><select name="fuel"><?php foreach (Catalog::FUEL as $k => $l): ?><option value="<?= $k ?>"<?= selected($k, $v('fuel', 'petrol')) ?>><?= $l ?></option><?php endforeach ?></select></div>
          <div class="field"><label>نوع بدنه</label><select name="body_type" data-body-select><?php foreach (Catalog::BODY_TYPES as $k => $l): ?><option value="<?= $k ?>"<?= selected($k, $v('body_type')) ?>><?= $l ?></option><?php endforeach ?></select></div>
          <div class="field"><label>رنگ بدنه</label><input name="color" list="colors" value="<?= e($v('color')) ?>"></div>
          <div class="field"><label>رنگ داخلی</label><input name="interior_color" list="colors" value="<?= e($v('interior_color')) ?>"></div>
          <datalist id="colors"><?php foreach (Catalog::COLORS as $k => $_): ?><option value="<?= e($k) ?>"><?php endforeach ?></datalist>
          <div class="field"><label>شهر محل بازدید</label><input name="city" list="cities" value="<?= e($v('city', 'تهران')) ?>"><datalist id="cities"><?php foreach (Catalog::CITIES as $ci): ?><option value="<?= $ci ?>"><?php endforeach ?></datalist></div>
          <div class="field" data-used-only><label>وضعیت شاسی</label><select name="chassis"><?php foreach (Catalog::CHASSIS as $k => $l): ?><option value="<?= $k ?>"<?= selected($k, $v('chassis')) ?>><?= $l ?></option><?php endforeach ?></select></div>
          <div class="field" data-used-only><label>وضعیت موتور</label><input name="engine_status" value="<?= e($v('engine_status', 'سالم')) ?>"></div>
          <div class="field" data-used-only><label>تعداد مالکین قبلی</label><input name="owners" inputmode="numeric" value="<?= e($v('owners')) ?>"></div>
        </div>
      </div></div>

      <div>
        <div class="card section-card"><h3>قیمت و فروش</h3><div class="body">
          <div class="field"><label>قیمت (تومان)</label><input name="price" inputmode="numeric" data-money value="<?= e($v('price')) ?>" data-price-words><div class="hint" data-words></div></div>
          <div class="field"><label>قیمت قبلی (برای نمایش تخفیف)</label><input name="old_price" inputmode="numeric" data-money value="<?= e($v('old_price')) ?>"></div>
          <label class="check mb-2"><input type="checkbox" name="price_call" value="1" <?= checked($v('price_call')) ?>> قیمت نمایش داده نشود (تماس بگیرید)</label>
          <label class="check mb-2"><input type="checkbox" name="installment" value="1" <?= checked($v('installment')) ?>> امکان خرید اقساطی</label>
          <div class="field"><label>شرایط اقساط</label><input name="installment_note" value="<?= e($v('installment_note')) ?>" placeholder="مثلاً ۳۰٪ پیش، ۲۴ ماهه"></div>
          <div class="field"><label>وضعیت انتشار</label><select name="status"><?php foreach (Catalog::CAR_STATUS as $k => $l): ?><option value="<?= $k ?>"<?= selected($k, $v('status', 'available')) ?>><?= $l ?></option><?php endforeach ?></select></div>
          <label class="check"><input type="checkbox" name="featured" value="1" <?= checked($v('featured')) ?>> نمایش ویژه در صفحه اول</label>
        </div></div>
        <div class="card section-card"><h3>برچسب‌ها</h3><div class="body">
          <div class="fopts"><?php foreach (Catalog::TAGS as $t): ?><label><input type="checkbox" name="tags[]" value="<?= e($t) ?>" <?= checked(in_array($t, $tags, true)) ?>><span><?= e($t) ?></span></label><?php endforeach ?></div>
          <div class="field mt-2"><label>برچسب‌های دیگر (با ویرگول جدا کنید)</label><input name="tags_extra" value="<?= e(implode('، ', array_diff($tags, Catalog::TAGS))) ?>"></div>
        </div></div>
        <?php if ($car): ?><div class="muted-box">کد خودرو: <b><?= fa($car['code']) ?></b> — بازدید: <?= fa($car['views']) ?> — ثبت: <?= jdate($car['created_at']) ?></div><?php endif ?>
      </div>
    </div>
  </div>

  <!-- DOCS -->
  <div class="tab" id="t-docs">
    <div class="grid2">
      <div class="card section-card"><h3><?= icon('doc') ?> بیمه شخص ثالث</h3><div class="body">
        <label class="check mb-2"><input type="checkbox" name="ins_third" value="1" <?= checked($v('ins_third', 1)) ?>> دارای بیمه شخص ثالث</label>
        <div class="form-grid">
          <div class="field"><label>شرکت بیمه</label><select name="ins_third_company"><option value="">—</option><?php foreach (Catalog::INSURERS as $i): ?><option<?= selected($i, $v('ins_third_company')) ?>><?= $i ?></option><?php endforeach ?></select></div>
          <div class="field"><label>تاریخ انقضا</label><input name="ins_third_until" placeholder="1406/08/06" class="ltr" value="<?= e($v('ins_third_until')) ?>"></div>
          <div class="field"><label>سال‌های تخفیف بیمه</label><input name="ins_discount_years" inputmode="numeric" value="<?= e($v('ins_discount_years')) ?>"></div>
        </div>
      </div></div>
      <div class="card section-card"><h3><?= icon('shield') ?> بیمه بدنه</h3><div class="body">
        <label class="check mb-2"><input type="checkbox" name="ins_body" value="1" <?= checked($v('ins_body')) ?>> دارای بیمه بدنه</label>
        <div class="form-grid">
          <div class="field"><label>شرکت بیمه</label><select name="ins_body_company"><option value="">—</option><?php foreach (Catalog::INSURERS as $i): ?><option<?= selected($i, $v('ins_body_company')) ?>><?= $i ?></option><?php endforeach ?></select></div>
          <div class="field"><label>تاریخ انقضا</label><input name="ins_body_until" placeholder="1406/02/15" class="ltr" value="<?= e($v('ins_body_until')) ?>"></div>
        </div>
      </div></div>
      <div class="card section-card"><h3><?= icon('card') ?> مدارک و گارانتی</h3><div class="body">
        <div class="form-grid">
          <div class="field"><label>نوع سند</label><select name="doc_type"><?php foreach (Catalog::DOC_TYPES as $k => $l): ?><option value="<?= $k ?>"<?= selected($k, $v('doc_type', '6dang')) ?>><?= $l ?></option><?php endforeach ?></select></div>
          <div class="field"><label>گارانتی</label><input name="warranty" value="<?= e($v('warranty')) ?>" placeholder="مثلاً گارانتی ۱۰ روزه بدنه و فنی"></div>
        </div>
        <label class="check mb-2"><input type="checkbox" name="tech_inspection" value="1" <?= checked($v('tech_inspection', 1)) ?>> دارای معاینه فنی معتبر</label>
        <label class="check"><input type="checkbox" name="fuel_card" value="1" <?= checked($v('fuel_card', 1)) ?>> دارای کارت سوخت</label>
      </div></div>
    </div>
  </div>

  <!-- BODY / DAMAGE -->
  <div class="tab" id="t-body">
    <input type="hidden" name="pt_present" value="1">
    <div class="card section-card"><h3><?= icon('paint') ?> نقشه نواقص بدنه</h3><div class="body">
      <div class="dmg-steps">
        <span><b>۱</b> نوع نقص را انتخاب کنید</span><span><b>۲</b> روی همان نقطه از خودرو کلیک کنید</span><span><b>۳</b> عکس و توضیح آن نقطه را اضافه کنید</span>
      </div>
      <div class="dmg-toolbar" role="radiogroup" aria-label="نوع نقص">
        <?php $first = true; foreach ($glyphs as $dk => $g): ?>
          <label class="dmg-tool"><input type="radio" name="_dmg_tool" value="<?= $dk ?>"<?= $first ? ' checked' : '' ?>><span><?= Catalog::damageIcon($dk, 24) ?><?= e($g['l']) ?></span></label>
        <?php $first = false; endforeach ?>
      </div>
      <div class="damage-admin-wrap" data-damage-editor data-glyphs="<?= e(json_encode($glyphs, JSON_UNESCAPED_UNICODE)) ?>" data-parts="<?= e(json_encode(Catalog::PARTS, JSON_UNESCAPED_UNICODE)) ?>">
        <div class="dmg-canvas"><?php partial('damage_map', ['damage' => $c['damage'] ?? [], 'points' => $points, 'admin' => true]) ?>
          <p class="muted small center">برای جابه‌جایی، نقطه را بکشید. برای ویرایش، روی آن کلیک کنید.</p></div>
        <div>
          <div class="pt-head"><b>نقاط ثبت‌شده</b> <span class="muted small" data-pt-count></span></div>
          <div class="pt-list" data-pt-list>
            <?php foreach ($points as $pt): $key = 'e' . $pt['id']; ?>
              <div class="pt-row" data-key="<?= $key ?>">
                <input type="hidden" name="pt[<?= $key ?>][id]" value="<?= (int)$pt['id'] ?>">
                <input type="hidden" name="pt[<?= $key ?>][x]" value="<?= (int)$pt['x'] ?>" data-x><input type="hidden" name="pt[<?= $key ?>][y]" value="<?= (int)$pt['y'] ?>" data-y>
                <span class="pt-num"></span>
                <div class="pt-fields">
                  <div class="pt-line">
                    <select name="pt[<?= $key ?>][part]" data-pt-part aria-label="قطعه"><?php foreach (Catalog::PARTS as $pk => $pl): ?><option value="<?= $pk ?>"<?= selected($pk, $pt['part']) ?>><?= $pl ?></option><?php endforeach ?></select>
                    <select name="pt[<?= $key ?>][type]" data-pt-type aria-label="نوع نقص"><?php foreach ($glyphs as $dk => $g): ?><option value="<?= $dk ?>"<?= selected($dk, $pt['type']) ?>><?= e($g['l']) ?></option><?php endforeach ?></select>
                  </div>
                  <input name="pt[<?= $key ?>][note]" value="<?= e($pt['note']) ?>" maxlength="250" placeholder="توضیح (مثلاً: ضربه کوچک روی لبه پایین)">
                </div>
                <label class="pt-photo" title="عکس این نقطه">
                  <?php if ($pt['photo']): ?><img src="<?= e(url('uploads/' . ($pt['thumb'] ?: $pt['photo']))) ?>" alt=""><?php else: ?><?= icon('camera') ?><?php endif ?>
                  <input type="file" name="pt_photo[<?= $key ?>]" accept="image/jpeg,image/png,image/webp" data-pt-file>
                </label>
                <?php if ($pt['photo']): ?><label class="pt-delphoto small"><input type="checkbox" name="pt[<?= $key ?>][del_photo]" value="1"> حذف عکس</label><?php endif ?>
                <button type="button" class="pt-del" data-pt-del aria-label="حذف نقطه"><?= icon('trash') ?></button>
              </div>
            <?php endforeach ?>
          </div>
          <div class="pt-empty muted small" data-pt-empty>هنوز نقطه‌ای ثبت نشده. نوع نقص را انتخاب و روی خودرو کلیک کنید.</div>

          <details class="panel-status mt-2"<?= $damage ? ' open' : '' ?>>
            <summary>وضعیت کلی قطعات (رنگ‌شدگی کل قطعه، بدون نقطه)</summary>
            <div class="dmg-table">
              <?php foreach (Catalog::PARTS as $pk => $pl): $cur = $damage[$pk] ?? 'ok'; ?>
                <label><span style="min-width:120px"><?= $pl ?></span>
                  <select name="damage[<?= $pk ?>]" data-part-select="<?= $pk ?>"><?php foreach (Catalog::DAMAGE as $dk => [$dl]): ?><option value="<?= $dk ?>"<?= selected($dk, $cur) ?>><?= $dl ?></option><?php endforeach ?></select></label>
              <?php endforeach ?>
            </div>
          </details>
        </div>
      </div>
      <template id="ptTpl">
        <div class="pt-row is-new" data-key="__K__">
          <input type="hidden" name="pt[__K__][id]" value=""><input type="hidden" name="pt[__K__][x]" data-x><input type="hidden" name="pt[__K__][y]" data-y>
          <span class="pt-num"></span>
          <div class="pt-fields">
            <div class="pt-line">
              <select name="pt[__K__][part]" data-pt-part aria-label="قطعه"><?php foreach (Catalog::PARTS as $pk => $pl): ?><option value="<?= $pk ?>"><?= $pl ?></option><?php endforeach ?></select>
              <select name="pt[__K__][type]" data-pt-type aria-label="نوع نقص"><?php foreach ($glyphs as $dk => $g): ?><option value="<?= $dk ?>"><?= e($g['l']) ?></option><?php endforeach ?></select>
            </div>
            <input name="pt[__K__][note]" maxlength="250" placeholder="توضیح (مثلاً: ضربه کوچک روی لبه پایین)">
          </div>
          <label class="pt-photo" title="عکس این نقطه"><?= icon('camera') ?><input type="file" name="pt_photo[__K__]" accept="image/jpeg,image/png,image/webp" data-pt-file></label>
          <button type="button" class="pt-del" data-pt-del aria-label="حذف نقطه"><?= icon('trash') ?></button>
        </div>
      </template>
    </div></div>
  </div>

  <!-- INSPECTION -->
  <div class="tab" id="t-insp">
    <div class="alert info"><?= icon('info') ?><span>برای هر مورد «تایید» یا «نقص» را انتخاب کنید. موارد انتخاب‌نشده در گزارش نمایش داده نمی‌شوند. امتیاز هر بخش اگر خالی بماند به‌صورت خودکار محاسبه می‌شود.</span></div>
    <div class="flex mb-2"><button type="button" class="btn btn-soft btn-sm" data-insp-all="ok"><?= icon('check') ?> تایید همه موارد</button><button type="button" class="btn btn-ghost btn-sm" data-insp-all=""><?= icon('x') ?> پاک کردن همه</button></div>
    <div class="grid2">
    <?php foreach (Catalog::INSPECTION as $k => [$catTitle, $ic, $items]): $d = $insp[$k] ?? []; ?>
      <div class="insp-adm card">
        <h4><span class="flex"><?= icon($ic) ?><?= e($catTitle) ?></span><span class="flex small">امتیاز (۰-۵): <input name="insp[<?= $k ?>][score]" value="<?= e($d['score'] ?? '') ?>" placeholder="خودکار" inputmode="decimal"></span></h4>
        <?php foreach ($items as $i => $label): $s = $d['items'][$i]['s'] ?? ''; ?>
          <div class="insp-row">
            <span class="small"><?= e($label) ?></span>
            <span class="seg">
              <label><input type="radio" name="insp[<?= $k ?>][items][<?= $i ?>][s]" value="ok" <?= checked($s === 'ok') ?>><span>تایید</span></label>
              <label><input type="radio" name="insp[<?= $k ?>][items][<?= $i ?>][s]" value="fail" <?= checked($s === 'fail') ?>><span>نقص</span></label>
              <label><input type="radio" name="insp[<?= $k ?>][items][<?= $i ?>][s]" value="" <?= checked($s === '') ?>><span>—</span></label>
            </span>
            <input type="text" name="insp[<?= $k ?>][items][<?= $i ?>][n]" value="<?= e($d['items'][$i]['n'] ?? '') ?>" placeholder="توضیح (اختیاری)">
          </div>
        <?php endforeach ?>
      </div>
    <?php endforeach ?>
    </div>
  </div>

  <!-- IMAGES -->
  <div class="tab" id="t-img">
    <div class="card section-card"><h3><?= icon('camera') ?> تصاویر خودرو</h3><div class="body">
      <label class="dropzone" data-dropzone>
        <?= icon('upload') ?><b>تصاویر را اینجا رها کنید یا کلیک کنید</b><br><small>JPG، PNG، WEBP — حداکثر <?= (int)config('upload.max_size_mb', 8) ?> مگابایت؛ تصاویر به‌صورت خودکار بهینه و فشرده می‌شوند</small>
        <input type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp" hidden data-file-input>
      </label>
      <div class="preview-row" data-preview></div>
      <?php if ($images): ?>
        <h4 class="mt-3">تصاویر فعلی <small class="muted">(برای تغییر ترتیب بکشید؛ اولین تصویر، تصویر شاخص است)</small></h4>
        <div class="img-grid" data-sortable>
          <?php foreach ($images as $i => $img): ?>
            <div class="img-item" draggable="true" data-id="<?= $img['id'] ?>">
              <img src="<?= e(upload_url($img['thumb'] ?: $img['path'])) ?>" alt="">
              <button type="button" class="tool rm" data-img-del="<?= $img['id'] ?>" aria-label="حذف"><?= icon('trash') ?></button>
              <?php if ($i === 0): ?><span class="flag accent first">تصویر شاخص</span><?php endif ?>
            </div>
          <?php endforeach ?>
        </div>
      <?php endif ?>
    </div></div>
  </div>

  <!-- MORE -->
  <div class="tab" id="t-more">
    <div class="grid2">
      <div class="card section-card"><h3>امکانات و آپشن‌ها</h3><div class="body">
        <div class="field"><label>هر آپشن در یک خط</label><textarea name="options" rows="10"><?= e($v('options')) ?></textarea></div>
      </div></div>
      <div class="card section-card"><h3>مشخصات فنی</h3><div class="body"><div class="form-grid">
        <?php foreach ($specLabels as $k => $l): ?><div class="field"><label><?= $l ?></label><input name="specs[<?= $k ?>]" value="<?= e($specs[$k] ?? '') ?>"></div><?php endforeach ?>
      </div></div></div>
    </div>
    <div class="card section-card"><h3>توضیحات کارشناس</h3><div class="body"><textarea name="description" rows="7"><?= e($v('description')) ?></textarea></div></div>
  </div>

  <div class="save-bar">
    <?php if ($car): ?><button type="submit" form="delForm" class="btn btn-ghost" style="color:var(--bad);margin-inline-end:auto" data-confirm="این خودرو حذف شود؟"><?= icon('trash') ?> حذف</button><?php endif ?>
    <a href="<?= url('admin/cars') ?>" class="btn btn-ghost">انصراف</a>
    <button class="btn btn-accent btn-lg"><?= icon('check') ?> ذخیره خودرو</button>
  </div>
</form>
<?php if ($car): ?><form id="delForm" method="post" action="<?= url('admin/cars/' . $car['id'] . '/delete') ?>"><?= csrf_field() ?></form><?php endif ?>
