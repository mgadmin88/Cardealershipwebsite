<?php
declare(strict_types=1);

final class Theme
{
    public static function colors(): array
    {
        $preset = Catalog::THEMES[setting('theme_preset', 'navy')][1] ?? Catalog::THEMES['navy'][1];
        $out = [];
        foreach ($preset as $k => $def) {
            $v = (string)setting('theme_' . $k, $def);
            $out[$k] = preg_match('/^#[0-9a-f]{3,8}$/i', $v) ? $v : $def;
        }
        return $out;
    }

    /** readable text color on top of a given bg color */
    public static function ink(string $hex): string
    {
        $h = ltrim($hex, '#');
        if (strlen($h) === 3) $h = $h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2];
        [$r, $g, $b] = array_map('hexdec', str_split(substr($h, 0, 6), 2));
        return (0.299 * $r + 0.587 * $g + 0.114 * $b) > 160 ? '#111827' : '#ffffff';
    }

    public static function css(): string
    {
        $c = self::colors();
        $radius = max(0, min(28, (int)setting('theme_radius', 14)));
        $container = max(960, min(1600, (int)setting('theme_container', 1280)));
        $scale = max(85, min(120, (int)setting('theme_font_scale', 100))) / 100;
        $css = ":root{--primary:{$c['primary']};--accent:{$c['accent']};--bg:{$c['bg']};--surface:{$c['surface']};--text:{$c['text']};"
            . "--dark-bg:{$c['dark_bg']};--dark-surface:{$c['dark_surface']};--radius:{$radius}px;--container:{$container}px;--font-scale:$scale;"
            . "--primary-ink:" . self::ink($c['primary']) . ";--accent-ink:" . self::ink($c['accent']) . "}";
        $custom = (string)setting('theme_custom_css', '');
        // custom css: strip anything that could break out of <style>
        $custom = str_ireplace(['</style', '<script', 'javascript:', 'expression('], '', $custom);
        return $css . "\n" . $custom;
    }

    public static function mode(): string
    {
        $m = setting('theme_mode', 'light');
        return in_array($m, ['light', 'dark', 'auto'], true) ? $m : 'light';
    }
}
