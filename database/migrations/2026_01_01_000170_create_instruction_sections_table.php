<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instruction_sections', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('title_ar');
            $table->string('description_ar')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique('key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instruction_sections');
    }
};
