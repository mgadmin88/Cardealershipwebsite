<?php
declare(strict_types=1);

final class Settings
{
    private static ?array $cache = null;

    private static function load(): void
    {
        if (self::$cache !== null) return;
        self::$cache = [];
        try {
            foreach (DB::all('SELECT k, v FROM settings') as $r) self::$cache[$r['k']] = $r['v'];
        } catch (Throwable) {}
    }

    public static function get(string $key, mixed $default = ''): mixed
    {
        self::load();
        return self::$cache[$key] ?? $default;
    }

    public static function json(string $key, array $default = []): array
    {
        $v = json_decode((string)self::get($key, ''), true);
        return is_array($v) ? $v : $default;
    }

    public static function set(string $key, mixed $value): void
    {
        self::load();
        $value = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value;
        if (array_key_exists($key, self::$cache) || DB::val('SELECT 1 FROM settings WHERE k = ?', [$key])) {
            DB::update('settings', ['v' => $value], 'k = ?', [$key]);
        } else {
            DB::insert('settings', ['k' => $key, 'v' => $value]);
        }
        self::$cache[$key] = $value;
    }

    public static function setMany(array $pairs): void
    {
        DB::tx(function () use ($pairs) { foreach ($pairs as $k => $v) self::set($k, $v); });
    }
}
