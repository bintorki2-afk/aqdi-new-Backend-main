<?php

namespace Tests\Unit\Auth;

use App\Models\User;
use App\Modules\Auth\Services\UserOtpService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserOtpServiceTest extends TestCase
{
    private UserOtpService $otp;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'otp.length' => 4,
            'otp.ttl_minutes' => 10,
            'otp.max_attempts' => 3,
            'otp.lock_minutes' => 15,
            'otp.send_cooldown_seconds' => 120,
            'otp.send_max_per_hour' => 5,
            'app.key' => 'base64:'.base64_encode(str_repeat('a', 32)),
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('fname')->nullable();
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('verification_code')->nullable();
            $table->timestamp('verification_code_expires_at')->nullable();
            $table->unsignedTinyInteger('verification_attempts')->default(0);
            $table->timestamp('verification_locked_until')->nullable();
            $table->string('reset_password_code')->nullable();
            $table->timestamp('reset_password_code_expires_at')->nullable();
            $table->unsignedTinyInteger('reset_password_attempts')->default(0);
            $table->timestamp('reset_password_locked_until')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $this->otp = app(UserOtpService::class);
        RateLimiter::clear('otp-send-cooldown:00966550000000');
        RateLimiter::clear('otp-send-hourly:00966550000000');
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_issue_stores_hmac_hash_not_plaintext(): void
    {
        $user = $this->makeUser();
        $plain = $this->otp->issue($user, UserOtpService::VERIFICATION);

        $user->refresh();

        $this->assertSame(4, strlen($plain));
        $this->assertNotSame($plain, $user->verification_code);
        $this->assertSame($this->otp->hash($plain), $user->verification_code);
        $this->assertNotNull($user->verification_code_expires_at);
        $this->assertTrue($user->verification_code_expires_at->greaterThan(now()->addMinutes(9)));
        $this->assertSame(0, (int) $user->verification_attempts);
    }

    public function test_verify_consumes_code_on_success(): void
    {
        $user = $this->makeUser();
        $plain = $this->otp->issue($user, UserOtpService::VERIFICATION);

        $outcome = $this->otp->verify($user, UserOtpService::VERIFICATION, $plain, consume: true);

        $this->assertTrue($outcome['ok']);
        $user->refresh();
        $this->assertNull($user->verification_code);
        $this->assertNull($user->verification_code_expires_at);
    }

    public function test_confirm_does_not_consume_reset_code(): void
    {
        $user = $this->makeUser();
        $plain = $this->otp->issue($user, UserOtpService::RESET);

        $outcome = $this->otp->verify($user, UserOtpService::RESET, $plain, consume: false);

        $this->assertTrue($outcome['ok']);
        $user->refresh();
        $this->assertSame($this->otp->hash($plain), $user->reset_password_code);
    }

    public function test_wrong_code_increments_attempts_then_locks(): void
    {
        $user = $this->makeUser();
        $this->otp->issue($user, UserOtpService::VERIFICATION);

        $first = $this->otp->verify($user, UserOtpService::VERIFICATION, '0000');
        $this->assertFalse($first['ok']);
        $this->assertSame(400, $first['code']);
        $this->assertSame(1, (int) $user->fresh()->verification_attempts);

        $second = $this->otp->verify($user->fresh(), UserOtpService::VERIFICATION, '0001');
        $this->assertSame(400, $second['code']);

        $third = $this->otp->verify($user->fresh(), UserOtpService::VERIFICATION, '0002');
        $this->assertFalse($third['ok']);
        $this->assertSame(429, $third['code']);

        $locked = $user->fresh();
        $this->assertNotNull($locked->verification_locked_until);
        $this->assertNull($locked->verification_code);
    }

    public function test_expired_code_is_rejected(): void
    {
        $user = $this->makeUser();
        $plain = $this->otp->issue($user, UserOtpService::VERIFICATION);
        $user->forceFill(['verification_code_expires_at' => now()->subMinute()])->save();

        $outcome = $this->otp->verify($user->fresh(), UserOtpService::VERIFICATION, $plain);

        $this->assertFalse($outcome['ok']);
        $this->assertSame(trans('api.otp_expired'), $outcome['message']);
        $this->assertSame(0, (int) $user->fresh()->verification_attempts);
    }

    public function test_send_is_rate_limited_per_mobile(): void
    {
        $user = $this->makeUser();
        $this->otp->issue($user, UserOtpService::VERIFICATION);

        $blocked = $this->otp->assertCanSend($user->fresh(), UserOtpService::VERIFICATION, $user->mobile);

        $this->assertNotNull($blocked);
        $this->assertSame(429, $blocked['code']);
    }

    public function test_locked_user_cannot_request_a_new_code(): void
    {
        $user = $this->makeUser();
        $user->forceFill([
            'verification_locked_until' => now()->addMinutes(10),
        ])->save();

        $blocked = $this->otp->assertCanSend($user->fresh(), UserOtpService::VERIFICATION, $user->mobile);

        $this->assertNotNull($blocked);
        $this->assertSame(429, $blocked['code']);
    }

    public function test_reset_password_action_consumes_otp_and_revokes_tokens(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        $user = $this->makeUser();
        $user->password = Hash::make('old-password');
        $user->save();
        $user->createToken('user_token');
        $plain = $this->otp->issue($user, UserOtpService::RESET);

        $request = \Illuminate\Http\Request::create('/api/auth/reset-password', 'POST', [
            'mobile' => $user->mobile,
            'code' => $plain,
            'password' => 'new-password-1',
        ]);

        $outcome = app(\App\Modules\Auth\Actions\ResetUserPasswordAction::class)->execute($request);

        $this->assertTrue($outcome['ok']);
        $this->assertTrue(Hash::check('new-password-1', $user->fresh()->password));
        $this->assertNull($user->fresh()->reset_password_code);
        $this->assertSame(0, $user->fresh()->tokens()->count());

        Schema::dropIfExists('personal_access_tokens');
    }

    private function makeUser(): User
    {
        return User::query()->create([
            'fname' => 'Test',
            'mobile' => '00966550000000',
            'password' => Hash::make('password'),
        ]);
    }
}
