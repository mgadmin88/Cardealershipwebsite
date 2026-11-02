<?php
declare(strict_types=1);

final class Cars
{
    public const SELECT = 'SELECT c.*, b.name_fa AS brand_name, b.name_en AS brand_en, m.name_fa AS model_name,
        (SELECT thumb FROM car_images i WHERE i.car_id = c.id ORDER BY sort, id LIMIT 1) AS thumb
        FROM cars c JOIN brands b ON b.id = c.brand_id LEFT JOIN models m ON m.id = c.model_id';

    public const SORTS = [
        'newest'     => ['جدیدترین', 'c.created_at DESC, c.id DESC'],
        'cheap'      => ['ارزان‌ترین', 'c.price_call ASC, c.price ASC'],
        'expensive'  => ['گران‌ترین', 'c.price DESC'],
        'km'         => ['کم‌کارکردترین', 'c.mileage ASC'],
        'year'       => ['جدیدترین سال ساخت', 'c.year DESC'],
        'popular'    => ['پربازدیدترین', 'c.views DESC'],
    ];

    /** Which car conditions are visible according to enabled modules */
    public static function allowedConditions(): array
    {
        $c = [];
        if (module('used_cars')) $c[] = 'used';
        if (module('new_cars')) $c[] = 'new';
        return $c ?: ['__none__'];
    }

    /** @return array{0:string,1:array} where SQL + params */
    public static function where(array $f): array
    {
        $w = ["c.status IN ('available','reserved','sold')"];
        $p = [];
        $conds = self::allowedConditions();
        $w[] = 'c.car_condition IN ' . DB::in($conds);
        array_push($p, ...$conds);
        if (!empty($f['condition']) && isset(Catalog::CONDITION[$f['condition']])) { $w[] = 'c.car_condition = ?'; $p[] = $f['condition']; }
        if (!empty($f['brand'])) { $w[] = 'c.brand_id = ?'; $p[] = (int)$f['brand']; }
        if (!empty($f['model'])) { $w[] = 'c.model_id = ?'; $p[] = (int)$f['model']; }
        if (!empty($f['origin']) && in_array($f['origin'], ['domestic', 'foreign'], true)) { $w[] = 'b.origin = ?'; $p[] = $f['origin']; }
        foreach (['city' => 'c.city', 'gearbox' => 'c.gearbox', 'fuel' => 'c.fuel', 'body' => 'c.body_type', 'color' => 'c.color', 'doc' => 'c.doc_type'] as $k => $col) {
            if (!empty($f[$k])) {
                $vals = array_slice(array_filter((array)$f[$k], 'is_string'), 0, 20);
                if ($vals) { $w[] = "$col IN " . DB::in($vals); array_push($p, ...$vals); }
            }
        }
        foreach (['price_min' => 'c.price >= ?', 'price_max' => 'c.price <= ?', 'year_min' => 'c.year >= ?', 'year_max' => 'c.year <= ?', 'km_max' => 'c.mileage <= ?'] as $k => $sql) {
            $v = (int)preg_replace('/\D/', '', to_en_digits((string)($f[$k] ?? '')));
            if ($v > 0) { $w[] = $sql; $p[] = $v; }
        }
        if (!empty($f['installment']) && module('installment')) $w[] = 'c.installment = 1';
        if (!empty($f['ins_body'])) $w[] = 'c.ins_body = 1';
        if (!empty($f['no_paint'])) $w[] = "(c.damage IS NULL OR c.damage IN ('[]','{}',''))";
        if (!empty($f['available'])) $w[] = "c.status = 'available'";
        if (!empty($f['q'])) {
            $q = mb_substr(trim((string)$f['q']), 0, 60);
            foreach (preg_split('/\s+/u', $q) as $word) {
                $w[] = '(b.name_fa LIKE ? OR b.name_en LIKE ? OR m.name_fa LIKE ? OR m.name_en LIKE ? OR c.trim_name LIKE ? OR c.code = ? OR c.year = ?)';
                $like = '%' . str_replace(['%', '_'], '', $word) . '%';
                array_push($p, $like, $like, $like, $like, $like, to_en_digits($word), (int)to_en_digits($word));
            }
        }
        return [implode(' AND ', $w), $p];
    }

    public static function search(array $f, int $page = 1, int $per = 12): array
    {
        [$where, $p] = self::where($f);
        $total = (int)DB::val("SELECT COUNT(*) FROM cars c JOIN brands b ON b.id = c.brand_id LEFT JOIN models m ON m.id = c.model_id WHERE $where", $p);
        $pg = paginate($total, $per, $page);
        $order = self::SORTS[$f['sort'] ?? ''][1] ?? self::SORTS['newest'][1];
        $order = "CASE c.status WHEN 'sold' THEN 1 ELSE 0 END, " . $order;
        $rows = DB::all(self::SELECT . " WHERE $where ORDER BY $order LIMIT {$pg['per']} OFFSET {$pg['offset']}", $p);
        return [$rows, $pg];
    }

    public static function latest(int $n = 8, array $f = []): array
    {
        [$where, $p] = self::where($f + ['available' => 1]);
        return DB::all(self::SELECT . " WHERE $where ORDER BY c.featured DESC, c.created_at DESC LIMIT $n", $p);
    }

    public static function find(int $id, bool $public = true): ?array
    {
        $car = DB::row(self::SELECT . ' WHERE c.id = ?', [$id]);
        if (!$car) return null;
        if ($public && ($car['status'] === 'draft' || !in_array($car['car_condition'], self::allowedConditions(), true))) return null;
        foreach (['damage', 'inspection', 'specs'] as $j) $car[$j] = json_decode((string)$car[$j], true) ?: [];
        return $car;
    }

    public static function images(int $id): array
    {
        return DB::all('SELECT * FROM car_images WHERE car_id = ? ORDER BY sort, id', [$id]);
    }

    public static function points(int $id): array
    {
        try { return DB::all('SELECT * FROM damage_points WHERE car_id = ? ORDER BY sort, id', [$id]); }
        catch (Throwable) { return []; }
    }

    public static function byIds(array $ids): array
    {
        $ids = array_slice(array_values(array_filter(array_map('intval', $ids))), 0, 24);
        if (!$ids) return [];
        return DB::all(self::SELECT . " WHERE c.id IN " . DB::in($ids) . " AND c.status != 'draft'", $ids);
    }

    public static function title(array $c): string
    {
        return trim($c['brand_name'] . ' ' . ($c['model_name'] ?? '') . ' ' . ($c['trim_name'] ?? '') . ' ' . $c['year']);
    }

    /** amount payable online for a car order */
    public static function depositFor(array $car): int
    {
        $v = (float)setting('deposit_value', 2);
        $amt = setting('deposit_type', 'percent') === 'fixed' ? (int)$v : (int)round($car['price'] * $v / 100);
        return max(10000, min($amt, (int)$car['price']));
    }
}
