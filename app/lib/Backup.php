<?php
declare(strict_types=1);

/**
 * Backup & restore.
 *  - one file: backup-*.zip  = database.json (all tables, driver-independent) + uploads/ (photos)
 *    (hosts without ext-zip get backup-*.json.gz with the database only)
 *  - stored in storage/backups (not reachable from the web), downloadable from the admin panel
 *  - restore from a stored file or an uploaded one; a safety backup is taken first
 *  - optional automatic daily database backup (keeps the last 7)
 */
final class Backup
{
    private const APP = 'autosite-backup';
    private const SKIP = ['login_attempts'];
    private const UPLOAD_EXT = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'ico'];

    public static function dir(): string
    {
        $d = STORAGE . '/backups';
        if (!is_dir($d)) @mkdir($d, 0750, true);
        if (!is_file("$d/.htaccess")) @file_put_contents("$d/.htaccess", "Require all denied\nDeny from all\n");
        return $d;
    }

    /** tables in dependency order (parents first), taken from the schema */
    public static function tables(): array
    {
        $t = [];
        foreach (Schema::sql() as $q) if (preg_match('/^CREATE TABLE IF NOT EXISTS (\w+)/', $q, $m) && !in_array($m[1], self::SKIP, true)) $t[] = $m[1];
        return $t;
    }

    public static function columns(string $table): array
    {
        if (DB::driver() === 'mysql') return array_column(DB::all('SHOW COLUMNS FROM ' . DB::ident($table)), 'Field');
        return array_column(DB::all('PRAGMA table_info(' . DB::ident($table) . ')'), 'name');
    }

    public static function zipAvailable(): bool { return class_exists('ZipArchive'); }

    /** @return string file name */
    public static function create(bool $withUploads = true, string $kind = 'manual'): string
    {
        @set_time_limit(300);
        $data = ['app' => self::APP, 'version' => 1, 'schema_version' => (string)Settings::get('schema_version', '1'),
            'created_at' => date('c'), 'driver' => DB::driver(), 'site' => (string)setting('site_name'), 'tables' => []];
        foreach (self::tables() as $t) {
            try { $rows = DB::all('SELECT * FROM ' . DB::ident($t)); } catch (Throwable) { continue; }   // table not created on this site yet
            $cols = $rows ? array_keys($rows[0]) : self::columns($t);
            $data['tables'][$t] = ['columns' => $cols, 'rows' => array_map('array_values', $rows)];
        }
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        $withUploads = $withUploads && self::zipAvailable();
        $base = 'backup-' . date('Ymd-His') . '-' . $kind . '-' . ($withUploads ? 'full' : 'db') . '-' . bin2hex(random_bytes(3));
        $dir = self::dir();
        if (self::zipAvailable()) {
            $zip = new ZipArchive();
            if ($zip->open("$dir/$base.zip", ZipArchive::CREATE) !== true) throw new RuntimeException('امکان ساخت فایل پشتیبان وجود ندارد.');
            $zip->addFromString('database.json', $json);
            $zip->addFromString('README.txt', "Backup of " . setting('site_name') . " — " . date('Y-m-d H:i') . "\nRestore from: admin panel → پشتیبان‌گیری و بازیابی\n");
            if ($withUploads && is_dir(PUB . '/uploads')) {
                $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(PUB . '/uploads', FilesystemIterator::SKIP_DOTS));
                foreach ($it as $f) {
                    if (!$f->isFile() || !in_array(strtolower($f->getExtension()), self::UPLOAD_EXT, true)) continue;
                    $rel = 'uploads/' . str_replace('\\', '/', substr($f->getPathname(), strlen(PUB . '/uploads/')));
                    $zip->addFile($f->getPathname(), $rel);
                    $zip->setCompressionName($rel, ZipArchive::CM_STORE);     // images are already compressed
                }
            }
            $zip->close();
            return "$base.zip";
        }
        file_put_contents("$dir/$base.json.gz", gzencode($json, 6));
        return "$base.json.gz";
    }

    public static function list(): array
    {
        $out = [];
        foreach (glob(self::dir() . '/backup-*') ?: [] as $f) {
            if (!preg_match('/\.(zip|json\.gz|json)$/', $f)) continue;
            $n = basename($f);
            $out[] = ['name' => $n, 'size' => filesize($f), 'time' => filemtime($f),
                'kind' => str_contains($n, '-auto-') ? 'auto' : (str_contains($n, '-safety-') ? 'safety' : 'manual'),
                'uploads' => str_contains($n, '-full-') || (str_ends_with($n, '.zip') && !str_contains($n, '-db-') && self::zipHasUploads($f))];
        }
        usort($out, fn($a, $b) => $b['time'] <=> $a['time']);
        return $out;
    }

    private static function zipHasUploads(string $f): bool
    {
        if (!self::zipAvailable()) return false;
        $z = new ZipArchive();
        if ($z->open($f) !== true) return false;
        $has = $z->numFiles > 2;
        $z->close();
        return $has;
    }

    /** safe path of a stored backup by name (no traversal) */
    public static function path(string $name): ?string
    {
        if (!preg_match('/^backup-[\w-]+\.(zip|json\.gz|json)$/', $name)) return null;
        $p = self::dir() . '/' . $name;
        return is_file($p) ? $p : null;
    }

    /** read database.json (+ zip handle) from a backup file */
    private static function open(string $file): array
    {
        $zip = null;
        if (str_ends_with($file, '.zip')) {
            if (!self::zipAvailable()) throw new RuntimeException('افزونه zip روی هاست فعال نیست؛ فایل zip قابل بازیابی نیست.');
            $zip = new ZipArchive();
            if ($zip->open($file) !== true) throw new RuntimeException('فایل پشتیبان خراب است یا zip نیست.');
            $json = $zip->getFromName('database.json');
        } elseif (str_ends_with($file, '.gz')) {
            $json = @gzdecode((string)file_get_contents($file));
        } else {
            $json = file_get_contents($file);
        }
        $data = is_string($json) ? json_decode($json, true) : null;
        if (!is_array($data) || ($data['app'] ?? '') !== self::APP || !is_array($data['tables'] ?? null)) {
            throw new RuntimeException('این فایل، پشتیبان معتبر این سایت نیست.');
        }
        return [$data, $zip];
    }

    /** @return array{tables:int, rows:int, files:int} */
    public static function restore(string $file, bool $withUploads = true): array
    {
        @set_time_limit(600);
        [$data, $zip] = self::open($file);
        $my = DB::driver() === 'mysql';
        $known = self::tables();
        $stats = ['tables' => 0, 'rows' => 0, 'files' => 0];
        $pdo = DB::pdo();
        if ($my) $pdo->exec('SET FOREIGN_KEY_CHECKS=0'); else $pdo->exec('PRAGMA foreign_keys = OFF');
        try {
            DB::tx(function () use ($data, $known, &$stats) {
                // children first when emptying, parents first when filling
                foreach (array_reverse($known) as $t) if (isset($data['tables'][$t])) DB::run('DELETE FROM ' . DB::ident($t));
                foreach ($known as $t) {
                    if (!isset($data['tables'][$t])) continue;
                    $have = self::columns($t);
                    $cols = $data['tables'][$t]['columns'];
                    $keep = array_values(array_filter(array_keys($cols), fn($i) => in_array($cols[$i], $have, true)));   // columns this version knows
                    if (!$keep) continue;
                    $names = array_map(fn($i) => DB::ident($cols[$i]), $keep);
                    $st = DB::pdo()->prepare('INSERT INTO ' . DB::ident($t) . ' (' . implode(',', $names) . ') VALUES (' . implode(',', array_fill(0, count($keep), '?')) . ')');
                    foreach ($data['tables'][$t]['rows'] as $r) { $st->execute(array_map(fn($i) => $r[$i] ?? null, $keep)); $stats['rows']++; }
                    $stats['tables']++;
                }
            });
        } finally {
            if ($my) $pdo->exec('SET FOREIGN_KEY_CHECKS=1'); else $pdo->exec('PRAGMA foreign_keys = ON');
        }
        if ($zip && $withUploads) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $n = (string)$zip->getNameIndex($i);
                if (!str_starts_with($n, 'uploads/') || str_ends_with($n, '/') || str_contains($n, '..') || str_contains($n, "\0")) continue;
                if (!in_array(strtolower(pathinfo($n, PATHINFO_EXTENSION)), self::UPLOAD_EXT, true)) continue;   // never write scripts
                $dest = PUB . '/' . $n;
                if (!is_dir(dirname($dest))) @mkdir(dirname($dest), 0755, true);
                if (@file_put_contents($dest, (string)$zip->getFromIndex($i)) !== false) $stats['files']++;
            }
        }
        $zip?->close();
        // an older backup may predate newer tables/columns: let the upgrader catch up
        try { Schema::upgrade(); } catch (Throwable $e) { app_log('error', 'upgrade after restore: ' . $e->getMessage()); }
        return $stats;
    }

    /** daily automatic database backup, run lazily from the admin panel */
    public static function autoDaily(): void
    {
        if (setting('backup_auto', '1') !== '1') return;
        $last = (int)setting('backup_auto_last', '0');
        if (time() - $last < 86400) return;
        Settings::set('backup_auto_last', (string)time());
        try {
            self::create(false, 'auto');
            $autos = array_values(array_filter(self::list(), fn($b) => $b['kind'] === 'auto'));
            foreach (array_slice($autos, 7) as $old) @unlink(self::dir() . '/' . $old['name']);
        } catch (Throwable $e) { app_log('error', 'auto backup: ' . $e->getMessage()); }
    }
}
