<?php
declare(strict_types=1);

// PHP built-in dev server: serve existing static files directly
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) return false;

define('PUB', __DIR__);
require dirname(__DIR__) . '/app/bootstrap.php';

/* ---------- Resolve path ---------- */
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = '/' . trim(substr(rawurldecode($uri), strlen(base_path())), '/');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$isAdmin = str_starts_with($path, '/admin');

require APP . '/controllers/site.php';
require APP . '/controllers/shop.php';
require APP . '/controllers/payment.php';
if ($isAdmin) require APP . '/controllers/admin.php';

/* ---------- Global CSRF for every POST (payment callbacks from banks are exempt) ---------- */
if ($method === 'POST' && !str_starts_with($path, '/payment/callback/')) csrf_check();

/* ---------- Captcha + honeypot on public forms ---------- */
if ($method === 'POST') {
    foreach (['#^/contact$#' => 'contact', '#^/sell$#' => 'sell', '#^/request$#' => 'request', '#^/buy/\d+$#' => 'buy',
              '#^/checkout$#' => 'checkout', '#^/track$#' => 'track'] as $re => $form) {
        if (!preg_match($re, $path) || Captcha::verify($form)) continue;
        $msg = 'کد امنیتی اشتباه است یا منقضی شده. کد جدید را وارد کنید.';
        if (($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json') json_out(['ok' => false, 'msg' => $msg]);
        keep_old(); flash('error', $msg);
        $form === 'request' ? back() : redirect(ltrim($path, '/'));
    }
}

/* ---------- Maintenance mode ---------- */
if (!$isAdmin && module('maintenance') && !Auth::check() && !str_starts_with($path, '/payment/')) {
    http_response_code(503);
    view('site/maintenance', [], '');
    exit;
}

/* ---------- Auto-upgrade database after an update ---------- */
try { Schema::upgrade(); } catch (Throwable $e) { app_log('error', 'schema upgrade: ' . $e->getMessage()); }

/* ---------- Dispatch ---------- */
try {
    foreach (require APP . '/routes.php' as [$methods, $pattern, $handler]) {
        if (!in_array($method, explode('|', $methods), true) && !($method === 'HEAD' && str_contains($methods, 'GET'))) continue;
        $re = '#^' . strtr(preg_quote($pattern, '#'), ['\{id\}' => '(\d+)', '\{any\}' => '([^/]+)']) . '$#u';
        if (preg_match($re, $path, $m)) {
            array_shift($m);
            $handler(...$m);
            exit;
        }
    }
    abort(404);
} catch (Throwable $e) {
    app_log('error', $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    if (config('debug')) { echo '<pre>' . e((string)$e) . '</pre>'; exit; }
    // logged-in admins see the actual cause (visitors never do)
    $isAdminUser = false;
    try { $isAdminUser = Auth::check() && (Auth::user()['role'] ?? '') === 'admin'; } catch (Throwable) {}
    if ($isAdminUser) {
        echo '<!doctype html><meta charset="utf-8"><body style="font-family:tahoma;direction:rtl;padding:40px;max-width:900px;margin:auto">'
            . '<h2>خطای داخلی سرور</h2><p>این جزئیات فقط برای مدیر سایت نمایش داده می‌شود. لطفاً از این صفحه عکس بگیرید و برای پشتیبانی بفرستید:</p>'
            . '<pre dir="ltr" style="background:#f3f4f6;padding:16px;border-radius:10px;white-space:pre-wrap;font-size:13px">'
            . e(get_class($e) . ': ' . $e->getMessage() . "
" . str_replace(ROOT, '', $e->getFile()) . ':' . $e->getLine()
                . "
PHP " . PHP_VERSION . ' | DB ' . (function () { try { return DB::driver() . ' ' . DB::pdo()->getAttribute(PDO::ATTR_SERVER_VERSION); } catch (Throwable) { return '?'; } })()
                . "
" . str_replace(ROOT, '', $e->getTraceAsString()))
            . '</pre><p><a href="javascript:history.back()">بازگشت</a></p></body>';
        exit;
    }
    echo '<!doctype html><meta charset="utf-8"><body style="font-family:tahoma;direction:rtl;padding:40px;text-align:center">'
        . '<h2>خطای داخلی سرور</h2><p>مشکلی پیش آمد. لطفاً دقایقی بعد دوباره تلاش کنید.</p></body>';
}
