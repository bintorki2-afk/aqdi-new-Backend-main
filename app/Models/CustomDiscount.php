<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomDiscount extends Model
{
    public const TYPE_PERCENTAGE = 'percentage';

    public const TYPE_FIXED = 'fixed';

    public const TYPE_WAIVER = 'waiver';

    protected $fillable = [
        'user_id',
        'contract_id',
        'contract_uuid',
        'coupon_id',
        'coupon_usage_id',
        'employee_id',
        'type',
        'value',
        'discount_amount',
        'total_before',
        'total_after',
        'reason',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_before' => 'decimal:2',
        'total_after' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function couponUsage(): BelongsTo
    {
        return $this->belongsTo(CouponUsage::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
