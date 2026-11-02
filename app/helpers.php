<?php
declare(strict_types=1);

/* =========================================================
 *  Output / escaping
 * ======================================================= */
function e(mixed $v): string
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function nonce(): string { return $GLOBALS['csp_nonce'] ?? ''; }

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (($_SERVER['SERVER_PORT'] ?? '') == 443);
}

function base_path(): string
{
    static $b = null;
    if ($b !== null) return $b;
    $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $script = rtrim($script === '/' ? '' : $script, '/');
    // when served through the root .htaccess (docroot = project root), hide "/public" from URLs
    $req = $_SERVER['REQUEST_URI'] ?? '/';
    if (str_ends_with($script, '/public') && !str_starts_with($req, $script . '/') && $req !== $script) {
        $script = substr($script, 0, -7);
    }
    return $b = $script;
}

function base_url(): string
{
    $cfg = rtrim((string)config('base_url', ''), '/');
    if ($cfg) return $cfg;
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return (is_https() ? 'https://' : 'http://') . $host . base_path();
}

function url(string $path = '', array $query = []): string
{
    $u = base_path() . '/' . ltrim($path, '/');
    if ($query) $u .= '?' . http_build_query(array_filter($query, fn($v) => $v !== '' && $v !== null));
    return $u === '' ? '/' : $u;
}

function abs_url(string $path = ''): string { return base_url() . '/' . ltrim($path, '/'); }

function asset(string $path): string
{
    $file = PUB . '/assets/' . $path;
    $v = is_file($file) ? substr(md5((string)filemtime($file)), 0, 8) : '1';
    return url('assets/' . $path) . '?v=' . $v;
}

function upload_url(?string $path, string $fallback = 'img/car-placeholder.svg'): string
{
    if (!$path) return asset($fallback);
    if (str_starts_with($path, 'http')) return $path;
    if (str_starts_with($path, 'assets/')) return url($path);
    return url('uploads/' . ltrim($path, '/'));
}

function redirect(string $to, int $code = 302): never
{
    $bp = base_path();
    if (!preg_match('~^https?://~', $to) && !($bp !== '' && str_starts_with($to, $bp . '/'))) $to = url($to);
    header('Location: ' . $to, true, $code);
    exit;
}

function back(): never
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    // only same-host redirects (open-redirect protection)
    $refHost = (string)parse_url($ref, PHP_URL_HOST) . (($p = parse_url($ref, PHP_URL_PORT)) ? ':' . $p : '');
    if ($ref && $refHost === ($_SERVER['HTTP_HOST'] ?? '')) {
        header('Location: ' . $ref, true, 302);
        exit;
    }
    redirect('/');
}

