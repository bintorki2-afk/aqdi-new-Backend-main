<?php

namespace App\Http\Requests\Admin\V2;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCouponRequest extends FormRequest
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
        $id = (int) $this->route('id');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code_coupon' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('coupons', 'code_coupon')->ignore($id),
            ],
            'type_coupon' => ['sometimes', 'required', 'in:ratio,value'],
            'value_coupon' => ['sometimes', 'required', 'numeric', 'min:0'],
            'date_start' => ['sometimes', 'required', 'date'],
            'date_end' => ['sometimes', 'required', 'date', 'after_or_equal:date_start'],
            'usage' => ['sometimes', 'required', 'integer', 'min:0'],
            'usage_of_user' => ['sometimes', 'required', 'integer', 'min:0'],
            'is_review' => ['sometimes', 'boolean'],
        ];
    }
}
