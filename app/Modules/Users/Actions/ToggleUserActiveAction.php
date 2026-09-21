<?php

namespace App\Modules\Users\Actions;

use App\Modules\Users\Models\User;

class ToggleUserActiveAction
{
    /**
     * @return array{user: User, was_active: bool}|null
     */
    public function execute(int|string $id): ?array
    {
        $user = User::find($id);

        if (! $user) {
            return null;
        }

        $wasActive = (int) $user->is_active === 1;
        $user->update(['is_active' => $wasActive ? 0 : 1]);
        $user->refresh();

        return ['user' => $user, 'was_active' => $wasActive];
    }
}
