<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class WebsiteImage extends Model
{
    protected $fillable = [
        'key',
        'label_ar',
        'label_en',
        'path',
        'static_path',
        'alt_ar',
        'alt_en',
        'meta_title_ar',
        'meta_title_en',
        'meta_description_ar',
        'meta_description_en',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('website_images.by_key'));
        static::deleted(fn () => Cache::forget('website_images.by_key'));
    }

    public function getUrlAttribute(): ?string
    {
        if ($this->path) {
            return getFilePath($this->path);
        }

        if ($this->static_path) {
            return asset(ltrim($this->static_path, '/'));
        }

        return null;
    }

    public function alt(?string $locale = null): ?string
    {
        $locale = $locale ?: app()->getLocale();

        return $locale === 'en'
            ? ($this->alt_en ?: $this->alt_ar)
            : ($this->alt_ar ?: $this->alt_en);
    }

    public function metaTitle(?string $locale = null): ?string
    {
        $locale = $locale ?: app()->getLocale();

        return $locale === 'en'
            ? ($this->meta_title_en ?: $this->meta_title_ar)
            : ($this->meta_title_ar ?: $this->meta_title_en);
    }

    public function metaDescription(?string $locale = null): ?string
    {
        $locale = $locale ?: app()->getLocale();

        return $locale === 'en'
            ? ($this->meta_description_en ?: $this->meta_description_ar)
            : ($this->meta_description_ar ?: $this->meta_description_en);
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminRow(): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'label_ar' => $this->label_ar,
            'label_en' => $this->label_en,
            'path' => $this->path,
            'static_path' => $this->static_path,
            'url' => $this->url,
            'alt_ar' => $this->alt_ar,
            'alt_en' => $this->alt_en,
            'meta_title_ar' => $this->meta_title_ar,
            'meta_title_en' => $this->meta_title_en,
            'meta_description_ar' => $this->meta_description_ar,
            'meta_description_en' => $this->meta_description_en,
            'is_active' => (bool) $this->is_active,
            'sort_order' => (int) $this->sort_order,
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }

    public static function findByKey(string $key): ?self
    {
        $map = Cache::remember('website_images.by_key', 300, function () {
            return self::query()
                ->where('is_active', true)
                ->get()
                ->keyBy('key');
        });

        return $map->get($key);
    }
}
