<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeoCrawlPage extends Model
{
    protected $fillable = [
        'seo_crawl_run_id',
        'url_hash',
        'url',
        'path',
        'status_code',
        'load_time_ms',
        'content_type',
        'title',
        'meta_description',
        'h1_count',
        'image_count',
        'images_missing_alt',
        'outbound_internal_links',
        'inbound_internal_links',
        'is_html',
        'is_indexable',
        'is_healthy',
    ];

    protected $casts = [
        'status_code' => 'integer',
        'load_time_ms' => 'integer',
        'h1_count' => 'integer',
        'image_count' => 'integer',
        'images_missing_alt' => 'integer',
        'outbound_internal_links' => 'integer',
        'inbound_internal_links' => 'integer',
        'is_html' => 'boolean',
        'is_indexable' => 'boolean',
        'is_healthy' => 'boolean',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(SeoCrawlRun::class, 'seo_crawl_run_id');
    }

    public function issues(): HasMany
    {
        return $this->hasMany(SeoCrawlIssue::class);
    }
}
