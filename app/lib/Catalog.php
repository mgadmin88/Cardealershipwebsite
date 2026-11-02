<?php
declare(strict_types=1);

/** Static domain dictionaries used across the site & admin. */
final class Catalog
{
    public const CONDITION = ['new' => 'صفر کیلومتر', 'used' => 'کارکرده'];

    public const BODY_TYPES = [
        'sedan' => 'سدان', 'hatchback' => 'هاچ‌بک', 'suv' => 'شاسی‌بلند (SUV)', 'crossover' => 'کراس‌اوور',
        'pickup' => 'وانت', 'coupe' => 'کوپه', 'convertible' => 'کروک', 'van' => 'ون',
    ];

    public const GEARBOX = ['manual' => 'دنده‌ای', 'automatic' => 'اتوماتیک', 'cvt' => 'CVT', 'dct' => 'دو کلاچه (DCT)'];

    public const FUEL = ['petrol' => 'بنزینی', 'dual' => 'دوگانه‌سوز', 'hybrid' => 'هیبرید', 'electric' => 'برقی', 'diesel' => 'دیزل'];

    public const CAR_STATUS = ['available' => 'موجود', 'reserved' => 'رزرو شده', 'sold' => 'فروخته شده', 'draft' => 'پیش‌نویس'];

    public const DOC_TYPES = ['6dang' => 'تک‌برگ ۶ دانگ', 'vekalat' => 'وکالتی', 'gharardad' => 'قولنامه‌ای', 'factor' => 'فاکتور فروش (صفر)'];

    public const CHASSIS = ['plomp' => 'پلمپ', 'zarbe' => 'ضربه‌خورده', 'jooshkari' => 'جوشکاری‌شده', 'na' => 'نامشخص'];

    public const TAGS = ['شاسی پلمپ', 'کم کارکرد', 'بدون رنگ', 'تک مالک', 'سرویس‌شده', 'فول آپشن', 'گارانتی دار', 'تحویل فوری', 'مناسب تاکسی اینترنتی'];

    public const COLORS = [
        'سفید' => '#f5f5f4', 'مشکی' => '#111827', 'نقره‌ای' => '#c0c4c9', 'خاکستری' => '#6b7280', 'نوک‌مدادی' => '#374151',
        'آبی' => '#1d4ed8', 'سرمه‌ای' => '#1e293b', 'قرمز' => '#b91c1c', 'زرشکی' => '#7f1d1d', 'سبز' => '#166534',
        'قهوه‌ای' => '#78350f', 'بژ' => '#d6c7a1', 'نارنجی' => '#ea580c', 'زرد' => '#facc15', 'بنفش' => '#6d28d9',
    ];

    public const INSURERS = ['ایران', 'آسیا', 'البرز', 'دانا', 'پارسیان', 'کوثر', 'ما', 'سامان', 'رازی', 'معلم', 'ملت', 'سینا', 'پاسارگاد', 'تعاون', 'سرمد', 'حافظ', 'تجارت نو', 'آرمان', 'نوین', 'کارآفرین', 'دی', 'میهن', 'حکمت', 'زندگی خاورمیانه'];

    public const CITIES = ['تهران', 'کرج', 'مشهد', 'اصفهان', 'شیراز', 'تبریز', 'اهواز', 'قم', 'کرمانشاه', 'رشت', 'ارومیه', 'یزد', 'کرمان', 'همدان', 'اراک', 'زنجان', 'قزوین', 'ساری', 'بندرعباس', 'گرگان', 'سنندج', 'خرم‌آباد', 'بوشهر', 'کیش'];

    /** Damage-map parts — keys match the SVG ids */
    public const PARTS = [
        'front_bumper' => 'سپر جلو', 'hood' => 'کاپوت', 'roof' => 'سقف', 'trunk' => 'درب صندوق', 'rear_bumper' => 'سپر عقب',
        'fender_fl' => 'گلگیر جلو چپ', 'door_fl' => 'درب جلو چپ', 'door_rl' => 'درب عقب چپ', 'fender_rl' => 'گلگیر عقب چپ',
        'fender_fr' => 'گلگیر جلو راست', 'door_fr' => 'درب جلو راست', 'door_rr' => 'درب عقب راست', 'fender_rr' => 'گلگیر عقب راست',
        'pillar_l' => 'ستون و رکاب چپ', 'pillar_r' => 'ستون و رکاب راست',
    ];

