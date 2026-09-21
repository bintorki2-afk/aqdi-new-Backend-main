<?php

namespace App\Modules\Employees\Requests\Admin\V2;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreEmployeeSalaryRequest extends FormRequest
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
            'addition_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:addition_date'],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'deduction' => ['required', 'numeric', 'min:0'],
            'bonus' => ['required', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'is_paid' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $expected = round(
                (float) $this->input('basic_salary')
                - (float) $this->input('deduction')
                + (float) $this->input('bonus'),
                2
            );
            $total = round((float) $this->input('total'), 2);

            if (abs($expected - $total) > 0.01) {
                $validator->errors()->add(
                    'total',
                    trans('api.salary_total_mismatch', ['expected' => $expected])
                );
            }
        });
    }
}
