<?php

namespace App\Modules\Users\Actions;

use App\Modules\Users\Models\User;
use Illuminate\Http\Request;

class UpdateOwnProfileAction
{
    /**
     * @return array<string, mixed>
     */
    public function execute(User $user, Request $request): array
    {
        $data = $request->only(['fname', 'email', 'mobile']);

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $path = fileUploader($file, 'users');

            if ($path) {
                deleteFile($user->photo);
                $data['photo'] = $path;
            }
        }

        $user->update($data);

        return $data;
    }
}
