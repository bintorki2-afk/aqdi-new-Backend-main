<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_crawl_runs', function (Blueprint $table) {
            $table->id();
            $table->string('base_url', 512);
            $table->string('status', 32)->default('queued');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('indexed_pages')->default(0);
            $table->unsignedInteger('healthy_pages')->default(0);
            $table->unsignedInteger('broken_pages')->default(0);
            $table->unsignedInteger('on_page_issues')->default(0);
            $table->unsignedInteger('pages_crawled')->default(0);
            $table->unsignedInteger('pages_failed')->default(0);
            $table->json('category_counts')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_crawl_runs');
    }
};
