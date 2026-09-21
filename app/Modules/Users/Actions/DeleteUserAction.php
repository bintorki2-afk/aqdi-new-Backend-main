<?php

namespace App\Modules\Users\Actions;

use App\Modules\Users\Models\User;

class DeleteUserAction
{
    public function execute(int|string $id): bool
    {
        $user = User::find($id);

        if (! $user) {
            return false;
        }

        $user->delete();

        return true;
    }
}
