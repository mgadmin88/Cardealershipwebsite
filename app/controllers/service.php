<?php
declare(strict_types=1);

/* =========================================================
 *  Workshop service booking (نوبت‌دهی سرویس و تعمیرات)
 * ======================================================= */

function service_book(): void
{
    require_module('service_booking');
    $types = DB::all('SELECT * FROM service_types WHERE active = 1 ORDER BY sort, id');
    $days = Service::days();

    if (is_post()) {
        $back = fn(string $err) => [keep_old(), flash('error', $err), redirect('service')];
        if (honeypot_failed()) redirect('service');
        if (!rate_limit('svc_book', 6, 900)) $back('تعداد درخواست‌ها زیاد است. چند دقیقه بعد تلاش کنید.');
        $type = DB::row('SELECT * FROM service_types WHERE id = ? AND active = 1', [input_int('type')]);
        $name = mb_substr(trim((string)input('name')), 0, 120);
        $mobile = normalize_mobile((string)input('mobile'));
        $day = (string)input('day');
        $slot = to_en_digits((string)input('slot'));
        $brandId = input_int('brand_id') ?: null;
        $modelId = input_int('model_id') ?: null;
        $brand = $brandId ? DB::row('SELECT name_fa FROM brands WHERE id = ?', [$brandId]) : null;
        $model = $modelId ? DB::row('SELECT name_fa FROM models WHERE id = ? AND brand_id = ?', [$modelId, $brandId]) : null;
        $carFree = mb_substr(trim((string)input('car_text')), 0, 80);
        $carText = trim(($brand['name_fa'] ?? '') . ' ' . ($model['name_fa'] ?? '')) ?: $carFree;

        if (!$type) $back('نوع خدمت را انتخاب کنید.');
        if (mb_strlen($name) < 3) $back('نام و نام خانوادگی را کامل وارد کنید.');
        if (!valid_mobile($mobile)) $back('شماره موبایل معتبر نیست.');
        if ($carText === '') $back('برند و مدل خودرو را مشخص کنید.');
        if (!isset($days[$day])) $back('روز انتخاب‌شده برای نوبت‌دهی باز نیست.');
        $active = (int)DB::val('SELECT COUNT(*) FROM appointments WHERE mobile = ? AND day >= ? AND status IN ' . DB::in(Service::BUSY),
            array_merge([$mobile, date('Y-m-d')], Service::BUSY));
        if ($active >= 3) $back('برای این شماره ۳ نوبت فعال ثبت شده است. برای نوبت جدید با ما تماس بگیرید.');

        $year = input_int('year') ?: null;
        $a = DB::tx(function () use ($type, $name, $mobile, $day, $slot, $brandId, $modelId, $carText, $year) {
            // re-check capacity inside the transaction (two people booking the last place at once)
            $free = Service::slots($day)[$slot] ?? 0;
            if ($free < 1) return null;
            $id = DB::insert('appointments', [
                'code' => Service::newCode(), 'token' => bin2hex(random_bytes(16)), 'service_type_id' => $type['id'],
                'customer_id' => Customer::enabled() ? Customer::id() : null, 'name' => $name, 'mobile' => $mobile,
                'brand_id' => $brandId, 'model_id' => $modelId, 'car_text' => $carText, 'year' => $year,
                'plate' => mb_substr(trim((string)input('plate')), 0, 30), 'mileage' => input('mileage') === '' ? null : max(0, input_int('mileage')),
                'day' => $day, 'slot' => $slot, 'status' => 'pending', 'note' => mb_substr(trim((string)input('note')), 0, 1000),
                'ip' => client_ip(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            return DB::find('appointments', $id);
        });
        if (!$a) $back('ظرفیت این ساعت همین حالا پر شد. لطفاً ساعت دیگری انتخاب کنید.');
        clear_old();
        Service::grant($a);
        Service::notify($a, 'booked');
        Notify::adminEvent('نوبت سرویس جدید ' . $a['code'], $name, $mobile);
        redirect('service/a/' . $a['code'] . '?t=' . $a['token'] . '&new=1');
    }
    $brands = DB::all('SELECT id, name_fa FROM brands ORDER BY origin, sort');
    $pf = Customer::enabled() ? (Customer::user() ?? []) : [];
    view('site/service', compact('types', 'days', 'brands', 'pf') + ['title' => 'نوبت سرویس و تعمیرات', 'cfg' => Service::cfg()]);
}

function api_slots(): void
{
    require_module('service_booking');
    $day = (string)($_GET['day'] ?? '');
    $out = [];
    if (isset(Service::days()[$day])) foreach (Service::slots($day) as $hm => $left) $out[] = ['t' => $hm, 'fa' => fa($hm), 'left' => $left];
    header('Cache-Control: no-store');
    json_out(['slots' => $out]);
}

function service_track(): void
{
    require_module('service_booking');
    if (is_post()) {
        if (!rate_limit('svc_track', 8, 600)) { flash('error', 'تعداد تلاش‌ها زیاد است. بعداً تلاش کنید.'); redirect('service/track'); }
        $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', to_en_digits((string)input('code'))));
        if ($code !== '' && $code[0] !== 'S') $code = 'S' . $code;
        $a = DB::row('SELECT * FROM appointments WHERE code = ? AND mobile = ?', [$code, normalize_mobile((string)input('mobile'))]);
        if (!$a) { flash('error', 'نوبتی با این کد پیگیری و شماره موبایل پیدا نشد.'); redirect('service/track'); }
        Service::grant($a);
        redirect('service/a/' . $a['code']);
    }
    view('site/service_track', ['title' => 'پیگیری نوبت']);
}

function service_load(string $code): array
{
    require_module('service_booking');
    $a = DB::row('SELECT a.*, t.title AS type_title, t.icon AS type_icon FROM appointments a LEFT JOIN service_types t ON t.id = a.service_type_id WHERE a.code = ?', [$code]);
    if (!$a || !Service::canView($a)) abort(404);
    Service::grant($a);
    return $a;
}

function service_view(string $code): void
{
    $a = service_load($code);
    $survey = DB::row('SELECT * FROM surveys WHERE appointment_id = ?', [$a['id']]);
    view('site/service_view', compact('a', 'survey') + ['title' => 'نوبت ' . $a['code'], 'isNew' => !empty($_GET['new'])]);
}

function service_cancel(string $code): void
{
    $a = service_load($code);
    if (!Service::canCancel($a)) { flash('error', 'این نوبت دیگر قابل لغو آنلاین نیست. لطفاً تماس بگیرید.'); redirect('service/a/' . $a['code']); }
    DB::update('appointments', ['status' => 'cancelled', 'admin_note' => trim(($a['admin_note'] ?? '') . ' [لغو توسط مشتری]'), 'updated_at' => now()], 'id = ?', [$a['id']]);
    Service::notify($a, 'cancelled');
    flash('success', 'نوبت لغو شد.');
    redirect('service/a/' . $a['code']);
}

function service_survey(string $code): void
{
    $a = service_load($code);
    if (!module('surveys') || $a['status'] !== 'done') { redirect('service/a/' . $a['code']); }
    $done = DB::row('SELECT * FROM surveys WHERE appointment_id = ?', [$a['id']]);
    if (is_post() && !$done) {
        $r = fn($k) => ($v = input_int($k)) >= 1 && $v <= 5 ? $v : null;
        if (!$r('overall')) { flash('error', 'لطفاً امتیاز کلی را انتخاب کنید.'); redirect('service/survey/' . $a['code']); }
        DB::insert('surveys', ['appointment_id' => $a['id'], 'overall' => $r('overall'), 'quality' => $r('quality'), 'timing' => $r('timing'),
            'staff' => $r('staff'), 'recommend' => input('recommend') === '' ? null : (input('recommend') === '1' ? 1 : 0),
            'comment' => mb_substr(trim((string)input('comment')), 0, 2000), 'created_at' => now()]);
        flash('success', 'از نظر شما سپاسگزاریم.');
        redirect('service/a/' . $a['code']);
    }
    view('site/service_survey', compact('a', 'done') + ['title' => 'نظرسنجی خدمات']);
}
