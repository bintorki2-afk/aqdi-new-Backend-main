<?php

namespace App\Services;

use App\Http\Resources\Website\BlogListItemResource;
use App\Http\Resources\Website\BlogShowResource;
use App\Models\Blog;
use App\Models\BlogTag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class PublicBlogService
{
    public function paginate(Request $request): LengthAwarePaginator
    {
        $query = Blog::query()->publiclyVisible();

        if ($request->filled('category')) {
            $query->where('category', $request->string('category')->toString());
        }

        if ($request->filled('tag')) {
            $slug = $request->string('tag')->toString();
            $query->whereHas('tags', fn ($q) => $q->where('slug', $slug));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%'.$search.'%')
                    ->orWhere('excerpt', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        }

        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        $sort = $request->input('sort', 'latest');
        if ($sort === 'popular') {
            $query->orderByDesc('views_count')->orderByDesc('publish_at');
        } else {
            $query->orderByDesc('publish_at')->orderByDesc('id');
        }

        $default = (int) config('blogs.list_per_page', 6);
        if ($request->boolean('featured') && ! $request->filled('per_page')) {
            $default = (int) config('blogs.featured_max', 4);
        }

        $max = (int) config('blogs.list_per_page_max', 1000);
        $perPage = min(max((int) $request->input('per_page', $default), 1), $max);

        return $query->paginate($perPage);
    }

    /**
     * @return array{blog: Blog, extra: array<string, mixed>}|null
     */
    public function findPublicBySlug(string $slug): ?array
    {
        $blog = Blog::query()
            ->publiclyVisible()
            ->with('tags')
            ->where('slug', $slug)
            ->first();

        if (! $blog) {
            return null;
        }

        return [
            'blog' => $blog,
            'extra' => [
                'related_posts' => $this->relatedPosts($blog),
                'prev' => $this->neighbor($blog, 'prev'),
                'next' => $this->neighbor($blog, 'next'),
            ],
        ];
    }

    public function shouldSkipViewTracking(Request $request): bool
    {
        if ($request->boolean('preview')) {
            return true;
        }

        $header = strtolower(trim((string) $request->header('X-No-Track', '')));

        return in_array($header, ['1', 'true', 'yes'], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function meta(): array
    {
        $locale = app()->getLocale();
        $ttl = (int) config('blogs.cache.meta_ttl', 300);

        return Cache::remember('public.blogs.meta.'.$locale, $ttl, function () use ($locale) {
            $categoryCounts = Blog::query()
                ->publiclyVisible()
                ->whereNotNull('category')
                ->where('category', '!=', '')
                ->selectRaw('category, COUNT(*) as posts_count')
                ->groupBy('category')
                ->pluck('posts_count', 'category');

            $categories = [];
            $id = 1;
            foreach (config('blogs.categories', []) as $category) {
                $slug = (string) ($category['slug'] ?? '');
                if ($slug === '') {
                    continue;
                }

                $label = $locale === 'en'
                    ? ($category['label_en'] ?? $category['label_ar'] ?? $slug)
                    : ($category['label_ar'] ?? $category['label_en'] ?? $slug);

                $categories[] = [
                    'id' => $id++,
                    'slug' => $slug,
                    'label' => $label,
                    'posts_count' => (int) ($categoryCounts[$slug] ?? 0),
                ];
            }

            $popularTags = [];
            if (Schema::hasTable('blog_tags')) {
                $limit = (int) config('blogs.popular_tags_limit', 12);
                $popularTags = BlogTag::query()
                    ->withCount(['blogs as posts_count' => fn ($q) => $q->publiclyVisible()])
                    ->get()
                    ->filter(fn (BlogTag $tag) => (int) $tag->posts_count > 0)
                    ->sortByDesc('posts_count')
                    ->take($limit)
                    ->map(fn (BlogTag $tag) => [
                        'slug' => $tag->slug,
                        'label' => $tag->label($locale),
                        'posts_count' => (int) $tag->posts_count,
                    ])
                    ->values()
                    ->all();
            }

            return [
                'categories' => $categories,
                'popular_tags' => $popularTags,
                'stats' => config('blogs.stats', []),
            ];
        });
    }

    public function showResource(Blog $blog, array $extra): BlogShowResource
    {
        return new BlogShowResource($blog, $extra);
    }

    public function forgetMetaCache(): void
    {
        Cache::forget('public.blogs.meta.ar');
        Cache::forget('public.blogs.meta.en');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function relatedPosts(Blog $blog): array
    {
        if (! filled($blog->category)) {
            return [];
        }

        $limit = (int) config('blogs.related_posts_max', 3);

        $related = Blog::query()
            ->publiclyVisible()
            ->where('id', '!=', $blog->id)
            ->where('category', $blog->category)
            ->orderByDesc('publish_at')
            ->limit($limit)
            ->get();

        return BlogListItemResource::collection($related)->resolve();
    }

    /**
     * @return array{slug: string, title: string}|null
     */
    private function neighbor(Blog $blog, string $direction): ?array
    {
        $publishedAt = $blog->publish_at ?? $blog->created_at;
        $query = Blog::query()->publiclyVisible()->where('id', '!=', $blog->id);

        if ($direction === 'next') {
            $query->where(function ($q) use ($publishedAt, $blog) {
                $q->where('publish_at', '>', $publishedAt)
                    ->orWhere(function ($inner) use ($publishedAt, $blog) {
                        $inner->where('publish_at', $publishedAt)->where('id', '>', $blog->id);
                    });
            })->orderBy('publish_at')->orderBy('id');
        } else {
            $query->where(function ($q) use ($publishedAt, $blog) {
                $q->where('publish_at', '<', $publishedAt)
                    ->orWhere(function ($inner) use ($publishedAt, $blog) {
                        $inner->where('publish_at', $publishedAt)->where('id', '<', $blog->id);
                    });
            })->orderByDesc('publish_at')->orderByDesc('id');
        }

        $neighbor = $query->first(['slug', 'title']);

        if (! $neighbor) {
            return null;
        }

        return [
            'slug' => $neighbor->slug,
            'title' => $neighbor->title,
        ];
    }
}
