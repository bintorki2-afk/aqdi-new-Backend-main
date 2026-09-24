<?php

namespace App\Modules\Auth\Actions;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Modules\Auth\Services\UserOtpService;
use App\Modules\Auth\Support\AuthMobile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LoginUserAction
{
    public function __construct(
        private readonly SendUserAuthSmsAction $sms,
        private readonly AppendLoginCouponNotificationAction $loginCoupon,
        private readonly UserOtpService $otp,
    ) {}

    /**
     * @return array{ok: true, result: array<string, mixed>}|array{ok: false, message: string, code?: int}|array{ok: true, unverified: true, result: array<string, mixed>}
     */
    public function execute(Request $request): array
    {
        $formattedMobile = AuthMobile::normalizeSaudiMobile($request->mobile);

        $user = User::whereIn('mobile', AuthMobile::lookupVariants($request->mobile))->first();

        // Verify the password BEFORE revealing anything about the account or
        // sending an OTP. Otherwise this endpoint leaks whether a number exists,
        // returns the account's details, and lets anyone trigger OTP texts.
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return ['ok' => false, 'message' => trans('api.credentials_error')];
        }

        if (! $user->isVerified()) {
            $otpType = 'login_account_verification';

            $blocked = $this->otp->assertCanSend($user, UserOtpService::VERIFICATION, $formattedMobile);
            if ($blocked !== null) {
                $this->sms->logBlockedResend($user->id, $formattedMobile, $otpType);

                return $blocked;
            }

            $plain = $this->otp->issue($user, UserOtpService::VERIFICATION);
            $this->sms->sendOtp(
                $this->otp->smsBody(UserOtpService::VERIFICATION, $plain),
                $user->mobile,
                $otpType,
                $user->id
            );

            return [
                'ok' => true,
                'unverified' => true,
                'result' => [
                    'user' => new UserResource($user->fresh()),
                ],
            ];
        }

        if (! $user->isActive()) {
            return ['ok' => false, 'message' => trans('api.block_account')];
        }

        // Password already verified above.
        if ($request->has('fcm_token')) {
            $user->fcm_token = $request->fcm_token;
            $user->save();
        }

        $user->refresh();
        $result = [
            'user' => new UserResource($user),
            'token' => $user->createToken('user_token')->plainTextToken,
        ];
        $this->loginCoupon->execute($result, $user);

        return ['ok' => true, 'result' => $result];
    }
}
