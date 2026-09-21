<?php

namespace App\Http\Resources\Admin\V2\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserCouponResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $coupon = $this->coupon;

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'coupon_id' => $this->coupon_id,
            'secret_code' => $coupon?->code_coupon,
            'code_coupon' => $coupon?->code_coupon,
            'type' => $this->type,
            'value' => round((float) $this->value, 2),
            'applies_to' => $this->applies_to,
            'applies_on' => 'first_year_fees',
            'expires_at' => $this->expires_at?->format('Y-m-d'),
            'reason' => $this->reason,
            'notify_on_login' => (bool) $this->notify_on_login,
            'notification_message' => $this->notification_message,
            'login_notified_at' => $this->login_notified_at?->format('Y-m-d H:i:s'),
            'acknowledged_at' => $this->acknowledged_at?->format('Y-m-d H:i:s'),
            'is_active' => (bool) $this->is_active,
            'is_pending' => $this->isPending(),
            'is_used' => $this->isUsed(),
            'employee_id' => $this->employee_id,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
