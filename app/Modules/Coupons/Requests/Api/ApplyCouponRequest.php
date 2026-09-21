<?php

namespace App\Modules\Coupons\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ApplyCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'code_coupon' => 'required',
        ];
    }
}
