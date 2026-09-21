<?php

namespace App\Http\Requests\Api\V2\RealEstate;

use App\Http\Requests\Api\V2\BaseApiV2Request;
use App\Http\Requests\Api\V2\Concerns\NormalizesSaudiMobileInputs;
use App\Models\RealEstate;
use App\Support\DateInputNormalizer;
use Illuminate\Contracts\Validation\Validator;

/**
 * V2 real-estate step 2 (owner data; formerly step 3).
 */
class Step2RealEstateRequest extends BaseApiV2Request
{
    use NormalizesSaudiMobileInputs;

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $this->normalizeSaudiMobileFields([
            'property_owner_mobile',
            'mobile_of_property_owner_agent',
        ]);

        foreach (['name_owner', 'property_owner_iban'] as $optionalField) {
            if ($this->exists($optionalField) && trim((string) $this->input($optionalField)) === '') {
                $this->merge([$optionalField => null]);
            }
        }

        if (! array_key_exists('add_legal_agent_of_owner', $this->all())) {
            $this->merge(['add_legal_agent_of_owner' => false]);
        } else {
            $add = $this->input('add_legal_agent_of_owner');
            if ($add === null || $add === '') {
                $this->merge(['add_legal_agent_of_owner' => false]);
            } elseif (is_string($add)) {
                $v = strtolower(trim($add));
                if (in_array($v, ['1', 'true', 'yes', 'on'], true)) {
                    $this->merge(['add_legal_agent_of_owner' => true]);
                } elseif (in_array($v, ['0', 'false', 'no', 'off'], true)) {
                    $this->merge(['add_legal_agent_of_owner' => false]);
                }
            }
        }

        if (! $this->filled('property_owner_dob_day') && $this->filled('property_owner_dob_hijri_day')) {
            $this->merge([
                'property_owner_dob_day' => $this->input('property_owner_dob_hijri_day'),
                'property_owner_dob_month' => $this->input('property_owner_dob_hijri_month'),
                'property_owner_dob_year' => $this->input('property_owner_dob_hijri_year'),
            ]);
        }

        if ($this->filled('property_owner_dob') && ! $this->filled('property_owner_dob_day')) {
            $raw = (string) $this->input('property_owner_dob');
            $parts = preg_split('/[-\/]/', trim($raw));
            if (count($parts) === 3) {
                $this->merge([
                    'property_owner_dob_day' => (int) $parts[0],
                    'property_owner_dob_month' => (int) $parts[1],
                    'property_owner_dob_year' => (int) $parts[2],
                ]);
            }
        }

        if ($this->filled('property_owner_dob_hijri') && ! $this->filled('property_owner_dob_day')) {
            $raw = (string) $this->input('property_owner_dob_hijri');
            $parts = preg_split('/[-\/]/', trim($raw));
            if (count($parts) === 3) {
                $this->merge([
                    'property_owner_dob_day' => (int) $parts[0],
                    'property_owner_dob_month' => (int) $parts[1],
                    'property_owner_dob_year' => (int) $parts[2],
                ]);
            }
        }

        if ($this->filled('dob_of_property_owner_agent') && ! $this->filled('dob_of_property_owner_agent_day')) {
            $raw = (string) $this->input('dob_of_property_owner_agent');
            $parts = preg_split('/[-\/]/', trim($raw));
            if (count($parts) === 3) {
                $this->merge([
                    'dob_of_property_owner_agent_day' => (int) $parts[0],
                    'dob_of_property_owner_agent_month' => (int) $parts[1],
                    'dob_of_property_owner_agent_year' => (int) $parts[2],
                ]);
            }
        }

