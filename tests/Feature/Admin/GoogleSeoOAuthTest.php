<?php

namespace Tests\Feature\Admin;

use App\Models\Employee;
use App\Models\GoogleSeoConnection;
use App\Models\Permission;
use App\Models\Role;
use App\Services\Seo\GoogleSeoOAuthService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class GoogleSeoOAuthTest extends TestCase
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
            'services.google.seo_redirect' => 'http://localhost/api/admin/seo-google/callback',
            'services.google.seo_frontend_redirect' => 'http://admin.test',
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');
        URL::forceRootUrl('http://localhost');

        $this->createMinimalSchema();
    }

    protected function tearDown(): void
    {
        Mockery::close();
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

    public function test_status_is_disconnected_by_default(): void
    {
        $this->actingEmployee();

        $this->getJson('/api/admin/seo-google/status')
            ->assertOk()
            ->assertJsonPath('data.connected', false)
            ->assertJsonPath('data.google_email', null);
    }

    public function test_connect_returns_google_auth_url(): void
    {
        $this->actingEmployee();

        $url = $this->postJson('/api/admin/seo-google/connect')
            ->assertOk()
            ->json('data.auth_url');

        $this->assertIsString($url);
        $this->assertStringContainsString('accounts.google.com', $url);
        $this->assertStringContainsString('test-google-client', $url);
        $this->assertStringContainsString('webmasters.readonly', $url);
        $this->assertStringContainsString('analytics.readonly', $url);
    }

    public function test_callback_saves_google_account_and_redirects_to_admin(): void
    {
        $employee = $this->actingEmployee();
        Cache::put('google-seo-oauth:state-1', $employee->id, 600);
        $this->fakeGoogleUser();

        $response = $this->get('/api/admin/seo-google/callback?code=ok&state=state-1');
        $response->assertRedirect();
        $this->assertStringContainsString('google_seo=connected', (string) $response->headers->get('Location'));

        $connection = GoogleSeoConnection::query()->first();
        $this->assertNotNull($connection);
        $this->assertSame('seo.owner@gmail.com', $connection->google_email);
        $this->assertSame($employee->id, $connection->connected_by_employee_id);
        $this->assertTrue($connection->isConnected());

        $this->getJson('/api/admin/seo-google/status')
            ->assertOk()
            ->assertJsonPath('data.connected', true)
            ->assertJsonPath('data.google_email', 'seo.owner@gmail.com')
            ->assertJsonPath('data.search_console', true)
            ->assertJsonPath('data.analytics', true);
    }

    public function test_disconnect_removes_connection(): void
    {
        $employee = $this->actingEmployee();
        GoogleSeoConnection::query()->create([
            'provider' => 'google',
            'google_email' => 'seo.owner@gmail.com',
            'access_token' => 'access',
            'refresh_token' => 'refresh',
            'connected_by_employee_id' => $employee->id,
            'scopes' => GoogleSeoOAuthService::SCOPES,
        ]);

        $this->postJson('/api/admin/seo-google/disconnect')
            ->assertOk()
            ->assertJsonPath('data.connected', false);

        $this->assertSame(0, GoogleSeoConnection::query()->count());
    }

    private function fakeGoogleUser(): void
    {
        $googleUser = new SocialiteUser;
        $googleUser->id = 'google-user-1';
        $googleUser->email = 'seo.owner@gmail.com';
        $googleUser->token = 'ya29.access';
        $googleUser->refreshToken = '1//refresh';
        $googleUser->expiresIn = 3600;
        $googleUser->approvedScopes = GoogleSeoOAuthService::SCOPES;

        $this->mock(GoogleProvider::class, function (MockInterface $mock) use ($googleUser): void {
            $mock->shouldReceive('stateless')->andReturnSelf();
            $mock->shouldReceive('redirectUrl')->andReturnSelf();
            $mock->shouldReceive('user')->andReturn($googleUser);
        });

        Socialite::shouldReceive('driver')->with('google')->andReturn(app(GoogleProvider::class));
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
