<?php

namespace Tests\Feature\Api;

use App\Models\Contract;
use App\Models\Coupon;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CouponApplySecurityTest extends TestCase
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
            'coupon_usages',
            'coupons',
            'contracts',
            'personal_access_tokens',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_unreviewed_coupon_is_rejected(): void
    {
        [$user, $contract] = $this->seedUserAndContract();

        Coupon::query()->create([
            'name' => 'Hidden',
            'code_coupon' => 'HIDDEN10',
            'type_coupon' => 'ratio',
            'value_coupon' => 10,
            'date_start' => now()->subDay()->toDateString(),
            'date_end' => now()->addDay()->toDateString(),
            'usage' => 5,
            'usage_of_user' => 1,
            'is_review' => false,
            'is_delete' => false,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v2/Coupon/'.$contract->uuid, [
            'code_coupon' => 'HIDDEN10',
        ])
            ->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'الكود غير صحيح');
    }

    /**
     * @return array{0: User, 1: Contract}
     */
    private function seedUserAndContract(): array
    {
        $userId = DB::table('users')->insertGetId([
            'fname' => 'عميل',
            'email' => 'client@example.com',
            'mobile' => '00966500000000',
            'password' => Hash::make('password'),
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $contractId = DB::table('contracts')->insertGetId([
            'uuid' => '654321',
            'user_id' => $userId,
            'contract_type' => 'housing',
            'is_completed' => 0,
            'is_delete' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            User::query()->findOrFail($userId),
            Contract::query()->findOrFail($contractId),
        ];
    }

    private function createMinimalSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('fname')->nullable();
            $table->string('lname')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile')->nullable();
            $table->string('password')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('contracts', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
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
