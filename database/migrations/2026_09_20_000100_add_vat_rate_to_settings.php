<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Single source for VAT: a proportional rate (percent) stored on settings.
 * Currently VAT is OFF, so the default is 0 (=> VAT amount 0 => shown as "مجانًا").
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('settings') && ! Schema::hasColumn('settings', 'vat_rate')) {
            Schema::table('settings', function (Blueprint $table) {
                // Percentage (e.g. 15 = 15%). Default 0 => VAT off.
                $table->decimal('vat_rate', 5, 2)->default(0)->after('commercial_tax');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('settings') && Schema::hasColumn('settings', 'vat_rate')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropColumn('vat_rate');
            });
        }
    }
};
