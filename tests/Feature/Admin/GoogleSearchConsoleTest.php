<?php

namespace Tests\Feature\Admin;

use App\Models\Employee;
use App\Models\GoogleSeoConnection;
use App\Models\Permission;
use App\Models\Role;
use App\Services\Seo\GoogleSeoOAuthService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GoogleSearchConsoleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'app.url' => 'http://localhost',
            'services.google.client_id' => 'test-google-client',
            'services.google.client_secret' => 'test-google-secret',
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');
        URL::forceRootUrl('http://localhost');

        $this->createMinimalSchema();
    }

    protected function tearDown(): void
    {
        foreach ([
            'google_seo_connections',
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

    public function test_overview_requires_a_connected_google_account(): void
    {
        $this->actingEmployee();

        $this->getJson('/api/admin/seo-google/search-console')
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_overview_returns_search_console_totals(): void
    {
        $this->actingEmployee();
        $this->connectGoogle('https://aqdi.sa/');
        $this->fakeSearchConsole();

        $this->getJson('/api/admin/seo-google/search-console?from=2026-08-01&to=2026-08-28')
            ->assertOk()
            ->assertJsonPath('data.site_url', 'https://aqdi.sa/')
            ->assertJsonPath('data.from', '2026-08-01')
            ->assertJsonPath('data.to', '2026-08-28')
            ->assertJsonPath('data.clicks', 10)
            ->assertJsonPath('data.impressions', 200)
            ->assertJsonPath('data.ctr', 0.05)
            ->assertJsonPath('data.position', 8.2);
    }

    public function test_queries_return_dimension_rows(): void
    {
        $this->actingEmployee();
        $this->connectGoogle('https://aqdi.sa/');
        $this->fakeSearchConsole();

        $this->getJson('/api/admin/seo-google/search-console/queries?from=2026-08-01&to=2026-08-28&limit=10')
            ->assertOk()
            ->assertJsonPath('data.dimension', 'query')
            ->assertJsonPath('data.items.0.query', 'عقد ايجار')
            ->assertJsonPath('data.items.0.clicks', 4);
    }

    public function test_pages_countries_devices_and_dates_are_available(): void
    {
        $this->actingEmployee();
        $this->connectGoogle('https://aqdi.sa/');
        $this->fakeSearchConsole();

        $this->getJson('/api/admin/seo-google/search-console/pages?from=2026-08-01&to=2026-08-28')
            ->assertOk()
            ->assertJsonPath('data.items.0.page', 'https://aqdi.sa/pricing');

        $this->getJson('/api/admin/seo-google/search-console/countries?from=2026-08-01&to=2026-08-28')
            ->assertOk()
            ->assertJsonPath('data.items.0.country', 'sau');

        $this->getJson('/api/admin/seo-google/search-console/devices?from=2026-08-01&to=2026-08-28')
            ->assertOk()
            ->assertJsonPath('data.items.0.device', 'MOBILE');

        $this->getJson('/api/admin/seo-google/search-console/dates?from=2026-08-01&to=2026-08-28')
            ->assertOk()
            ->assertJsonPath('data.items.0.date', '2026-08-01');
    }

    public function test_sites_lists_and_can_select_a_property(): void
    {
        $this->actingEmployee();
        $this->connectGoogle(null);
        $this->fakeSearchConsole();

        $this->getJson('/api/admin/seo-google/search-console/sites')
            ->assertOk()
            ->assertJsonPath('data.items.0.site_url', 'https://aqdi.sa/')
            ->assertJsonPath('data.selected_site_url', 'https://aqdi.sa/');

        $this->postJson('/api/admin/seo-google/search-console/sites', [
            'site_url' => 'https://aqdi.sa/',
        ])
            ->assertOk()
            ->assertJsonPath('data.site_url', 'https://aqdi.sa/');

        $this->assertSame(
            'https://aqdi.sa/',
            GoogleSeoConnection::query()->value('search_console_site_url')
        );
    }

    public function test_selecting_an_unknown_site_is_rejected(): void
    {
        $this->actingEmployee();
        $this->connectGoogle('https://aqdi.sa/');
        $this->fakeSearchConsole();

        $this->postJson('/api/admin/seo-google/search-console/sites', [
            'site_url' => 'https://not-aqdi.example/',
        ])->assertStatus(422);
    }

    public function test_sitemaps_return_submitted_feeds(): void
    {
        $this->actingEmployee();
        $this->connectGoogle('https://aqdi.sa/');
        $this->fakeSearchConsole();

        $this->getJson('/api/admin/seo-google/search-console/sitemaps')
            ->assertOk()
            ->assertJsonPath('data.items.0.path', 'https://aqdi.sa/sitemap.xml')
            ->assertJsonPath('data.items.0.errors', 0);
    }

    public function test_expired_access_token_is_refreshed_before_the_console_call(): void
    {
        $this->actingEmployee();
        $this->connectGoogle('https://aqdi.sa/', expired: true);
        $this->fakeSearchConsole(refresh: true);

        $this->getJson('/api/admin/seo-google/search-console?from=2026-08-01&to=2026-08-28')
            ->assertOk()
            ->assertJsonPath('data.clicks', 10);

        $connection = GoogleSeoConnection::query()->first();
        $this->assertSame('ya29.refreshed', $connection->access_token);
        $this->assertTrue($connection->token_expires_at->isFuture());
    }

    private function fakeSearchConsole(bool $refresh = false): void
    {
        Http::fake(function (\Illuminate\Http\Client\Request $request) use ($refresh) {
            if (str_contains($request->url(), 'oauth2.googleapis.com/token')) {
                $this->assertTrue($refresh);

                return Http::response([
                    'access_token' => 'ya29.refreshed',
                    'expires_in' => 3600,
                ]);
            }

            $url = $request->url();

            if (str_contains($url, '/sitemaps')) {
                return Http::response([
                    'sitemap' => [[
                        'path' => 'https://aqdi.sa/sitemap.xml',
                        'lastSubmitted' => '2026-08-01T00:00:00.000Z',
                        'type' => 'sitemap',
                        'warnings' => 0,
                        'errors' => 0,
                    ]],
                ]);
            }

            if (str_contains($url, 'searchAnalytics/query')) {
                $dimensions = $request['dimensions'] ?? [];
                if ($dimensions === []) {
                    return Http::response([
                        'rows' => [[
                            'clicks' => 10,
                            'impressions' => 200,
                            'ctr' => 0.05,
                            'position' => 8.2,
                        ]],
                    ]);
                }

                $key = match ($dimensions[0] ?? '') {
                    'query' => 'عقد ايجار',
                    'page' => 'https://aqdi.sa/pricing',
                    'country' => 'sau',
                    'device' => 'MOBILE',
                    'date' => '2026-08-01',
                    default => 'unknown',
                };

                return Http::response([
                    'rows' => [[
                        'keys' => [$key],
                        'clicks' => 4,
                        'impressions' => 80,
                        'ctr' => 0.05,
                        'position' => 7.1,
                    ]],
                ]);
            }

            if (str_contains($url, '/sites') && $request->method() === 'GET') {
                return Http::response([
                    'siteEntry' => [[
                        'siteUrl' => 'https://aqdi.sa/',
                        'permissionLevel' => 'siteFullUser',
                    ]],
                ]);
            }

            return Http::response(['error' => 'unmocked '.$url], 500);
        });
    }

    private function connectGoogle(?string $siteUrl, bool $expired = false): GoogleSeoConnection
    {
        return GoogleSeoConnection::query()->create([
            'provider' => 'google',
            'google_email' => 'seo.owner@gmail.com',
            'access_token' => 'ya29.access',
            'refresh_token' => '1//refresh',
            'token_expires_at' => $expired ? now()->subHour() : now()->addHour(),
            'scopes' => GoogleSeoOAuthService::SCOPES,
            'search_console_site_url' => $siteUrl,
        ]);
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
            'email' => 'seo-console@aqdi.test',
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
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('section')->nullable();
            $table->string('action')->nullable();
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
        Schema::create('google_seo_connections', function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 32)->default('google');
            $table->string('google_email')->nullable();
            $table->string('google_user_id')->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->json('scopes')->nullable();
            $table->string('search_console_site_url', 512)->nullable();
            $table->string('analytics_property_id', 64)->nullable();
            $table->unsignedBigInteger('connected_by_employee_id')->nullable();
            $table->timestamps();
            $table->unique('provider');
        });
    }
}
