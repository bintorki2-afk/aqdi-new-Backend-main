<?php

namespace App\Modules\Auth\Actions;

use App\Models\User;
use App\Modules\Auth\Services\UserOtpService;
use App\Modules\Auth\Support\AuthMobile;
use Illuminate\Http\Request;

class ResendVerificationAction
{
    public function __construct(
        private readonly SendUserAuthSmsAction $sms,
        private readonly UserOtpService $otp,
    ) {}

    /**
     * @return array{ok: true}|array{ok: false, message: string, code?: int}
     */
    public function execute(Request $request): array
    {
        $formattedMobile = AuthMobile::normalizeSaudiMobile($request->mobile);

        $user = User::whereIn('mobile', AuthMobile::lookupVariants($request->mobile))->first();

        if (! $user) {
            return ['ok' => true];
        }

        if ($user->isVerified()) {
            return ['ok' => false, 'message' => trans('api.verified_account'), 'code' => 409];
        }

        $otpType = 'resend_account_verification';

        $blocked = $this->otp->assertCanSend($user, UserOtpService::VERIFICATION, $formattedMobile);
        if ($blocked !== null) {
            $this->sms->logBlockedResend($user->id, $formattedMobile, $otpType);

            return $blocked;
        }

        $plain = $this->otp->issue($user, UserOtpService::VERIFICATION);
        $smsResult = $this->sms->sendOtp(
            $this->otp->smsBody(UserOtpService::VERIFICATION, $plain),
            $user->mobile,
            $otpType,
            $user->id
        );

        if ($smsResult === true) {
            return ['ok' => true];
        }

        return ['ok' => false, 'message' => $smsResult ?: trans('api.error_sending_sms')];
    }
}
