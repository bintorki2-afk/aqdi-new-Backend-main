<?php

namespace Tests\Feature\Api;

use App\Models\RealEstate;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RealEstateStepIdorTest extends TestCase
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
        foreach (['real_estates', 'cities', 'regions', 'personal_access_tokens', 'users'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_step2_cannot_update_another_users_real_estate(): void
    {
        [$attacker, $victimProperty] = $this->seedOwnerAndForeignProperty();

        Sanctum::actingAs($attacker);

        $this->postJson('/api/realState/step2', [
            'id' => $victimProperty->id,
            'property_place_id' => 1,
            'property_city_id' => 1,
            'neighborhood' => 'hijacked',
            'street' => 'hijacked',
            'building_number' => '99',
            'postal_code' => '99999',
            'extra_figure' => 'x',
        ])
            ->assertJsonPath('success', false);

        $this->assertSame('original-street', $victimProperty->fresh()->street);
        $this->assertSame((int) $victimProperty->user_id, (int) $victimProperty->fresh()->user_id);
    }

    public function test_step3_cannot_update_another_users_real_estate(): void
    {
        [$attacker, $victimProperty] = $this->seedOwnerAndForeignProperty();

        Sanctum::actingAs($attacker);

        $this->postJson('/api/realState/step3', [
            'id' => $victimProperty->id,
            'name_owner' => 'Hijacker',
            'property_owner_id_num' => '1234567890',
            'property_owner_dob_hijri' => '01-01-1410',
            'property_owner_mobile' => '0512345678',
            'add_legal_agent_of_owner' => 0,
        ])
            ->assertJsonPath('success', false);

        $this->assertSame('Original Owner', $victimProperty->fresh()->name_owner);
        $this->assertSame((int) $victimProperty->user_id, (int) $victimProperty->fresh()->user_id);
    }

    /**
     * @return array{0: User, 1: RealEstate}
     */
    private function seedOwnerAndForeignProperty(): array
    {
        $attackerId = DB::table('users')->insertGetId([
            'fname' => 'Attacker',
            'email' => 'attacker@example.com',
            'mobile' => '00966501111111',
            'password' => Hash::make('password'),
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $victimId = DB::table('users')->insertGetId([
            'fname' => 'Victim',
            'email' => 'victim@example.com',
            'mobile' => '00966502222222',
            'password' => Hash::make('password'),
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('regions')->insert([
            'id' => 1,
            'name_ar' => 'منطقة',
            'name_en' => 'Region',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('cities')->insert([
            'id' => 1,
            'region_id' => 1,
            'name_ar' => 'مدينة',
            'name_en' => 'City',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $propertyId = DB::table('real_estates')->insertGetId([
            'user_id' => $victimId,
            'name_owner' => 'Original Owner',
            'street' => 'original-street',
            'neighborhood' => 'original',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            User::query()->findOrFail($attackerId),
            RealEstate::query()->findOrFail($propertyId),
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

        Schema::create('regions', function (Blueprint $table): void {
            $table->id();
            $table->string('name_ar')->nullable();
            $table->string('name_en')->nullable();
            $table->timestamps();
        });

        Schema::create('cities', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('region_id')->nullable();
            $table->string('name_ar')->nullable();
            $table->string('name_en')->nullable();
            $table->timestamps();
        });

        Schema::create('real_estates', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name_owner')->nullable();
            $table->string('street')->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('building_number')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('extra_figure')->nullable();
            $table->unsignedBigInteger('property_place_id')->nullable();
            $table->unsignedBigInteger('property_city_id')->nullable();
            $table->unsignedInteger('step')->nullable();
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
