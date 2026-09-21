<?php

namespace App\Support;

/**
 * Arabic validation labels and messages for Contract API V2.
 */
final class ContractV2ValidationMessages
{
    /**
     * @var array<string, string>
     */
    private const EXTRA_ATTRIBUTES = [
        'id' => 'معرف العقد',
        'is_real' => 'عقد على عقار مسجل',
        'real_id' => 'العقار',
        'real_units_id' => 'وحدة العقار',
        'unit_ids' => 'وحدات العقار',
        'units' => 'الوحدات',
        'property_place_id' => 'منطقة العقار',
        'street' => 'الشارع',
        'name_owner' => 'اسم المالك',
        'property_owner_dob_day' => 'يوم تاريخ ميلاد المالك',
        'property_owner_dob_month' => 'شهر تاريخ ميلاد المالك',
        'property_owner_dob_year' => 'سنة تاريخ ميلاد المالك',
        'type_dob_property_owner' => 'نوع تقويم تاريخ ميلاد المالك',
        'type_dob_property_owner_agent' => 'نوع تقويم تاريخ ميلاد وكيل المالك',
        'type_agency_instrument_date_of_property_owner' => 'نوع تقويم تاريخ صك الوكالة',
        'agency_instrument_date_of_property_owner_day' => 'يوم تاريخ صك الوكالة',
        'agency_instrument_date_of_property_owner_month' => 'شهر تاريخ صك الوكالة',
        'agency_instrument_date_of_property_owner_year' => 'سنة تاريخ صك الوكالة',
        'copy_of_the_endowment_registration_certificate' => 'نسخة شهادة تسجيل الوقف',
        'copy_of_the_trusteeship_deed' => 'نسخة صك الولاية',
        'is_multiple_trusteeship_deed_copy' => 'تعدد نسخ صك الولاية',
        'Image_inheritance_certificate' => 'صورة شهادة حصر الورثة',
        'copy_power_of_attorney_from_heirs_to_agent' => 'نسخة وكالة الورثة للوكيل',
        'copy_of_guardians_power_of_attorney_for_agent' => 'نسخة وكالة النظار للوكيل',
        'type_instrument_history' => 'نوع تقويم تاريخ الصك',
        'instrument_history' => 'تاريخ الصك',
        'instrument_history_day' => 'يوم تاريخ الصك',
        'instrument_history_month' => 'شهر تاريخ الصك',
        'instrument_history_year' => 'سنة تاريخ الصك',
        'type_date_first_registration' => 'نوع تقويم تاريخ أول تسجيل',
        'real_estate_registry_number' => 'رقم السجل العقاري',
        'date_first_registration' => 'تاريخ أول تسجيل',
        'date_first_registration_day' => 'يوم تاريخ أول تسجيل',
        'date_first_registration_month' => 'شهر تاريخ أول تسجيل',
        'date_first_registration_year' => 'سنة تاريخ أول تسجيل',
        'instrument_number' => 'رقم الصك',
        'number_of_floors' => 'عدد الأدوار',
        'lat' => 'خط العرض',
        'lng' => 'خط الطول',
        'tenant_dob_day' => 'يوم تاريخ ميلاد المستأجر',
        'tenant_dob_month' => 'شهر تاريخ ميلاد المستأجر',
        'tenant_dob_year' => 'سنة تاريخ ميلاد المستأجر',
        'type_tenant_dob' => 'نوع تقويم تاريخ ميلاد المستأجر',
        'type_dob_tenant_agent' => 'نوع تقويم تاريخ ميلاد وكيل المستأجر',
        'region_of_the_tenant_legal_agent' => 'منطقة ممثل المستأجر',
       // 'city_of_the_tenant_legal_agent' => 'مدينة ممثل المستأجر',
        'copy_of_the_owner_record' => 'نسخة سجل المالك',
        'dobof_property_tenant_agent' => 'تاريخ ميلاد وكيل المستأجر',
        'dobof_property_tenant_agent_day' => 'يوم تاريخ ميلاد وكيل المستأجر',
        'dobof_property_tenant_agent_month' => 'شهر تاريخ ميلاد وكيل المستأجر',
        'dobof_property_tenant_agent_year' => 'سنة تاريخ ميلاد وكيل المستأجر',
        'real_estates_units_id' => 'العقار',
        'The_number_of_toilets' => 'عدد دورات المياه',
        'kitchen_cabinets' => 'خزائن المطبخ',
        'unit_usage_id' => 'استخدام الوحدة',
        'window_ac' => 'عدد مكيفات الشباك',
        'split_ac' => 'عدد مكيفات السبليت',
        'kitchen_tank' => 'خزان المطبخ',
        'furnished' => 'مفروشة',
        'type_furnished' => 'نوع التأثيث',
        'electricity_meter' => 'عداد الكهرباء',
        'water_meter' => 'عداد الماء',
        'electricity_meter_ownership' => 'ملكية عداد الكهرباء',
        'water_meter_ownership' => 'ملكية عداد المياه',
        'is_draft' => 'حالة المسودة',
        'The_number_of_halls' => 'عدد الصالات',
        'number_of_councils' => 'عدد المجالس',
        'The_number_of_kitchens' => 'عدد المطابخ',
        'The_number_of_the_toilet' => 'عدد دورات المياه',
        'type_contract_starting_date' => 'نوع تقويم تاريخ بداية العقد',
        'duration_preset' => 'مدة العقد',
        'duration_years' => 'عدد سنوات العقد',
        'duration_months' => 'أشهر مدة العقد الإضافية',
        'conditions' => 'الشروط الإضافية',
        'other_conditions' => 'الشروط الأخرى',
        'other_conditions_list' => 'قائمة الشروط الأخرى',
        'tenant_role_ids.*' => 'صفة المستأجر',
    ];

