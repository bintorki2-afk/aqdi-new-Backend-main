<?php

namespace App\Modules\RealEstate\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStep3Request extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'id' => 'required|exists:real_estates,id',
            'name_owner' => 'nullable|string',
            'name_real_estate' => 'sometimes|string',
            'property_owner_id_num' => 'nullable|min:10',
            'property_owner_dob_hijri' => 'nullable',
            'property_owner_mobile' => 'nullable|min:9|regex:/^05[0-9]{8}$/',
            'property_owner_iban' => 'nullable|min:22',
            'add_legal_agent_of_owner' => 'nullable',
            'id_num_of_property_owner_agent' => 'nullable|required_if:add_legal_agent_of_owner,1|min:10',
            'dob_of_property_owner_agent' => 'nullable|required_if:add_legal_agent_of_owner,1',
            'mobile_of_property_owner_agent' => 'nullable|required_if:add_legal_agent_of_owner,1|min:10|regex:/^05[0-9]{8}$/',
            'agency_number_in_instrument_of_property_owner' => 'nullable|required_if:add_legal_agent_of_owner,1',
            'agency_instrument_date_of_property_owner' => 'nullable|required_if:add_legal_agent_of_owner,1',
        ];
    }

    public function messages(): array
    {
        return [
            'name_owner.required' => 'اسم المالك مطلوب.',
            'property_owner_id_num.required' => 'رقم هوية المالك مطلوب.',
            'property_owner_id_num.min' => 'رقم هوية المالك لا يقل عن عشرة أرقام.',
            'property_owner_dob_hijri.required' => 'تاريخ ميلاد المالك مطلوب.',
            'property_owner_dob_hijri.date_format' => 'تاريخ ميلاد المالك يجب أن يكون بالشكل: يوم-شهر-سنة.',
            'property_owner_mobile.required' => 'رقم جوال المالك مطلوب.',
            'property_owner_mobile.min' => 'رقم جوال المالك 10 ارقام علي الاقل.',
            'property_owner_mobile.regex' => 'رقم جوال المالك  يبدا ب 05 ويتبعه ثمانية ارقام',
            'property_owner_iban.required' => 'رقم الآيبان الخاص بالمالك مطلوب.',
            'property_owner_iban.min' => 'رقم الآيبان المالك يجب ان يكون 22 رقم',
            'id_num_of_property_owner_agent.required_if' => 'رقم هوية وكيل المالك مطلوب عند وجود وكيل.',
            'id_num_of_property_owner_agent.min' => 'رقم هوية وكيل المالك 10 أرقام علي الاقل.',
            'dob_of_property_owner_agent.required_if' => 'تاريخ ميلاد وكيل المالك مطلوب عند وجود وكيل.',
            'dob_of_property_owner_agent.date_format' => 'تاريخ ميلاد وكيل المالك يجب أن يكون بالشكل: يوم-شهر-سنة.',
            'mobile_of_property_owner_agent.required_if' => 'رقم جوال وكيل المالك مطلوب عند وجود وكيل.',
            'mobile_of_property_owner_agent.min' => 'رقم جوال وكيل المالك  يبدا ب 05 ويتبعه ثمانية ارقام',
            'mobile_of_property_owner_agent.regex' => 'رقم جوال وكيل المالك  يبدا ب 05 ويتبعه ثمانية ارقام',
            'agency_number_in_instrument_of_property_owner.required_if' => 'رقم الوكالة مطلوب عند وجود وكيل.',
            'agency_instrument_date_of_property_owner.required_if' => 'تاريخ الوكالة مطلوب عند وجود وكيل.',
        ];
    }
}
