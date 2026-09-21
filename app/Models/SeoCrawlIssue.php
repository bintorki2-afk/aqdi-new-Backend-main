<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoCrawlIssue extends Model
{
    protected $fillable = [
        'seo_crawl_run_id',
        'seo_crawl_page_id',
        'path',
        'type',
        'severity',
        'message_ar',
        'message_en',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(SeoCrawlRun::class, 'seo_crawl_run_id');
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(SeoCrawlPage::class, 'seo_crawl_page_id');
    }
}
