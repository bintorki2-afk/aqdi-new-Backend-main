<?php

namespace App\Modules\Catalog\Requests\Admin;

use App\Modules\Catalog\Models\TenantRole;
use Illuminate\Validation\Rule;

class StoreTenantRoleRequest extends AdminFormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $hasInputType = $this->filled('input_field_type');
        $hasInputLabel = $this->filled('input_field_label');

        return [
            'text_of_reason' => ['required', 'string', 'max:500'],
            'service_definition' => ['nullable', 'string', 'max:5000'],
            'input_field_label' => [
                Rule::requiredIf($hasInputType),
                'nullable',
                'string',
                'max:255',
            ],
            'input_field_type' => [
                Rule::requiredIf($hasInputLabel),
                'nullable',
                Rule::in(TenantRole::inputFieldTypes()),
            ],
            'icon' => ['nullable', 'string', 'max:255'],
            'input_icon' => ['nullable', 'string', 'max:255'],
            'pop' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'input_field_label.required' => 'اسم حقل المستخدم مطلوب عند تحديد نوع الحقل.',
            'input_field_type.required' => 'نوع حقل المستخدم مطلوب عند إدخال اسم الحقل.',
            'input_field_type.in' => 'نوع الحقل يجب أن يكون نص أو رقم (text أو number).',
            'pop.boolean' => 'قيمة pop يجب أن تكون true أو false.',
        ];
    }

    public function attributes(): array
    {
        return [
            'text_of_reason' => 'عنوان الصلاحية',
            'service_definition' => 'التعريف بالخدمة',
            'input_field_label' => 'اسم حقل المستخدم',
            'input_field_type' => 'نوع حقل المستخدم',
            'icon' => 'أيقونة الصلاحية',
            'input_icon' => 'أيقونة حقل الإدخال',
            'pop' => 'عرض النافذة المنبثقة',
        ];
    }
}
