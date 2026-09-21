<?php

namespace App\Support\Migrations;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait AltersInstrumentTypeEnumSafely
{
    protected function withoutForeignKeyChecks(callable $callback): void
    {
        Schema::disableForeignKeyConstraints();
        try {
            $callback();
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    /**
     * @param  list<string>  $tables
     */
    protected function normalizeLegacyInstrumentTypeValues(array $tables = ['contracts', 'real_estates']): void
    {
        $map = [
            'property_ownership__owner_are_deceased_endowment' => 'property_ownership_owner_are_deceased_endowment',
            'property_ownership__owner_are_deceased' => 'property_ownership_owner_are_deceased',
        ];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'instrument_type')) {
                continue;
            }

            foreach ($map as $from => $to) {
                DB::table($table)->where('instrument_type', $from)->update(['instrument_type' => $to]);
            }
        }
    }

    protected function modifyInstrumentTypeEnum(string $table, string $enumSqlBody): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'instrument_type')) {
            return;
        }

        $this->normalizeLegacyInstrumentTypeValues([$table]);

        $this->withoutForeignKeyChecks(function () use ($table, $enumSqlBody): void {
            DB::statement("ALTER TABLE `{$table}` {$enumSqlBody}");
        });
    }
}
