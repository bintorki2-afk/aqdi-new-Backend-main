<?php

namespace App\Modules\Auth\Actions;

use App\Models\User;
use App\Modules\Auth\Services\UserOtpService;
use App\Modules\Auth\Support\AuthMobile;
use Illuminate\Http\Request;

class VerifyUserAction
{
    public function __construct(private readonly UserOtpService $otp) {}

    /**
     * @return array{ok: true}|array{ok: false, message: string, code?: int}
     */
    public function execute(Request $request): array
    {
        $user = User::whereIn('mobile', AuthMobile::lookupVariants($request->mobile))->first();

        if (! $user) {
            return ['ok' => false, 'message' => trans('api.otp_expired'), 'code' => 400];
        }

        $outcome = $this->otp->verify(
            $user,
            UserOtpService::VERIFICATION,
            (string) $request->verification_code,
            consume: true
        );

        if (! $outcome['ok']) {
            return $outcome;
        }

        $user->refresh();
        $user->email_verified_at = now();
        $user->save();

        return ['ok' => true];
    }
}
