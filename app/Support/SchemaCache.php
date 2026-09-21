<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

/**
 * Request-lifetime cache for Schema::getColumnListing / hasColumn.
 * Shared-hosting MySQL information_schema probes are expensive; UnitsReal
 * and attribution checks used to hit them many times per contract step.
 */
final class SchemaCache
{
    /** @var array<string, list<string>> */
    private static array $columns = [];

    /** @var array<string, bool> */
    private static array $tables = [];

    public static function hasTable(string $table): bool
    {
        return self::$tables[$table] ??= Schema::hasTable($table);
    }

    public static function hasColumn(string $table, string $column): bool
    {
        $listing = self::columns($table);

        if (in_array($column, $listing, true)) {
            return true;
        }

        $lower = strtolower($column);

        foreach ($listing as $name) {
            if (strtolower($name) === $lower) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public static function columns(string $table): array
    {
        return self::$columns[$table] ??= Schema::getColumnListing($table);
    }

    public static function flush(): void
    {
        self::$columns = [];
        self::$tables = [];
    }
}