    /** status => [label, color] */
    public const DAMAGE = [
        'ok'       => ['سالم و بی‌رنگ', 'var(--dm-ok)'],
        'scratch'  => ['خط و خش', '#a78bfa'],
        'dent'     => ['فرورفتگی و ضربه', '#60a5fa'],
        'pdr'      => ['صافکاری بدون رنگ', '#22d3ee'],
        'spot'     => ['لکه رنگ / نقاشی جزئی', '#facc15'],
        'painted'  => ['صافکاری و نقاشی شده', '#fb923c'],
        'full'     => ['تمام رنگ', '#f97316'],
        'replaced' => ['تعویض شده', '#ef4444'],
    ];

    /** severity rank of a damage type (higher = worse) */
    public static function damageRank(string $t): int
    {
        $i = array_search($t, array_keys(self::DAMAGE), true);
        return $i === false ? 0 : (int)$i;
    }

    /** white glyph for a damage type, centred on 0,0 (fits a r=13 pin) */
    public static function damageGlyph(string $t): string
    {
        $st = 'fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"';
        return match ($t) {
            'replaced' => '<g ' . $st . '><path d="M-5.5,-1.5 A5.5,5.5 0 0 1 4.5,-3.5"/><path d="M4.8,-6.8 L4.8,-3.2 L1.2,-3.2"/><path d="M5.5,1.5 A5.5,5.5 0 0 1 -4.5,3.5"/><path d="M-4.8,6.8 L-4.8,3.2 L-1.2,3.2"/></g>',
            'painted', 'full' => '<g ' . $st . '><rect x="-5.5" y="-6" width="9" height="4.5" rx="1.2"/><path d="M3.5,-3.8 L5.8,-3.8 L5.8,0 L0,0 L0,2.2"/><rect x="-1.2" y="2.2" width="2.4" height="4.6" rx="1" fill="#fff"/></g>'
                . ($t === 'full' ? '<circle cx="-5.5" cy="5" r="1.6" fill="#fff"/>' : ''),
            'spot' => '<path fill="#fff" d="M0,-6.5 C2.8,-2.8 4.6,-0.4 4.6,2 A4.6,4.6 0 0 1 -4.6,2 C-4.6,-0.4 -2.8,-2.8 0,-6.5 Z"/>',
            'dent' => '<path fill="#fff" d="M0,-6.5 L1.6,-2.3 L6.2,-2.6 L2.6,0.6 L4.2,5.4 L0,2.8 L-4.2,5.4 L-2.6,0.6 L-6.2,-2.6 L-1.6,-2.3 Z"/>',
            'pdr' => '<g ' . $st . '><path d="M-5.5,5.5 L1,-1"/><path d="M-1.5,-3.5 L3,-6.5 L6.5,-3 L3.5,1.5 Z" fill="#fff"/></g>',
            'scratch' => '<g ' . $st . '><path d="M-6,3.5 L-3,-2.5 L-0.5,2.5 L2.5,-3.5 L6,1.5"/></g>',
            default => '<circle r="3" fill="#fff"/>',
        };
    }

    /** small standalone svg icon for legends/lists */
    public static function damageIcon(string $t, int $size = 22): string
    {
        $c = self::DAMAGE[$t][1] ?? '#999';
        return '<svg class="dmg-ico" width="' . $size . '" height="' . $size . '" viewBox="-13 -13 26 26" aria-hidden="true"><circle r="12" fill="' . $c . '"/>' . self::damageGlyph($t) . '</svg>';
    }

    /** Inspection checklist — category => [title, icon, items[]] */
    public const INSPECTION = [
        'body' => ['بدنه و لاستیک', 'car', ['لاستیک‌های جلو', 'لاستیک‌های عقب', 'لاستیک زاپاس', 'رینگ‌ها', 'شیشه جلو', 'شیشه‌های جانبی و عقب', 'چراغ‌های جلو', 'چراغ‌های عقب', 'آینه‌های بغل', 'زنگ‌زدگی و پوسیدگی', 'کف و زیر خودرو', 'درزها و فاصله قطعات بدنه']],
        'engine' => ['موتور و گیربکس', 'engine', ['استارت و روشن شدن', 'صدای موتور', 'نشتی روغن', 'دود اگزوز', 'عملکرد گیربکس', 'کلاچ', 'تسمه‌ها', 'سیستم خنک‌کننده', 'باتری', 'دیاگ و خطاهای ECU', 'دسته موتور', 'کمپرسی']],
        'electric' => ['برقی و داخلی', 'bolt', ['پشت آمپر و چراغ‌های هشدار', 'شیشه بالابرها', 'قفل مرکزی', 'سیستم صوتی و مانیتور', 'سنسور و دوربین دنده عقب', 'صندلی‌ها و رودری‌ها', 'کیسه‌های هوا', 'برف‌پاک‌کن', 'بوق', 'سقف و داشبورد']],
        'suspension' => ['تعلیق و ترمز', 'gauge', ['کمک‌فنرها', 'فنرها', 'سیبک و طبق', 'جلوبندی', 'فرمان', 'لنت و دیسک ترمز', 'ترمز دستی', 'ABS و ESP', 'تنظیم فرمان (بالانس)']],
        'ac' => ['تهویه مطبوع', 'snow', ['عملکرد کولر', 'عملکرد بخاری', 'فن و دریچه‌ها']],
    ];

