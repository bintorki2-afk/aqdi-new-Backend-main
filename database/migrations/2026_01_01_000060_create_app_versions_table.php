<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_versions', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 32);
            $table->string('latest_version', 50)->nullable();
            $table->string('min_version', 50)->nullable();
            $table->boolean('force_update')->default(false);
            $table->string('store_url', 500)->nullable();
            $table->text('message_ar')->nullable();
            $table->text('message_en')->nullable();
            $table->timestamps();
            $table->unique('platform');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_versions');
    }
};
