<?php

namespace App\Support;

final class WebsiteImageDefinitions
{
    /**
     * Seeded website assets editable from the admin panel.
     *
     * @return list<array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            [
                'key' => 'logo',
                'label_ar' => 'شعار الموقع',
                'label_en' => 'Website logo',
                'static_path' => 'website/asset/images/logo.svg',
                'alt_ar' => 'شعار أقدي',
                'alt_en' => 'Aqdi logo',
                'meta_title_ar' => 'أقدي',
                'meta_description_ar' => 'شعار منصة أقدي لتوثيق عقود الإيجار إلكترونيًا',
                'sort_order' => 10,
            ],
            [
                'key' => 'favicon',
                'label_ar' => 'أيقونة المتصفح',
                'label_en' => 'Favicon',
                'static_path' => 'website/asset/images/favicon.jpg',
                'alt_ar' => 'أيقونة أقدي',
                'alt_en' => 'Aqdi favicon',
                'sort_order' => 20,
            ],
            [
                'key' => 'login-hero',
                'label_ar' => 'صورة تسجيل الدخول',
                'label_en' => 'Login hero',
                'static_path' => 'website/asset/images/hero.png',
                'alt_ar' => 'واجهة توثيق العقود',
                'alt_en' => 'Contract documentation hero',
                'meta_title_ar' => 'تسجيل الدخول — أقدي',
                'meta_description_ar' => 'سجّل الدخول لتوثيق عقد الإيجار إلكترونيًا عبر منصة أقدي',
                'sort_order' => 30,
            ],
            [
                'key' => 'landing-banner',
                'label_ar' => 'بانر الصفحة التعريفية',
                'label_en' => 'Landing banner',
                'static_path' => 'website/asset/images/30-min.png',
                'alt_ar' => 'وثّق عقدك خلال 30 دقيقة',
                'alt_en' => 'Document your contract in 30 minutes',
                'meta_title_ar' => 'توثيق عقد إيجار خلال 30 دقيقة',
                'meta_description_ar' => 'خدمة توثيق عقود الإيجار إلكترونيًا عبر منصة إيجار خلال 30 دقيقة',
                'sort_order' => 40,
            ],
            [
                'key' => 'home-create-contract',
                'label_ar' => 'الرئيسية — إنشاء عقد',
                'label_en' => 'Home — create contract',
                'static_path' => 'website/asset/images/createcontract.svg',
                'alt_ar' => 'إنشاء عقد إيجار',
                'alt_en' => 'Create rental contract',
                'sort_order' => 50,
            ],
            [
                'key' => 'home-choose-contract',
                'label_ar' => 'الرئيسية — اختيار نوع العقد',
                'label_en' => 'Home — choose contract type',
                'static_path' => 'website/asset/images/choosecontract.svg',
                'alt_ar' => 'اختيار نوع العقد',
                'alt_en' => 'Choose contract type',
                'sort_order' => 60,
            ],
            [
                'key' => 'ejar-icon',
                'label_ar' => 'أيقونة إيجار',
                'label_en' => 'Ejar icon',
                'static_path' => 'website/asset/images/ejar-icon.svg',
                'alt_ar' => 'منصة إيجار',
                'alt_en' => 'Ejar platform',
                'sort_order' => 70,
            ],
            [
                'key' => 'whatsapp',
                'label_ar' => 'أيقونة واتساب',
                'label_en' => 'WhatsApp icon',
                'static_path' => 'website/asset/images/whatsapp-icon.svg',
                'alt_ar' => 'تواصل عبر واتساب',
                'alt_en' => 'Contact via WhatsApp',
                'sort_order' => 80,
            ],
            [
                'key' => 'footer-whatsapp',
                'label_ar' => 'تذييل — واتساب',
                'label_en' => 'Footer — WhatsApp',
                'static_path' => 'website/asset/images/whatsapp-footer-icon.svg',
                'alt_ar' => 'واتساب',
                'alt_en' => 'WhatsApp',
                'sort_order' => 90,
            ],
            [
                'key' => 'footer-tiktok',
                'label_ar' => 'تذييل — تيك توك',
                'label_en' => 'Footer — TikTok',
                'static_path' => 'website/asset/images/tiktok-footer-icon.svg',
                'alt_ar' => 'تيك توك',
                'alt_en' => 'TikTok',
                'sort_order' => 100,
            ],
            [
                'key' => 'footer-x',
                'label_ar' => 'تذييل — إكس',
                'label_en' => 'Footer — X',
                'static_path' => 'website/asset/images/x-footer-icon.svg',
                'alt_ar' => 'إكس (تويتر)',
                'alt_en' => 'X (Twitter)',
                'sort_order' => 110,
            ],
            [
                'key' => 'success-icon',
                'label_ar' => 'أيقونة النجاح',
                'label_en' => 'Success icon',
                'static_path' => 'website/asset/images/success-icon.svg',
                'alt_ar' => 'تمت العملية بنجاح',
                'alt_en' => 'Success',
                'sort_order' => 120,
            ],
            [
                'key' => 'about-buyer',
                'label_ar' => 'من نحن — المستأجر',
                'label_en' => 'About — tenant',
                'static_path' => 'website/asset/images/whobuy.svg',
                'alt_ar' => 'للمستأجرين',
                'alt_en' => 'For tenants',
                'sort_order' => 130,
            ],
            [
                'key' => 'about-seller',
                'label_ar' => 'من نحن — المؤجر',
                'label_en' => 'About — landlord',
                'static_path' => 'website/asset/images/whosell.svg',
                'alt_ar' => 'للمؤجرين',
                'alt_en' => 'For landlords',
                'sort_order' => 140,
            ],
            [
                'key' => 'about-middle',
                'label_ar' => 'من نحن — الوسطاء',
                'label_en' => 'About — brokers',
                'static_path' => 'website/asset/images/whomiddle.svg',
                'alt_ar' => 'للوسطاء العقاريين',
                'alt_en' => 'For real-estate brokers',
                'sort_order' => 150,
            ],
        ];
    }
}
