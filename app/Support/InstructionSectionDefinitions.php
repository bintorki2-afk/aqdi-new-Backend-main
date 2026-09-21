<?php

namespace App\Support;

/**
 * Predefined instructional image sections (seeded once). Keys are stable identifiers
 * used by the mobile app when calling GET /api/instruction-images/{key}.
 */
final class InstructionSectionDefinitions
{
    /**
     * @return list<array{key: string, title_ar: string, description_ar: string, sort_order: int}>
     */
    public static function predefined(): array
    {
        return [
            ['key' => 'new-client', 'title_ar' => 'عميل جديد', 'description_ar' => 'صورة تظهر للعملاء - الجدد', 'sort_order' => 1],
            ['key' => 'start', 'title_ar' => 'بداية', 'description_ar' => 'صورة تظهر في شاشة البداية', 'sort_order' => 2],
            ['key' => 'create-residential-contract', 'title_ar' => 'إنشاء عقد سكني', 'description_ar' => 'صورة تظهر عند إنشاء عقد سكني', 'sort_order' => 3],
            ['key' => 'create-commercial-contract', 'title_ar' => 'إنشاء عقد تجاري', 'description_ar' => 'صورة تظهر عند إنشاء عقد تجاري', 'sort_order' => 4],
            ['key' => 'my-real-estate', 'title_ar' => 'عقاراتي', 'description_ar' => 'صورة تظهر في قائمة عقاراتي', 'sort_order' => 5],
            ['key' => 'units', 'title_ar' => 'الوحدات', 'description_ar' => 'بعد الضغط على إنشاء وحدة', 'sort_order' => 6],
            ['key' => 'deed', 'title_ar' => 'الصك', 'description_ar' => 'صورة تظهر في خطوة الصك', 'sort_order' => 7],
            ['key' => 'address', 'title_ar' => 'العنوان', 'description_ar' => 'صورة تظهر في خطوة العنوان', 'sort_order' => 8],
            ['key' => 'owner', 'title_ar' => 'المالك', 'description_ar' => 'صورة تظهر في خطوة بيانات المالك', 'sort_order' => 9],
            ['key' => 'tenant', 'title_ar' => 'المستأجر', 'description_ar' => 'صورة تظهر في خطوة بيانات المستأجر', 'sort_order' => 10],
            ['key' => 'real-estate', 'title_ar' => 'العقار', 'description_ar' => 'صورة تظهر في خطوة بيانات العقار', 'sort_order' => 11],
            ['key' => 'instrument', 'title_ar' => 'نوع السند', 'description_ar' => 'صورة تظهر عند اختيار نوع السند', 'sort_order' => 12],
            ['key' => 'agent', 'title_ar' => 'الوكيل', 'description_ar' => 'صورة تظهر في خطوة الوكيل', 'sort_order' => 13],
            ['key' => 'authorization', 'title_ar' => 'التفويض', 'description_ar' => 'صورة تظهر في خطوة التفويض', 'sort_order' => 14],
            ['key' => 'endowment', 'title_ar' => 'الوقف', 'description_ar' => 'صورة تظهر في خطوة الوقف', 'sort_order' => 15],
            ['key' => 'financial-data', 'title_ar' => 'البيانات المالية', 'description_ar' => 'صورة تظهر في خطوة البيانات المالية', 'sort_order' => 16],
            ['key' => 'payment-completion', 'title_ar' => 'إتمام الدفع', 'description_ar' => 'صورة تظهر بعد إتمام الدفع', 'sort_order' => 17],
            ['key' => 'requests', 'title_ar' => 'الطلبات', 'description_ar' => 'صورة تظهر في قسم الطلبات', 'sort_order' => 18],
            ['key' => 'documentation', 'title_ar' => 'التوثيق', 'description_ar' => 'صورة تظهر في خطوة التوثيق', 'sort_order' => 19],
            ['key' => 'contract-review', 'title_ar' => 'مراجعة العقد', 'description_ar' => 'صورة تظهر قبل إرسال العقد', 'sort_order' => 20],
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_column(self::predefined(), 'key');
    }
}
