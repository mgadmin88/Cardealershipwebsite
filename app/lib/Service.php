<?php
declare(strict_types=1);

/** Workshop appointment booking: schedule, free slots, statuses, notifications */
final class Service
{
    public const STATUS = [
        'pending'     => ['در انتظار تأیید', 'warn'],
        'confirmed'   => ['تأیید شد', 'ok'],
        'in_progress' => ['در حال انجام', 'info'],
        'done'        => ['انجام شد', 'ok'],
        'rejected'    => ['رد شد', 'bad'],
        'cancelled'   => ['لغو شد', 'bad'],
        'no_show'     => ['مراجعه نکرد', 'bad'],
    ];
    /** statuses that occupy a slot */
    public const BUSY = ['pending', 'confirmed', 'in_progress'];

    public const WEEKDAYS = [6 => 'شنبه', 0 => 'یکشنبه', 1 => 'دوشنبه', 2 => 'سه‌شنبه', 3 => 'چهارشنبه', 4 => 'پنجشنبه', 5 => 'جمعه'];

    public static function cfg(): array
    {
        $days = array_values(array_filter(array_map('intval', explode(',', (string)setting('svc_days', '6,0,1,2,3,4'))), fn($d) => $d >= 0 && $d <= 6));
        return [
            'days' => $days,
            'start' => self::hm((string)setting('svc_start', '08:00'), '08:00'),
            'end' => self::hm((string)setting('svc_end', '17:00'), '17:00'),
            'thu_end' => self::hm((string)setting('svc_thu_end', '13:00'), '13:00'),
            'slot' => max(15, min(240, (int)setting('svc_slot', 60))),
            'capacity' => max(1, min(50, (int)setting('svc_capacity', 2))),
            'ahead' => max(1, min(60, (int)setting('svc_ahead', 14))),
            'notice' => max(0, min(72, (int)setting('svc_notice', 2))),
            'cancel_hours' => max(0, min(72, (int)setting('svc_cancel_hours', 3))),
            'holidays' => self::holidays(),
        ];
    }

