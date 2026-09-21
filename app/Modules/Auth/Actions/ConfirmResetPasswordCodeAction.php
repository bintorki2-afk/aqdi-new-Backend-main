<?php

namespace App\Modules\Auth\Actions;

use App\Models\User;
use App\Modules\Auth\Services\UserOtpService;
use App\Modules\Auth\Support\AuthMobile;
use Illuminate\Http\Request;

class ConfirmResetPasswordCodeAction
{
    public function __construct(private readonly UserOtpService $otp) {}

    /**
     * @return array{ok: true}|array{ok: false, message: string, code?: int}
     */
    public function execute(Request $request): array
    {
        $user = User::whereIn('mobile', AuthMobile::lookupVariants($request->mobile))->first();

        if (! $user) {
            return ['ok' => false, 'message' => trans('api.wrong_code_to_reset_password'), 'code' => 400];
        }

        return $this->otp->verify(
            $user,
            UserOtpService::RESET,
            (string) $request->code,
            consume: false
        );
    }
}
