<?php

namespace App\Modules\Auth\Actions;

use App\Models\User;
use App\Services\Admin\UserCouponService;

class AppendLoginCouponNotificationAction
{
    /**
     * @param  array<string, mixed>  $result
     */
    public function execute(array &$result, User $user): void
    {
        $notifications = app(UserCouponService::class)->pendingLoginNotifications($user);
        $result['login_notification'] = $notifications[0] ?? null;
        $result['login_notifications'] = $notifications;
    }
}