    private static function hm(string $v, string $def): string
    {
        $v = to_en_digits(trim($v));
        return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $v) ? $v : $def;
    }

    /** holidays entered as Jalali dates (one per line) → Gregorian Y-m-d */
    public static function holidays(): array
    {
        $out = [];
        foreach (preg_split('/\R/', to_en_digits((string)setting('svc_holidays', ''))) as $l) {
            if (preg_match('~^\s*(\d{4})[/-](\d{1,2})[/-](\d{1,2})~', $l, $m)) {
                [$gy, $gm, $gd] = j2g((int)$m[1], (int)$m[2], (int)$m[3]);
                $out[] = sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
            }
        }
        return $out;
    }

    /** bookable days: [Y-m-d => label] */
    public static function days(): array
    {
        $c = self::cfg();
        $out = [];
        for ($i = 0; $i <= $c['ahead']; $i++) {
            $ts = strtotime("+$i day", strtotime('today'));
            $d = date('Y-m-d', $ts);
            if (!in_array((int)date('w', $ts), $c['days'], true) || in_array($d, $c['holidays'], true)) continue;
            $sl = self::slots($d);
            if (!$sl) continue;                                   // e.g. today after closing time
            $out[$d] = ['w' => self::WEEKDAYS[(int)date('w', $ts)], 'j' => jdate($ts, 'long'), 'today' => $i === 0, 'tomorrow' => $i === 1,
                'full' => array_sum($sl) < 1];                    // every slot taken: shown, but not selectable
        }
        return $out;
    }

    /** slots of a day: [HH:MM => remaining capacity] (only future slots, respecting the minimum notice) */
    public static function slots(string $day, ?int $ignoreId = null): array
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $day)) return [];
        $c = self::cfg();
        $ts = strtotime($day);
        if (!$ts || !in_array((int)date('w', $ts), $c['days'], true) || in_array($day, $c['holidays'], true)) return [];
        $end = (int)date('w', $ts) === 4 ? min($c['end'], $c['thu_end']) : $c['end'];
        $taken = [];
        $sql = 'SELECT slot, COUNT(*) n FROM appointments WHERE day = ? AND status IN ' . DB::in(self::BUSY) . ($ignoreId ? ' AND id <> ?' : '') . ' GROUP BY slot';
        foreach (DB::all($sql, array_merge([$day], self::BUSY, $ignoreId ? [$ignoreId] : [])) as $r) $taken[$r['slot']] = (int)$r['n'];
        $out = [];
        $earliest = time() + $c['notice'] * 3600;
        for ($t = strtotime("$day {$c['start']}"); $t + $c['slot'] * 60 <= strtotime("$day $end"); $t += $c['slot'] * 60) {
            if ($t < $earliest) continue;
            $hm = date('H:i', $t);
            $out[$hm] = max(0, $c['capacity'] - ($taken[$hm] ?? 0));
        }
        return $out;
    }

    public static function newCode(): string
    {
        do { $c = 'S' . random_int(100000, 999999); } while (DB::val('SELECT 1 FROM appointments WHERE code = ?', [$c]));
        return $c;
    }

    public static function badge(string $s): string
    {
        [$l, $c] = self::STATUS[$s] ?? [$s, 'info'];
        return '<span class="badge ' . $c . '">' . e($l) . '</span>';
    }

    public static function when(array $a): string
    {
        $ts = strtotime($a['day']);
        return self::WEEKDAYS[(int)date('w', $ts)] . ' ' . jdate($ts, 'long') . '، ساعت ' . fa($a['slot']);
    }

    public static function link(array $a): string { return abs_url('service/a/' . $a['code'] . '?t=' . $a['token']); }

    public static function grant(array $a): void { $_SESSION['appts'][$a['code']] = true; }

    public static function canView(array $a): bool
    {
        if (!empty($_SESSION['appts'][$a['code']])) return true;
        if (Customer::enabled() && Customer::owns($a)) return true;
        $t = (string)($_GET['t'] ?? '');
        return $t !== '' && hash_equals((string)$a['token'], $t);
    }

    public static function canCancel(array $a): bool
    {
        if (!in_array($a['status'], ['pending', 'confirmed'], true)) return false;
        return strtotime($a['day'] . ' ' . $a['slot']) - time() >= self::cfg()['cancel_hours'] * 3600;
    }

    /** progress steps for the timeline */
    public static function timeline(array $a): array
    {
        $order = ['pending' => 0, 'confirmed' => 1, 'in_progress' => 2, 'done' => 3];
        $steps = ['ثبت نوبت', 'تأیید نوبت', 'پذیرش و انجام کار', 'تحویل خودرو'];
        $bad = !isset($order[$a['status']]);
        $pos = $bad ? 0 : $order[$a['status']];
        $out = [];
        foreach ($steps as $i => $l) {
            $out[] = [$l, $a['status'] === 'done' || $i <= $pos ? 'done' : ($i === $pos + 1 && !$bad ? 'current' : 'todo')];
        }
        if ($bad) $out[] = [self::STATUS[$a['status']][0] ?? $a['status'], 'bad'];
        return $out;
    }

    /** SMS to the customer about a status change (no-op when SMS is off) */
    public static function notify(array $a, string $event): void
    {
        if (!module('sms')) return;
        $site = setting('site_name');
        $when = self::when($a);
        $msg = match ($event) {
            'booked'    => "نوبت شما در $site ثبت شد.\nکد پیگیری: {$a['code']}\nزمان درخواستی: $when\nپس از تأیید، پیامک دریافت می‌کنید.",
            'confirmed' => "نوبت {$a['code']} شما تأیید شد.\n$when\n$site",
            'moved'     => "زمان نوبت {$a['code']} تغییر کرد: $when\n$site",
            'rejected'  => "متأسفانه نوبت {$a['code']} در $site تأیید نشد." . ($a['admin_note'] ? "\n" . $a['admin_note'] : '') . "\nبرای زمان دیگر دوباره نوبت بگیرید.",
            'done'      => "کار خودروی شما (نوبت {$a['code']}) انجام شد و آماده تحویل است.\n$site",
            'survey'    => "از اینکه $site را انتخاب کردید متشکریم. لطفاً در یک دقیقه به کیفیت خدمات امتیاز دهید:\n" . abs_url('service/survey/' . $a['code'] . '?t=' . $a['token']),
            'cancelled' => "نوبت {$a['code']} لغو شد.\n$site",
            default     => '',
        };
        if ($msg !== '') Sms::send($a['mobile'], $msg);
    }
}
