<?php
require_once __DIR__ . '/../config/config.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        // Upgrade path: older databases get the discount column automatically (see database/migrations/001_add_discount.sql).
        try {
            if ($pdo->query("SHOW TABLES LIKE 'products'")->fetch() && !$pdo->query("SHOW COLUMNS FROM products LIKE 'discount_percent'")->fetch())
                $pdo->exec('ALTER TABLE products ADD COLUMN discount_percent DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER price');
        } catch (Throwable $e) { /* no ALTER permission: run the migration SQL by hand */ }
    }
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement {
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}
function fetch_all(string $sql, array $p = []): array { return q($sql, $p)->fetchAll(); }
function fetch_one(string $sql, array $p = []): ?array { return q($sql, $p)->fetch() ?: null; }
function fetch_val(string $sql, array $p = []) { return q($sql, $p)->fetchColumn(); }

/** Column names must come from code, never from user input. */
function db_insert(string $table, array $data): int {
    $cols = implode(',', array_map(fn($c) => "`$c`", array_keys($data)));
    $ph = implode(',', array_fill(0, count($data), '?'));
    q("INSERT INTO `$table` ($cols) VALUES ($ph)", array_values($data));
    return (int) db()->lastInsertId();
}
function db_update(string $table, int $id, array $data): void {
    $set = implode(',', array_map(fn($c) => "`$c`=?", array_keys($data)));
    q("UPDATE `$table` SET $set WHERE id=?", [...array_values($data), $id]);
}
function db_delete(string $table, int $id): void {
    q("DELETE FROM `$table` WHERE id=?", [$id]);
}
