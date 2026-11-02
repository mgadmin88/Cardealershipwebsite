<?php
declare(strict_types=1);

/** Encrypts sensitive config values (gateway passwords, API keys) at rest with AES-256-GCM using app_key. */
final class Secret
{
    private static function key(): string
    {
        return hash('sha256', (string)config('app_key', 'change-me'), true);
    }

    public static function encrypt(string $plain): string
    {
        if ($plain === '') return '';
        $iv = random_bytes(12);
        $ct = openssl_encrypt($plain, 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $iv, $tag);
        return 'enc:' . base64_encode($iv . $tag . $ct);
    }

    public static function decrypt(string $val): string
    {
        if (!str_starts_with($val, 'enc:')) return $val;
        $bin = base64_decode(substr($val, 4), true);
        if ($bin === false || strlen($bin) < 29) return '';
        $pt = openssl_decrypt(substr($bin, 28), 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, substr($bin, 0, 12), substr($bin, 12, 16));
        return $pt === false ? '' : $pt;
    }

    public static function decryptArray(array $a): array
    {
        return array_map(fn($v) => is_string($v) ? self::decrypt($v) : $v, $a);
    }

    public static function mask(string $v): string
    {
        $p = self::decrypt($v);
        return $p === '' ? '' : str_repeat('•', 8) . mb_substr($p, -3);
    }
}
