<?php
declare(strict_types=1);

final class Seeder
{
    public static function defaults(): array
    {
        $t = Catalog::THEMES['navy'][1];
        $d = [
            'site_name' => 'آریا موتور', 'site_tagline' => 'نمایندگی رسمی فروش خودروی صفر و کارکرده',
            'site_description' => 'خرید و فروش خودروی صفر و کارکرده کارشناسی‌شده با ضمانت، پرداخت آنلاین و اقساط؛ فروش قطعات و لوازم جانبی اصلی.',
            'logo' => '', 'favicon' => '',
            'phone' => '021-91000000', 'phone2' => '', 'mobile' => '09120000000', 'whatsapp' => '989120000000', 'email' => 'info@example.com',
            'address' => 'تهران، بزرگراه اشرفی اصفهانی، نبش خیابان پیامبر، نمایشگاه آریا موتور', 'work_hours' => 'شنبه تا پنجشنبه ۹ الی ۲۰',
            'map_lat' => '35.7448', 'map_lng' => '51.3134', 'instagram' => '', 'telegram' => '', 'linkedin' => '', 'aparat' => '',
            'enamad_link' => '', 'enamad_img' => '',
            'footer_about' => 'آریا موتور با بیش از ۱۵ سال سابقه در زمینه فروش خودروهای صفر و کارکرده، با کارشناسی ۱۲۰ نقطه‌ای و ضمانت اصالت، خریدی مطمئن را برای شما فراهم می‌کند.',
            'stats' => json_encode([['۱۵+', 'سال تجربه'], ['۸,۵۰۰+', 'خودروی فروخته‌شده'], ['۱۲۰', 'نقطه کارشناسی'], ['۹۸٪', 'رضایت مشتریان']], JSON_UNESCAPED_UNICODE),
            // theme
            'theme_preset' => 'sunset', 'theme_radius' => '14', 'theme_mode' => 'light', 'theme_header' => 'light', 'theme_hero' => 'slider',
            'theme_card' => 'elevated', 'theme_container' => '1280', 'theme_custom_css' => '', 'theme_font_scale' => '100',
            // commerce
            'deposit_type' => 'percent', 'deposit_value' => '2', 'car_full_pay' => '1', 'car_max_online' => '0',
            'shipping_cost' => '90000', 'free_shipping_over' => '5000000',
            'installment_rate' => '23', 'installment_max_months' => '36', 'installment_min_down' => '30',
            'expert_fee_note' => 'کارشناسی خودرو برای خریداران خودروهای نمایشگاه رایگان است.',
            // sms
            'sms_provider' => 'log', 'sms_config' => '{}', 'sms_admin_mobiles' => '',
            'sms_tpl_order' => '{name} عزیز، سفارش {code} در {site} ثبت شد. مبلغ: {amount} تومان',
            'sms_tpl_paid' => 'پرداخت سفارش {code} با موفقیت انجام شد. کد رهگیری: {ref} - {site}',
            'sms_tpl_request' => '{name} عزیز، درخواست شما در {site} ثبت شد. کارشناسان ما به‌زودی با شما تماس می‌گیرند.',
            'sms_tpl_admin' => 'رویداد جدید در سایت: {event} - {name} {mobile}',
            'installed_at' => now(),
        ];
        foreach ($t as $k => $v) $d['theme_' . $k] = $v;
        foreach (Catalog::MODULES as $k => [, , $def]) $d['module_' . $k] = (string)$def;
        return $d;
    }

    public static function run(string $adminUser, string $adminPass, bool $demo = true): void
    {
        DB::tx(function () use ($adminUser, $adminPass, $demo) {
            foreach (self::defaults() as $k => $v) {
                if (DB::val('SELECT 1 FROM settings WHERE k = ?', [$k]) === null) DB::insert('settings', ['k' => $k, 'v' => $v]);
            }
            if (!DB::val('SELECT COUNT(*) FROM users')) {
                DB::insert('users', ['name' => 'مدیر سایت', 'username' => $adminUser, 'password' => password_hash($adminPass, PASSWORD_DEFAULT),
                    'role' => 'admin', 'active' => 1, 'created_at' => now()]);
            }
            if (!DB::val('SELECT COUNT(*) FROM brands')) self::brands();
            if (!DB::val('SELECT COUNT(*) FROM gateways')) self::gateways();
            if (!DB::val('SELECT COUNT(*) FROM service_types')) self::serviceTypes();
            if (!DB::val('SELECT COUNT(*) FROM pages')) self::pages();
            if (!DB::val('SELECT COUNT(*) FROM faqs')) self::faqs();
            if (!DB::val('SELECT COUNT(*) FROM part_categories')) self::partCategories();
            if ($demo) {
                if (!DB::val('SELECT COUNT(*) FROM cars')) self::cars();
                if (!DB::val('SELECT COUNT(*) FROM parts')) self::parts();
                if (!DB::val('SELECT COUNT(*) FROM slides')) self::slides();
            }
        });
    }

