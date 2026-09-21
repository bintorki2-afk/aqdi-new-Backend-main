<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_images', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64);
            $table->string('label_ar');
            $table->string('label_en')->nullable();
            $table->string('path', 500)->nullable();
            $table->string('static_path', 500)->nullable();
            $table->string('alt_ar')->nullable();
            $table->string('alt_en')->nullable();
            $table->string('meta_title_ar')->nullable();
            $table->string('meta_title_en')->nullable();
            $table->text('meta_description_ar')->nullable();
            $table->text('meta_description_en')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique('key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_images');
    }
};
