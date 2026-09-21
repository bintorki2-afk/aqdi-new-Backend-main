<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rea_estat_types', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->enum('contract_type', ['housing', 'commercial']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rea_estat_types');
    }
};
