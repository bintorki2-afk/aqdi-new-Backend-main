<?php

namespace Tests\Feature\Admin;

use App\Models\Contract;
use App\Models\Coupon;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Models\UserCoupon;
use App\Services\Admin\UserCouponService;
use App\Services\CouponDiscountResolver;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserCouponTest extends TestCase
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
            'user_coupons',
            'coupon_usages',
            'coupons',
            'personal_access_tokens',
            'employees',
            'roles',
            'contracts',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_create_assigns_secret_code_and_login_notification(): void
    {
        $userId = DB::table('users')->insertGetId([
            'fname' => 'عميل',
            'lname' => 'تجريبي',
            'email' => 'client@example.com',
            'mobile' => '00966500000000',
            'password' => Hash::make('password'),
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user = User::query()->find($userId);

        $created = app(UserCouponService::class)->create($user, [
            'type' => UserCoupon::TYPE_PERCENTAGE,
            'value' => 10,
            'applies_to' => UserCoupon::APPLIES_ALL,
            'reason' => 'عميل مميز',
            'notify_on_login' => true,
            'notification_message' => 'تهانينا! حصلت على خصم خاص على رسوم السنة الأولى.',
        ]);

        $this->assertNotNull($created->coupon?->code_coupon);
        $this->assertTrue(str_starts_with((string) $created->coupon->code_coupon, 'AQ'));
        $this->assertSame('ratio', $created->coupon->type_coupon);
        $this->assertTrue($created->notify_on_login);

        $payload = app(UserCouponService::class)->loginNotificationPayload($user);
        $this->assertSame('custom_coupon', $payload['type']);
        $this->assertSame($created->id, $payload['user_coupon_id']);
        $this->assertSame('تهانينا! حصلت على خصم خاص على رسوم السنة الأولى.', $payload['message']);
        $this->assertSame($created->coupon->code_coupon, $payload['code_coupon']);
    }

    public function test_user_coupon_cannot_be_applied_by_another_user(): void
    {
        $ownerId = DB::table('users')->insertGetId([
            'fname' => 'مالك',
            'password' => Hash::make('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $otherId = DB::table('users')->insertGetId([
            'fname' => 'آخر',
            'password' => Hash::make('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $owner = User::query()->find($ownerId);
        $other = User::query()->find($otherId);
        $created = app(UserCouponService::class)->create($owner, [
            'type' => UserCoupon::TYPE_FIXED,
            'value' => 50,
            'applies_to' => UserCoupon::APPLIES_HOUSING,
        ]);

        $contractId = DB::table('contracts')->insertGetId([
            'uuid' => 'c-1',
            'user_id' => $otherId,
            'contract_type' => 'housing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $contract = Contract::query()->find($contractId);

        $this->expectException(ValidationException::class);
        app(CouponDiscountResolver::class)->assertCanApply($created->coupon, $other, $contract);
    }

    public function test_admin_store_endpoint_returns_secret_code(): void
    {
        $userId = DB::table('users')->insertGetId([
            'fname' => 'عميل',
            'password' => Hash::make('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAdmin();

        $response = $this->postJson("/api/admin/users/{$userId}/coupons", [
            'type' => 'percentage',
            'value' => 15,
            'applies_to' => 'all',
            'reason' => 'تعويض',
            'notify_on_login' => true,
            'notification_message' => 'خصم خاص لك',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'percentage')
            ->assertJsonPath('data.value', 15)
            ->assertJsonPath('data.applies_on', 'first_year_fees');

        $this->assertNotEmpty($response->json('data.secret_code'));
        $this->assertTrue(Coupon::query()->where('code_coupon', $response->json('data.secret_code'))->exists());
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

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('fname')->nullable();
            $table->string('lname')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile')->nullable();
            $table->string('password')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('contracts', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->string('contract_type')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->boolean('is_delete')->default(false);
            $table->timestamps();
        });

        Schema::create('coupons', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code_coupon')->unique();
            $table->string('type_coupon');
            $table->decimal('value_coupon', 10, 2);
            $table->date('date_start');
            $table->date('date_end');
            $table->integer('usage')->default(1);
            $table->integer('usage_of_user')->default(1);
            $table->boolean('is_review')->default(true);
            $table->boolean('is_delete')->default(false);
            $table->timestamps();
        });

        Schema::create('coupon_usages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('coupon_id')->nullable();
            $table->string('contract_uuid')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('user_coupons', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('coupon_id')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->string('type', 20);
            $table->decimal('value', 10, 2)->default(0);
            $table->string('applies_to', 20)->default('all');
            $table->date('expires_at')->nullable();
            $table->text('reason')->nullable();
            $table->boolean('notify_on_login')->default(true);
            $table->text('notification_message')->nullable();
            $table->timestamp('login_notified_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->boolean('is_active')->default(true);
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
