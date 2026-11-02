<?php
declare(strict_types=1);

/** Database schema (works on SQLite & MySQL/MariaDB) */
final class Schema
{
    /** bump when tables are added — existing sites upgrade automatically on next request */
    public const VERSION = 4;

    public static function sql(): array
    {
        $my = DB::driver() === 'mysql';
        $ID = $my ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $TEXT = $my ? 'MEDIUMTEXT' : 'TEXT';
        $BIG = $my ? 'BIGINT' : 'INTEGER';
        $END = $my ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';
        $DT = $my ? 'DATETIME' : 'TEXT';

        return [
            "CREATE TABLE IF NOT EXISTS settings (k VARCHAR(100) NOT NULL PRIMARY KEY, v $TEXT)$END",

            "CREATE TABLE IF NOT EXISTS users (id $ID, name VARCHAR(120) NOT NULL, username VARCHAR(60) NOT NULL UNIQUE,
              password VARCHAR(255) NOT NULL, role VARCHAR(20) NOT NULL DEFAULT 'expert', mobile VARCHAR(15),
              active TINYINT NOT NULL DEFAULT 1, last_login $DT NULL, created_at $DT NULL)$END",

            "CREATE TABLE IF NOT EXISTS login_attempts (id $ID, ip VARCHAR(45), username VARCHAR(100), created_at $DT NULL)$END",

            "CREATE TABLE IF NOT EXISTS brands (id $ID, name_fa VARCHAR(80) NOT NULL, name_en VARCHAR(80), origin VARCHAR(10) NOT NULL DEFAULT 'domestic',
              logo VARCHAR(255), sort INT NOT NULL DEFAULT 0, popular TINYINT NOT NULL DEFAULT 0)$END",

