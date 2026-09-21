<?php

namespace App\Models;

use App\Support\BlogHtmlSanitizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Blog extends Model
{
    use HasFactory;

    protected $fillable = [
        'description',
        'image',
        'image_alt',
        'og_image',
        'image_width',
        'image_height',
        'slug',
        'title',
        'excerpt',
        'is_active',
        'is_featured',
        'meta_title',
        'meta_description',
        'status',
        'publish_at',
        'category',
        'category_label_ar',
        'author',
        'views_count',
    ];

    protected $casts = [
        'publish_at' => 'datetime',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'views_count' => 'integer',
        'image_width' => 'integer',
        'image_height' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($blog) {
            if (! $blog->slug) {
                $slug = $blog->title;
                $slug = trim($slug);
                $slug = mb_strtolower($slug, 'UTF-8');

                $slug = str_replace(['/', '\\'], '-', $slug);
                $slug = preg_replace("/[^a-z0-9_\sءاأإآؤئبتثجحخدذرزسشصضطظعغفقكلمنهويةى]/u", '', $slug);
                $slug = preg_replace("/[\s-]+/", ' ', $slug);
                $slug = preg_replace("/[\s_]/", '-', $slug);
                $blog->slug = $slug;
            }
        });
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(BlogTag::class, 'blog_tag', 'blog_id', 'blog_tag_id');
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where('is_active', 1)
            ->where(function (Builder $q) use ($now) {
                $q->where('status', 'published')
                    ->orWhere(function (Builder $scheduled) use ($now) {
                        $scheduled->whereIn('status', ['schedule', 'scheduled'])
                            ->whereNotNull('publish_at')
                            ->where('publish_at', '<=', $now);
                    });
            });
    }

    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    public function absoluteImageUrl(?string $path = null): ?string
    {
        $path = $path ?? $this->image;

        if (! filled($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return (string) url('storage/'.ltrim((string) $path, '/'));
    }

    public function excerptText(): string
    {
        if (filled($this->excerpt)) {
            return (string) $this->excerpt;
        }

        return BlogHtmlSanitizer::excerpt(
            $this->description,
            (int) config('blogs.excerpt_length', 160)
        );
    }

    public function wordCount(): int
    {
        return BlogHtmlSanitizer::wordCount($this->description);
    }

    public function readingTimeMinutes(): int
    {
        $wpm = max(1, (int) config('blogs.words_per_minute', 200));
        $words = $this->wordCount();

        return max(1, (int) ceil($words / $wpm));
    }

    public function publishedAtIso(): ?string
    {
        $at = $this->publish_at ?? $this->created_at;

        return $at ? $at->clone()->utc()->format('Y-m-d\TH:i:s\Z') : null;
    }

    public function updatedAtIso(): ?string
    {
        return $this->updated_at
            ? $this->updated_at->clone()->utc()->format('Y-m-d\TH:i:s\Z')
            : null;
    }

    public function categorySlug(): ?string
    {
        return filled($this->category) ? (string) $this->category : null;
    }

    public function categoryLabel(?string $locale = null): ?string
    {
        $locale = $locale ?: app()->getLocale();
        $slug = $this->categorySlug();

        if ($locale !== 'en' && filled($this->category_label_ar)) {
            return (string) $this->category_label_ar;
        }

        foreach (config('blogs.categories', []) as $category) {
            if (($category['slug'] ?? null) !== $slug) {
                continue;
            }

            if ($locale === 'en') {
                return $category['label_en'] ?? $category['label_ar'] ?? $slug;
            }

            return $category['label_ar'] ?? $category['label_en'] ?? $slug;
        }

        return $this->category_label_ar ?: $slug;
    }

    public function authorName(): string
    {
        return filled($this->author)
            ? (string) $this->author
            : (string) config('blogs.default_author', 'فريق عقدي');
    }

    /**
     * @param  list<string|array{slug?: string, label?: string}>  $tags
     */
    public function syncTagsInput(array $tags): void
    {
        $ids = [];

        foreach ($tags as $tag) {
            if (is_string($tag)) {
                $slug = $this->normalizeTagSlug($tag);
                $label = $tag;
            } elseif (is_array($tag)) {
                $label = (string) ($tag['label'] ?? $tag['slug'] ?? '');
                $slug = $this->normalizeTagSlug((string) ($tag['slug'] ?? $label));
            } else {
                continue;
            }

            if ($slug === '') {
                continue;
            }

            $model = BlogTag::query()->firstOrCreate(
                ['slug' => $slug],
                [
                    'label_ar' => $label !== '' ? $label : $slug,
                    'label_en' => $slug,
                ]
            );

            $ids[] = $model->id;
        }

        $this->tags()->sync($ids);
    }

    private function normalizeTagSlug(string $value): string
    {
        $value = trim($value);
        $slug = Str::slug($value, '-', 'en');

        if ($slug !== '') {
            return $slug;
        }

        $slug = (string) preg_replace('/[^\p{L}\p{N}]+/u', '-', mb_strtolower($value, 'UTF-8'));

        return trim($slug, '-');
    }
}
