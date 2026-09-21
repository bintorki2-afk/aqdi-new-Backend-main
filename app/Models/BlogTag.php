<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BlogTag extends Model
{
    protected $fillable = [
        'slug',
        'label_ar',
        'label_en',
    ];

    public function blogs(): BelongsToMany
    {
        return $this->belongsToMany(Blog::class, 'blog_tag', 'blog_tag_id', 'blog_id');
    }

    public function label(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();

        if ($locale === 'en') {
            return (string) ($this->label_en ?: $this->label_ar ?: $this->slug);
        }

        return (string) ($this->label_ar ?: $this->label_en ?: $this->slug);
    }

    /**
     * @return array{slug: string, label: string}
     */
    public function toPublicArray(?string $locale = null): array
    {
        return [
            'slug' => $this->slug,
            'label' => $this->label($locale),
        ];
    }
}