            "CREATE TABLE IF NOT EXISTS models (id $ID, brand_id INT NOT NULL, name_fa VARCHAR(80) NOT NULL, name_en VARCHAR(80), body_type VARCHAR(20),
              FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE)$END",

            "CREATE TABLE IF NOT EXISTS cars (id $ID, code VARCHAR(20) NOT NULL UNIQUE, slug VARCHAR(191), car_condition VARCHAR(10) NOT NULL DEFAULT 'used',
              brand_id INT NOT NULL, model_id INT NULL, trim_name VARCHAR(80), year INT, mileage INT NOT NULL DEFAULT 0,
              gearbox VARCHAR(15), fuel VARCHAR(15), body_type VARCHAR(20), color VARCHAR(30), interior_color VARCHAR(30), city VARCHAR(40),
              price $BIG NOT NULL DEFAULT 0, old_price $BIG NULL, price_call TINYINT NOT NULL DEFAULT 0,
              installment TINYINT NOT NULL DEFAULT 0, installment_note VARCHAR(255),
              ins_third TINYINT NOT NULL DEFAULT 0, ins_third_until VARCHAR(20), ins_third_company VARCHAR(40), ins_discount_years INT NULL,
              ins_body TINYINT NOT NULL DEFAULT 0, ins_body_until VARCHAR(20), ins_body_company VARCHAR(40),
              tech_inspection TINYINT NOT NULL DEFAULT 0, fuel_card TINYINT NOT NULL DEFAULT 0, doc_type VARCHAR(20),
              warranty VARCHAR(120), chassis VARCHAR(20), engine_status VARCHAR(120), owners INT NULL,
              tags VARCHAR(255), options $TEXT, description $TEXT, damage $TEXT, inspection $TEXT, specs $TEXT,
              status VARCHAR(15) NOT NULL DEFAULT 'available', featured TINYINT NOT NULL DEFAULT 0, views INT NOT NULL DEFAULT 0,
              created_by INT NULL, created_at $DT NULL, updated_at $DT NULL,
              FOREIGN KEY (brand_id) REFERENCES brands(id))$END",
            "CREATE INDEX idx_cars_list ON cars (status, car_condition, price)",
            "CREATE INDEX idx_cars_brand ON cars (brand_id, model_id)",

            "CREATE TABLE IF NOT EXISTS car_images (id $ID, car_id INT NOT NULL, path VARCHAR(255) NOT NULL, thumb VARCHAR(255), sort INT NOT NULL DEFAULT 0,
              FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE)$END",

            "CREATE TABLE IF NOT EXISTS damage_points (id $ID, car_id INT NOT NULL, part VARCHAR(30) NOT NULL, type VARCHAR(20) NOT NULL,
              x INT NOT NULL DEFAULT 0, y INT NOT NULL DEFAULT 0, note VARCHAR(255), photo VARCHAR(255), thumb VARCHAR(255), sort INT NOT NULL DEFAULT 0)$END",
            "CREATE INDEX idx_dp_car ON damage_points (car_id)",

            "CREATE TABLE IF NOT EXISTS part_categories (id $ID, name VARCHAR(80) NOT NULL, slug VARCHAR(100), icon VARCHAR(30), sort INT NOT NULL DEFAULT 0)$END",

            "CREATE TABLE IF NOT EXISTS parts (id $ID, category_id INT NULL, title VARCHAR(200) NOT NULL, sku VARCHAR(60), brand VARCHAR(80),
              compatible VARCHAR(255), price $BIG NOT NULL DEFAULT 0, sale_price $BIG NULL, stock INT NOT NULL DEFAULT 0,
              image VARCHAR(255), thumb VARCHAR(255), description $TEXT, is_option TINYINT NOT NULL DEFAULT 0, active TINYINT NOT NULL DEFAULT 1,
              featured TINYINT NOT NULL DEFAULT 0, created_at $DT NULL)$END",

            "CREATE TABLE IF NOT EXISTS orders (id $ID, code VARCHAR(20) NOT NULL UNIQUE, type VARCHAR(10) NOT NULL, car_id INT NULL,
              customer_name VARCHAR(120) NOT NULL, mobile VARCHAR(15) NOT NULL, national_code VARCHAR(10), email VARCHAR(120),
              province VARCHAR(40), city VARCHAR(60), address VARCHAR(500), postal_code VARCHAR(10), note VARCHAR(1000),
              subtotal $BIG NOT NULL DEFAULT 0, shipping $BIG NOT NULL DEFAULT 0, total $BIG NOT NULL DEFAULT 0,
              pay_amount $BIG NOT NULL DEFAULT 0, pay_type VARCHAR(10) NOT NULL DEFAULT 'full', paid_amount $BIG NOT NULL DEFAULT 0,
              status VARCHAR(15) NOT NULL DEFAULT 'pending', admin_note VARCHAR(1000), track_token VARCHAR(64), customer_id INT NULL,
              created_at $DT NULL, updated_at $DT NULL)$END",
            "CREATE INDEX idx_orders_mobile ON orders (mobile)",

            "CREATE TABLE IF NOT EXISTS order_items (id $ID, order_id INT NOT NULL, part_id INT NULL, title VARCHAR(200), price $BIG NOT NULL, qty INT NOT NULL,
              FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE)$END",

            "CREATE TABLE IF NOT EXISTS gateways (id $ID, label VARCHAR(80) NOT NULL, bank VARCHAR(40), driver VARCHAR(30) NOT NULL,
              config $TEXT, sandbox TINYINT NOT NULL DEFAULT 0, active TINYINT NOT NULL DEFAULT 0, sort INT NOT NULL DEFAULT 0)$END",

            "CREATE TABLE IF NOT EXISTS transactions (id $ID, order_id INT NOT NULL, gateway_id INT NULL, driver VARCHAR(30), amount $BIG NOT NULL,
              authority VARCHAR(191), ref_id VARCHAR(100), card_pan VARCHAR(30), status VARCHAR(10) NOT NULL DEFAULT 'init',
              message VARCHAR(500), raw $TEXT, created_at $DT NULL, verified_at $DT NULL)$END",
            "CREATE INDEX idx_tx_authority ON transactions (authority)",

            "CREATE TABLE IF NOT EXISTS slides (id $ID, title VARCHAR(160), subtitle VARCHAR(255), image VARCHAR(255), btn_text VARCHAR(60),
              btn_link VARCHAR(255), align VARCHAR(10) DEFAULT 'right', sort INT NOT NULL DEFAULT 0, active TINYINT NOT NULL DEFAULT 1)$END",

            "CREATE TABLE IF NOT EXISTS requests (id $ID, type VARCHAR(20) NOT NULL, name VARCHAR(120), mobile VARCHAR(15), car_id INT NULL,
              data $TEXT, message VARCHAR(2000), status VARCHAR(10) NOT NULL DEFAULT 'new', ip VARCHAR(45), customer_id INT NULL, created_at $DT NULL)$END",

            "CREATE TABLE IF NOT EXISTS customers (id $ID, mobile VARCHAR(15) NOT NULL UNIQUE, name VARCHAR(120), email VARCHAR(120),
              national_code VARCHAR(10), password VARCHAR(255) NULL, verified TINYINT NOT NULL DEFAULT 0, active TINYINT NOT NULL DEFAULT 1,
              province VARCHAR(40), city VARCHAR(60), address VARCHAR(500), postal_code VARCHAR(10),
              created_at $DT NULL, last_login $DT NULL)$END",
            "CREATE INDEX idx_orders_customer ON orders (customer_id)",

            "CREATE TABLE IF NOT EXISTS faqs (id $ID, question VARCHAR(255) NOT NULL, answer $TEXT, grp VARCHAR(20) DEFAULT 'general',
              sort INT NOT NULL DEFAULT 0, active TINYINT NOT NULL DEFAULT 1)$END",

            "CREATE TABLE IF NOT EXISTS pages (id $ID, slug VARCHAR(100) NOT NULL UNIQUE, title VARCHAR(160) NOT NULL, content $TEXT,
              active TINYINT NOT NULL DEFAULT 1, in_footer TINYINT NOT NULL DEFAULT 1)$END",

            "CREATE TABLE IF NOT EXISTS sms_log (id $ID, mobile VARCHAR(15), message VARCHAR(1000), provider VARCHAR(30), ok TINYINT,
              response VARCHAR(1000), created_at $DT NULL)$END",

            "CREATE TABLE IF NOT EXISTS activity_log (id $ID, user_id INT NULL, action VARCHAR(60), details VARCHAR(500), ip VARCHAR(45), created_at $DT NULL)$END",
        ];
    }

    /** run pending schema changes once (idempotent) */
    public static function upgrade(): void
    {
        if ((int)Settings::get('schema_version', '1') >= self::VERSION) return;
        // each statement on its own, so one refusal never blocks the rest
        foreach (self::sql() as $q) {
            try { DB::pdo()->exec($q); }
            catch (PDOException $e) {
                $m = $e->getMessage();
                // indexes on columns added later in this method are retried below; don't log those
                if (!str_contains(strtolower($m), 'exist') && !str_contains($m, 'Duplicate') && !str_starts_with($q, 'CREATE INDEX')) app_log('error', 'schema upgrade: ' . $m);
            }
        }
        try { DB::val('SELECT COUNT(*) FROM damage_points'); }
        catch (Throwable $e) { app_log('error', 'schema upgrade: damage_points missing: ' . $e->getMessage()); return; }   // retry next request
        // v4: link orders/requests to customer accounts (columns added to existing tables)
        self::addColumn('orders', 'customer_id', 'INT NULL');
        self::addColumn('requests', 'customer_id', 'INT NULL');
        try { DB::pdo()->exec('CREATE INDEX idx_orders_customer ON orders (customer_id)'); } catch (Throwable) {}
        try { DB::val('SELECT COUNT(*) FROM customers'); DB::val('SELECT customer_id FROM orders LIMIT 1'); DB::val('SELECT customer_id FROM requests LIMIT 1'); }
        catch (Throwable $e) { app_log('error', 'schema upgrade v4 incomplete: ' . $e->getMessage()); return; }
        // v3: sites still on the old default (navy, untouched colours) move to the warm "sunset" preset once
        if ((int)Settings::get('schema_version', '1') < 3 && Settings::get('theme_preset', 'navy') === 'navy'
            && in_array(Settings::get('theme_primary', ''), ['', '#1e3a5f'], true) && in_array(Settings::get('theme_accent', ''), ['', '#f7931e'], true)) {
            Settings::setMany(['theme_preset' => 'sunset', 'theme_primary' => '#2d1b3d', 'theme_accent' => '#ff9a2e', 'theme_bg' => '#f6f5f3', 'theme_text' => '#1f1a24']);
        }
        Settings::set('schema_version', (string)self::VERSION);
    }

    /** ALTER TABLE … ADD COLUMN, only if the column is missing (works on MySQL & SQLite) */
    public static function addColumn(string $table, string $col, string $def): void
    {
        try { DB::val("SELECT $col FROM $table LIMIT 1"); return; } catch (Throwable) {}
        try { DB::pdo()->exec("ALTER TABLE $table ADD COLUMN $col $def"); }
        catch (Throwable $e) { app_log('error', "schema: add $table.$col failed: " . $e->getMessage()); }
    }

    public static function install(): void
    {
        foreach (self::sql() as $q) {
            try { DB::pdo()->exec($q); }
            catch (PDOException $e) {
                // ignore "index already exists"
                if (!str_contains(strtolower($e->getMessage()), 'exist') && !str_contains($e->getMessage(), 'Duplicate')) throw $e;
            }
        }
    }
}
