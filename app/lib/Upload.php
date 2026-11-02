<?php
declare(strict_types=1);

/**
 * Secure image upload:
 *  - size limit, real MIME detection (finfo, or magic bytes + getimagesize when ext-fileinfo is missing), extension whitelist
 *  - re-encodes images through GD (strips EXIF/GPS & any embedded payload)
 *  - random file names, date-based folders, auto thumbnail + WebP when available
 */
final class Upload
{
    private const MIMES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];

    /** Real content type: finfo when available; otherwise file signature + getimagesize (works on hosts without ext-fileinfo) */
    private static function mime(string $path): string
    {
        if (class_exists('finfo')) {
            $m = (string)(new finfo(FILEINFO_MIME_TYPE))->file($path);
            if ($m !== '' && $m !== 'application/octet-stream') return $m;
        }
        $head = (string)@file_get_contents($path, false, null, 0, 16);
        $sig = match (true) {
            str_starts_with($head, "\xFF\xD8\xFF") => 'image/jpeg',
            str_starts_with($head, "\x89PNG\r\n\x1A\n") => 'image/png',
            str_starts_with($head, 'GIF87a'), str_starts_with($head, 'GIF89a') => 'image/gif',
            str_starts_with($head, 'RIFF') && substr($head, 8, 4) === 'WEBP' => 'image/webp',
            default => '',
        };
        $info = @getimagesize($path);
        // both checks must agree
        return ($sig !== '' && is_array($info) && ($info['mime'] ?? '') === $sig) ? $sig : '';
    }

    /** Normalize $_FILES['x'] (single or multiple) into a list */
    public static function files(string $field): array
    {
        $f = $_FILES[$field] ?? null;
        if (!$f) return [];
        if (!is_array($f['name'])) return $f['error'] === UPLOAD_ERR_NO_FILE ? [] : [$f];
        $out = [];
        foreach ($f['name'] as $i => $n) {
            if ($f['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
            $out[] = ['name' => $n, 'type' => $f['type'][$i], 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]];
        }
        return $out;
    }

    /** @return array{path:string, thumb:string}|string  string = error message */
    public static function image(array $file, string $folder = 'cars', int $thumbW = 480): array|string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) return 'خطا در بارگذاری فایل.';
        $max = (int)config('upload.max_size_mb', 8) * 1024 * 1024;
        if ($file['size'] > $max) return 'حجم فایل بیش از حد مجاز است.';
        if (!is_uploaded_file($file['tmp_name']) && PHP_SAPI !== 'cli') return 'فایل نامعتبر است.';

        $mime = self::mime($file['tmp_name']);
        if (!isset(self::MIMES[$mime])) return 'فقط تصاویر JPG، PNG، WEBP و GIF مجاز هستند.';
        $info = @getimagesize($file['tmp_name']);
        if (!$info || $info[0] < 10 || $info[0] > 12000 || $info[1] > 12000) return 'تصویر معتبر نیست.';

        $sub = $folder . '/' . date('Y/m');
        $dir = PUB . '/uploads/' . $sub;
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $name = bin2hex(random_bytes(12));

        if (!function_exists('imagecreatefromstring')) {
            // GD unavailable: fall back to moving the file with a safe extension
            $ext = self::MIMES[$mime];
            move_uploaded_file($file['tmp_name'], "$dir/$name.$ext");
            return ['path' => "$sub/$name.$ext", 'thumb' => "$sub/$name.$ext"];
        }

        $src = @imagecreatefromstring((string)file_get_contents($file['tmp_name']));
        if (!$src) return 'امکان پردازش تصویر وجود ندارد.';
        $src = self::orient($src, $file['tmp_name'], $mime);

        $useWebp = function_exists('imagewebp');
        $ext = $useWebp ? 'webp' : 'jpg';
        $maxW = (int)config('upload.max_width', 1920);

        $main = self::resize($src, $maxW);
        self::save($main, "$dir/$name.$ext", $ext, 82);
        $thumb = self::resize($src, $thumbW);
        self::save($thumb, "$dir/{$name}_t.$ext", $ext, 76);
        imagedestroy($src);

        return ['path' => "$sub/$name.$ext", 'thumb' => "$sub/{$name}_t.$ext"];
    }

    private static function resize(GdImage $src, int $maxW): GdImage
    {
        $w = imagesx($src); $h = imagesy($src);
        if ($w <= $maxW) { $nw = $w; $nh = $h; } else { $nw = $maxW; $nh = (int)round($h * $maxW / $w); }
        $dst = imagecreatetruecolor($nw, $nh);
        imagealphablending($dst, false); imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        return $dst;
    }

    private static function save(GdImage $img, string $path, string $ext, int $q): void
    {
        $ext === 'webp' ? imagewebp($img, $path, $q) : imagejpeg($img, $path, $q);
        imagedestroy($img);
    }

    private static function orient(GdImage $img, string $file, string $mime): GdImage
    {
        if ($mime !== 'image/jpeg' || !function_exists('exif_read_data')) return $img;
        $exif = @exif_read_data($file);
        $o = $exif['Orientation'] ?? 1;
        return match ((int)$o) { 3 => imagerotate($img, 180, 0), 6 => imagerotate($img, -90, 0), 8 => imagerotate($img, 90, 0), default => $img };
    }

    public static function remove(?string ...$paths): void
    {
        foreach ($paths as $p) {
            if (!$p || str_contains($p, '..') || str_starts_with($p, 'assets/')) continue;
            $f = PUB . '/uploads/' . $p;
            if (is_file($f)) @unlink($f);
        }
    }
}
