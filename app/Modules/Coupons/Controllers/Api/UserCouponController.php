<?php

namespace App\Modules\Coupons\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Services\Admin\UserCouponService;
use Illuminate\Http\Request;

class UserCouponController extends Controller
{
    use Responser;

    public function mine(Request $request, UserCouponService $service)
    {
        $user = $request->user();
        $pending = $service->pendingCouponsPayload($user);

        return $this->apiResponse([
            'login_notification' => $pending[0] ?? null,
            'items' => $pending,
        ], trans('api.success'));
    }

    public function acknowledge(Request $request, UserCouponService $service)
    {
        $request->validate([
            'user_coupon_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $service->acknowledge($request->user(), $request->integer('user_coupon_id') ?: null);

        return $this->successMessage(trans('api.user_coupon_ack_success'));
    }
}
