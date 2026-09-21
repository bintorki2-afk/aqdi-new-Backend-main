<?php

namespace App\Modules\Contracts\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class Step4Request extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'tenant_entity' => 'required|in:person,institution',
            'tenant_id_num' => 'nullable|required_if:tenant_entity,person|min:10',
            'tenant_dob' => 'nullable|required_if:tenant_entity,person',
            'tenant_mobile' => 'nullable|required_if:tenant_entity,person|min:10|regex:/^05[0-9]{8}$/',
            'region_of_the_tenant_legal_agent' => 'nullable|required_if:tenant_entity,institution|exists:regions,id',
            'city_of_the_tenant_legal_agent' => 'nullable|required_if:tenant_entity,institution|exists:cities,id',
            'tenant_entity_unified_registry_number' => 'nullable|required_if:tenant_entity,institution',
            'authorization_type' => 'nullable|required_if:tenant_entity,institution|in:owner_and_representative_of_record,agent_for_the_tenant,agent_or_authorized_by_registry_owner',
            'copy_of_the_authorization_or_agency' => 'nullable|required_if:authorization_type,agent_for_the_tenant|mimes:jpg,jpeg,png,pdf',
            'copy_of_the_owner_record' => 'nullable|mimes:jpg,jpeg,png,pdf',
            'id_num_of_property_tenant_agent' => 'nullable|min:10',
            'mobile_of_property_tenant_agent' => 'nullable',
            'dob_of_property_tenant_agent' => 'nullable|required_if:tenant_entity,institution',
        ];
    }

    public function messages(): array
    {
        return [
            'tenant_entity.required' => 'نوع الكيان المستأجر مطلوب.',
            'tenant_entity.in' => 'الكيان المستأجر يجب أن يكون شخص أو مؤسسة.',
            'tenant_id_num.required_if' => 'رقم الهوية مطلوب إذا كان الكيان المستأجر شخصاً.',
            'tenant_dob.required_if' => 'تاريخ ميلاد المستأجر مطلوب إذا كان الكيان شخصاً.',
            'tenant_mobile.required_if' => 'رقم الجوال مطلوب إذا كان الكيان المستأجر شخصاً.',
            'tenant_mobile.regex' => 'رقم الجوال يجب أن يبدأ بـ 05 ويكون مكون من 10 أرقام.',
            'authorization_type.required_if' => 'نوع التوكيل مطلوب إذا كان الكيان مؤسسة.',
            'id_num_of_property_tenant_agent.min' => 'رقم الهوية لا يقل عن عشرة أرقام.',
            'dob_of_property_tenant_agent.required_if' => 'تاريخ ميلاد وكيل المالك مطلوب.',
            'copy_of_the_owner_record.mimes' => 'نسخة السجل يجب أن تكون بصيغة jpg, jpeg, png, أو pdf.',
            'copy_of_the_authorization_or_agency.required_if' => 'نسخة من التوكيل مطلوبة.',
            'copy_of_the_authorization_or_agency.mimes' => 'نسخة التوكيل يجب أن تكون بصيغة jpg, jpeg, png, أو pdf.',
        ];
    }
}
