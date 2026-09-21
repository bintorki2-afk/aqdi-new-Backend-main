<?php

namespace App\Modules\Auth\Actions;

use App\Models\User;
use App\Modules\Auth\Services\UserOtpService;
use App\Modules\Auth\Support\AuthMobile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ResetUserPasswordAction
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

        $outcome = $this->otp->verify(
            $user,
            UserOtpService::RESET,
            (string) $request->code,
            consume: true
        );

        if (! $outcome['ok']) {
            return $outcome;
        }

        $user->refresh();
        $user->password = Hash::make($request->password);
        $user->save();
        $user->tokens()->delete();

        return ['ok' => true];
    }
}