    private static function brands(): void
    {
        $popular = ['ایران خودرو', 'پژو', 'سایپا', 'ام وی ام', 'فونیکس', 'کی ام سی', 'تویوتا', 'هیوندای', 'کیا', 'مرسدس بنز', 'بی ام و', 'رنو'];
        foreach (require APP . '/data/brands.php' as $i => [$fa, $en, $origin, $models]) {
            $bid = DB::insert('brands', ['name_fa' => $fa, 'name_en' => $en, 'origin' => $origin, 'sort' => $i, 'popular' => in_array($fa, $popular, true) ? 1 : 0]);
            foreach ($models as $m) {
                [$mfa, $men, $body] = array_pad(explode('|', $m), 3, null);
                DB::insert('models', ['brand_id' => $bid, 'name_fa' => $mfa, 'name_en' => $men, 'body_type' => $body]);
            }
        }
    }

    private static function gateways(): void
    {
        $list = [
            ['درگاه آزمایشی (تست)', 'test', 'test', 1],
            ['بانک سامان', 'saman', 'sep', 0],
            ['بانک سینا', 'sina', 'sep', 0],
            ['بانک سرمایه', 'sarmayeh', 'behpardakht', 0],
            ['بانک ملت', 'mellat', 'behpardakht', 0],
            ['بانک ملی (سداد)', 'melli', 'sadad', 0],
            ['بانک پارسیان', 'parsian', 'pec', 0],
            ['بانک پاسارگاد', 'pasargad', 'pep', 0],
            ['بانک صادرات (سپهر)', 'saderat', 'sepehr', 0],
            ['زرین‌پال', 'zarinpal', 'zarinpal', 0],
            ['زیبال', 'zibal', 'zibal', 0],
            ['پی دات آی آر', 'payir', 'payir', 0],
            ['نکست‌پی', 'nextpay', 'nextpay', 0],
            ['آسان پرداخت', 'other', 'asanpardakht', 0],
        ];
        foreach ($list as $i => [$label, $bank, $driver, $active]) {
            DB::insert('gateways', ['label' => $label, 'bank' => $bank, 'driver' => $driver, 'config' => '{}', 'sandbox' => $driver === 'test' ? 1 : 0, 'active' => $active, 'sort' => $i]);
        }
    }

