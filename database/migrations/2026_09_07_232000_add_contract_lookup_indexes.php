<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (! $this->hasIndex('contracts', 'contracts_uuid_index')) {
                $table->index('uuid', 'contracts_uuid_index');
            }
            if (! $this->hasIndex('contracts', 'contracts_user_incomplete_index')) {
                $table->index(
                    ['user_id', 'is_completed', 'is_delete', 'contract_type'],
                    'contracts_user_incomplete_index'
                );
            }
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if ($this->hasIndex('contracts', 'contracts_uuid_index')) {
                $table->dropIndex('contracts_uuid_index');
            }
            if ($this->hasIndex('contracts', 'contracts_user_incomplete_index')) {
                $table->dropIndex('contracts_user_incomplete_index');
            }
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        $connection = Schema::getConnection();

        if ($connection->getDriverName() === 'sqlite') {
            $indexes = $connection->select("PRAGMA index_list('{$table}')");

            return collect($indexes)->contains(fn ($row) => ($row->name ?? '') === $index);
        }

        return $connection->select(
            'select 1 from information_schema.statistics where table_schema = database() and table_name = ? and index_name = ? limit 1',
            [$table, $index]
        ) !== [];
    }
};
