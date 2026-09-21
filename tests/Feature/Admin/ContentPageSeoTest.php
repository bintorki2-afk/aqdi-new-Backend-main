<?php

namespace Tests\Feature\Admin;

use App\Models\ContentPage;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContentPageSeoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'app.url' => 'http://localhost',
        ]);

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        DB::reconnect('sqlite');
        URL::forceRootUrl('http://localhost');

        $this->createSchema();
        $this->actingEmployee([
            'app_content.view',
            'app_content.edit',
            'blogs.view',
            'blogs.edit',
            'analytics.view',
            'analytics.edit',
            'faqs.view',
            'faqs.edit',
        ]);
    }

    protected function tearDown(): void
    {
        foreach ([
            'content_pages',
            'personal_access_tokens',
            'role_permissions',
            'permissions',
            'employees',
            'roles',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_get_returns_meta_for_all_page_keys(): void
    {
        foreach (['home', 'about', 'faq', 'blogs', 'services'] as $page) {
            $json = $this->getJson('/api/admin/content-pages/'.$page)
                ->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonPath('data.page', $page)
                ->json('data');

            $this->assertSame('', $json['meta_title']);
            $this->assertSame('', $json['meta_description']);
            $this->assertSame('', $json['meta']['meta_title']);
            $this->assertSame('', $json['meta']['meta_description']);
            $this->assertArrayHasKey('sections', $json);
            $this->assertArrayHasKey('updated_at', $json);
        }

        $blogs = $this->getJson('/api/admin/content-pages/blogs')->json('data');
        $this->assertSame([], (array) $blogs['sections']);
    }

    public function test_post_persists_meta_without_wiping_home_sections(): void
    {
        ContentPage::query()->create([
            'page_key' => 'home',
            'content_json' => [
                'page' => 'home',
                'meta_title' => 'قديم',
                'meta_description' => 'وصف قديم',
                'sections' => [
                    'hero' => [
                        'badge_text' => 'شارة',
                        'main_title' => 'عنوان البطل',
                        'description' => 'وصف البطل',
                        'image_url' => '',
                    ],
                ],
            ],
        ]);

        $this->post('/api/admin/content-pages/home', [
            'meta_title' => 'مدونة عقدي — الصفحة الرئيسية',
            'meta_description' => 'وثّق عقد الإيجار إلكترونيًا.',
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.page', 'home')
            ->assertJsonPath('data.meta_title', 'مدونة عقدي — الصفحة الرئيسية')
            ->assertJsonPath('data.meta_description', 'وثّق عقد الإيجار إلكترونيًا.')
            ->assertJsonPath('data.meta.meta_title', 'مدونة عقدي — الصفحة الرئيسية')
            ->assertJsonPath('data.sections.hero.main_title', 'عنوان البطل');

        $stored = ContentPage::query()->where('page_key', 'home')->first();
        $this->assertSame('عنوان البطل', $stored->content_json['sections']['hero']['main_title']);
        $this->assertSame('مدونة عقدي — الصفحة الرئيسية', $stored->content_json['meta_title']);
    }

    public function test_section_only_post_does_not_clear_meta(): void
    {
        ContentPage::query()->create([
            'page_key' => 'about',
            'content_json' => [
                'page' => 'about',
                'meta_title' => 'من نحن — عقدي',
                'meta_description' => 'وصف من نحن',
                'sections' => [
                    'hero' => [
                        'badge_text' => '',
                        'main_title' => 'قديم',
                        'description' => '',
                    ],
                ],
            ],
        ]);

        $this->post('/api/admin/content-pages/about', [
            'hero' => [
                'main_title' => 'عنوان محدّث',
            ],
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.meta_title', 'من نحن — عقدي')
            ->assertJsonPath('data.meta_description', 'وصف من نحن')
            ->assertJsonPath('data.sections.hero.main_title', 'عنوان محدّث');
    }

    public function test_empty_string_clears_meta(): void
    {
        ContentPage::query()->create([
            'page_key' => 'blogs',
            'content_json' => [
                'page' => 'blogs',
                'meta_title' => 'مدونة عقدي',
                'meta_description' => 'مقالات',
                'sections' => [],
            ],
        ]);

        $this->post('/api/admin/content-pages/blogs', [
            'meta_title' => '',
            'meta_description' => 'اقرأ أحدث المقالات.',
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.meta_title', '')
            ->assertJsonPath('data.meta_description', 'اقرأ أحدث المقالات.');
    }

    public function test_public_v2_returns_stored_meta_for_index_pages(): void
    {
        ContentPage::query()->create([
            'page_key' => 'blogs',
            'content_json' => [
                'page' => 'blogs',
                'meta_title' => 'مدونة عقدي — مقالات عن توثيق الإيجار',
                'meta_description' => 'اقرأ أحدث المقالات والأدلة حول توثيق عقود الإيجار إلكترونيًا.',
                'sections' => [],
            ],
        ]);

        $this->getJson('/api/v2/content-pages/blogs')
            ->assertOk()
            ->assertJsonPath('data.page', 'blogs')
            ->assertJsonPath('data.meta_title', 'مدونة عقدي — مقالات عن توثيق الإيجار')
            ->assertJsonPath('data.meta_description', 'اقرأ أحدث المقالات والأدلة حول توثيق عقود الإيجار إلكترونيًا.');

        $this->getJson('/api/v2/content-pages/services')
            ->assertOk()
            ->assertJsonPath('data.page', 'services')
            ->assertJsonPath('data.meta_title', '');

        $this->getJson('/api/v2/content-pages/faq')
            ->assertOk()
            ->assertJsonPath('data.page', 'faq')
            ->assertJsonPath('data.meta_title', '');

        $this->getJson('/api/v2/content-pages/faqs')
            ->assertOk()
            ->assertJsonPath('data.page', 'faq');
    }

    public function test_public_index_returns_meta_for_every_page(): void
    {
        ContentPage::query()->create([
            'page_key' => 'home',
            'content_json' => [
                'page' => 'home',
                'meta_title' => 'عقدي',
                'meta_description' => 'توثيق عقود الإيجار',
                'sections' => [],
            ],
        ]);

        $json = $this->getJson('/api/v2/content-pages')
            ->assertOk()
            ->assertJsonPath('data.home.page', 'home')
            ->assertJsonPath('data.home.meta_title', 'عقدي')
            ->assertJsonPath('data.home.meta_description', 'توثيق عقود الإيجار')
            ->assertJsonPath('data.about.page', 'about')
            ->assertJsonPath('data.faq.page', 'faq')
            ->assertJsonPath('data.blogs.page', 'blogs')
            ->json('data');

        $this->assertArrayHasKey('meta_title', $json['about']);
        $this->assertArrayHasKey('meta_description', $json['faq']);
        $this->assertArrayHasKey('meta_title', $json['blogs']);
    }

    public function test_blogs_seo_requires_blogs_permission(): void
    {
        $this->actingEmployee(['app_content.view', 'app_content.edit']);

        $this->getJson('/api/admin/content-pages/home')->assertOk();
        $this->getJson('/api/admin/content-pages/blogs')->assertForbidden();
        $this->getJson('/api/admin/content-pages/faq')->assertForbidden();
        $this->post('/api/admin/content-pages/services', [
            'meta_title' => 'خدمات',
        ], ['Accept' => 'application/json'])->assertForbidden();
    }

    /**
     * @param  list<string>  $permissionNames
     */
    private function actingEmployee(array $permissionNames): void
    {
        Role::query()->delete();
        Permission::query()->delete();
        DB::table('role_permissions')->delete();
        Employee::query()->delete();

        $role = Role::query()->create([
            'name' => 'content-editor',
            'title_ar' => 'محرر محتوى',
            'is_active' => true,
        ]);

        foreach ($permissionNames as $name) {
            [$section, $action] = explode('.', $name, 2);
            $permission = Permission::query()->create([
                'name' => $name,
                'section' => $section,
                'action' => $action,
                'action_label_ar' => $action,
                'is_active' => true,
            ]);
            DB::table('role_permissions')->insert([
                'role_id' => $role->id,
                'permission_id' => $permission->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Sanctum::actingAs(Employee::query()->create([
            'name' => 'Content Editor',
            'email' => 'content-pages@aqdi.test',
            'password' => Hash::make('password'),
            'is_active' => true,
            'role_id' => $role->id,
        ]));
    }

    private function createSchema(): void
    {
        Schema::create('content_pages', function (Blueprint $table): void {
            $table->id();
            $table->string('page_key')->unique();
            $table->json('content_json')->nullable();
            $table->timestamps();
        });
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('title_ar')->nullable();
            $table->string('title_en')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('section')->nullable();
            $table->string('action')->nullable();
            $table->string('action_label_ar')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('role_permissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');
            $table->timestamps();
        });
        Schema::create('employees', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('role_id')->nullable();
            $table->timestamps();
        });
        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }
}
