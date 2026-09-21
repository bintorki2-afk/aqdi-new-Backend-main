<?php

namespace Tests\Feature\Admin;

use App\Models\Employee;
use App\Models\Role;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserClientsIntegrationsTest extends TestCase
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
        DB::reconnect('sqlite');
        URL::forceRootUrl('http://localhost');

        $this->createMinimalSchema();
    }

    protected function tearDown(): void
    {
        foreach ([
            'custom_discounts',
            'coupon_usages',
            'coupons',
            'payments',
            'refundable_contracts',
            'real_units',
            'real_estates',
            'contracts',
            'personal_access_tokens',
            'employees',
            'roles',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
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

        Schema::create('employees', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('role_id')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->string('role')->nullable();
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

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('fname')->nullable();
            $table->string('lname')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('platform')->nullable();
            $table->string('password')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('contracts', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->boolean('is_draft')->default(false);
            $table->boolean('is_delete')->default(false);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->string('contract_uuid')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->nullable();
            $table->timestamps();
        });

        Schema::create('refundable_contracts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->decimal('refund_amount', 10, 2)->default(0);
            $table->boolean('is_refunded')->default(false);
            $table->timestamps();
        });

        Schema::create('real_estates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('name_real_estate')->nullable();
            $table->string('image_instrument')->nullable();
            $table->integer('is_deleted')->default(0);
            $table->timestamps();
        });

        Schema::create('real_units', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->unsignedBigInteger('real_estates_units_id')->nullable();
            $table->string('unit_number')->nullable();
            $table->integer('is_deleted')->default(0);
            $table->timestamps();
        });
    }

    public function test_export_returns_csv_file(): void
    {
        $this->actingAdmin();

        DB::table('users')->insert([
            'fname' => 'أحمد',
            'lname' => 'علي',
            'email' => 'ahmed@test.local',
            'mobile' => '0500000000',
            'is_active' => 1,
            'platform' => 'website',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->get('/api/admin/users/export');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('clients-', (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString('أحمد', $response->streamedContent());
    }

    public function test_properties_return_404_for_missing_user(): void
    {
        $this->actingAdmin();

        $this->getJson('/api/admin/users/999/properties')
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_discount_returns_404_for_missing_user(): void
    {
        $this->actingAdmin();

        $this->postJson('/api/admin/users/999/discount', [
            'contract_id' => 1,
            'type' => 'waiver',
            'reason' => 'إعفاء تجريبي',
        ])->assertNotFound();
    }

    public function test_discount_requires_contract_and_reason(): void
    {
        $this->actingAdmin();

        $userId = DB::table('users')->insertGetId([
            'fname' => 'سارة',
            'lname' => 'محمد',
            'email' => 'sara@test.local',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson('/api/admin/users/'.$userId.'/discount', [
            'type' => 'percentage',
        ])->assertStatus(422);
    }

    private function actingAdmin(): Employee
    {
        $role = Role::query()->create([
            'name' => 'admin',
            'title_ar' => 'مدير النظام',
            'title_en' => 'System Admin',
            'is_active' => true,
        ]);

        $employee = Employee::query()->create([
            'name' => 'Admin',
            'email' => 'admin@aqdi.test',
            'password' => Hash::make('password'),
            'is_active' => true,
            'role_id' => $role->id,
            'role' => $role->name,
        ]);

        Sanctum::actingAs($employee);

        return $employee;
    }
}
