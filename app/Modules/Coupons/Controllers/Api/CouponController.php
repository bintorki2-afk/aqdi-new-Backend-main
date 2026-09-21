<?php

namespace App\Modules\Coupons\Controllers\Api;

use App\Http\Controllers\Controller;
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

class CouponController extends Controller
{
    use Responser;

    public function Coupon(ApplyCouponRequest $request, $uuid)
    {

        try {
            $contract = Contract::findOwnedByUuidOrFail($uuid);

            // Check if the coupon exists and is valid
            $contract_coupon = Coupon::where('is_delete', 0)
                ->where('is_review', true)
                ->where('code_coupon', $request->code_coupon)
                ->where('date_start', '<=', now())
                ->where('date_end', '>=', now())
                ->first();

            if (!$contract_coupon) {
                return $this->errorMessage('الكود غير صحيح', 404);
            }

            $user = Auth::user();

            try {
                app(CouponDiscountResolver::class)->assertCanApply($contract_coupon, $user, $contract);
            } catch (ValidationException $e) {
                return $this->errorMessage(app(CouponDiscountResolver::class)->firstErrorMessage($e), 422);
            }

            $usage_limit = $contract_coupon->usage_of_user;

            $applyResult = DB::transaction(function () use ($contract_coupon, $user, $contract, $usage_limit) {
                $locked = Coupon::query()->whereKey($contract_coupon->id)->lockForUpdate()->first();
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

                if ($userCouponUsageCount >= $usage_limit) {
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

            if ($contract_coupon->type_coupon == 'ratio') {
                return $this->successMessage([
                     'status' => 'success',
                    'message' => 'تم خصم ' . $contract_coupon->value_coupon . '% من قيمة العقد بنجاح',
                    'data' => $contract_coupon->value_coupon
                ], 200);
            }

            return $this->successMessage([
                 'status' => 'success',
                'message' => 'تم خصم ' . $contract_coupon->value_coupon . ' ريال بنجاح',
                'data' => $contract_coupon->value_coupon
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error applying coupon: ' . $e->getMessage() . ' Contract UUID: ' . $uuid);
            return $this->errorMessage('حدث خطأ أثناء تطبيق الكوبون. الرجاء المحاولة مرة أخرى.', 500);
        }
    }
}
