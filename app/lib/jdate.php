<?php
declare(strict_types=1);

/** Gregorian → Jalali (algorithm by jdf.scr.ir, public domain) */
function g2j(int $gy, int $gm, int $gd): array
{
    $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
    $days = 355666 + (365 * $gy) + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100) + intdiv($gy2 + 399, 400) + $gd + $g_d_m[$gm - 1];
    $jy = -1595 + (33 * intdiv($days, 12053));
    $days %= 12053;
    $jy += 4 * intdiv($days, 1461);
    $days %= 1461;
    if ($days > 365) {
        $jy += intdiv($days - 1, 365);
        $days = ($days - 1) % 365;
    }
    if ($days < 186) {
        $jm = 1 + intdiv($days, 31);
        $jd = 1 + ($days % 31);
    } else {
        $jm = 7 + intdiv($days - 186, 30);
        $jd = 1 + (($days - 186) % 30);
    }
    return [$jy, $jm, $jd];
}

function j2g(int $jy, int $jm, int $jd): array
{
    $jy += 1595;
    $days = -355668 + (365 * $jy) + (intdiv($jy, 33) * 8) + intdiv(($jy % 33) + 3, 4) + $jd + (($jm < 7) ? ($jm - 1) * 31 : (($jm - 7) * 30) + 186);
    $gy = 400 * intdiv($days, 146097);
    $days %= 146097;
    if ($days > 36524) {
        $gy += 100 * intdiv(--$days, 36524);
        $days %= 36524;
        if ($days >= 365) $days++;
    }
    $gy += 4 * intdiv($days, 1461);
    $days %= 1461;
    if ($days > 365) {
        $gy += intdiv($days - 1, 365);
        $days = ($days - 1) % 365;
    }
    $gd = $days + 1;
    $sal = [0, 31, (($gy % 4 == 0 && $gy % 100 != 0) || ($gy % 400 == 0)) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    for ($gm = 0; $gm < 13 && $gd > $sal[$gm]; $gm++) $gd -= $sal[$gm];
    return [$gy, $gm, $gd];
}

const JMONTHS = ['', 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];

/** Format a datetime string/timestamp as Jalali. $fmt: 'date' | 'datetime' | 'long' */
function jdate(mixed $dt = null, string $fmt = 'date'): string
{
    if ($dt === null || $dt === '') return '—';
    $ts = is_numeric($dt) ? (int)$dt : strtotime((string)$dt);
    if (!$ts) return '—';
    [$y, $m, $d] = g2j((int)date('Y', $ts), (int)date('n', $ts), (int)date('j', $ts));
    return match ($fmt) {
        'long'     => fa("$d " ) . JMONTHS[$m] . fa(" $y"),
        'datetime' => fa(sprintf('%04d/%02d/%02d - %s', $y, $m, $d, date('H:i', $ts))),
        'year'     => fa((string)$y),
        default    => fa(sprintf('%04d/%02d/%02d', $y, $m, $d)),
    };
}

function jyear_now(): int { return g2j((int)date('Y'), (int)date('n'), (int)date('j'))[0]; }
