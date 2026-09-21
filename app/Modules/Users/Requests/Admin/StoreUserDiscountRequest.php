<?php

namespace App\Modules\Users\Requests\Admin;

use App\Models\CustomDiscount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('notes') && ! $this->filled('reason')) {
            $this->merge(['reason' => $this->input('notes')]);
        }

        $type = strtolower(trim((string) $this->input('type', '')));
        $this->merge([
            'type' => match ($type) {
                'ratio', 'percent', 'percentage' => CustomDiscount::TYPE_PERCENTAGE,
                'value', 'amount', 'fixed', 'fixed_amount' => CustomDiscount::TYPE_FIXED,
                'exemption', 'full', 'free', 'waiver' => CustomDiscount::TYPE_WAIVER,
                default => $type,
            },
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'contract_id' => ['required', 'integer', 'min:1'],
            'type' => ['required', Rule::in([
                CustomDiscount::TYPE_PERCENTAGE,
                CustomDiscount::TYPE_FIXED,
                CustomDiscount::TYPE_WAIVER,
            ])],
            'value' => [
                Rule::requiredIf(fn () => $this->input('type') !== CustomDiscount::TYPE_WAIVER),
                'nullable',
                'numeric',
                'min:0',
            ],
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'contract_id.required' => 'رقم العقد مطلوب.',
            'contract_id.min' => 'رقم العقد غير صالح.',
            'type.required' => 'نوع الخصم مطلوب (percentage أو fixed أو waiver).',
            'type.in' => 'نوع الخصم يجب أن يكون percentage أو fixed أو waiver.',
            'value.required' => 'قيمة الخصم مطلوبة إلا في حالة الإعفاء الكامل.',
            'reason.required' => 'سبب الخصم/الإعفاء مطلوب.',
        ];
    }
}
