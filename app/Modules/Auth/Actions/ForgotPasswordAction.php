<?php

namespace App\Modules\Auth\Actions;

use App\Models\User;
use App\Modules\Auth\Services\UserOtpService;
use App\Modules\Auth\Support\AuthMobile;
use Illuminate\Http\Request;

class ForgotPasswordAction
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

        $otpType = 'forgot_password';

        $blocked = $this->otp->assertCanSend($user, UserOtpService::RESET, $formattedMobile);
        if ($blocked !== null) {
            $this->sms->logBlockedResend($user->id, $formattedMobile, $otpType);

            return $blocked;
        }

        $plain = $this->otp->issue($user, UserOtpService::RESET);
        $smsResult = $this->sms->sendOtp(
            $this->otp->smsBody(UserOtpService::RESET, $plain),
            $user->mobile,
            $otpType,
            $user->id
        );

        if ($smsResult === true) {
            return ['ok' => true];
        }

        return ['ok' => false, 'message' => $smsResult ?: trans('api.send_reset_password_code_failed')];
    }
}
