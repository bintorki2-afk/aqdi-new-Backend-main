<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_crawl_pages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seo_crawl_run_id');
            $table->string('url_hash', 64);
            $table->text('url');
            $table->text('path');
            $table->unsignedSmallInteger('status_code')->default(0);
            $table->unsignedInteger('load_time_ms')->default(0);
            $table->string('content_type', 191)->nullable();
            $table->text('title')->nullable();
            $table->text('meta_description')->nullable();
            $table->unsignedSmallInteger('h1_count')->default(0);
            $table->unsignedSmallInteger('image_count')->default(0);
            $table->unsignedSmallInteger('images_missing_alt')->default(0);
            $table->unsignedSmallInteger('outbound_internal_links')->default(0);
            $table->unsignedSmallInteger('inbound_internal_links')->default(0);
            $table->boolean('is_html')->default(false);
            $table->boolean('is_indexable')->default(true);
            $table->boolean('is_healthy')->default(false);
            $table->timestamps();
            $table->index(['seo_crawl_run_id', 'status_code'], 'seo_crawl_pages_seo_crawl_run_id_status_code_index');
            $table->unique(['seo_crawl_run_id', 'url_hash'], 'seo_crawl_pages_seo_crawl_run_id_url_hash_unique');
            $table->foreign('seo_crawl_run_id', 'seo_crawl_pages_seo_crawl_run_id_foreign')->references('id')->on('seo_crawl_runs')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_crawl_pages');
    }
};