    private static function pages(): void
    {
        DB::insert('pages', ['slug' => 'about', 'title' => 'درباره ما', 'in_footer' => 1, 'active' => 1, 'content' =>
            "<h2>داستان ما</h2><p>آریا موتور از سال ۱۳۸۹ فعالیت خود را در زمینه خرید و فروش خودرو آغاز کرد. هدف ما از ابتدا ساده بود: خریدی شفاف، مطمئن و بدون دغدغه برای هر ایرانی.</p>"
            . "<p>امروز با نمایشگاهی به وسعت ۳۰۰۰ متر مربع، تیمی از کارشناسان مجرب و سیستم کارشناسی ۱۲۰ نقطه‌ای، هر خودرو را پیش از عرضه به‌دقت بررسی می‌کنیم و گزارش کامل آن را در اختیار شما می‌گذاریم.</p>"
            . "<h3>چرا آریا موتور؟</h3><ul><li>کارشناسی دقیق و گزارش شفاف بدنه و فنی</li><li>ضمانت اصالت سند و کارکرد</li><li>امکان خرید نقدی، اقساطی و پرداخت آنلاین</li><li>خدمات پس از فروش و فروش قطعات اصلی</li></ul>"]);
        DB::insert('pages', ['slug' => 'terms', 'title' => 'قوانین و مقررات', 'in_footer' => 1, 'active' => 1, 'content' =>
            "<p>استفاده از خدمات این وب‌سایت به منزله پذیرش قوانین زیر است.</p><ul><li>مبلغ بیعانه پرداخت‌شده برای رزرو خودرو، در صورت انصراف خریدار طبق قرارداد قابل بازگشت است.</li><li>قیمت‌ها به تومان و شامل مالیات بر ارزش افزوده هستند.</li><li>ارسال قطعات به سراسر کشور از طریق پست و تیپاکس انجام می‌شود.</li></ul>"]);
        DB::insert('pages', ['slug' => 'privacy', 'title' => 'حریم خصوصی', 'in_footer' => 1, 'active' => 1, 'content' =>
            "<p>اطلاعات شخصی شما صرفاً برای پردازش سفارش و ارتباط با شما استفاده می‌شود و در اختیار شخص ثالث قرار نمی‌گیرد. پرداخت‌ها مستقیماً در درگاه امن بانکی (شاپرک) انجام می‌شود و اطلاعات کارت شما در این سایت ذخیره نمی‌شود.</p>"]);
    }

    private static function faqs(): void
    {
        $f = [
            ['خودروها چگونه قیمت‌گذاری می‌شوند؟', 'قیمت هر خودرو بر اساس مدل، سال ساخت، کارکرد، وضعیت بدنه و فنی و قیمت روز بازار توسط کارشناسان ما تعیین می‌شود.', 'car'],
            ['هزینه کارشناسی خودرو چقدر است؟', 'کارشناسی خودروهای موجود در نمایشگاه برای خریداران رایگان است. برای کارشناسی خودروی شخصی می‌توانید از طریق فرم درخواست اقدام کنید.', 'car'],
            ['روند خرید آنلاین خودرو چگونه است؟', 'پس از انتخاب خودرو، با پرداخت آنلاین بیعانه خودرو برای شما رزرو می‌شود. سپس کارشناس فروش برای هماهنگی بازدید، تنظیم قرارداد و تسویه با شما تماس می‌گیرد.', 'car'],
            ['خودرو در چه محلی کارشناسی می‌شود؟', 'تمامی خودروها در مرکز کارشناسی نمایشگاه و با تجهیزات تخصصی (دیاگ، ضخامت‌سنج رنگ و جک بالابر) بررسی می‌شوند.', 'car'],
            ['آیا امکان خرید اقساطی وجود دارد؟', 'بله، خودروهایی که برچسب «خرید اقساطی» دارند تا ۳۶ ماه قابل خرید اقساطی هستند. با ماشین‌حساب اقساط می‌توانید مبلغ اقساط را محاسبه کنید.', 'general'],
            ['قطعات چگونه ارسال می‌شوند؟', 'سفارش‌های قطعات در تهران با پیک و در سایر شهرها با پست پیشتاز یا تیپاکس ارسال می‌شوند.', 'general'],
        ];
        foreach ($f as $i => [$q, $a, $g]) DB::insert('faqs', ['question' => $q, 'answer' => $a, 'grp' => $g, 'sort' => $i, 'active' => 1]);
    }

    private static function partCategories(): void
    {
        $c = [['قطعات موتوری', 'engine'], ['ترمز و تعلیق', 'gauge'], ['برق و روشنایی', 'bolt'], ['بدنه و تزئینات', 'car'], ['لوازم جانبی و آپشن', 'star'], ['روغن و فیلتر', 'drop'], ['لاستیک و رینگ', 'wheel'], ['صوتی و تصویری', 'music']];
        foreach ($c as $i => [$n, $ic]) DB::insert('part_categories', ['name' => $n, 'slug' => slugify($n), 'icon' => $ic, 'sort' => $i]);
    }

    private static function brandModel(string $brand, string $model): array
    {
        $b = DB::row('SELECT id FROM brands WHERE name_fa = ?', [$brand]);
        $m = $b ? DB::row('SELECT id, body_type FROM models WHERE brand_id = ? AND name_fa = ?', [$b['id'], $model]) : null;
        return [$b['id'] ?? 1, $m['id'] ?? null, $m['body_type'] ?? 'sedan'];
    }