        if ($this->filled('agency_instrument_date_of_property_owner') && ! $this->filled('agency_instrument_date_of_property_owner_day')) {
            $mysql = DateInputNormalizer::toMysqlDate((string) $this->input('agency_instrument_date_of_property_owner'));
            if ($mysql !== null) {
                $p = DateInputNormalizer::splitMysqlDate($mysql);
                $this->merge([
                    'agency_instrument_date_of_property_owner_day' => (int) $p['day'],
                    'agency_instrument_date_of_property_owner_month' => (int) $p['month'],
                    'agency_instrument_date_of_property_owner_year' => (int) $p['year'],
                ]);
            }
        }
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'id' => 'required|exists:real_estates,id',
            'type_dob_property_owner' => 'nullable|in:hijri,gregorian',
            'type_dob_property_owner_agent' => 'nullable|in:hijri,gregorian',
            'type_agency_instrument_date_of_property_owner' => 'nullable|in:hijri,gregorian',
            'name_real_estate' => 'nullable|string|max:255',
            'name_owner' => 'nullable|string',
            'property_owner_id_num' => 'required|min:10',
            'property_owner_dob_day' => ['nullable'],
            'property_owner_dob_month' => ['nullable'],
            'property_owner_dob_year' => ['nullable'],
            'property_owner_mobile' => 'required|min:9|regex:/^5[0-9]{8}$/',
            'property_owner_iban' => 'nullable|string|min:22',
            'add_legal_agent_of_owner' => 'nullable|boolean',
            'id_num_of_property_owner_agent' => 'nullable|min:10',
            'dob_of_property_owner_agent_day' => 'nullable',
            'dob_of_property_owner_agent_month' => 'nullable',
            'dob_of_property_owner_agent_year' => 'nullable',
            'mobile_of_property_owner_agent' => 'nullable|min:9|regex:/^5[0-9]{8}$/',
            'agency_number_in_instrument_of_property_owner' => 'nullable|string|max:255',
            'agency_instrument_date_of_property_owner_day' => 'nullable',
            'agency_instrument_date_of_property_owner_month' => 'nullable|integer|between:1,12',
            'agency_instrument_date_of_property_owner_year' => 'nullable|integer|min:1900|max:2100',
            'copy_of_the_authorization_or_agency' => 'nullable|file',
        ];
    }

    private function wantsLegalAgent(): bool
    {
        $add = $this->input('add_legal_agent_of_owner');

        return in_array((string) $add, ['1', 'true'], true)
            || $add === 1
            || $add === true;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if (! $this->wantsLegalAgent()) {
                return;
            }

            if (! $this->filled('id_num_of_property_owner_agent')) {
                $validator->errors()->add('id_num_of_property_owner_agent', 'رقم هوية وكيل المالك مطلوب.');
            }

            if (! $this->filled('dob_of_property_owner_agent_day')
                || ! $this->filled('dob_of_property_owner_agent_month')
                || ! $this->filled('dob_of_property_owner_agent_year')) {
                $validator->errors()->add('dob_of_property_owner_agent_day', 'تاريخ ميلاد وكيل المالك مطلوب.');
            }

            if (! $this->filled('mobile_of_property_owner_agent')) {
                $validator->errors()->add('mobile_of_property_owner_agent', 'رقم جوال وكيل المالك مطلوب.');
            }

            if ($this->hasFile('copy_of_the_authorization_or_agency')) {
                return;
            }

            $realEstate = RealEstate::query()->find($this->input('id'));
            if ($realEstate?->copy_of_the_authorization_or_agency) {
                return;
            }

            $validator->errors()->add(
                'copy_of_the_authorization_or_agency',
                'إرفاق صورة الوكالة مطلوب عند إضافة وكيل للمالك.'
            );
        });
    }

    public function messages(): array
    {
        return [
            'id.required' => 'معرف العقار مطلوب.',
            'id.exists' => 'العقار المحدد غير موجود.',
            'type_dob_property_owner.in' => 'نوع تاريخ الميلاد للمالك غير صالح.',
            'type_dob_property_owner_agent.in' => 'نوع تاريخ الميلاد لوكيل المالك غير صالح.',
            'name_real_estate.required' => 'اسم العقار مطلوب.',
            'name_real_estate.max' => 'اسم العقار يجب ألا يزيد عن 255 حرفاً.',
            'property_owner_id_num.required' => 'رقم هوية المالك مطلوب.',
            'property_owner_id_num.min' => 'رقم هوية المالك لا يقل عن 10 أرقام.',
            'property_owner_mobile.required' => 'رقم جوال المالك مطلوب.',
            'property_owner_mobile.regex' => 'رقم جوال المالك يجب أن يبدأ بـ 5 ويتكون من 9 أرقام.',
            'id_num_of_property_owner_agent.min' => 'رقم هوية وكيل المالك لا يقل عن 10 أرقام.',
            'mobile_of_property_owner_agent.regex' => 'رقم جوال وكيل المالك يجب أن يبدأ بـ 5 ويتكون من 9 أرقام.',
        ];
    }
}
