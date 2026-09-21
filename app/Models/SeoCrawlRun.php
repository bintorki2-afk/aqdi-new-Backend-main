<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeoCrawlRun extends Model
{
    public const STATUS_QUEUED = 'queued';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_STOPPED = 'stopped';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'base_url',
        'status',
        'started_at',
        'finished_at',
        'indexed_pages',
        'healthy_pages',
        'broken_pages',
        'on_page_issues',
        'pages_crawled',
        'pages_failed',
        'category_counts',
        'error_message',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'indexed_pages' => 'integer',
        'healthy_pages' => 'integer',
        'broken_pages' => 'integer',
        'on_page_issues' => 'integer',
        'pages_crawled' => 'integer',
        'pages_failed' => 'integer',
        'category_counts' => 'array',
    ];

    public function pages(): HasMany
    {
        return $this->hasMany(SeoCrawlPage::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(SeoCrawlIssue::class);
    }

    public function isInProgress(): bool
    {
        return in_array($this->status, [self::STATUS_QUEUED, self::STATUS_RUNNING], true);
    }
}