function json_out(mixed $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function abort(int $code = 404, string $msg = ''): never
{
    http_response_code($code);
    if ($code === 404) {
        view('site/404', ['title' => 'صفحه پیدا نشد']);
    } else {
        echo '<!doctype html><meta charset="utf-8"><body style="font-family:tahoma;direction:rtl;padding:40px">'
            . '<h2>خطا ' . $code . '</h2><p>' . e($msg ?: 'دسترسی امکان‌پذیر نیست.') . '</p></body>';
    }
    exit;
}

/* =========================================================
 *  Views
 * ======================================================= */
function view(string $name, array $data = [], ?string $layout = null): void
{
    $file = APP . '/views/' . $name . '.php';
    if (!is_file($file)) throw new RuntimeException("View not found: $name");
    extract($data, EXTR_SKIP);
    ob_start();
    require $file;
    $content = ob_get_clean();
    if ($layout === null) $layout = str_starts_with($name, 'admin/') ? 'admin/_layout' : 'site/_layout';
    if ($layout === '') { echo $content; return; }
    require APP . '/views/' . $layout . '.php';
}

function partial(string $name, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require APP . '/views/partials/' . $name . '.php';
}

/* =========================================================
 *  Request helpers
 * ======================================================= */
function is_post(): bool { return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'; }

function input(string $key, mixed $default = ''): mixed
{
    $v = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $v;
}

function input_int(string $key, int $default = 0): int
{
    $v = to_en_digits((string)input($key, ''));
    $v = preg_replace('/[^\d\-]/', '', $v);
    return $v === '' || $v === '-' ? $default : (int)$v;
}

function client_ip(): string
{
    return filter_var($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', FILTER_VALIDATE_IP) ?: '0.0.0.0';
}

/* =========================================================
 *  CSRF
 * ======================================================= */
function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $t = $_POST['_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($t) || !hash_equals(csrf_token(), $t)) {
        abort(419, 'نشست شما منقضی شده است. لطفاً صفحه را تازه کنید و دوباره تلاش کنید.');
    }
}

/* =========================================================
 *  Flash / old input
 * ======================================================= */
function flash(string $type, string $msg): void { $_SESSION['_flash'][] = [$type, $msg]; }

function flashes(): array
{
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

function keep_old(): void { $_SESSION['_old'] = $_POST; unset($_SESSION['_old']['_token']); }

function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['_old'][$key] ?? $default;
}

function clear_old(): void { unset($_SESSION['_old']); }

/* =========================================================
 *  Settings & modules
 * ======================================================= */
function setting(string $key, mixed $default = ''): mixed { return Settings::get($key, $default); }

function module(string $key): bool
{
    $v = Settings::get('module_' . $key, null);
    if ($v === null) $v = (string)(Catalog::MODULES[$key][2] ?? '0');   // module added by an update → its default
    return $v === '1';
}

function require_module(string $key): void { if (!module($key)) abort(404); }

/* =========================================================
 *  Formatting
 * ======================================================= */
function to_en_digits(string $s): string
{
    return strtr($s, ['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
        '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9']);
}

function fa(mixed $s): string
{
    return strtr((string)$s, ['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹']);
}

function num(mixed $n): string { return number_format((float)$n); }

function money(mixed $n, bool $unit = true): string
{
    return num($n) . ($unit ? ' <small class="unit">تومان</small>' : '');
}

function money_text(mixed $n): string { return num($n) . ' تومان'; }

function price_words(int $n): string
{
    if ($n >= 1_000_000_000) return rtrim(rtrim(number_format($n / 1_000_000_000, 2), '0'), '.') . ' میلیارد';
    if ($n >= 1_000_000) return rtrim(rtrim(number_format($n / 1_000_000, 1), '0'), '.') . ' میلیون';
    return num($n);
}

function slugify(string $s): string
{
    $s = trim(mb_strtolower($s));
    $s = preg_replace('/[^\p{L}\p{N}]+/u', '-', $s);
    return trim($s, '-') ?: 'item';
}

function excerpt(string $s, int $len = 140): string
{
    $s = trim(preg_replace('/\s+/u', ' ', strip_tags($s)));
    return mb_strlen($s) > $len ? mb_substr($s, 0, $len) . '…' : $s;
}

function normalize_mobile(string $m): string
{
    $m = preg_replace('/\D/', '', to_en_digits($m));
    if (str_starts_with($m, '98')) $m = '0' . substr($m, 2);
    if (strlen($m) === 10 && $m[0] === '9') $m = '0' . $m;
    return $m;
}

function valid_mobile(string $m): bool { return (bool)preg_match('/^09\d{9}$/', $m); }

function valid_national_code(string $c): bool
{
    $c = to_en_digits($c);
    if (!preg_match('/^\d{10}$/', $c) || preg_match('/^(\d)\1{9}$/', $c)) return false;
    $sum = 0;
    for ($i = 0; $i < 9; $i++) $sum += (int)$c[$i] * (10 - $i);
    $r = $sum % 11;
    return (int)$c[9] === ($r < 2 ? $r : 11 - $r);
}

/** rich-text sanitizer for admin-entered HTML (about page, descriptions) */
function clean_html(string $html): string
{
    $allowed = '<p><br><b><strong><i><em><u><ul><ol><li><h2><h3><h4><a><blockquote><span><hr><table><tr><td><th><thead><tbody>';
    $html = strip_tags($html, $allowed);
    // remove event handlers / javascript: urls / style expressions
    $html = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
    $html = preg_replace('/(href|src)\s*=\s*([\'"])\s*(javascript|data|vbscript):[^\'"]*\2/i', '$1="#"', $html);
    $html = preg_replace('/\sstyle\s*=\s*("[^"]*"|\'[^\']*\')/i', '', $html);
    return $html;
}

function nl2p(string $text): string
{
    if (preg_match('/<(p|ul|ol|h\d|table)\b/i', $text)) return clean_html($text);
    $parts = preg_split('/\n\s*\n/', trim($text));
    return implode('', array_map(fn($p) => '<p>' . nl2br(e($p)) . '</p>', array_filter($parts)));
}

/* =========================================================
 *  Rate limiting (file based, per IP + key)
 * ======================================================= */
function rate_limit(string $key, int $max, int $seconds): bool
{
    $dir = STORAGE . '/cache/rl';
    if (!is_dir($dir)) @mkdir($dir, 0700, true);
    $file = $dir . '/' . hash('sha256', $key . '|' . client_ip()) . '.json';
    $now = time();
    $hits = is_file($file) ? (json_decode((string)file_get_contents($file), true) ?: []) : [];
    $hits = array_values(array_filter($hits, fn($t) => $t > $now - $seconds));
    if (count($hits) >= $max) return false;
    $hits[] = $now;
    file_put_contents($file, json_encode($hits), LOCK_EX);
    return true;
}

/* honeypot anti-spam field for public forms */
function honeypot(): string
{
    return '<div class="hp" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>';
}
function honeypot_failed(): bool { return !empty($_POST['website']); }

/* =========================================================
 *  Misc
 * ======================================================= */
function log_activity(string $action, string $details = ''): void
{
    try {
        DB::insert('activity_log', [
            'user_id' => Auth::id(), 'action' => $action, 'details' => mb_substr($details, 0, 500),
            'ip' => client_ip(), 'created_at' => now(),
        ]);
    } catch (Throwable) {}
}

function now(): string { return date('Y-m-d H:i:s'); }

function app_log(string $channel, string $msg): void
{
    @file_put_contents(STORAGE . '/logs/' . preg_replace('/\W/', '', $channel) . '.log',
        '[' . now() . '] ' . $msg . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function paginate(int $total, int $perPage, int $page): array
{
    $pages = max(1, (int)ceil($total / $perPage));
    $page = min(max(1, $page), $pages);
    return ['total' => $total, 'per' => $perPage, 'page' => $page, 'pages' => $pages, 'offset' => ($page - 1) * $perPage];
}

function query_with(array $changes): string
{
    $q = array_merge($_GET, $changes);
    unset($q['_url']);
    return '?' . http_build_query(array_filter($q, fn($v) => $v !== '' && $v !== null));
}

function icon(string $name, string $cls = ''): string
{
    return '<svg class="ic ' . e($cls) . '" aria-hidden="true"><use href="' . asset('img/icons.svg') . '#' . e($name) . '"></use></svg>';
}

function selected(mixed $a, mixed $b): string { return (string)$a === (string)$b ? ' selected' : ''; }
function checked(mixed $v): string { return $v ? ' checked' : ''; }

/** request status label for buyers */
function request_badge(string $s): string
{
    [$l, $c] = ['new' => ['ثبت شد، در صف بررسی', 'warn'], 'seen' => ['در حال پیگیری کارشناس', 'info'], 'done' => ['انجام شد', 'ok']][$s] ?? [$s, 'info'];
    return '<span class="badge ' . $c . '">' . e($l) . '</span>';
}
