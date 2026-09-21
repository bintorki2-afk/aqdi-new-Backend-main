<?php

namespace App\Support\Migrations;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeoCrawlTables
{
    public static function ensure(): void
    {
        self::ensureRunsTable();
        self::ensurePagesTable();
        self::ensureIssuesTable();
        self::widenExistingColumns();
    }

    public static function drop(): void
    {
        Schema::dropIfExists('seo_crawl_issues');
        Schema::dropIfExists('seo_crawl_pages');
        Schema::dropIfExists('seo_crawl_runs');
    }

    private static function ensureRunsTable(): void
    {
        if (Schema::hasTable('seo_crawl_runs')) {
            return;
        }

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

    private static function ensurePagesTable(): void
    {
        if (Schema::hasTable('seo_crawl_pages')) {
            return;
        }

        Schema::create('seo_crawl_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seo_crawl_run_id')->constrained('seo_crawl_runs')->cascadeOnDelete();
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

            $table->unique(['seo_crawl_run_id', 'url_hash']);
            $table->index(['seo_crawl_run_id', 'status_code']);
        });
    }

    private static function ensureIssuesTable(): void
    {
        if (Schema::hasTable('seo_crawl_issues')) {
            return;
        }

        Schema::create('seo_crawl_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seo_crawl_run_id')->constrained('seo_crawl_runs')->cascadeOnDelete();
            $table->foreignId('seo_crawl_page_id')->nullable()->constrained('seo_crawl_pages')->nullOnDelete();
            $table->text('path');
            $table->string('type', 64);
            $table->string('severity', 16);
            $table->text('message_ar');
            $table->text('message_en');
            $table->json('details')->nullable();
            $table->timestamps();

            $table->index(['seo_crawl_run_id', 'type']);
            $table->index(['seo_crawl_run_id', 'severity']);
        });
    }

    /**
     * Upgrade already-created VARCHAR columns to TEXT.
     */
    public static function widenExistingColumns(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        self::widenColumnIfVarchar('seo_crawl_pages', 'path', nullable: false);
        self::widenColumnIfVarchar('seo_crawl_issues', 'path', nullable: false);
        self::widenColumnIfVarchar('seo_crawl_issues', 'message_ar', nullable: false);
        self::widenColumnIfVarchar('seo_crawl_issues', 'message_en', nullable: false);
    }

    private static function widenColumnIfVarchar(string $table, string $column, bool $nullable): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        $row = DB::selectOne(
            'SELECT DATA_TYPE AS data_type
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?',
            [$table, $column]
        );

        if (! $row || ! in_array(strtolower((string) $row->data_type), ['varchar', 'char'], true)) {
            return;
        }

        $nullSql = $nullable ? 'NULL' : 'NOT NULL';
        DB::statement("ALTER TABLE {$table} MODIFY {$column} TEXT {$nullSql}");
    }
}
