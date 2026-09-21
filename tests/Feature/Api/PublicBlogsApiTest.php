<?php

namespace Tests\Feature\Api;

use App\Models\Blog;
use App\Models\BlogTag;
use App\Models\NewsletterSubscriber;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PublicBlogsApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'app.url' => 'http://localhost',
            'blogs.cache.meta_ttl' => 0,
        ]);

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        DB::reconnect('sqlite');
        URL::forceRootUrl('http://localhost');

        $this->createSchema();
    }

    protected function tearDown(): void
    {
        foreach (['blog_tag', 'blog_tags', 'blogs', 'newsletter_subscribers'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_list_uses_paginator_envelope_and_omits_html_body(): void
    {
        $this->makePost(['slug' => 'a', 'title' => 'عقد أ', 'category' => 'contracts', 'publish_at' => now()->subDay()]);
        $this->makePost(['slug' => 'b', 'title' => 'عقد ب', 'category' => 'contracts', 'publish_at' => now()]);

        $json = $this->getJson('/api/blogs?page=1')
            ->assertOk()
            ->assertHeader('Cache-Control')
            ->json();

        $this->assertArrayHasKey('data', $json);
        $this->assertArrayHasKey('meta', $json);
        $this->assertSame(1, $json['meta']['current_page']);
        $this->assertSame(6, $json['meta']['per_page']);
        $this->assertSame(2, $json['meta']['total']);
        $this->assertCount(2, $json['data']);
        $this->assertArrayNotHasKey('description', $json['data'][0]);
        $this->assertArrayNotHasKey('content', $json['data'][0]);
        $this->assertNotEmpty($json['data'][0]['excerpt']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $json['data'][0]['published_at']);
        $this->assertIsInt($json['data'][0]['reading_time']);
        $this->assertIsBool($json['data'][0]['is_featured']);
    }

    public function test_empty_list_is_200_with_empty_data(): void
    {
        $json = $this->getJson('/api/blogs')->assertOk()->json();

        $this->assertSame([], $json['data']);
        $this->assertSame(0, $json['meta']['total']);
    }

    public function test_category_tag_search_and_sort_filters(): void
    {
        $ijar = BlogTag::query()->create(['slug' => 'ijar', 'label_ar' => '#إيجار']);

        $contracts = $this->makePost([
            'slug' => 'contract-post',
            'title' => 'عقد إيجار سكني',
            'category' => 'contracts',
            'views_count' => 3,
            'publish_at' => now()->subDays(2),
        ]);
        $contracts->tags()->attach($ijar->id);

        $this->makePost([
            'slug' => 'market-post',
            'title' => 'سوق العقارات',
            'category' => 'real-estate-market',
            'views_count' => 50,
            'publish_at' => now()->subDay(),
        ]);

        $this->getJson('/api/blogs?category=contracts')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.slug', 'contract-post')
            ->assertJsonPath('data.0.category', 'contracts');

        $this->getJson('/api/blogs?tag=ijar')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.slug', 'contract-post');

        $this->getJson('/api/blogs?search='.rawurlencode('عقد إيجار'))
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $this->getJson('/api/blogs?sort=popular')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'market-post');
    }

    public function test_featured_filter_and_per_page_override(): void
    {
        $this->makePost(['slug' => 'hero', 'title' => 'مميز', 'is_featured' => true, 'publish_at' => now()]);
        $this->makePost(['slug' => 'normal', 'title' => 'عادي', 'is_featured' => false, 'publish_at' => now()->subHour()]);

        $this->getJson('/api/blogs?featured=1')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.slug', 'hero')
            ->assertJsonPath('data.0.is_featured', true);

        $this->getJson('/api/blogs?per_page=1')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonCount(1, 'data');
    }

    public function test_sitemap_fields_slim_payload(): void
    {
        $this->makePost(['slug' => 'map-me', 'title' => 'خريطة', 'publish_at' => now()]);

        $item = $this->getJson('/api/blogs?per_page=1000&fields=slug,updated_at')
            ->assertOk()
            ->json('data.0');

        $this->assertSame(['slug', 'updated_at'], array_keys($item));
        $this->assertSame('map-me', $item['slug']);
    }

    public function test_show_returns_related_tags_and_neighbors_and_increments_views(): void
    {
        $tag = BlogTag::query()->create(['slug' => 'ijar', 'label_ar' => '#إيجار']);

        $older = $this->makePost([
            'slug' => 'older',
            'title' => 'أقدم',
            'category' => 'contracts',
            'publish_at' => now()->subDays(2),
        ]);
        $current = $this->makePost([
            'slug' => 'current',
            'title' => 'الحالي',
            'category' => 'contracts',
            'description' => '<p>نص آمن</p><script>alert(1)</script>',
            'publish_at' => now()->subDay(),
            'views_count' => 0,
        ]);
        $newer = $this->makePost([
            'slug' => 'newer',
            'title' => 'أحدث',
            'category' => 'contracts',
            'publish_at' => now(),
        ]);
        $current->tags()->attach($tag->id);

        $json = $this->getJson('/api/blogs/current')->assertOk()->json('data');

        $this->assertSame(1, $json['views_count']);
        $this->assertSame(1, $current->fresh()->views_count);
        $this->assertSame('نص آمن', strip_tags($json['content']));
        $this->assertStringNotContainsString('script', $json['content']);
        $this->assertSame($json['content'], $json['description']);
        $this->assertSame('ijar', $json['tags'][0]['slug']);
        $this->assertNotEmpty($json['related_posts']);
        $this->assertSame('older', $json['prev']['slug']);
        $this->assertSame('newer', $json['next']['slug']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $json['updated_at']);
        $this->assertArrayHasKey('og_image', $json);
    }

    public function test_preview_and_no_track_skip_view_increment(): void
    {
        $this->makePost(['slug' => 'tracked', 'title' => 'تتبع', 'views_count' => 4, 'publish_at' => now()]);

        $this->getJson('/api/blogs/tracked?preview=1')
            ->assertOk()
            ->assertJsonPath('data.views_count', 4);

        $this->withHeaders(['X-No-Track' => '1'])
            ->getJson('/api/blogs/tracked')
            ->assertOk()
            ->assertJsonPath('data.views_count', 4);

        $this->assertSame(4, Blog::query()->where('slug', 'tracked')->value('views_count'));
    }

    public function test_unpublished_and_unknown_slug_are_404(): void
    {
        $this->makePost([
            'slug' => 'draft-post',
            'title' => 'مسودة',
            'status' => 'draft',
            'is_active' => false,
        ]);

        $this->getJson('/api/blogs/draft-post')
            ->assertNotFound()
            ->assertJsonPath('message', trans('api.blog_not_found'));

        $this->getJson('/api/blogs/missing-slug')
            ->assertNotFound()
            ->assertJsonPath('message', trans('api.blog_not_found'));
    }

    public function test_meta_returns_canonical_categories_and_tags(): void
    {
        $tag = BlogTag::query()->create(['slug' => 'ijar', 'label_ar' => '#إيجار']);
        $post = $this->makePost(['slug' => 'c1', 'title' => 'عقود', 'category' => 'contracts', 'publish_at' => now()]);
        $post->tags()->attach($tag->id);

        $json = $this->getJson('/api/blogs/meta')
            ->assertOk()
            ->json();

        $slugs = collect($json['categories'])->pluck('slug')->all();
        $this->assertSame([
            'property-management',
            'contracts',
            'real-estate-market',
            'guides',
        ], $slugs);
        $this->assertSame(1, collect($json['categories'])->firstWhere('slug', 'contracts')['posts_count']);
        $this->assertSame('ijar', $json['popular_tags'][0]['slug']);
        $this->assertSame('+5M', $json['stats']['active_users']);
    }

    public function test_accept_language_localizes_category_label(): void
    {
        $this->makePost([
            'slug' => 'en-label',
            'title' => 'عقود',
            'category' => 'contracts',
            'publish_at' => now(),
        ]);

        $this->withHeaders(['Accept-Language' => 'en'])
            ->getJson('/api/blogs')
            ->assertOk()
            ->assertJsonPath('data.0.category_label', 'Contracts');

        $this->withHeaders(['Accept-Language' => 'ar'])
            ->getJson('/api/blogs')
            ->assertOk()
            ->assertJsonPath('data.0.category_label', 'العقود');
    }

    public function test_newsletter_subscribe_and_validation(): void
    {
        $this->postJson('/api/newsletter', ['email' => 'user@example.com'])
            ->assertOk()
            ->assertJsonStructure(['message']);

        $this->assertSame(1, NewsletterSubscriber::query()->count());

        $this->postJson('/api/newsletter', ['email' => 'user@example.com'])
            ->assertOk();

        $this->assertSame(1, NewsletterSubscriber::query()->count());

        $this->postJson('/api/newsletter', ['email' => 'not-an-email'])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors' => ['email']]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makePost(array $overrides = []): Blog
    {
        return Blog::query()->create(array_merge([
            'title' => 'مقال',
            'description' => '<p>محتوى المقال للاختبار مع كلمات كافية لحساب وقت القراءة.</p>',
            'excerpt' => 'مقتطف المقال',
            'slug' => 'post-'.uniqid(),
            'status' => 'published',
            'is_active' => true,
            'is_featured' => false,
            'publish_at' => now(),
            'author' => 'فريق عقدي',
            'views_count' => 0,
        ], $overrides));
    }

    private function createSchema(): void
    {
        Schema::create('blogs', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->longText('description')->nullable();
            $table->text('title')->nullable();
            $table->string('excerpt', 500)->nullable();
            $table->string('image')->nullable();
            $table->string('image_alt')->nullable();
            $table->string('og_image')->nullable();
            $table->unsignedSmallInteger('image_width')->nullable();
            $table->unsignedSmallInteger('image_height')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->text('slug');
            $table->string('category', 64)->nullable();
            $table->string('category_label_ar', 191)->nullable();
            $table->string('author', 191)->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->integer('is_active')->default(0);
            $table->string('status', 32)->default('draft');
            $table->timestamp('publish_at')->nullable();
            $table->unique('slug');
        });

        Schema::create('blog_tags', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('label_ar', 191);
            $table->string('label_en', 191)->nullable();
            $table->timestamps();
        });

        Schema::create('blog_tag', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('blog_id');
            $table->unsignedBigInteger('blog_tag_id');
            $table->unique(['blog_id', 'blog_tag_id']);
        });

        Schema::create('newsletter_subscribers', function (Blueprint $table): void {
            $table->id();
            $table->string('email', 191)->unique();
            $table->timestamp('subscribed_at')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamps();
        });
    }
}