    /** All toggleable modules: key => [title, description, default] */
    public const MODULES = [
        'used_cars'        => ['فروش خودروی کارکرده', 'نمایش و فروش خودروهای دست دوم کارشناسی‌شده', 1],
        'new_cars'         => ['فروش خودروی صفر', 'نمایش و فروش خودروهای صفر کیلومتر نمایندگی', 1],
        'parts_shop'       => ['فروشگاه قطعات و آپشن', 'فروش آنلاین قطعات یدکی و لوازم جانبی با سبد خرید', 1],
        'online_payment'   => ['پرداخت آنلاین', 'اتصال به درگاه‌های بانکی و پرداخت آنلاین سفارش‌ها', 1],
        'car_reserve'      => ['خرید / رزرو آنلاین خودرو', 'امکان پرداخت بیعانه یا مبلغ کامل خودرو به‌صورت آنلاین', 1],
        'installment'      => ['خرید اقساطی', 'نمایش برچسب اقساطی، فیلتر و ماشین‌حساب اقساط', 1],
        'slider'           => ['اسلایدشو صفحه اصلی', 'نمایش بنر اسلایدی در بالای صفحه اول', 1],
        'damage_map'       => ['نقشه وضعیت بدنه', 'نمایش نقشه رنگ‌شدگی و نواقص بدنه خودرو', 1],
        'inspection'       => ['گزارش کارشناسی', 'نمایش گزارش چک‌لیست کارشناسی و امتیازدهی', 1],
        'test_drive'       => ['رزرو بازدید / تست درایو', 'رزرو آنلاین زمان بازدید حضوری خودرو', 1],
        'sell_request'     => ['درخواست فروش خودرو', 'فرم «ماشینت رو بفروش» برای خرید خودرو از مشتری', 1],
        'compare'          => ['مقایسه خودروها', 'مقایسه مشخصات تا ۳ خودرو در کنار هم', 1],
        'favorites'        => ['علاقه‌مندی‌ها', 'ذخیره خودروهای مورد علاقه توسط بازدیدکننده', 1],
        'order_tracking'   => ['پیگیری سفارش', 'پیگیری وضعیت سفارش با کد پیگیری', 1],
        'faq'              => ['سوالات متداول', 'نمایش بخش سوالات متداول', 1],
        'about'            => ['صفحه درباره ما', '', 1],
        'contact'          => ['صفحه تماس با ما', 'فرم تماس، نقشه و اطلاعات تماس', 1],
        'brands_strip'     => ['نوار برندها', 'نمایش برندهای محبوب در صفحه اول', 1],
        'stats'            => ['آمار و ارقام', 'نمایش آمار نمایندگی در صفحه اول', 1],
        'sms'              => ['اطلاع‌رسانی پیامکی', 'ارسال پیامک به مشتری و مدیر در رویدادها', 0],
        'floating_contact' => ['دکمه تماس شناور', 'دکمه شناور تماس / واتس‌اپ در گوشه صفحه', 1],
        'dark_toggle'      => ['انتخاب حالت شب', 'امکان تغییر تم روشن/تیره توسط بازدیدکننده', 1],
        'service_booking'  => ['نوبت‌دهی سرویس و تعمیرات', 'رزرو آنلاین نوبت سرویس دوره‌ای، تعمیر و کارشناسی، پیگیری و تأیید نوبت در پنل', 1],
        'surveys'          => ['نظرسنجی مشتریان', 'ارسال لینک نظرسنجی پس از انجام خدمات و نمایش نتایج در پنل', 1],
        'customer_accounts'=> ['حساب کاربری خریداران', 'ورود خریداران با کد پیامکی یا رمز، مشاهده سفارش‌ها، پرداخت‌ها و درخواست‌ها', 1],
        'captcha'          => ['کپچا (کد امنیتی)', 'کد امنیتی تصویری در ورود مدیریت و همه فرم‌های سایت برای جلوگیری از ربات‌ها', 1],
        'maintenance'      => ['حالت تعمیرات', 'سایت برای بازدیدکنندگان بسته می‌شود (مدیران دسترسی دارند)', 0],
    ];

