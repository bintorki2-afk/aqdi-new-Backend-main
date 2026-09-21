<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instruction_section_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('instruction_section_id');
            $table->string('title_ar')->nullable();
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->string('file_extension', 10)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->foreign('instruction_section_id', 'instruction_section_images_instruction_section_id_foreign')->references('id')->on('instruction_sections')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instruction_section_images');
    }
};
