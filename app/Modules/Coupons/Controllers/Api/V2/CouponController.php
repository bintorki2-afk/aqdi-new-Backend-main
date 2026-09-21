<?php

namespace App\Modules\Coupons\Controllers\Api\V2;

use App\Http\Traits\Responser;
use App\Models\Contract;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Modules\Coupons\Requests\Api\ApplyCouponRequest;
use App\Services\CouponDiscountResolver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CouponController extends \App\Http\Controllers\Api\CouponController
{
    use Responser;

    public function Coupon(ApplyCouponRequest $request, $uuid)
    {

        try {
            $contract = Contract::findOwnedByUuidOrFail($uuid);

            $coupon = Coupon::where('is_delete', 0)
                ->where('is_review', true)
                ->where('code_coupon', $request->code_coupon)
                ->where('date_start', '<=', now())
                ->where('date_end', '>=', now())
                ->first();

            if (! $coupon) {
                return $this->errorMessage('الكود غير صحيح', 404);
            }

            $user = Auth::user();

            try {
                app(CouponDiscountResolver::class)->assertCanApply($coupon, $user, $contract);
            } catch (ValidationException $e) {
                return $this->errorMessage(app(CouponDiscountResolver::class)->firstErrorMessage($e), 422);
            }

            $usageLimit = $coupon->usage_of_user;
            $applyResult = DB::transaction(function () use ($coupon, $user, $contract, $usageLimit) {
                $locked = Coupon::query()->whereKey($coupon->id)->lockForUpdate()->first();
                if (! $locked || $locked->usage <= 0) {
                    return 'exhausted';
                }

                $userCouponUsageCount = CouponUsage::where('user_id', $user->id)
                    ->where('coupon_id', $locked->id)
                    ->count();

                $userContractCouponUsageCount = CouponUsage::where('user_id', $user->id)
                    ->where('coupon_id', $locked->id)
                    ->where('contract_uuid', $contract->uuid)
                    ->count();

                if ($userCouponUsageCount >= $usageLimit) {
                    return 'exhausted';
                }

                if ($userContractCouponUsageCount > 0) {
                    return 'already';
                }

                $locked->decrement('usage');

                CouponUsage::create([
                    'user_id' => $user->id,
                    'coupon_id' => $locked->id,
                    'contract_uuid' => $contract->uuid,
                    'used_at' => now(),
                ]);

                return 'ok';
            });

            if ($applyResult === 'exhausted') {
                return $this->errorMessage('تم تجاوز حد استخدام الكوبون', 422);
            }

            if ($applyResult === 'already') {
                return $this->errorMessage('لقد استخدمت هذا الكوبون لهذا العقد بالفعل', 409);
            }

            // Same calculator as the finance / payment / invoice screens (fee + VAT + meter fees),
            // so "before" and "after" here match the total the user is actually charged.
            $totalBefore = \App\Support\ContractPricing::total($contract, false);
            $discount = app(CouponDiscountResolver::class)->amount(
                $coupon,
                $contract,
                (float) $contract->getPriceContractAttribute()
            );
            $totalAfter = max(0.0, $totalBefore - $discount);

            $message = $coupon->type_coupon === 'ratio'
                ? 'تم خصم ' . $coupon->value_coupon . '% من قيمة العقد بنجاح'
                : 'تم خصم ' . $coupon->value_coupon . ' ريال بنجاح';

            return $this->apiResponse([
                'type_coupon' => $coupon->type_coupon,
                'value_coupon' => (float) $coupon->value_coupon,
                'discount' => round($discount, 2),
                'total_price_before_coupon' => round($totalBefore, 2),
                'total_price_after_coupon' => round($totalAfter, 2),
            ], $message, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error applying coupon: ' . $e->getMessage() . ' Contract UUID: ' . $uuid);

            return $this->errorMessage('حدث خطأ أثناء تطبيق الكوبون. الرجاء المحاولة مرة أخرى.', 500);
        }
    }
}
