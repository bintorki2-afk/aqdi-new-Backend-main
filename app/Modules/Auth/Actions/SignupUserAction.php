<?php

namespace App\Modules\Auth\Actions;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Modules\Auth\Services\UserOtpService;
use App\Modules\Auth\Support\AuthMobile;
use Illuminate\Http\Request;

class SignupUserAction
{
    public function __construct(
        private readonly SendUserAuthSmsAction $sms,
        private readonly UserOtpService $otp,
    ) {}

    /**
     * @return array{ok: true, user: UserResource}|array{ok: false, message: string, code?: int}
     */
    public function execute(Request $request): array
    {
        $otpType = 'signup';
        $formattedMobile = AuthMobile::normalizeSaudiMobile($request->mobile);

        $blocked = $this->otp->assertCanSend(null, UserOtpService::VERIFICATION, $formattedMobile);
        if ($blocked !== null) {
            $this->sms->logBlockedResend(null, $formattedMobile, $otpType);

            return $blocked;
        }

        $data = $request->only(['fname', 'mobile']);
        $data['mobile'] = $formattedMobile;
        if ($request->filled('email')) {
            $data['email'] = $request->email;
        }
        $data['password'] = bcrypt($request->password);

        if ($request->filled('platform')) {
            $data['platform'] = User::normalizePlatform((string) $request->input('platform'));
        }

        $user = User::create($data);
        $plain = $this->otp->issue($user, UserOtpService::VERIFICATION);

        $smsResult = $this->sms->sendOtp(
            $this->otp->smsBody(UserOtpService::VERIFICATION, $plain),
            $user->mobile,
            $otpType,
            $user->id
        );

        if ($smsResult === true) {
            return ['ok' => true, 'user' => new UserResource($user)];
        }

        return ['ok' => false, 'message' => $smsResult ?: trans('api.error_sending_sms')];
    }
}
