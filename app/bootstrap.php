<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
define('APP', ROOT . '/app');
if (!defined('PUB')) define('PUB', ROOT . '/public');   // web root (public or public_html)
define('STORAGE', ROOT . '/storage');

if (PHP_VERSION_ID < 80100) {
    http_response_code(500);
    exit('PHP 8.1 or newer is required.');
}

$configFile = ROOT . '/config.php';
if (!is_file($configFile)) {
    if (PHP_SAPI !== 'cli' && !str_contains($_SERVER['REQUEST_URI'] ?? '', 'install.php')) {
        header('Location: install.php');
        exit;
    }
    $GLOBALS['config'] = require ROOT . '/config.sample.php';
} else {
    $GLOBALS['config'] = require $configFile;
}

date_default_timezone_set(config('timezone', 'Asia/Tehran'));
mb_internal_encoding('UTF-8');

/* ---------- Error handling ---------- */
error_reporting(E_ALL);
ini_set('display_errors', config('debug') ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', STORAGE . '/logs/php-error.log');

/* ---------- Autoload ---------- */
spl_autoload_register(function (string $class): void {
    $class = str_replace('\\', '/', $class);
    foreach (['/lib/', '/lib/payment/', '/lib/sms/'] as $dir) {
        $f = APP . $dir . basename($class) . '.php';
        if (is_file($f)) { require $f; return; }
    }
});

require APP . '/helpers.php';
require APP . '/lib/jdate.php';

function config(string $key, mixed $default = null): mixed
{
    $v = $GLOBALS['config'];
    foreach (explode('.', $key) as $k) {
        if (!is_array($v) || !array_key_exists($k, $v)) return $default;
        $v = $v[$k];
    }
    return $v;
}

/* ---------- Session (hardened) ---------- */
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    $secure = is_https();
    session_name('AS_SESS');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.save_path', STORAGE . '/sessions');
    if (!is_dir(STORAGE . '/sessions')) @mkdir(STORAGE . '/sessions', 0700, true);
    session_start();
    if (empty($_SESSION['_created'])) $_SESSION['_created'] = time();
    if (time() - $_SESSION['_created'] > 1800) {      // rotate id every 30 min
        session_regenerate_id(true);
        $_SESSION['_created'] = time();
    }
}

/* ---------- Security headers ---------- */
if (PHP_SAPI !== 'cli' && !headers_sent()) {
    if (config('force_https') && !is_https()) {
        header('Location: https://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
        exit;
    }
    $GLOBALS['csp_nonce'] = base64_encode(random_bytes(16));
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(self)');
    header('Cross-Origin-Opener-Policy: same-origin');
    if (is_https()) header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob: https:; "
        . "style-src 'self' 'unsafe-inline'; script-src 'self' 'nonce-{$GLOBALS['csp_nonce']}'; "
        . "font-src 'self' data:; frame-src https://www.google.com https://maps.google.com https://www.openstreetmap.org https://map.ir https://neshan.org; "
        . "connect-src 'self'; form-action 'self' https:; base-uri 'self'; frame-ancestors 'self'");
    header_remove('X-Powered-By');
}
