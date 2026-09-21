<?php

namespace App\Modules\Content\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Models\MessageAlert;
use App\Models\MessageAlertSection;
use App\Models\Page;
use App\Models\PaymentType;
use App\Support\MessageAlertType;

class AppContentOverviewController extends Controller
{
    use Responser;

    /**
     * Dashboard cards: payment methods, legal pages, customer application messages.
     *
     * GET /api/admin/app-content/overview
     */
    public function overview()
    {
        try {
            $terms = Page::query()->where('page', 'term_and_condition')->first();
            $privacy = Page::query()->where('page', 'privacy')->first();

            $clientMessagesCount = MessageAlert::query()
                ->whereHas('sectionItem.section', fn ($q) => $q->where('type', MessageAlertType::CLIENT))
                ->count();

            return $this->apiResponse([
                'sections' => [
                    [
                        'key' => 'payment_types',
                        'label_ar' => 'طرق الدفع',
                        'label_en' => 'Payment methods',
                        'count' => PaymentType::query()->count(),
                        'routes' => [
                            'list' => 'GET /api/admin/payment-types',
                            'create' => 'POST /api/admin/payment-types',
                            'show' => 'GET /api/admin/payment-types/{id}',
                            'update' => 'POST /api/admin/payment-types/{id}',
                            'delete' => 'POST /api/admin/payment-types/{id}/delete',
                        ],
                    ],
                    [
                        'key' => 'terms_and_conditions',
                        'label_ar' => 'الشروط والأحكام',
                        'label_en' => 'Terms and conditions',
                        'has_content' => $terms !== null && trim((string) $terms->description_ar) !== '',
                        'routes' => [
                            'show' => 'GET /api/admin/content/terms-and-conditions',
                            'update' => 'POST /api/admin/content/terms-and-conditions',
                        ],
                    ],
                    [
                        'key' => 'privacy_policy',
                        'label_ar' => 'سياسة الخصوصية',
                        'label_en' => 'Privacy policy',
                        'has_content' => $privacy !== null && trim((string) $privacy->description_ar) !== '',
                        'routes' => [
                            'show' => 'GET /api/admin/content/privacy',
                            'update' => 'POST /api/admin/content/privacy',
                        ],
                    ],
                    [
                        'key' => 'customer_messages',
                        'label_ar' => 'رسائل التطبيق للعميل',
                        'label_en' => 'Customer application messages',
                        'count' => $clientMessagesCount,
                        'sections_count' => MessageAlertSection::query()
                            ->where('type', MessageAlertType::CLIENT)
                            ->count(),
                        'routes' => [
                            'list' => 'GET /api/admin/customer-messages',
                            'all' => 'GET /api/admin/customer-messages/all',
                            'create_form' => 'GET /api/admin/customer-messages/create',
                            'create' => 'POST /api/admin/customer-messages',
                            'show' => 'GET /api/admin/customer-messages/{id}',
                            'update' => 'POST /api/admin/customer-messages/{id}',
                            'delete' => 'POST /api/admin/customer-messages/{id}/delete',
                        ],
                    ],
                ],
            ], trans('api.success'));
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }
}
