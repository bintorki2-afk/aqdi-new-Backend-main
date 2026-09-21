<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_crawl_issues', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seo_crawl_run_id');
            $table->unsignedBigInteger('seo_crawl_page_id')->nullable();
            $table->text('path');
            $table->string('type', 64);
            $table->string('severity', 16);
            $table->text('message_ar');
            $table->text('message_en');
            $table->json('details')->nullable();
            $table->timestamps();
            $table->index(['seo_crawl_run_id', 'severity'], 'seo_crawl_issues_seo_crawl_run_id_severity_index');
            $table->index(['seo_crawl_run_id', 'type'], 'seo_crawl_issues_seo_crawl_run_id_type_index');
            $table->foreign('seo_crawl_page_id', 'seo_crawl_issues_seo_crawl_page_id_foreign')->references('id')->on('seo_crawl_pages')->nullOnDelete();
            $table->foreign('seo_crawl_run_id', 'seo_crawl_issues_seo_crawl_run_id_foreign')->references('id')->on('seo_crawl_runs')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_crawl_issues');
    }
};