    /**
     * @var array<string, string>
     */
    private const RULE_TEMPLATES = [
        'required' => ':attribute مطلوب.',
        'exists' => ':attribute غير موجود.',
        'in' => 'قيمة :attribute غير صالحة.',
        'integer' => ':attribute يجب أن يكون رقماً صحيحاً.',
        'numeric' => ':attribute يجب أن يكون رقماً.',
        'boolean' => ':attribute يجب أن يكون نعم أو لا.',
        'string' => ':attribute يجب أن يكون نصاً.',
        'array' => ':attribute يجب أن يكون قائمة.',
        'image' => ':attribute يجب أن يكون ملف صورة.',
        'file' => ':attribute يجب أن يكون ملفاً.',
        'date' => ':attribute يجب أن يكون تاريخاً صالحاً.',
        'max' => ':attribute يجب ألا يزيد عن :max.',
        'min' => ':attribute يجب ألا يقل عن :min.',
        'regex' => 'صيغة :attribute غير صحيحة.',
        'mimes' => ':attribute يجب أن يكون بصيغة: :values.',
        'required_if' => ':attribute مطلوب في هذه الحالة.',
    ];

    /**
     * @var array<string, string>
     */
    private const FIELD_OVERRIDES = [
        'property_owner_mobile.regex' => 'رقم جوال المالك يجب أن يبدأ بـ 5 ويتكون من 9 أرقام.',
        'property_owner_mobile.min' => 'رقم جوال المالك يجب ألا يقل عن 9 أرقام.',
        'mobile_of_property_owner_agent.regex' => 'رقم جوال وكيل المالك يجب أن يبدأ بـ 5 ويتكون من 9 أرقام.',
        'mobile_of_property_owner_agent.min' => 'رقم جوال وكيل المالك يجب ألا يقل عن 9 أرقام.',
        'tenant_mobile.regex' => 'رقم جوال المستأجر يجب أن يبدأ بـ 5 ويتكون من 9 أرقام.',
        'tenant_mobile.min' => 'رقم جوال المستأجر يجب ألا يقل عن 9 أرقام.',
        'mobile_of_property_tenant_agent.regex' => 'رقم جوال وكيل المستأجر يجب أن يبدأ بـ 5 ويتكون من 9 أرقام.',
        'mobile_of_property_tenant_agent.min' => 'رقم جوال وكيل المستأجر يجب ألا يقل عن 9 أرقام.',
        'property_owner_id_num.min' => 'رقم هوية المالك يجب ألا يقل عن 10 أرقام.',
        'property_owner_id_num.regex' => 'رقم هوية المالك يجب أن يتكون من 10 أرقام ويبدأ بـ 1 أو 2.',
        'id_num_of_property_owner_agent.regex' => 'رقم هوية وكيل المالك يجب أن يتكون من 10 أرقام ويبدأ بـ 1 أو 2.',
        'id_num_of_property_tenant_agent.regex' => 'رقم هوية وكيل المستأجر يجب أن يتكون من 10 أرقام ويبدأ بـ 1 أو 2.',
        'tenant_id_num.regex' => 'رقم هوية المستأجر يجب أن يتكون من 10 أرقام ويبدأ بـ 1 أو 2.',
        'authorization_type.in' => 'نوع التوكيل غير صالح.',
        'tenant_entity_unified_registry_number.regex' => 'الرقم الموحد للسجل يجب أن يتكون من 10 أرقام ويبدأ بـ 7.',
        'copy_of_the_owner_record.max' => 'حجم صورة السجل يجب ألا يتجاوز 10 ميجابايت.',
        'copy_of_the_owner_record.file' => 'صورة السجل يجب أن تكون ملفاً.',
        'id_num_of_property_owner_agent.min' => 'رقم هوية وكيل المالك يجب ألا يقل عن 10 أرقام.',
        'id_num_of_property_tenant_agent.min' => 'رقم هوية وكيل المستأجر يجب ألا يقل عن 10 أرقام.',
        'tenant_id_num.min' => 'رقم هوية المستأجر يجب ألا يقل عن 10 أرقام.',
        'property_owner_iban.min' => 'رقم آيبان المالك يجب ألا يقل عن 22 حرفاً.',
        'instrument_type.in' => 'نوع الصك غير صالح.',
        'contract_type.in' => 'نوع العقد غير صالح (سكني أو تجاري).',
        'tenant_entity.in' => 'كيان المستأجر يجب أن يكون شخصاً أو مؤسسة.',
        'tenant_roles.boolean' => 'صلاحيات المستخدم يجب أن تكون true أو false أو 1 أو 0.',
        'other_conditions.required_if' => 'حقل الشروط الأخرى مطلوب عند تفعيل الشروط الإضافية.',
        'other_conditions_list.required' => 'أضف شرطاً واحداً على الأقل في الشروط الأخرى.',
        'other_conditions_list.min' => 'أضف شرطاً واحداً على الأقل في الشروط الأخرى.',
        'copy_of_the_endowment_registration_certificate.mimes' => 'نسخة شهادة تسجيل الوقف يجب أن تكون بصيغة jpg أو jpeg أو png أو pdf.',
        'copy_of_the_trusteeship_deed.mimes' => 'نسخة صك الولاية يجب أن تكون بصيغة jpg أو jpeg أو png أو pdf.',
        'copy_of_the_owner_record.mimes' => 'نسخة السجل يجب أن تكون بصيغة jpg أو jpeg أو png أو pdf.',
        'copy_of_the_authorization_or_agency.mimes' => 'نسخة التوكيل يجب أن تكون بصيغة jpg أو jpeg أو png أو pdf.',
        'image_instrument.required' => 'صورة الصك مطلوبة.',
        'image_instrument.mimes' => 'ملف الصك يجب أن يكون بصيغة jpg أو jpeg أو png أو webp أو pdf.',
        'image_address.image' => 'صورة العنوان يجب أن تكون ملف صورة.',
        'property_type_id.required_if' => 'نوع العقار مطلوب عند اختيار صك إلكتروني أو سجل عقاري.',
        'property_usages_id.required_if' => 'استخدام العقار مطلوب عند اختيار صك إلكتروني أو سجل عقاري.',
        'number_of_units_in_realestate.required' => 'عدد الوحدات في العقار مطلوب.',
        'number_of_units_in_realestate.integer' => 'عدد الوحدات يجب أن يكون رقماً صحيحاً.',
        'number_of_floors.required' => 'عدد الأدوار مطلوب.',
        'real_id.required' => 'العقار مطلوب عند اختيار عقد على عقار مسجل.',
        'real_units_id.required' => 'وحدة العقار مطلوبة عند اختيار عقد على عقار مسجل.',
        'unit_ids.required' => 'يجب اختيار وحدة واحدة على الأقل من العقار.',
        'unit_ids.min' => 'يجب اختيار وحدة واحدة على الأقل من العقار.',
        'tenant_id_num.required_if' => 'رقم هوية المستأجر مطلوب عندما يكون الكيان شخصاً.',
        'tenant_dob_day.required_if' => 'يوم تاريخ ميلاد المستأجر مطلوب عندما يكون الكيان شخصاً.',
        'tenant_dob_month.required_if' => 'شهر تاريخ ميلاد المستأجر مطلوب عندما يكون الكيان شخصاً.',
        'tenant_dob_year.required_if' => 'سنة تاريخ ميلاد المستأجر مطلوبة عندما يكون الكيان شخصاً.',
        'tenant_mobile.required_if' => 'رقم جوال المستأجر مطلوب عندما يكون الكيان شخصاً.',
        'region_of_the_tenant_legal_agent.required_if' => 'منطقة المستأجر مطلوبة عندما يكون الكيان مؤسسة.',
      //  'city_of_the_tenant_legal_agent.required_if' => 'مدينة المستأجر مطلوبة عندما يكون الكيان مؤسسة.',
        'tenant_entity_unified_registry_number.required_if' => 'الرقم الموحد للسجل مطلوب عندما يكون الكيان مؤسسة.',
        'authorization_type.required_if' => 'نوع التوكيل مطلوب عندما يكون الكيان مؤسسة.',
        'id_num_of_property_owner_agent.required_if' => 'رقم هوية وكيل المالك مطلوب عند وجود وكيل.',
        'dob_of_property_owner_agent_day.required_if' => 'يوم تاريخ ميلاد وكيل المالك مطلوب عند وجود وكيل.',
        'dob_of_property_owner_agent_month.required_if' => 'شهر تاريخ ميلاد وكيل المالك مطلوب عند وجود وكيل.',
        'dob_of_property_owner_agent_year.required_if' => 'سنة تاريخ ميلاد وكيل المالك مطلوبة عند وجود وكيل.',
        'mobile_of_property_owner_agent.required_if' => 'رقم جوال وكيل المالك مطلوب عند وجود وكيل.',
    ];

    /**
     * @return array<string, string>
     */
    public static function attributes(): array
    {
        $fromLang = trans('validation.attributes');

        return array_merge(
            is_array($fromLang) ? $fromLang : [],
            self::EXTRA_ATTRIBUTES
        );
    }

    /**
     * @param  list<string>  $fields
     * @param  array<string, string>  $extra
     * @return array<string, string>
     */
    public static function messagesFor(array $fields, array $extra = []): array
    {
        $attributes = self::attributes();
        $messages = [];

        foreach ($fields as $field) {
            $label = $attributes[$field] ?? self::humanize($field);

            foreach (self::RULE_TEMPLATES as $rule => $template) {
                $messages["{$field}.{$rule}"] = str_replace(':attribute', $label, $template);
            }
        }

        foreach ($fields as $field) {
            foreach (self::FIELD_OVERRIDES as $key => $message) {
                if (str_starts_with($key, "{$field}.")) {
                    $messages[$key] = $message;
                }
            }
        }

        return array_merge($messages, $extra);
    }

    private static function humanize(string $field): string
    {
        return str_replace('_', ' ', $field);
    }
}
