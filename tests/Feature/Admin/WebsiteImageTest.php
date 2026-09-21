<?php

namespace Tests\Feature\Admin;

use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\WebsiteImage;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WebsiteImageTest extends TestCase
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
        $this->actingEmployee();
    }

    protected function tearDown(): void
    {
        foreach ([
            'website_images',
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

    public function test_list_update_and_helpers(): void
    {
        WebsiteImage::query()->create([
            'key' => 'logo',
            'label_ar' => 'شعار الموقع',
            'static_path' => 'website/asset/images/logo.svg',
            'alt_ar' => 'شعار أقدي',
            'meta_title_ar' => 'أقدي',
            'meta_description_ar' => 'وصف الشعار',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $list = $this->getJson('/api/admin/website-images')
            ->assertOk()
            ->json('data');

        $this->assertSame(1, $list['summary']['total']);
        $this->assertSame('logo', $list['items'][0]['key']);
        $this->assertSame('شعار أقدي', $list['items'][0]['alt_ar']);

        $id = $list['items'][0]['id'];
        $this->putJson('/api/admin/website-images/'.$id, [
            'alt_ar' => 'شعار محدث',
            'meta_title_ar' => 'عنوان محدث',
            'meta_description_ar' => 'وصف محدث',
        ])->assertOk()
            ->assertJsonPath('data.alt_ar', 'شعار محدث')
            ->assertJsonPath('data.meta_title_ar', 'عنوان محدث');

        $this->assertSame('شعار محدث', website_image_alt('logo'));
        $this->assertSame('عنوان محدث', website_image_title('logo'));
        $this->assertStringContainsString('logo.svg', (string) website_image_url('logo'));
    }

    public function test_sync_defaults_seeds_catalog(): void
    {
        $body = $this->postJson('/api/admin/website-images/sync-defaults')
            ->assertOk()
            ->json('data');

        $this->assertGreaterThan(5, $body['created']);
        $this->assertTrue(collect($body['items'])->contains(fn ($row) => $row['key'] === 'login-hero'));
    }

    private function actingEmployee(): void
    {
        $role = Role::query()->create([
            'name' => 'seo-editor',
            'title_ar' => 'محرر SEO',
            'is_active' => true,
        ]);

        foreach (['view', 'create', 'edit', 'delete'] as $action) {
            $permission = Permission::query()->create([
                'name' => 'website_images.'.$action,
                'section' => 'website_images',
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
            'name' => 'SEO Admin',
            'email' => 'seo-images@aqdi.test',
            'password' => Hash::make('password'),
            'is_active' => true,
            'role_id' => $role->id,
        ]));
    }

    private function createSchema(): void
    {
        Schema::create('website_images', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 64)->unique();
            $table->string('label_ar');
            $table->string('label_en')->nullable();
            $table->string('path', 500)->nullable();
            $table->string('static_path', 500)->nullable();
            $table->string('alt_ar', 255)->nullable();
            $table->string('alt_en', 255)->nullable();
            $table->string('meta_title_ar', 255)->nullable();
            $table->string('meta_title_en', 255)->nullable();
            $table->text('meta_description_ar')->nullable();
            $table->text('meta_description_en')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
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
