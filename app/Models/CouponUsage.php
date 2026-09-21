<?php

namespace App\Models;

use App\Models\Coupon;
use App\Models\User;
use App\Services\CouponDiscountResolver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CouponUsage extends Model
{
    use HasFactory;
    protected $table='coupon_usages';
    protected $fillable = [
        'user_id',
        'coupon_id',
        'used_at',
        'contract_uuid'
    ];

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }


public function calculateDiscountedPrice($contract)
{
    $total = $contract->getPriceContractAttribute();

    if ($this->coupon && $this->coupon->date_end < now()) {
        return $total;
    }

    $discount = app(CouponDiscountResolver::class)->amount($this->coupon, $contract, (float) $total);

    return max(0, $total - $discount);
}


}
