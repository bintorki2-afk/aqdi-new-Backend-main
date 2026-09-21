<?php

namespace App\Modules\Users\Actions;

class DeactivateOwnAccountAction
{
    /**
     * @return array{ok: true}|array{ok: false, message: string}
     */
    public function execute(mixed $user): array
    {
        if (! $user) {
            return ['ok' => false, 'message' => trans('api.profile_not_exist')];
        }

        $user->is_active = false;

        if ($user->save()) {
            $token = $user->currentAccessToken();
            if ($token && isset($token->id)) {
                $user->tokens()->where('id', $token->id)->delete();
            }

            return ['ok' => true];
        }

        return ['ok' => false, 'message' => trans('api.error_deactivating')];
    }
}
