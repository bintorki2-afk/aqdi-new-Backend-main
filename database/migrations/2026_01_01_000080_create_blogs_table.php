<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->longText('description')->nullable();
            $table->text('title')->nullable();
            $table->string('image')->nullable();
            $table->string('image_alt')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->text('slug');
            $table->string('category', 64)->nullable();
            $table->string('category_label_ar', 191)->nullable();
            $table->string('author', 191)->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->integer('is_active')->default(0);
            $table->string('status', 32)->default('draft');
            $table->timestamp('publish_at')->nullable();
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blogs');
    }
};