    /** Theme presets */
    public const THEMES = [
        'sunset'  => ['غروب طلایی (گرم)', ['primary' => '#2d1b3d', 'accent' => '#ff9a2e', 'bg' => '#f6f5f3', 'surface' => '#ffffff', 'text' => '#1f1a24', 'dark_bg' => '#120d18', 'dark_surface' => '#1d1626']],
        'navy'    => ['کلاسیک سرمه‌ای', ['primary' => '#1e3a5f', 'accent' => '#f7931e', 'bg' => '#f4f6f9', 'surface' => '#ffffff', 'text' => '#1f2937', 'dark_bg' => '#0b1220', 'dark_surface' => '#131c2e']],
        'luxury'  => ['مشکی طلایی لوکس', ['primary' => '#111111', 'accent' => '#c9a227', 'bg' => '#f6f4ef', 'surface' => '#ffffff', 'text' => '#1c1917', 'dark_bg' => '#0a0a0a', 'dark_surface' => '#161616']],
        'racing'  => ['قرمز اسپرت', ['primary' => '#b91c1c', 'accent' => '#111827', 'bg' => '#f7f7f8', 'surface' => '#ffffff', 'text' => '#18181b', 'dark_bg' => '#0c0a0a', 'dark_surface' => '#1a1414']],
        'ocean'   => ['آبی اقیانوسی', ['primary' => '#0369a1', 'accent' => '#06b6d4', 'bg' => '#f0f7fb', 'surface' => '#ffffff', 'text' => '#0f172a', 'dark_bg' => '#06131d', 'dark_surface' => '#0d1f2d']],
        'emerald' => ['سبز زمردی', ['primary' => '#047857', 'accent' => '#f59e0b', 'bg' => '#f2f7f5', 'surface' => '#ffffff', 'text' => '#13201b', 'dark_bg' => '#06120e', 'dark_surface' => '#0e1f19']],
        'violet'  => ['بنفش مدرن', ['primary' => '#5b21b6', 'accent' => '#ec4899', 'bg' => '#f6f5fb', 'surface' => '#ffffff', 'text' => '#1e1b2e', 'dark_bg' => '#0d0a17', 'dark_surface' => '#181327']],
    ];

    public const ORDER_STATUS = [
        'pending'    => ['در انتظار پرداخت', 'warn'],
        'paid'       => ['پرداخت شده', 'ok'],
        'processing' => ['در حال پردازش', 'info'],
        'shipped'    => ['ارسال شده', 'info'],
        'completed'  => ['تکمیل شده', 'ok'],
        'cancelled'  => ['لغو شده', 'bad'],
        'failed'     => ['پرداخت ناموفق', 'bad'],
    ];

    public const REQUEST_TYPES = [
        'contact'     => 'پیام تماس با ما',
        'test_drive'  => 'رزرو بازدید / تست درایو',
        'sell'        => 'درخواست فروش خودرو',
        'installment' => 'درخواست خرید اقساطی',
        'callback'    => 'درخواست تماس کارشناس',
    ];

    public static function partsCount(array $damage): array
    {
        $c = [];
        foreach ($damage as $st) if ($st && $st !== 'ok') $c[$st] = ($c[$st] ?? 0) + 1;
        return $c;
    }

    /** Compute per-category scores + totals from inspection json */
    public static function inspectionSummary(array $insp): array
    {
        $out = [];
        foreach (self::INSPECTION as $key => [$title, $icon, $items]) {
            $data = $insp[$key] ?? [];
            $ok = $fail = 0;
            $rows = [];
            foreach ($items as $i => $label) {
                $s = $data['items'][$i]['s'] ?? '';
                $n = $data['items'][$i]['n'] ?? '';
                if ($s === 'ok') $ok++;
                if ($s === 'fail') $fail++;
                if ($s) $rows[] = ['label' => $label, 's' => $s, 'n' => $n];
            }
            if (!$ok && !$fail) continue;
            $score = isset($data['score']) && $data['score'] !== '' ? (float)$data['score']
                : round(1 + 4 * ($ok / max(1, $ok + $fail)), 1);
            $out[$key] = ['title' => $title, 'icon' => $icon, 'ok' => $ok, 'fail' => $fail, 'score' => min(5, $score), 'rows' => $rows];
        }
        return $out;
    }
}
