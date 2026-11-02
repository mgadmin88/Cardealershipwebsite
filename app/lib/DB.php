<?php
declare(strict_types=1);

/** Thin PDO wrapper. All queries use prepared statements. */
final class DB
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo) return self::$pdo;
        $c = config('db');
        $opts = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        if (($c['driver'] ?? 'sqlite') === 'mysql') {
            $dsn = "mysql:host={$c['host']};port={$c['port']};dbname={$c['name']};charset=" . ($c['charset'] ?? 'utf8mb4');
            self::$pdo = new PDO($dsn, $c['user'], $c['pass'], $opts);
        } else {
            $dir = dirname($c['path']);
            if (!is_dir($dir)) mkdir($dir, 0700, true);
            self::$pdo = new PDO('sqlite:' . $c['path'], null, null, $opts);
            self::$pdo->exec('PRAGMA foreign_keys = ON; PRAGMA journal_mode = WAL; PRAGMA busy_timeout = 5000;');
            // case-insensitive unicode-aware LIKE helper not needed: Persian has no case
        }
        return self::$pdo;
    }

    public static function driver(): string { return config('db.driver', 'sqlite'); }

    public static function run(string $sql, array $params = []): PDOStatement
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        return $st;
    }

    public static function all(string $sql, array $p = []): array { return self::run($sql, $p)->fetchAll(); }
    public static function row(string $sql, array $p = []): ?array { $r = self::run($sql, $p)->fetch(); return $r ?: null; }
    public static function val(string $sql, array $p = []): mixed { $r = self::run($sql, $p)->fetchColumn(); return $r === false ? null : $r; }

    public static function find(string $table, int $id): ?array
    {
        return self::row('SELECT * FROM ' . self::ident($table) . ' WHERE id = ?', [$id]);
    }

    public static function insert(string $table, array $data): int
    {
        $cols = array_map([self::class, 'ident'], array_keys($data));
        $sql = 'INSERT INTO ' . self::ident($table) . ' (' . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($data), '?')) . ')';
        self::run($sql, array_values($data));
        return (int)self::pdo()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $params = []): int
    {
        $set = implode(',', array_map(fn($k) => self::ident($k) . ' = ?', array_keys($data)));
        return self::run('UPDATE ' . self::ident($table) . " SET $set WHERE $where", [...array_values($data), ...$params])->rowCount();
    }

    public static function delete(string $table, string $where, array $params = []): int
    {
        return self::run('DELETE FROM ' . self::ident($table) . " WHERE $where", $params)->rowCount();
    }

    public static function tx(callable $fn): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try { $r = $fn(); $pdo->commit(); return $r; }
        catch (Throwable $e) { $pdo->rollBack(); throw $e; }
    }

    /** Column / table identifiers are whitelisted by pattern (never user input). */
    public static function ident(string $name): string
    {
        if (!preg_match('/^[a-z_][a-z0-9_]*$/i', $name)) throw new InvalidArgumentException('Bad identifier');
        return self::driver() === 'mysql' ? "`$name`" : "\"$name\"";
    }

    /** build "IN (?,?,?)" */
    public static function in(array $values): string { return '(' . implode(',', array_fill(0, max(1, count($values)), '?')) . ')'; }
}
