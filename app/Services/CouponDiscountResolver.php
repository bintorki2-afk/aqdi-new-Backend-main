<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Coupon;
use App\Models\User;
use App\Models\UserCoupon;
use Illuminate\Validation\ValidationException;

class CouponDiscountResolver
{
    public function userCouponFor(?Coupon $coupon): ?UserCoupon
    {
        if ($coupon === null) {
            return null;
        }

        if ($coupon->relationLoaded('userCoupon')) {
            return $coupon->userCoupon;
        }

        return UserCoupon::query()->where('coupon_id', $coupon->id)->first();
    }

    public function assertCanApply(Coupon $coupon, User $user, Contract $contract): void
    {
        $userCoupon = $this->userCouponFor($coupon);
        if ($userCoupon === null) {
            if (! $coupon->isValidNow()) {
                throw ValidationException::withMessages([
                    'code_coupon' => [trans('api.user_coupon_expired')],
                ]);
            }

            return;
        }

        if ((int) $userCoupon->user_id !== (int) $user->id) {
            throw ValidationException::withMessages([
                'code_coupon' => [trans('api.user_coupon_not_for_user')],
            ]);
        }

        if (! $userCoupon->is_active || $userCoupon->isExpired() || ! $coupon->isValidNow()) {
            throw ValidationException::withMessages([
                'code_coupon' => [trans('api.user_coupon_expired')],
            ]);
        }

        if (! $userCoupon->appliesToContractType((string) $contract->contract_type)) {
            throw ValidationException::withMessages([
                'code_coupon' => [trans('api.user_coupon_wrong_contract_type')],
            ]);
        }
    }

    public function amount(?Coupon $coupon, Contract $contract, float $totalContractPrice): float
    {
        if ($coupon === null) {
            return 0.0;
        }

        $userCoupon = $this->userCouponFor($coupon);
        if ($userCoupon !== null) {
            return min($userCoupon->discountAmount($contract, $totalContractPrice), $totalContractPrice);
        }

        $discount = $coupon->type_coupon === 'ratio'
            ? round($totalContractPrice * (float) $coupon->value_coupon / 100, 2)
            : (float) $coupon->value_coupon;

        return max(0, min($discount, $totalContractPrice));
    }

    public function firstErrorMessage(ValidationException $e): string
    {
        $messages = collect($e->errors())->flatten()->filter();

        return (string) ($messages->first() ?: trans('api.user_coupon_expired'));
    }
}