    private static function cars(): void
    {
        $dir = PUB . '/uploads/demo';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $cars = [
            ['ام وی ام', '550', 'used', 1395, 75662, 'automatic', 'مشکی', 'تهران', 1515000000, 1, ['fender_fl' => 'spot', 'door_fr' => 'painted', 'door_rr' => 'painted', 'rear_bumper' => 'spot', 'fender_rl' => 'spot'], 'شاسی پلمپ,کم کارکرد', 'آپشن استاندارد'],
            ['ایران خودرو', 'سمند', 'used', 1398, 181398, 'manual', 'سفید', 'تهران', 1616000000, 1, ['hood' => 'spot'], 'شاسی پلمپ', 'XU7 ساده'],
            ['سایپا', 'کوییک R', 'used', 1401, 61440, 'manual', 'قرمز', 'کرمانشاه', 1221900000, 1, [], 'شاسی پلمپ,بدون رنگ', 'R'],
            ['تویوتا', 'کمری', 'used', 1397, 98000, 'automatic', 'سفید', 'تهران', 4950000000, 0, ['front_bumper' => 'spot'], 'فول آپشن,تک مالک', 'GLX'],
            ['هیوندای', 'توسان', 'used', 1396, 120500, 'automatic', 'نقره‌ای', 'تهران', 3890000000, 0, ['door_fl' => 'pdr', 'trunk' => 'painted'], 'فول آپشن', 'IX35'],
            ['فونیکس', 'تیگو 8 پرو مکس', 'new', 1405, 0, 'dct', 'خاکستری', 'تهران', 4280000000, 1, [], 'تحویل فوری,گارانتی دار', 'Premium'],
            ['کی ام سی', 'J7', 'new', 1405, 0, 'dct', 'سفید', 'تهران', 3150000000, 1, [], 'گارانتی دار', 'Full'],
            ['پژو', '207 پانوراما', 'new', 1405, 0, 'automatic', 'مشکی', 'تهران', 1890000000, 1, [], 'تحویل فوری', 'اتوماتیک TU5P'],
            ['مرسدس بنز', 'E250', 'used', 1395, 142000, 'automatic', 'مشکی', 'تهران', 9800000000, 0, ['roof' => 'ok', 'fender_fr' => 'replaced'], 'فول آپشن', 'AMG Line'],
            ['کیا', 'اسپورتیج', 'used', 1396, 110000, 'automatic', 'سرمه‌ای', 'اصفهان', 3650000000, 0, [], 'بدون رنگ,سرویس‌شده', 'GT Line'],
            ['رنو', 'ساندرو استپ‌وی', 'used', 1399, 54000, 'automatic', 'نارنجی', 'شیراز', 1590000000, 1, ['rear_bumper' => 'scratch'], 'کم کارکرد', 'اتوماتیک'],
            ['ام وی ام', 'X55 Pro', 'new', 1405, 0, 'cvt', 'آبی', 'تهران', 2790000000, 1, [], 'تحویل فوری,گارانتی دار', 'Excellent'],
        ];
        $insp = function (bool $perfect) {
            $o = [];
            foreach (Catalog::INSPECTION as $k => [, , $items]) {
                foreach ($items as $i => $_) $o[$k]['items'][$i] = ['s' => (!$perfect && ($i + strlen($k)) % 6 === 0) ? 'fail' : 'ok', 'n' => ''];
            }
            if (!$perfect) $o['body']['items'][0]['n'] = 'آج لاستیک حدود ۴۰٪';
            return $o;
        };
        foreach ($cars as $i => [$brand, $model, $cond, $year, $km, $gear, $color, $city, $price, $inst, $damage, $tags, $trim]) {
            [$bid, $mid, $body] = self::brandModel($brand, $model);
            $code = (string)(54230 + $i);
            $hex = Catalog::COLORS[$color] ?? '#444';
            $cid = DB::insert('cars', [
                'code' => $code, 'slug' => slugify("$brand $model $year"), 'car_condition' => $cond, 'brand_id' => $bid, 'model_id' => $mid,
                'trim_name' => $trim, 'year' => $year, 'mileage' => $km, 'gearbox' => $gear, 'fuel' => 'petrol', 'body_type' => $body,
                'color' => $color, 'interior_color' => 'مشکی', 'city' => $city, 'price' => $price, 'installment' => $inst,
                'installment_note' => $inst ? '۳۰٪ پیش‌پرداخت، باقی تا ۳۶ ماه' : null,
                'ins_third' => 1, 'ins_third_until' => '1406/08/06', 'ins_third_company' => 'ایران', 'ins_discount_years' => $cond === 'new' ? 0 : 4,
                'ins_body' => $i % 3 === 0 ? 1 : 0, 'ins_body_until' => $i % 3 === 0 ? '1406/02/15' : null, 'ins_body_company' => $i % 3 === 0 ? 'آسیا' : null,
                'tech_inspection' => $cond === 'used' ? 1 : 0, 'fuel_card' => 1, 'doc_type' => $cond === 'new' ? 'factor' : '6dang',
                'warranty' => $cond === 'new' ? '۲ سال یا ۶۰,۰۰۰ کیلومتر' : ($i % 2 ? 'گارانتی ۱۰ روزه بدنه و فنی' : null),
                'chassis' => 'plomp', 'engine_status' => 'سالم', 'owners' => $cond === 'new' ? 0 : 1 + $i % 2,
                'tags' => $tags, 'options' => "کروز کنترل\nسنسور دنده عقب\nدوربین دنده عقب\nسیستم صوتی لمسی\nکیسه هوای جلو\nترمز ABS\nاستارت دکمه‌ای",
                'description' => "این خودرو توسط کارشناسان {$code} بررسی و تمامی موارد فنی و بدنه آن در گزارش کارشناسی ثبت شده است.\n\nسرویس‌های دوره‌ای به‌موقع انجام شده و خودرو آماده تحویل است.",
                'damage' => json_encode($damage), 'inspection' => json_encode($cond === 'used' ? $insp(empty($damage)) : []),
                'specs' => json_encode(['engine' => '۱۵۰۰ سی‌سی توربو', 'power' => '۱۵۰ اسب بخار', 'torque' => '۲۱۰ نیوتن‌متر', 'consumption' => '۷.۵ لیتر در ۱۰۰ کیلومتر', 'drive' => 'دیفرانسیل جلو', 'seats' => '۵ نفر'], JSON_UNESCAPED_UNICODE),
                'status' => 'available', 'featured' => $i < 8 ? 1 : 0, 'views' => rand(40, 900),
                'created_at' => date('Y-m-d H:i:s', time() - $i * 7200), 'updated_at' => now(),
            ]);
            for ($v = 0; $v < 3; $v++) {
                $file = "demo/car{$cid}_{$v}.svg";
                file_put_contents(PUB . '/uploads/' . $file, DemoArt::car($hex, $body, $v));
                DB::insert('car_images', ['car_id' => $cid, 'path' => $file, 'thumb' => $file, 'sort' => $v]);
            }
        }
    }

