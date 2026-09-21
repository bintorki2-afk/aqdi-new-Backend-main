<?php

namespace Tests\Feature\Users;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UpdatePasswordMassAssignmentTest extends TestCase
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
        foreach (['personal_access_tokens', 'users'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_password_update_ignores_extra_mass_assignable_fields(): void
    {
        $userId = DB::table('users')->insertGetId([
            'fname' => 'عميل',
            'email' => 'client@example.com',
            'mobile' => '00966500000000',
            'password' => Hash::make('old-password'),
            'is_active' => 0,
            'verification_code' => '1111',
            'email_verified_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user = User::query()->findOrFail($userId);

        Sanctum::actingAs($user);

        $this->postJson('/api/v2/update/password', [
            'current_password' => 'old-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
            'mobile' => '00966509999999',
            'is_active' => true,
            'email_verified_at' => now()->toDateTimeString(),
            'verification_code' => '9999',
        ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $user->refresh();

        $this->assertTrue(Hash::check('new-password', $user->getRawOriginal('password')));
        $this->assertSame('00966500000000', $user->getRawOriginal('mobile'));
        $this->assertFalse((bool) $user->is_active);
        $this->assertNull($user->email_verified_at);
        $this->assertSame('1111', $user->getRawOriginal('verification_code'));
    }

    public function test_wrong_current_password_does_not_change_password(): void
    {
        $userId = DB::table('users')->insertGetId([
            'fname' => 'عميل',
            'email' => 'client2@example.com',
            'mobile' => '00966500000001',
            'password' => Hash::make('old-password'),
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user = User::query()->findOrFail($userId);

        Sanctum::actingAs($user);

        $this->postJson('/api/v2/update/password', [
            'current_password' => 'not-the-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $user->refresh();
        $this->assertTrue(Hash::check('old-password', $user->getRawOriginal('password')));
    }

    public function test_user_fill_ignores_otp_and_verification_columns(): void
    {
        $user = new User();
        $user->fill([
            'fname' => 'علي',
            'verification_code' => '9999',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->assertSame('علي', $user->fname);
        $this->assertTrue((bool) $user->is_active);
        $this->assertNull($user->verification_code);
        $this->assertNull($user->email_verified_at);
        $this->assertArrayNotHasKey('password', $user->toArray());
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
            $table->string('fcm_token')->nullable();
            $table->string('photo')->nullable();
            $table->string('verification_code')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->softDeletes();
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
