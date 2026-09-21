<?php

namespace App\Http\Requests\Admin\V2;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code_coupon' => ['required', 'string', 'max:255', Rule::unique('coupons', 'code_coupon')],
            'type_coupon' => ['required', 'in:ratio,value'],
            'value_coupon' => ['required', 'numeric', 'min:0'],
            'date_start' => ['required', 'date'],
            'date_end' => ['required', 'date', 'after_or_equal:date_start'],
            'usage' => ['required', 'integer', 'min:0'],
            'usage_of_user' => ['required', 'integer', 'min:0'],
            'is_review' => ['sometimes', 'boolean'],
        ];
    }
}
