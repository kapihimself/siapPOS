<?php
declare(strict_types=1);

namespace Siappos\Shared;

use PDO;
use RuntimeException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function connect(string $databasePath): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $directory = dirname($databasePath);

        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create storage directory.');
        }

        self::$pdo = new PDO('sqlite:' . $databasePath);
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        self::$pdo->exec('PRAGMA foreign_keys = ON;');

        self::migrate(self::$pdo);
        self::ensureSchemaCompatibility(self::$pdo);

        return self::$pdo;
    }

    private static function migrate(PDO $pdo): void
    {
        $schemaFile = SIAPPOS_ROOT . '/database/schema.sql';

        if (!is_file($schemaFile)) {
            throw new RuntimeException('Schema file not found: ' . $schemaFile);
        }

        $schema = file_get_contents($schemaFile);

        if ($schema === false) {
            throw new RuntimeException('Unable to read schema file.');
        }

        $pdo->exec($schema);
    }

    private static function ensureSchemaCompatibility(PDO $pdo): void
    {
        self::ensureColumn(
            $pdo,
            'settings',
            'outlet_name',
            "ALTER TABLE settings ADD COLUMN outlet_name TEXT NOT NULL DEFAULT 'Outlet Utama'"
        );

        self::ensureColumn(
            $pdo,
            'settings',
            'active_template',
            "ALTER TABLE settings ADD COLUMN active_template TEXT NOT NULL DEFAULT 'retail'"
        );

        self::ensureColumn(
            $pdo,
            'settings',
            'onboarding_completed',
            'ALTER TABLE settings ADD COLUMN onboarding_completed INTEGER NOT NULL DEFAULT 0'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS outlets (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                address TEXT,
                phone TEXT,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )'
        );
    }

    private static function ensureColumn(PDO $pdo, string $table, string $column, string $ddl): void
    {
        if (self::columnExists($pdo, $table, $column)) {
            return;
        }

        $pdo->exec($ddl);
    }

    private static function columnExists(PDO $pdo, string $table, string $column): bool
    {
        $stmt = $pdo->query("PRAGMA table_info($table)");

        foreach ($stmt->fetchAll() as $row) {
            if (($row['name'] ?? null) === $column) {
                return true;
            }
        }

        return false;
    }
}
