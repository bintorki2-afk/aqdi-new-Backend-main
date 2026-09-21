<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketingServicePage extends Model
{
    public const STATUSES = [
        'published' => 'منشور',
        'draft' => 'مسودة',
        'archived' => 'مؤرشف',
    ];

    protected $fillable = [
        'title',
        'path',
        'target_keyword',
        'status',
        'body',
    ];

    public static function normalizePath(?string $path): string
    {
        $value = '/'.ltrim(trim((string) $path), '/');
        if ($value !== '/') {
            $value = rtrim($value, '/');
        }

        return $value === '' ? '/' : $value;
    }

    public function statusLabelAr(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function publicUrl(): string
    {
        $base = rtrim((string) config('seo_crawl.base_url', 'https://aqdi.sa'), '/');

        return $this->path === '/' ? $base.'/' : $base.$this->path;
    }

    /**
     * @return array<string, mixed>
     */
    public function toMarketingRow(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'path' => $this->path,
            'target_keyword' => $this->target_keyword,
            'status' => $this->status,
            'status_label_ar' => $this->statusLabelAr(),
            'updated_at' => $this->updated_at?->toDateString(),
            'url' => $this->publicUrl(),
        ];
    }
}
