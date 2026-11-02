<?php
declare(strict_types=1);

/**
 * Self-hosted image captcha (no external service — works on Iranian hosts without Google access).
 *  - 5 digits drawn with GD, per-character rotation/offset, noise arcs & dots
 *  - answer stored hashed in the session per form, single-use, 10 minute lifetime
 *  - accepts Persian or Latin digits
 *  - plus a hidden "website" honeypot field that bots tend to fill
 */
final class Captcha
{
    private const TTL = 600;
    private const LEN = 5;

    public static function enabled(): bool
    {
        return module('captcha') && function_exists('imagecreatetruecolor');
    }

    private static function form(string $f): string { return preg_match('/^[a-z_]{2,20}$/', $f) ? $f : 'default'; }

    /** HTML for a form: image + refresh + input (pair with honeypot()) */
    public static function field(string $form, bool $lazy = false): string
    {
        if (!self::enabled()) return '';
        $form = self::form($form);
        $src = url('captcha/' . $form);
        $img = $lazy ? '<img alt="کد امنیتی" width="150" height="50" data-captcha-img data-src="' . e($src) . '">'
                     : '<img alt="کد امنیتی" width="150" height="50" data-captcha-img src="' . e($src . '?r=' . bin2hex(random_bytes(4))) . '">';
        return '<div class="field captcha-field"><label>کد امنیتی</label><div class="captcha-box">'
            . '<input name="captcha" inputmode="numeric" autocomplete="off" maxlength="6" required placeholder="کد تصویر" dir="ltr">'
            . $img . '<button type="button" class="captcha-refresh" data-captcha-refresh title="کد جدید" aria-label="نمایش کد جدید">' . icon('refresh') . '</button>'
            . '</div></div>';
    }

    /** Verify the posted answer (single use). Honeypot is always checked. */
    public static function verify(string $form): bool
    {
        if (trim((string)($_POST['website'] ?? '')) !== '') return false;
        if (!self::enabled()) return true;
        $form = self::form($form);
        $saved = $_SESSION['captcha'][$form] ?? null;
        unset($_SESSION['captcha'][$form]);                         // single use, success or not
        if (!$saved || time() - $saved['t'] > self::TTL) return false;
        $given = preg_replace('/\D/', '', to_en_digits((string)($_POST['captcha'] ?? '')));
        return $given !== '' && hash_equals($saved['h'], hash_hmac('sha256', $given, (string)config('app_key')));
    }

    /** Output a PNG and remember its answer */
    public static function image(string $form): never
    {
        $form = self::form($form);
        $code = '';
        for ($i = 0; $i < self::LEN; $i++) $code .= (string)random_int(($i === 0 ? 1 : 0), 9);
        $_SESSION['captcha'][$form] = ['h' => hash_hmac('sha256', $code, (string)config('app_key')), 't' => time()];

        $w = 150; $h = 50;
        $im = imagecreatetruecolor($w, $h);
        imagefill($im, 0, 0, imagecolorallocate($im, 246, 247, 251));
        // background noise arcs
        for ($i = 0; $i < 6; $i++) {
            $c = imagecolorallocate($im, random_int(150, 210), random_int(150, 210), random_int(170, 225));
            imagesetthickness($im, random_int(1, 2));
            imagearc($im, random_int(0, $w), random_int(0, $h), random_int(60, 220), random_int(30, 90), random_int(0, 360), random_int(0, 360), $c);
        }
        imagesetthickness($im, 1);
        // digits: draw each glyph small, rotate, scale up onto the canvas
        $x = 10;
        foreach (str_split($code) as $ch) {
            $g = imagecreatetruecolor(12, 18);
            $bg = imagecolorallocate($g, 246, 247, 251);
            imagefill($g, 0, 0, $bg);
            imagecolortransparent($g, $bg);
            $col = imagecolorallocate($g, random_int(10, 70), random_int(20, 80), random_int(80, 150));
            imagestring($g, 5, 2, 1, $ch, $col);
            $r = imagerotate($g, random_int(-10, 10), $bg);
            imagecolortransparent($r, $bg);
            $sw = imagesx($r); $sh = imagesy($r);
            $scale = random_int(185, 215) / 100;
            imagecopyresampled($im, $r, $x, random_int(2, 10), 0, 0, (int)($sw * $scale), (int)($sh * $scale), $sw, $sh);
            $x += random_int(23, 27);
            imagedestroy($g); imagedestroy($r);
        }
        // foreground noise
        for ($i = 0; $i < 3; $i++) {
            imageline($im, 0, random_int(8, $h - 8), $w, random_int(8, $h - 8), imagecolorallocate($im, random_int(80, 140), random_int(90, 150), random_int(130, 190)));
        }
        for ($i = 0; $i < 220; $i++) imagesetpixel($im, random_int(0, $w - 1), random_int(0, $h - 1), imagecolorallocate($im, random_int(100, 200), random_int(100, 200), random_int(100, 200)));

        header('Content-Type: image/png');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        imagepng($im);
        imagedestroy($im);
        exit;
    }
}
