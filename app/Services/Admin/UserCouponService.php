<?php

namespace App\Services\Admin;

use App\Models\Coupon;
use App\Models\Offer;
use App\Models\User;
use App\Models\UserCoupon;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserCouponService
{
    /**
     * @param  array{
     *   type:string,
     *   value:mixed,
     *   applies_to?:string,
     *   expires_at?:mixed,
     *   reason?:mixed,
     *   notify_on_login?:mixed,
     *   notification_message?:mixed,
     *   secret_code?:mixed
     * }  $payload
     */
    public function create(User $user, array $payload, ?int $employeeId = null): UserCoupon
    {
        $type = (string) $payload['type'];
        $value = round((float) ($payload['value'] ?? 0), 2);

        if ($type === UserCoupon::TYPE_PERCENTAGE && ($value <= 0 || $value > 100)) {
            throw ValidationException::withMessages([
                'value' => [trans('api.discount_percentage_invalid')],
            ]);
        }

        if ($type === UserCoupon::TYPE_FIXED && $value <= 0) {
            throw ValidationException::withMessages([
                'value' => [trans('api.discount_fixed_invalid')],
            ]);
        }

        $appliesTo = (string) ($payload['applies_to'] ?? UserCoupon::APPLIES_ALL);
        $expiresAt = $payload['expires_at'] ?? null;
        $notifyOnLogin = array_key_exists('notify_on_login', $payload)
            ? (bool) $payload['notify_on_login']
            : true;
        $message = trim((string) ($payload['notification_message'] ?? ''));
        $secretCode = strtoupper(trim((string) ($payload['secret_code'] ?? '')));

        if ($secretCode === '') {
            $secretCode = $this->generateSecretCode();
        } elseif (Coupon::query()->where('code_coupon', $secretCode)->exists()) {
            throw ValidationException::withMessages([
                'secret_code' => [trans('api.user_coupon_code_taken')],
            ]);
        }

        $dateEnd = $expiresAt
            ? Carbon::parse($expiresAt)->toDateString()
            : now()->addYears(10)->toDateString();

        $couponType = $type === UserCoupon::TYPE_FIXED ? 'value' : 'ratio';

        return DB::transaction(function () use (
            $user,
            $employeeId,
            $type,
            $value,
            $appliesTo,
            $expiresAt,
            $notifyOnLogin,
            $message,
            $secretCode,
            $dateEnd,
            $couponType,
            $payload
        ) {
            $coupon = Coupon::query()->create([
                'name' => 'خصم مخصص - العميل '.$user->id,
                'code_coupon' => $secretCode,
                'type_coupon' => $couponType,
                'value_coupon' => $value,
                'date_start' => now()->toDateString(),
                'date_end' => $dateEnd,
                'usage' => 1,
                'usage_of_user' => 1,
                'is_review' => true,
                'is_delete' => false,
            ]);

            $userCoupon = UserCoupon::query()->create([
                'user_id' => $user->id,
                'coupon_id' => $coupon->id,
                'employee_id' => $employeeId,
                'type' => $type,
                'value' => $value,
                'applies_to' => $appliesTo,
                'expires_at' => $expiresAt,
                'reason' => $payload['reason'] ?? null,
                'notify_on_login' => $notifyOnLogin,
                'notification_message' => $message !== '' ? $message : null,
                'is_active' => true,
            ]);

            if ($notifyOnLogin) {
                $this->storeInboxNotification($user, $userCoupon->fresh(['coupon']) ?? $userCoupon);
            }

            return $userCoupon->load('coupon');
        });
    }

    /**
     * @return list<UserCoupon>
     */
    public function listForUser(User $user)
    {
        return UserCoupon::query()
            ->with('coupon')
            ->where('user_id', $user->id)
            ->latest()
            ->get();
    }

    public function deactivate(UserCoupon $userCoupon): UserCoupon
    {
        $userCoupon->is_active = false;
        $userCoupon->save();

        if ($userCoupon->coupon_id) {
            Coupon::query()->whereKey($userCoupon->coupon_id)->update([
                'is_review' => false,
            ]);
        }

        return $userCoupon->fresh(['coupon']) ?? $userCoupon;
    }

    /**
     * Pending login popup for this user (unused, valid, notify_on_login).
     *
     * @return array<string, mixed>|null
     */
    public function loginNotificationPayload(User $user): ?array
    {
        $pending = $this->pendingLoginNotifications($user);

        return $pending[0] ?? null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function pendingLoginNotifications(User $user): array
    {
        $coupons = UserCoupon::query()
            ->with('coupon')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->where('notify_on_login', true)
            ->whereNull('acknowledged_at')
            ->latest()
            ->get()
            ->filter(fn (UserCoupon $coupon) => $coupon->isPending());

        $payloads = [];
        foreach ($coupons as $coupon) {
            if ($coupon->login_notified_at === null) {
                $coupon->login_notified_at = now();
                $coupon->save();
            }
            $payloads[] = $this->toLoginPayload($coupon);
        }

        return $payloads;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function pendingCouponsPayload(User $user): array
    {
        return UserCoupon::query()
            ->with('coupon')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->latest()
            ->get()
            ->filter(fn (UserCoupon $coupon) => $coupon->isPending())
            ->map(fn (UserCoupon $coupon) => $this->toLoginPayload($coupon))
            ->values()
            ->all();
    }

    public function acknowledge(User $user, ?int $userCouponId = null): void
    {
        $query = UserCoupon::query()
            ->where('user_id', $user->id)
            ->whereNull('acknowledged_at');

        if ($userCouponId) {
            $query->whereKey($userCouponId);
        }

        $query->update(['acknowledged_at' => now()]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toLoginPayload(UserCoupon $userCoupon): array
    {
        $userCoupon->loadMissing('coupon');

        return [
            'type' => 'custom_coupon',
            'user_coupon_id' => $userCoupon->id,
            'title' => trans('api.user_coupon_login_title'),
            'message' => $userCoupon->resolvedNotificationMessage(),
            'code_coupon' => $userCoupon->coupon?->code_coupon,
            'discount_type' => $userCoupon->type,
            'value' => round((float) $userCoupon->value, 2),
            'applies_to' => $userCoupon->applies_to,
            'applies_on' => 'first_year_fees',
            'expires_at' => $userCoupon->expires_at?->format('Y-m-d'),
        ];
    }

    private function generateSecretCode(): string
    {
        do {
            $code = 'AQ'.strtoupper(Str::random(8));
        } while (Coupon::query()->where('code_coupon', $code)->exists());

        return $code;
    }

    private function storeInboxNotification(User $user, UserCoupon $userCoupon): void
    {
        if (! Schema::hasTable('offers')) {
            return;
        }

        Offer::query()->create([
            'user_id' => $user->id,
            'title' => trans('api.user_coupon_login_title'),
            'body' => $userCoupon->resolvedNotificationMessage(),
            'is_active' => true,
            'start_date' => now()->toDateString(),
            'end_date' => $userCoupon->expires_at?->toDateString()
                ?? $userCoupon->coupon?->date_end?->format('Y-m-d'),
            'is_read' => false,
        ]);
    }
}
