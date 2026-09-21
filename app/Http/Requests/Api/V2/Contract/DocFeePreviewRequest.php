<?php

namespace App\Http\Requests\Api\V2\Contract;

use App\Http\Requests\Api\V2\BaseApiV2Request;
use App\Http\Requests\Api\V2\Concerns\ResolvesContractIdInput;
use App\Models\Contract;
use Illuminate\Contracts\Validation\Validator;

class DocFeePreviewRequest extends BaseApiV2Request
{
    use ResolvesContractIdInput;

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $this->resolveContractIdInput();

        $this->merge([
            'duration_preset' => $this->input('duration_preset', 'other'),
            'duration_years' => (int) $this->input('duration_years', 0),
            'duration_months' => (int) $this->input('duration_months', 0),
        ]);

        if (! $this->filled('contract_type') && $this->filled('id')) {
            $type = Contract::query()->whereKey($this->input('id'))->value('contract_type');
            if ($type) {
                $this->merge(['contract_type' => $type]);
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
            'id' => 'nullable|exists:contracts,id',
            'contract_type' => 'required|in:housing,commercial',
            'duration_preset' => 'required|in:other',
            'duration_years' => 'required|integer|min:1|max:30',
            'duration_months' => 'required|integer|min:0|max:11',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            if (! $this->filled('contract_type') && ! $this->filled('id')) {
                $v->errors()->add('contract_type', 'نوع العقد مطلوب أو معرّف العقد.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'contract_type.required' => 'نوع العقد مطلوب.',
            'contract_type.in' => 'نوع العقد يجب أن يكون سكني أو تجاري.',
            'duration_years.required' => 'عدد السنوات مطلوب.',
            'duration_years.min' => 'عدد السنوات يجب ألا يقل عن 1.',
            'duration_years.max' => 'عدد السنوات يجب ألا يزيد عن 30.',
            'duration_months.required' => 'عدد الأشهر مطلوب.',
            'duration_months.min' => 'عدد الأشهر يجب ألا يقل عن 0.',
            'duration_months.max' => 'عدد الأشهر يجب ألا يزيد عن 11.',
        ];
    }
}
