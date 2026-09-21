<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_units', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('real_unit_id');
            $table->unsignedBigInteger('real_estate_id')->nullable();
            $table->timestamps();
            $table->unique(['contract_id', 'real_unit_id'], 'contract_units_contract_id_real_unit_id_unique');
            $table->index('real_estate_id');
            $table->foreign('contract_id', 'contract_units_contract_id_foreign')->references('id')->on('contracts')->cascadeOnDelete();
            $table->foreign('real_estate_id', 'contract_units_real_estate_id_foreign')->references('id')->on('real_estates')->nullOnDelete();
            $table->foreign('real_unit_id', 'contract_units_real_unit_id_foreign')->references('id')->on('real_units')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_units');
    }
};