    private static function parts(): void
    {
        $dir = PUB . '/uploads/demo';
        $p = [
            [1, 'فیلتر روغن اصلی', 'IKCO', 'پژو 206، 207، رانا', 285000, null, 40, 0],
            [6, 'روغن موتور ۵W30 چهار لیتری', 'Total', 'عمومی', 2150000, 1990000, 25, 0],
            [2, 'لنت ترمز جلو سرامیکی', 'Brembo', 'تویوتا کمری ۲۰۱۵-۲۰۱۸', 3450000, null, 12, 0],
            [3, 'لامپ هدلایت LED سری H7', 'Philips', 'عمومی', 1890000, 1650000, 30, 0],
            [5, 'دوربین ۳۶۰ درجه خودرو', 'Teyes', 'عمومی', 18500000, null, 6, 1],
            [5, 'ضبط اندروید ۱۰ اینچ', 'Teyes', 'دنا، سمند، ۲۰۶', 14900000, 13800000, 8, 1],
            [4, 'کاور صندلی چرم دوخت الماسی', 'AutoLux', 'سدان‌های عمومی', 6900000, null, 15, 1],
            [5, 'سنسور پارک ۴ چشم', 'Steelmate', 'عمومی', 2450000, null, 20, 1],
            [7, 'لاستیک ۲۰۵/۵۵R16', 'Kumho', 'عمومی', 4890000, null, 16, 0],
            [8, 'آمپلی‌فایر ۴ کانال', 'JBL', 'عمومی', 9600000, null, 4, 1],
        ];
        $hues = ['#1e3a5f', '#f7931e', '#0369a1', '#047857', '#b91c1c', '#5b21b6'];
        foreach ($p as $i => [$cat, $title, $brand, $comp, $price, $sale, $stock, $opt]) {
            $file = "demo/part$i.svg";
            file_put_contents(PUB . '/uploads/' . $file, DemoArt::part($brand, $hues[$i % 6]));
            DB::insert('parts', ['category_id' => $cat, 'title' => $title, 'sku' => 'PRT-' . (1000 + $i), 'brand' => $brand, 'compatible' => $comp,
                'price' => $price, 'sale_price' => $sale, 'stock' => $stock, 'image' => $file, 'thumb' => $file, 'is_option' => $opt,
                'description' => "{$title} با کیفیت اصلی و ضمانت اصالت کالا. مناسب برای {$comp}.", 'active' => 1, 'featured' => $i < 8 ? 1 : 0, 'created_at' => now()]);
        }
    }

