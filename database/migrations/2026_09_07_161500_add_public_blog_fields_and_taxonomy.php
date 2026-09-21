<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->string('excerpt', 500)->nullable()->after('title');
            $table->boolean('is_featured')->default(false)->after('author');
            $table->string('og_image')->nullable()->after('image_alt');
            $table->unsignedSmallInteger('image_width')->nullable()->after('og_image');
            $table->unsignedSmallInteger('image_height')->nullable()->after('image_width');
            $table->index('category');
            $table->index('is_featured');
            $table->index('publish_at');
        });

        Schema::create('blog_tags', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('label_ar', 191);
            $table->string('label_en', 191)->nullable();
            $table->timestamps();
        });

        Schema::create('blog_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_id')->constrained('blogs')->cascadeOnDelete();
            $table->foreignId('blog_tag_id')->constrained('blog_tags')->cascadeOnDelete();
            $table->unique(['blog_id', 'blog_tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_tag');
        Schema::dropIfExists('blog_tags');

        Schema::table('blogs', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropIndex(['is_featured']);
            $table->dropIndex(['publish_at']);
            $table->dropColumn([
                'excerpt',
                'is_featured',
                'og_image',
                'image_width',
                'image_height',
            ]);
        });
    }
};
