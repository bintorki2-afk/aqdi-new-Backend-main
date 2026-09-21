<?php

namespace App\Models;

use App\Support\DocFee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserCoupon extends Model
{
    public const TYPE_PERCENTAGE = 'percentage';

    public const TYPE_FIXED = 'fixed';

    public const APPLIES_ALL = 'all';

    public const APPLIES_HOUSING = 'housing';

    public const APPLIES_COMMERCIAL = 'commercial';

    protected $fillable = [
        'user_id',
        'coupon_id',
        'employee_id',
        'type',
        'value',
        'applies_to',
        'expires_at',
        'reason',
        'notify_on_login',
        'notification_message',
        'login_notified_at',
        'acknowledged_at',
        'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'expires_at' => 'date',
        'notify_on_login' => 'boolean',
        'is_active' => 'boolean',
        'login_notified_at' => 'datetime',
        'acknowledged_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class, 'coupon_id', 'coupon_id');
    }

    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        return $this->expires_at->copy()->endOfDay()->lt(now());
    }

    public function isUsed(): bool
    {
        if ($this->coupon_id === null) {
            return false;
        }

        return CouponUsage::query()->where('coupon_id', $this->coupon_id)->exists();
    }

    public function isPending(): bool
    {
        if (! $this->is_active || $this->isExpired() || $this->isUsed()) {
            return false;
        }

        $coupon = $this->relationLoaded('coupon') ? $this->coupon : $this->coupon()->first();

        return $coupon !== null && $coupon->isValidNow();
    }

    public function appliesToContractType(?string $contractType): bool
    {
        if ($this->applies_to === self::APPLIES_ALL) {
            return true;
        }

        return $contractType !== null && $this->applies_to === $contractType;
    }

    /**
     * Discount is on first-year documentation fees only (249 housing / 349 commercial).
     */
    public function discountAmount(Contract $contract, ?float $totalContractPrice = null): float
    {
        $firstYear = DocFee::firstYearFee((string) $contract->contract_type);
        $cap = $firstYear;
        if ($totalContractPrice !== null && $totalContractPrice >= 0) {
            $cap = min($firstYear, $totalContractPrice);
        }

        $amount = $this->type === self::TYPE_PERCENTAGE
            ? round($cap * (float) $this->value / 100, 2)
            : min((float) $this->value, $cap);

        return max(0, round($amount, 2));
    }

    public function resolvedNotificationMessage(): string
    {
        $message = trim((string) $this->notification_message);

        return $message !== ''
            ? $message
            : trans('api.user_coupon_default_message');
    }
}
