<?php

namespace App\Http\Resources\Admin\V2\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isActive = (bool) $this->is_review;
        $isDeleted = (bool) $this->is_delete;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'code_coupon' => $this->code_coupon,
            'type_coupon' => $this->type_coupon,
            'value_coupon' => $this->value_coupon,
            'date_start' => $this->date_start?->format('Y-m-d'),
            'date_end' => $this->date_end?->format('Y-m-d'),
            'usage' => (int) $this->usage,
            'usage_of_user' => (int) $this->usage_of_user,
            'usages_count' => $this->usages_count ?? $this->usages?->count(),
            'is_review' => $isActive,
            'is_active' => $isActive,
            'is_inactive' => ! $isActive,
            'is_delete' => $isDeleted,
            'is_deleted' => $isDeleted,
            'is_valid_now' => $this->isValidNow(),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
