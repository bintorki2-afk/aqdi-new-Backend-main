<?php

namespace Database\Seeders;

use App\Models\Question;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        $questions = [
            [
                'title_ar' => 'ما هي مدة توثيق العقد؟',
                'title_en' => 'How long does contract certification take?',
                'answer_ar' => 'عادةً تتم عملية التوثيق خلال 3-5 أيام عمل.',
                'answer_en' => 'Contract certification usually takes 3-5 business days.',
            ],
            [
                'title_ar' => 'ما هي المستندات المطلوبة لتوثيق العقد؟',
                'title_en' => 'What documents are required for contract certification?',
                'answer_ar' => 'تحتاج إلى هوية المستأجر والمؤجر، ورقة الملكية أو الإيجار، وصورة العقد.',
                'answer_en' => 'You need tenant and landlord IDs, property deed or rental proof, and contract copy.',
            ],
            [
                'title_ar' => 'هل يمكن إلغاء العقد بعد التوثيق؟',
                'title_en' => 'Can the contract be cancelled after certification?',
                'answer_ar' => 'نعم، وفقاً لبنود العقد والشروط المتفق عليها بين الطرفين.',
                'answer_en' => 'Yes, according to contract terms agreed upon by both parties.',
            ],
            [
                'title_ar' => 'ما هو الفرق بين العقد السكني والتجاري؟',
                'title_en' => 'What is the difference between housing and commercial contract?',
                'answer_ar' => 'العقد السكني لعقارات السكن، والتجاري للعقارات المستخدمة في النشاط التجاري.',
                'answer_en' => 'Housing contract is for residential properties, commercial for business use.',
            ],
            [
                'title_ar' => 'كيف أتابع حالة عقدي؟',
                'title_en' => 'How can I track my contract status?',
                'answer_ar' => 'يمكنك متابعة العقد من خلال التطبيق أو الموقع باستخدام رقم المرجع.',
                'answer_en' => 'You can track your contract via the app or website using the reference number.',
            ],
            [
                'title_ar' => 'ما هي رسوم التوثيق؟',
                'title_en' => 'What are the certification fees?',
                'answer_ar' => 'تختلف الرسوم حسب نوع العقد والمدة، ويمكنك الاطلاع عليها في صفحة تسعير الخدمات.',
                'answer_en' => 'Fees vary by contract type and duration. See the pricing page for details.',
            ],
        ];

        foreach ($questions as $q) {
            Question::updateOrCreate(
                ['title_ar' => $q['title_ar']],
                $q
            );
        }
    }
}
