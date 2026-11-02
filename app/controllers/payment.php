<?php
declare(strict_types=1);

function pay_start(string $code): void
{
    require_module('online_payment');
    $order = DB::row('SELECT * FROM orders WHERE code = ?', [$code]);
    if (!$order || !Orders::canView($order)) abort(404);
    if (!in_array($order['status'], ['pending', 'failed'], true)) redirect(Orders::link($order));
    if (!rate_limit('pay', 15, 600)) { flash('error', 'تعداد تلاش برای پرداخت زیاد است.'); redirect(Orders::link($order)); }

    // car orders: make sure the car has not been reserved by someone else meanwhile
    if ($order['type'] === 'car') {
        $car = DB::find('cars', (int)$order['car_id']);
        if (!$car || $car['status'] !== 'available') { flash('error', 'متاسفانه این خودرو دیگر قابل رزرو نیست.'); redirect(Orders::link($order)); }
    }
    $gw = DB::row('SELECT * FROM gateways WHERE id = ? AND active = 1', [input_int('gw')]) ?? DB::row('SELECT * FROM gateways WHERE active = 1 ORDER BY sort LIMIT 1');
    if (!$gw) { flash('error', 'درگاه پرداخت فعالی وجود ندارد.'); redirect(Orders::link($order)); }
    if ($order['status'] === 'failed') DB::update('orders', ['status' => 'pending'], 'id = ?', [$order['id']]);

    $r = Payment::start($order, $gw);
    if (!empty($r['error'])) { flash('error', $r['error']); redirect(Orders::link($order)); }
    if (!empty($r['redirect'])) redirect($r['redirect']);
    view('site/pay_redirect', ['form' => $r['form'], 'label' => $gw['label'], 'title' => 'انتقال به درگاه']);
}

function pay_callback(string $txId): void
{
    $in = array_merge($_GET, $_POST);
    $r = Payment::callback((int)$txId, $in);
    $order = $r['order'] ?? null;
    if (!$order) abort(404);
    Orders::grant($order);
    if (!$r['ok'] && !empty($r['error'])) flash('error', $r['error']);
    redirect(url('order/' . $order['code'], ['t' => $order['track_token'], 'r' => $r['ok'] ? 'ok' : 'fail']));
}

function pay_test_bank(string $authority): void
{
    $tx = DB::row("SELECT t.* FROM transactions t JOIN gateways g ON g.id = t.gateway_id WHERE t.authority = ? AND t.driver = 'test' AND g.active = 1 AND t.status = 'init'", [$authority]);
    if (!$tx) abort(404);
    view('site/test_bank', ['tx' => $tx, 'sig' => TestGateway::sig($tx), 'title' => 'درگاه آزمایشی']);
}
