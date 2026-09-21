<?php

namespace Database\Seeders;

use App\Models\Blog;
use App\Models\BlogTag;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            ['slug' => 'ijar', 'label_ar' => '#إيجار', 'label_en' => '#Ijar'],
            ['slug' => 'contracts', 'label_ar' => '#العقود', 'label_en' => '#Contracts'],
            ['slug' => 'documentation', 'label_ar' => '#توثيق', 'label_en' => '#Documentation'],
            ['slug' => 'tenant-rights', 'label_ar' => '#حقوق-المستأجر', 'label_en' => '#Tenant rights'],
            ['slug' => 'taxes', 'label_ar' => '#ضرائب', 'label_en' => '#Taxes'],
        ];

        foreach ($tags as $tag) {
            BlogTag::query()->updateOrCreate(['slug' => $tag['slug']], $tag);
        }

        $blogs = [
            [
                'title' => 'كيفية توثيق عقد الإيجار إلكترونياً',
                'excerpt' => 'توثيق عقد الإيجار إلكترونياً أصبح متاحاً الآن من خلال منصة عقدي دون الحاجة للانتظار أو مراجعة الجهات.',
                'description' => '<p>توثيق عقد الإيجار إلكترونياً أصبح متاحاً الآن من خلال منصة عقدي. تتيح لك الخدمة توثيق عقودك دون الحاجة للانتظار أو الذهاب إلى الجهات الرسمية. فقط قم برفع المستندات المطلوبة وسنقوم بإنجاز الإجراءات نيابة عنك.</p><p>الخطوات بسيطة: أنشئ حسابك، املأ بيانات العقد، ارفع المستندات، وادفع الرسوم الإلكترونية. سيصل لك العقد الموثق خلال 3-5 أيام عمل.</p>',
                'slug' => 'how-to-certify-rental-contract-online',
                'meta_title' => 'توثيق عقد الإيجار إلكترونياً - دليل شامل',
                'meta_description' => 'تعرف على خطوات توثيق عقد الإيجار إلكترونياً عبر منصة عقدي بكل سهولة وسرعة.',
                'status' => 'published',
                'publish_at' => Carbon::now()->subDays(6),
                'is_active' => 1,
                'is_featured' => true,
                'category' => 'contracts',
                'category_label_ar' => 'العقود',
                'author' => 'فريق عقدي',
                'tags' => ['ijar', 'documentation', 'contracts'],
            ],
            [
                'title' => 'حقوق وواجبات المستأجر والمؤجر',
                'excerpt' => 'يعرّف نظام الإيجار السعودي حقوق وواجبات كل من المستأجر والمؤجر بشكل واضح.',
                'description' => '<p>يعرّف نظام الإيجار السعودي حقوق وواجبات كل من المستأجر والمؤجر بشكل واضح. للمستأجر حق الانتفاع بالعقار للاستخدام المتفق عليه، بينما يتوجب على المؤجر توفير العقد المكتوب وتسليم العقار بحالة جيدة.</p><p>من الحقوق المهمة: حق التجديد، حق الصيانة، وعدم رفع الإيجار إلا وفق النظام. ومن الواجبات: الالتزام بدفع الإيجار، المحافظة على العقار، وإبلاغ المؤجر بأي تلف.</p>',
                'slug' => 'tenant-landlord-rights-obligations',
                'meta_title' => 'حقوق وواجبات المستأجر والمؤجر في السعودية',
                'meta_description' => 'دليل شامل لحقوق وواجبات المستأجر والمؤجر وفق نظام الإيجار السعودي.',
                'status' => 'published',
                'publish_at' => Carbon::now()->subDays(5),
                'is_active' => 1,
                'is_featured' => true,
                'category' => 'property-management',
                'category_label_ar' => 'إدارة العقارات',
                'author' => 'فريق عقدي',
                'tags' => ['ijar', 'tenant-rights'],
            ],
            [
                'title' => 'الفرق بين العقد السكني والتجاري',
                'excerpt' => 'العقد السكني يغطي عقارات السكن مثل الشقق والفلل، بينما العقد التجاري يختص بالمحلات والمكاتب.',
                'description' => '<p>العقد السكني يغطي عقارات السكن مثل الشقق والفلل، بينما العقد التجاري يختص بالمحلات والمكاتب والمستودعات. الرسوم والإجراءات تختلف بين النوعين.</p><p>العقد التجاري عادةً يكون بمبالغ أعلى ومدد أطول. كما أن الضرائب والمستندات المطلوبة قد تختلف. ننصح باختيار النوع المناسب لعقارك منذ البداية.</p>',
                'slug' => 'housing-vs-commercial-contract',
                'meta_title' => 'الفرق بين العقد السكني والتجاري',
                'meta_description' => 'ما الفرق بين عقد الإيجار السكني والتجاري؟ تعرف على الفروقات والإجراءات.',
                'status' => 'published',
                'publish_at' => Carbon::now()->subDays(4),
                'is_active' => 1,
                'is_featured' => false,
                'category' => 'contracts',
                'category_label_ar' => 'العقود',
                'author' => 'فريق عقدي',
                'tags' => ['contracts', 'ijar'],
            ],
            [
                'title' => 'المستندات المطلوبة لتوثيق العقد',
                'excerpt' => 'لتوثيق عقد الإيجار تحتاج إلى هوية الطرفين وإثبات ملكية العقار وصورة العقد الموقع.',
                'description' => '<p>لتوثيق عقد الإيجار تحتاج إلى: هوية المستأجر والمؤجر، ورقة الملكية أو إثبات ملكية العقار، وصورة العقد الموقع. للعقارات التجارية قد تُطلب رخصة تجارية.</p><p>تأكد من صلاحية المستندات ووضوحها. المستندات المرفوعة إلكترونياً يجب أن تكون بجودة جيدة وقابلة للقراءة. فريقنا سيراجع المستندات ويخبرك بأي نقص.</p>',
                'slug' => 'documents-required-contract-certification',
                'meta_title' => 'المستندات المطلوبة لتوثيق عقد الإيجار',
                'meta_description' => 'قائمة كاملة بالمستندات المطلوبة لتوثيق عقود الإيجار السكني والتجاري.',
                'status' => 'published',
                'publish_at' => Carbon::now()->subDays(3),
                'is_active' => 1,
                'is_featured' => false,
                'category' => 'guides',
                'category_label_ar' => 'أدلة',
                'author' => 'فريق عقدي',
                'tags' => ['documentation', 'contracts'],
            ],
            [
                'title' => 'نصائح لتجنب المشكلات في عقود الإيجار',
                'excerpt' => 'اكتب العقد بوضوح، حدد المدة والمبلغ بدقة، وثّق حالة العقار عند التسليم واحتفظ بنسخة موثقة.',
                'description' => '<p>لتجنب النزاعات مع المؤجر أو المستأجر، اتبع هذه النصائح: اكتب العقد بوضوح، حدد مدة الإيجار والمبلغ بدقة، وثّق حالة العقار عند التسليم، واحتفظ بنسخة موثقة من العقد.</p><p>التوثيق الإلكتروني يمنحك نسخة رسمية ومعتمدة تحمي حقوقك. لا تعتمد على الاتفاقيات الشفهية في العقود المهمة.</p>',
                'slug' => 'tips-avoid-rental-disputes',
                'meta_title' => 'نصائح لتجنب المشكلات في عقود الإيجار',
                'meta_description' => 'كيف تتجنب النزاعات والمشكلات في عقود الإيجار؟ نصائح عملية من خبراء.',
                'status' => 'published',
                'publish_at' => Carbon::now()->subDays(2),
                'is_active' => 1,
                'is_featured' => true,
                'category' => 'real-estate-market',
                'category_label_ar' => 'سوق العقارات',
                'author' => 'فريق عقدي',
                'tags' => ['ijar', 'tenant-rights'],
            ],
            [
                'title' => 'دليل الضرائب على عقود الإيجار',
                'excerpt' => 'عقود الإيجار في السعودية تخضع لضريبة القيمة المضافة بنسبة 15٪ مع اختلاف المعاملة بين السكني والتجاري.',
                'description' => '<p>عقود الإيجار في السعودية تخضع لضريبة القيمة المضافة بنسبة 15%. تختلف المعاملة بين السكني والتجاري من حيث الإعفاءات والإجراءات.</p><p>المنصة تحسب الضرائب تلقائياً عند إكمال إجراءات التوثيق. تأكد من إدخال البيانات الصحيحة للحصول على حساب دقيق للرسوم والضرائب.</p>',
                'slug' => 'tax-guide-rental-contracts',
                'meta_title' => 'دليل الضرائب على عقود الإيجار في السعودية',
                'meta_description' => 'كل ما تحتاج معرفته عن الضرائب المفروضة على عقود الإيجار السكني والتجاري.',
                'status' => 'draft',
                'publish_at' => null,
                'is_active' => 0,
                'is_featured' => false,
                'category' => 'guides',
                'category_label_ar' => 'أدلة',
                'author' => 'فريق عقدي',
                'tags' => ['taxes', 'contracts'],
            ],
        ];

        foreach ($blogs as $blog) {
            $tagSlugs = $blog['tags'] ?? [];
            unset($blog['tags']);

            $model = Blog::updateOrCreate(
                ['slug' => $blog['slug']],
                $blog
            );

            $model->syncTagsInput($tagSlugs);
        }
    }
}
