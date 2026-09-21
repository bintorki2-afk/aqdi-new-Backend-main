<?php

namespace App\Http\Resources\Website;

use App\Models\Blog;
use App\Support\BlogHtmlSanitizer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Blog
 */
class BlogShowResource extends JsonResource
{
    /**
     * @param  array<string, mixed>  $extra
     */
    public function __construct($resource, private readonly array $extra = [])
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $content = BlogHtmlSanitizer::sanitize($this->description);
        $image = $this->absoluteImageUrl();
        $ogImage = $this->absoluteImageUrl($this->og_image) ?: $image;

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'excerpt' => $this->excerptText(),
            'description' => $content,
            'content' => $content,
            'category' => $this->categorySlug(),
            'category_label' => $this->categoryLabel(),
            'image' => $image,
            'image_alt' => $this->image_alt ?: $this->title,
            'image_width' => $this->image_width,
            'image_height' => $this->image_height,
            'og_image' => $ogImage,
            'published_at' => $this->publishedAtIso(),
            'updated_at' => $this->updatedAtIso(),
            'reading_time' => $this->readingTimeMinutes(),
            'word_count' => $this->wordCount(),
            'views_count' => (int) $this->views_count,
            'is_featured' => (bool) $this->is_featured,
            'author' => $this->authorName(),
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'tags' => $this->whenLoaded('tags', fn () => $this->tags
                ->map(fn ($tag) => $tag->toPublicArray())
                ->values()
                ->all()),
            'related_posts' => $this->extra['related_posts'] ?? [],
            'prev' => $this->extra['prev'] ?? null,
            'next' => $this->extra['next'] ?? null,
        ];
    }
}