    private static function slides(): void
    {
        $s = [
            ['مسیر ساده‌تر خرید خودرو', 'خودروهای کارشناسی‌شده با گزارش ۱۲۰ نقطه‌ای، ضمانت و امکان خرید اقساطی', 'مشاهده خودروها', '/cars', '#1e3a5f', 'suv'],
            ['خودروی صفر، تحویل فوری', 'جدیدترین محصولات فونیکس، کی‌ام‌سی و ام‌وی‌ام با گارانتی رسمی نمایندگی', 'خودروهای صفر', '/cars?condition=new', '#0f172a', 'sedan'],
            ['ماشینت رو با بهترین قیمت بفروش', 'کارشناسی رایگان و خرید نقدی خودروی شما در کمتر از ۲۴ ساعت', 'ثبت درخواست فروش', '/sell', '#7c2d12', 'suv'],
        ];
        foreach ($s as $i => [$t, $st, $bt, $bl, $c, $type]) {
            $file = "demo/slide$i.svg";
            file_put_contents(PUB . '/uploads/' . $file, DemoArt::car(['#e5e7eb', '#f7931e', '#9ca3af'][$i], $type, 1));
            DB::insert('slides', ['title' => $t, 'subtitle' => $st, 'image' => $file, 'btn_text' => $bt, 'btn_link' => $bl, 'align' => 'right', 'sort' => $i, 'active' => 1]);
        }
    }

    /** default workshop services (editable in admin → نوبت‌دهی → تنظیمات) */
    public static function serviceTypes(): void
    {
        $list = [
            ['سرویس دوره‌ای', 'تعویض روغن موتور، فیلتر روغن، فیلتر هوا و بازدید کامل فنی طبق دفترچه سرویس', 'wrench', 1500000, 90],
            ['تعویض روغن و فیلتر', 'تعویض روغن موتور و فیلتر روغن با روغن اصل', 'drop', 900000, 30],
            ['تعمیرات مکانیکی', 'عیب‌یابی و تعمیر موتور، گیربکس، جلوبندی و سیستم ترمز', 'engine', null, 120],
            ['برق و دیاگ خودرو', 'عیب‌یابی با دستگاه دیاگ، تعمیر سیستم برق، باتری و استارت', 'bolt', 500000, 60],
            ['صافکاری و نقاشی', 'برآورد هزینه و انجام صافکاری بدون رنگ، صافکاری و نقاشی قطعات', 'paint', null, 60],
            ['لاستیک، بالانس و تنظیم فرمان', 'تعویض لاستیک، بالانس چرخ و تنظیم زوایای فرمان', 'wheel', 600000, 60],
            ['کارشناسی خودرو', 'کارشناسی فنی و بدنه پیش از خرید یا فروش خودرو', 'shield', 800000, 60],
        ];
        foreach ($list as $i => [$t, $d, $ic, $p, $dur]) {
            DB::insert('service_types', ['title' => $t, 'description' => $d, 'icon' => $ic, 'price_from' => $p, 'duration' => $dur, 'active' => 1, 'sort' => $i]);
        }
    }
}
