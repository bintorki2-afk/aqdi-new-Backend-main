<?php

namespace App\Support\Marketing;

use Illuminate\Support\Facades\DB;

/**
 * Live MySQL never received contracts.utm_* (row size 1118). SQLite in tests
 * also treats double-quoted names as strings, so Schema::hasColumn / wrapped
 * SELECT probes are not a reliable source of truth.
 *
 * Results are cached for the PHP process — information_schema round-trips on
 * every UTM field used to stall contract create/update on shared hosting.
 */
final class AttributionSchema
{
    /** @var array<string, bool> */
    private static array $tables = [];

    /** @var array<string, bool> */
    private static array $columns = [];

    public static function hasTable(string $table): bool
    {
        if (! self::isSafeIdentifier($table)) {
            return false;
        }

        return self::$tables[$table] ??= self::probeTable($table);
    }

    public static function hasColumn(string $table, string $column): bool
    {
        if (! self::isSafeIdentifier($table) || ! self::isSafeIdentifier($column)) {
            return false;
        }

        $key = $table.'.'.$column;

        return self::$columns[$key] ??= self::probeColumn($table, $column);
    }

    public static function flush(): void
    {
        self::$tables = [];
        self::$columns = [];
    }

    private static function probeTable(string $table): bool
    {
        try {
            $connection = DB::connection();

            if ($connection->getDriverName() === 'sqlite') {
                return $connection->select(
                    'select name from sqlite_master where type = ? and name = ?',
                    ['table', $table]
                ) !== [];
            }

            return $connection->select(
                'select 1 from information_schema.tables where table_schema = database() and table_name = ? limit 1',
                [$table]
            ) !== [];
        } catch (\Throwable) {
            return false;
        }
    }

    private static function probeColumn(string $table, string $column): bool
    {
        try {
            $connection = DB::connection();

            if ($connection->getDriverName() === 'sqlite') {
                $names = collect($connection->select('PRAGMA table_info('.$table.')'))
                    ->pluck('name')
                    ->all();

                return in_array($column, $names, true);
            }

            return $connection->select(
                'select 1 from information_schema.columns where table_schema = database() and table_name = ? and column_name = ? limit 1',
                [$table, $column]
            ) !== [];
        } catch (\Throwable) {
            return false;
        }
    }

    private static function isSafeIdentifier(string $value): bool
    {
        return (bool) preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $value);
    }
}
