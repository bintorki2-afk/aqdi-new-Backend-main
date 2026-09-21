<?php

namespace Tests\Feature\Admin;

use App\Jobs\RunSeoCrawlJob;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SeoCrawlIssue;
use App\Models\SeoCrawlRun;
use App\Services\Seo\SeoCrawlService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SeoCrawlTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'app.url' => 'http://localhost',
            'seo_crawl.base_url' => 'https://aqdi.sa',
            'seo_crawl.delay_ms' => 0,
            'seo_crawl.slow_page_ms' => 3000,
            'seo_crawl.weak_inbound_links' => 1,
            'seo_crawl.max_pages' => 50,
            'seo_crawl.seed_urls' => [],
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');
        URL::forceRootUrl('http://localhost');

        $this->createMinimalSchema();
    }

    protected function tearDown(): void
    {
        foreach ([
            'seo_crawl_issues',
            'seo_crawl_pages',
            'seo_crawl_runs',
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

    public function test_execute_persists_dashboard_metrics_from_a_fake_site(): void
    {
        $this->fakeAqdiSite();

        $service = app(SeoCrawlService::class);
        $run = $service->createRun('https://aqdi.sa');
        $run = $service->execute($run->id);

        $this->assertSame(SeoCrawlRun::STATUS_COMPLETED, $run->status);
        $this->assertGreaterThanOrEqual(4, $run->pages_crawled);
        $this->assertSame(1, SeoCrawlIssue::query()->where('type', 'page_404')->count());
        $this->assertSame(1, SeoCrawlIssue::query()->where('type', 'broken_link')->count());
        $this->assertGreaterThan(0, SeoCrawlIssue::query()->where('type', 'duplicate_title')->count());
        $this->assertGreaterThan(0, SeoCrawlIssue::query()->where('type', 'missing_description')->count());
        $this->assertGreaterThan(0, SeoCrawlIssue::query()->where('type', 'missing_h1')->count());
        $this->assertGreaterThan(0, SeoCrawlIssue::query()->where('type', 'images_missing_alt')->count());

        $dashboard = $service->dashboard($run);
        $this->assertSame('completed', $dashboard['status']);
        $this->assertNotEmpty($dashboard['last_scanned_at']);
        $this->assertCount(12, $dashboard['categories']);
        $this->assertSame('missing_title', $dashboard['categories'][1]['type']);
    }

    public function test_duplicate_title_issue_persists_long_encoded_arabic_paths(): void
    {
        $run = SeoCrawlRun::query()->create([
            'base_url' => 'https://aqdi.sa',
            'status' => SeoCrawlRun::STATUS_COMPLETED,
            'started_at' => now(),
            'finished_at' => now(),
        ]);

        $path = 'blogs.aqdi.sa/blogs/'.str_repeat('%D8%AF%D9%84%D9%8A%D9%84-', 40).'guide/';
        $otherPath = 'blog/'.str_repeat('%D8%AF%D9%84%D9%8A%D9%84-', 40).'guide/';
        $messageAr = 'عنوان صفحة مكرر مع '.$otherPath;
        $messageEn = 'Duplicate page title with '.$otherPath;

        $this->assertGreaterThan(512, strlen($messageAr));

        $issue = SeoCrawlIssue::query()->create([
            'seo_crawl_run_id' => $run->id,
            'path' => $path,
            'type' => 'duplicate_title',
            'severity' => 'medium',
            'message_ar' => $messageAr,
            'message_en' => $messageEn,
            'details' => ['other_path' => $otherPath],
        ]);

        $this->assertSame($messageAr, $issue->fresh()->message_ar);
        $this->assertSame($messageEn, $issue->fresh()->message_en);
        $this->assertSame($path, $issue->fresh()->path);
    }

    public function test_admin_dashboard_and_issues_endpoints(): void
    {
        $this->actingEmployee();
        $this->fakeAqdiSite();

        $service = app(SeoCrawlService::class);
        $run = $service->execute($service->createRun()->id);

        $dashboard = $this->getJson('/api/admin/seo-crawl')->assertOk()->json('data');
        $this->assertSame($run->id, $dashboard['id']);
        $this->assertArrayHasKey('summary', $dashboard);
        $this->assertArrayHasKey('indexed_pages', $dashboard['summary']);

        $issues = $this->getJson('/api/admin/seo-crawl/issues?per_page=50')->assertOk()->json('data');
        $this->assertNotEmpty($issues['items']);
        $this->assertArrayHasKey('page', $issues['items'][0]);
        $this->assertArrayHasKey('problem', $issues['items'][0]);
        $this->assertArrayHasKey('severity', $issues['items'][0]);
        $this->assertArrayHasKey('problems', $issues['items'][0]);
        $this->assertArrayHasKey('problems_count', $issues['items'][0]);
        $this->assertSame(
            count(array_unique(array_column($issues['items'], 'page'))),
            count($issues['items']),
            'Each crawled page must appear only once in the issues response.'
        );

        $pageWithMultipleProblems = collect($issues['items'])
            ->first(fn (array $item) => $item['problems_count'] > 1);
        $this->assertNotNull($pageWithMultipleProblems);
        $this->assertCount(
            $pageWithMultipleProblems['problems_count'],
            $pageWithMultipleProblems['problems']
        );

        $this->getJson('/api/admin/seo-crawl/issues/'.$pageWithMultipleProblems['id'])
            ->assertOk()
            ->assertJsonPath('data.id', $pageWithMultipleProblems['id'])
            ->assertJsonPath('data.run_id', $run->id)
            ->assertJsonPath('data.problems_count', $pageWithMultipleProblems['problems_count'])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'page',
                    'problem',
                    'type',
                    'severity',
                    'details',
                    'run_id',
                    'problems' => [
                        '*' => [
                            'id',
                            'problem',
                            'problem_ar',
                            'problem_en',
                            'type',
                            'severity',
                            'details',
                        ],
                    ],
                    'page_details' => [
                        'url',
                        'status_code',
                        'load_time_ms',
                        'title',
                        'meta_description',
                        'is_indexable',
                    ],
                ],
            ]);
    }

    public function test_crawl_includes_blogs_subdomain_and_excludes_private_website_pages(): void
    {
        config([
            'seo_crawl.seed_urls' => ['https://blogs.aqdi.sa'],
            'seo_crawl.allowed_hosts' => ['aqdi.sa', 'www.aqdi.sa', 'blogs.aqdi.sa'],
            'seo_crawl.canonical_hosts' => ['www.aqdi.sa' => 'aqdi.sa'],
        ]);

        Http::preventStrayRequests();
        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            $url = rtrim($request->url(), '/');

            if ($url === 'https://aqdi.sa/sitemap.xml') {
                return Http::response('', 404);
            }

            if ($url === 'https://blogs.aqdi.sa/sitemap.xml') {
                return Http::response(
                    '<?xml version="1.0"?><urlset><loc>https://blogs.aqdi.sa/article-one</loc></urlset>',
                    200,
                    ['Content-Type' => 'application/xml']
                );
            }

            if ($url === 'https://aqdi.sa') {
                return Http::response(
                    '<html><head><title>Home</title></head><body><h1>Home</h1>'.
                    '<a href="/myContract">private contracts</a>'.
                    '<a href="/real-estate">private real estate</a>'.
                    '<a href="https://blogs.aqdi.sa/">blog</a></body></html>',
                    200,
                    ['Content-Type' => 'text/html']
                );
            }

            if ($url === 'https://blogs.aqdi.sa') {
                return Http::response(
                    '<html><head><title>Blog</title></head><body><h1>Blog</h1>'.
                    '<a href="https://blogs.aqdi.sa/article-one">article</a></body></html>',
                    200,
                    ['Content-Type' => 'text/html']
                );
            }

            if ($url === 'https://blogs.aqdi.sa/article-one') {
                return Http::response(
                    '<html><head><title>Article</title></head><body><h1>Article</h1></body></html>',
                    200,
                    ['Content-Type' => 'text/html']
                );
            }

            return Http::response('', 404);
        });

        $service = app(SeoCrawlService::class);
        $run = $service->execute($service->createRun('https://aqdi.sa')->id, 20);

        $this->assertSame(SeoCrawlRun::STATUS_COMPLETED, $run->status);
        $this->assertDatabaseHas('seo_crawl_pages', [
            'seo_crawl_run_id' => $run->id,
            'url' => 'https://blogs.aqdi.sa/article-one',
            'path' => 'blogs.aqdi.sa/article-one/',
        ]);
        $this->assertDatabaseMissing('seo_crawl_pages', ['url' => 'https://aqdi.sa/myContract']);
        $this->assertDatabaseMissing('seo_crawl_pages', ['url' => 'https://aqdi.sa/real-estate']);
        Http::assertNotSent(
            fn (\Illuminate\Http\Client\Request $request) => str_contains($request->url(), '/myContract')
                || str_contains($request->url(), '/real-estate')
        );
    }

    public function test_start_scan_queues_a_job(): void
    {
        $this->actingEmployee();
        Queue::fake();

        $this->postJson('/api/admin/seo-crawl/run')
            ->assertStatus(202)
            ->assertJsonPath('data.status', 'queued')
            ->assertJsonPath('data.progress.is_running', true)
            ->assertJsonPath('data.progress.percent', 0)
            ->assertJsonPath('data.progress.current', 0);

        Queue::assertPushed(RunSeoCrawlJob::class);
    }

    public function test_crawl_status_is_written_to_firebase_realtime_database(): void
    {
        config([
            'seo_crawl.firebase_status' => true,
            'seo_crawl.firebase_status_in_tests' => true,
            'seo_crawl.firebase_path' => 'seo_crawl/status',
            'services.firebase.database_url' => 'https://example-default-rtdb.firebaseio.com/',
            'services.firebase.database_access_token' => 'test-token',
        ]);

        Http::fake([
            'https://example-default-rtdb.firebaseio.com/*' => Http::response(['ok' => true], 200),
        ]);

        $service = app(SeoCrawlService::class);
        $run = $service->createRun('https://aqdi.sa');

        Http::assertSent(function ($request) use ($run) {
            $data = $request->data();

            return $request->method() === 'PUT'
                && str_contains($request->url(), 'seo_crawl/status.json')
                && ($data['status'] ?? null) === SeoCrawlRun::STATUS_QUEUED
                && (int) ($data['id'] ?? 0) === $run->id;
        });

        $service->stop($run->id);

        Http::assertSent(function ($request) {
            return $request->method() === 'PUT'
                && ($request->data()['status'] ?? null) === SeoCrawlRun::STATUS_STOPPED;
        });
    }

    public function test_system_admin_can_start_scan_without_seo_crawl_permission(): void
    {
        Queue::fake();

        $role = Role::query()->create([
            'name' => 'admin',
            'title_ar' => 'مدير النظام',
            'title_en' => 'System Admin',
            'is_active' => true,
        ]);
        $employee = Employee::query()->create([
            'name' => 'Super Admin',
            'email' => 'admin@aqdi.test',
            'password' => Hash::make('password'),
            'is_active' => true,
            'role_id' => $role->id,
        ]);
        Sanctum::actingAs($employee);

        $this->postJson('/api/admin/seo-crawl/run')
            ->assertStatus(202)
            ->assertJsonPath('data.status', 'queued');
    }

    public function test_stop_scan_marks_queued_run_stopped(): void
    {
        $this->actingEmployee();
        Queue::fake();

        $started = $this->postJson('/api/admin/seo-crawl/run')->assertStatus(202)->json('data');

        $this->postJson('/api/admin/seo-crawl/stop')
            ->assertOk()
            ->assertJsonPath('data.status', 'stopped')
            ->assertJsonPath('data.id', $started['id']);

        $this->assertSame(SeoCrawlRun::STATUS_STOPPED, SeoCrawlRun::query()->find($started['id'])?->status);
    }

    public function test_stop_scan_returns_409_when_nothing_is_running(): void
    {
        $this->actingEmployee();

        $this->postJson('/api/admin/seo-crawl/stop')
            ->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_execute_honors_stop_request(): void
    {
        $this->fakeAqdiSite();

        $service = app(SeoCrawlService::class);
        $run = $service->createRun('https://aqdi.sa');
        $seen = 0;
        $run = $service->execute($run->id, null, function () use ($service, $run, &$seen) {
            $seen++;
            if ($seen >= 2) {
                $service->requestStop($run->id);
            }
        });

        $this->assertSame(SeoCrawlRun::STATUS_STOPPED, $run->status);
        $this->assertLessThan(6, $run->pages_crawled);
    }

    public function test_empty_dashboard_when_never_crawled(): void
    {
        $this->actingEmployee();

        $this->getJson('/api/admin/seo-crawl')
            ->assertOk()
            ->assertJsonPath('data.status', 'never_run')
            ->assertJsonPath('data.summary.indexed_pages.count', 0)
            ->assertJsonPath('data.realtime.path', 'seo_crawl/status')
            ->assertJsonPath('data.progress.current', 0)
            ->assertJsonPath('data.progress.percent', 0)
            ->assertJsonPath('data.progress.is_running', false)
            ->assertJsonPath('data.progress.current_url', null);
    }

    public function test_dashboard_includes_live_crawl_progress(): void
    {
        $this->actingEmployee();
        $this->fakeAqdiSite();

        $service = app(SeoCrawlService::class);
        $run = $service->createRun('https://aqdi.sa', 50);

        $queued = $this->getJson('/api/admin/seo-crawl')->assertOk()->json('data');
        $this->assertSame('queued', $queued['status']);
        $this->assertSame(0, $queued['progress']['current']);
        $this->assertSame(50, $queued['progress']['max']);
        $this->assertSame(0, $queued['progress']['percent']);
        $this->assertTrue($queued['progress']['is_running']);
        $this->assertNull($queued['progress']['current_url']);

        $snapshots = [];
        $service->execute($run->id, 50, function () use ($service, &$snapshots) {
            $snapshots[] = $service->dashboard();
        });

        $live = collect($snapshots)->first(fn (array $row) => ($row['progress']['current'] ?? 0) > 0);
        $this->assertNotNull($live);
        $this->assertSame('running', $live['status']);
        $this->assertTrue($live['progress']['is_running']);
        $this->assertNotNull($live['progress']['current_url']);
        $this->assertSame($live['progress']['current'], $live['pages_crawled']);
        $this->assertGreaterThanOrEqual(0, $live['progress']['percent']);
        $this->assertLessThan(100, $live['progress']['percent']);

        $done = $this->getJson('/api/admin/seo-crawl')->assertOk()->json('data');
        $this->assertSame('completed', $done['status']);
        $this->assertSame(100, $done['progress']['percent']);
        $this->assertFalse($done['progress']['is_running']);
        $this->assertNull($done['progress']['current_url']);
        $this->assertGreaterThan(0, $done['progress']['current']);
    }

    private function fakeAqdiSite(): void
    {
        $headers = ['Content-Type' => 'text/html; charset=UTF-8'];
        $html = function (string $title, string $body, ?string $description = 'desc', bool $noindex = false): string {
            $robots = $noindex ? '<meta name="robots" content="noindex">' : '';
            $desc = $description === null ? '' : '<meta name="description" content="'.$description.'">';

            return '<html><head><title>'.$title.'</title>'.$desc.$robots.'</head><body>'.$body.'</body></html>';
        };

        $pages = [
            'https://aqdi.sa' => $html(
                'Home',
                '<h1>Aqdi</h1><img src="/logo.png" alt="logo"><a href="/about-us">about</a><a href="/commercial">c</a><a href="/residential">r</a><a href="/noindex">n</a><a href="/missing">broken</a>'
            ),
            'https://aqdi.sa/about-us' => $html('About', '<h1>About</h1><a href="/">home</a>'),
            'https://aqdi.sa/commercial' => $html('Units', '<h1>Commercial</h1><a href="/">home</a><a href="/about-us">about</a>', null),
            'https://aqdi.sa/residential' => $html(
                'Units',
                '<a href="/">home</a><img src="/a.png"><img src="/b.png"><img src="/c.png"><img src="/d.png">',
                'residential desc'
            ),
            'https://aqdi.sa/noindex' => $html('Hidden', '<h1>Hidden</h1><a href="/">home</a>', 'hidden', true),
        ];

        Http::preventStrayRequests();
        Http::fake(function (\Illuminate\Http\Client\Request $request) use ($pages, $headers, $html) {
            $url = rtrim($request->url(), '/');
            if (str_ends_with($url, '/sitemap.xml')) {
                return Http::response(
                    '<?xml version="1.0"?><urlset><loc>https://aqdi.sa/</loc><loc>https://aqdi.sa/about-us</loc></urlset>',
                    200,
                    ['Content-Type' => 'application/xml']
                );
            }
            if ($url === 'https://aqdi.sa/missing') {
                return Http::response($html('404', 'missing'), 404, $headers);
            }
            if (isset($pages[$url])) {
                return Http::response($pages[$url], 200, $headers);
            }

            return Http::response($html('404', 'missing'), 404, $headers);
        });
    }

    private function actingEmployee(): Employee
    {
        $role = Role::query()->create([
            'name' => 'seo',
            'title_ar' => 'سيو',
            'is_active' => true,
        ]);
        $view = Permission::query()->create([
            'name' => 'seo_crawl.view',
            'section' => 'seo_crawl',
            'action' => 'view',
            'is_active' => true,
        ]);
        $create = Permission::query()->create([
            'name' => 'seo_crawl.create',
            'section' => 'seo_crawl',
            'action' => 'create',
            'is_active' => true,
        ]);
        DB::table('role_permissions')->insert([
            ['role_id' => $role->id, 'permission_id' => $view->id, 'created_at' => now(), 'updated_at' => now()],
            ['role_id' => $role->id, 'permission_id' => $create->id, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $employee = Employee::query()->create([
            'name' => 'SEO Admin',
            'email' => 'seo-admin@aqdi.test',
            'password' => Hash::make('password'),
            'is_active' => true,
            'role_id' => $role->id,
        ]);

        Sanctum::actingAs($employee);

        return $employee;
    }

    private function createMinimalSchema(): void
    {
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
        Schema::create('seo_crawl_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('base_url', 512);
            $table->string('status', 32)->default('queued');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('indexed_pages')->default(0);
            $table->unsignedInteger('healthy_pages')->default(0);
            $table->unsignedInteger('broken_pages')->default(0);
            $table->unsignedInteger('on_page_issues')->default(0);
            $table->unsignedInteger('pages_crawled')->default(0);
            $table->unsignedInteger('pages_failed')->default(0);
            $table->json('category_counts')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
        Schema::create('seo_crawl_pages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('seo_crawl_run_id');
            $table->string('url_hash', 64);
            $table->text('url');
            $table->text('path');
            $table->unsignedSmallInteger('status_code')->default(0);
            $table->unsignedInteger('load_time_ms')->default(0);
            $table->string('content_type', 191)->nullable();
            $table->text('title')->nullable();
            $table->text('meta_description')->nullable();
            $table->unsignedSmallInteger('h1_count')->default(0);
            $table->unsignedSmallInteger('image_count')->default(0);
            $table->unsignedSmallInteger('images_missing_alt')->default(0);
            $table->unsignedSmallInteger('outbound_internal_links')->default(0);
            $table->unsignedSmallInteger('inbound_internal_links')->default(0);
            $table->boolean('is_html')->default(false);
            $table->boolean('is_indexable')->default(true);
            $table->boolean('is_healthy')->default(false);
            $table->timestamps();
            $table->unique(['seo_crawl_run_id', 'url_hash']);
        });
        Schema::create('seo_crawl_issues', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('seo_crawl_run_id');
            $table->unsignedBigInteger('seo_crawl_page_id')->nullable();
            $table->text('path');
            $table->string('type', 64);
            $table->string('severity', 16);
            $table->text('message_ar');
            $table->text('message_en');
            $table->json('details')->nullable();
            $table->timestamps();
        });
    }
}
