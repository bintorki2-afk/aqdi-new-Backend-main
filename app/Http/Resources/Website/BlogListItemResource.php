<?php

namespace App\Http\Resources\Website;

use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Blog
 */
class BlogListItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $fields = $this->requestedFields($request);

        $payload = [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'excerpt' => $this->excerptText(),
            'category' => $this->categorySlug(),
            'category_label' => $this->categoryLabel(),
            'image' => $this->absoluteImageUrl(),
            'image_alt' => $this->image_alt ?: $this->title,
            'image_width' => $this->image_width,
            'image_height' => $this->image_height,
            'published_at' => $this->publishedAtIso(),
            'updated_at' => $this->updatedAtIso(),
            'reading_time' => $this->readingTimeMinutes(),
            'views_count' => (int) $this->views_count,
            'is_featured' => (bool) $this->is_featured,
            'author' => $this->authorName(),
        ];

        if ($fields === []) {
            return $payload;
        }

        return array_intersect_key($payload, array_flip($fields));
    }

    /**
     * @return list<string>
     */
    private function requestedFields(Request $request): array
    {
        $raw = trim((string) $request->query('fields', ''));

        if ($raw === '') {
            return [];
        }

        $allowed = [
            'id', 'slug', 'title', 'excerpt', 'category', 'category_label',
            'image', 'image_alt', 'image_width', 'image_height',
            'published_at', 'updated_at', 'reading_time', 'views_count',
            'is_featured', 'author',
        ];

        $requested = array_values(array_filter(array_map(
            static fn (string $field): string => strtolower(trim($field)),
            explode(',', $raw)
        )));

        return array_values(array_intersect($allowed, $requested));
    }
}
