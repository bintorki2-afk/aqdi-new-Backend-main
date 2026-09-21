<?php

namespace App\Modules\Users\Requests\Admin;

use App\Models\UserCoupon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('valid_until') && ! $this->filled('expires_at')) {
            $this->merge(['expires_at' => $this->input('valid_until')]);
        }

        if ($this->filled('code') && ! $this->filled('secret_code')) {
            $this->merge(['secret_code' => $this->input('code')]);
        }

        if ($this->filled('message') && ! $this->filled('notification_message')) {
            $this->merge(['notification_message' => $this->input('message')]);
        }

        if ($this->has('notify') && ! $this->has('notify_on_login')) {
            $this->merge(['notify_on_login' => $this->boolean('notify')]);
        }

        $type = strtolower(trim((string) $this->input('type', '')));
        $appliesTo = strtolower(trim((string) $this->input('applies_to', UserCoupon::APPLIES_ALL)));

        $this->merge([
            'type' => match ($type) {
                'ratio', 'percent', 'percentage', 'نسبة' => UserCoupon::TYPE_PERCENTAGE,
                'value', 'amount', 'fixed', 'fixed_amount', 'مبلغ' => UserCoupon::TYPE_FIXED,
                default => $type,
            },
            'applies_to' => match ($appliesTo) {
                'all', 'both', 'housing_and_commercial', 'all_contracts' => UserCoupon::APPLIES_ALL,
                'housing', 'residential', 'سكني' => UserCoupon::APPLIES_HOUSING,
                'commercial', 'تجاري' => UserCoupon::APPLIES_COMMERCIAL,
                default => $appliesTo !== '' ? $appliesTo : UserCoupon::APPLIES_ALL,
            },
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in([
                UserCoupon::TYPE_PERCENTAGE,
                UserCoupon::TYPE_FIXED,
            ])],
            'value' => ['required', 'numeric', 'min:0'],
            'applies_to' => ['nullable', Rule::in([
                UserCoupon::APPLIES_ALL,
                UserCoupon::APPLIES_HOUSING,
                UserCoupon::APPLIES_COMMERCIAL,
            ])],
            'expires_at' => ['nullable', 'date', 'after_or_equal:today'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'notify_on_login' => ['sometimes', 'boolean'],
            'notification_message' => ['nullable', 'string', 'max:500'],
            'secret_code' => ['nullable', 'string', 'max:40', 'regex:/^[A-Za-z0-9\-_]+$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'نوع الخصم مطلوب (percentage أو fixed).',
            'type.in' => 'نوع الخصم يجب أن يكون percentage أو fixed.',
            'value.required' => 'قيمة الخصم مطلوبة.',
            'applies_to.in' => 'ينطبق على: all أو housing أو commercial.',
            'secret_code.regex' => 'الرمز السري يجب أن يحتوي حروفاً وأرقاماً فقط.',
        ];
    }
}
