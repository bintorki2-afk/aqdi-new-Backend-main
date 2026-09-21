<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('general_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('label_ar');
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->unique('key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('general_settings');
    }
};
