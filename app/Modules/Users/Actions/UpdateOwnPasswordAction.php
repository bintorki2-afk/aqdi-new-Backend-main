<?php

namespace App\Modules\Users\Actions;

use App\Modules\Users\Models\User;
use Illuminate\Support\Facades\Hash;

class UpdateOwnPasswordAction
{
    public function execute(User $user, string $password, string $currentPassword): bool
    {
        if (! Hash::check($currentPassword, $user->getAuthPassword())) {
            return false;
        }

        $user->update([
            'password' => $password,
        ]);

        return true;
    }
}
