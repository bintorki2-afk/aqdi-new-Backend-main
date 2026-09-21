<?php

namespace App\Services\Admin\Analytics;

use App\Models\Contract;
use App\Models\ContractWhatsApp;
use App\Models\Payment;

class OrderAnalyticsService
{
    public function getOrderCompletionRate(): float
    {
        $total = Contract::where('is_delete', 0)->count();
        if ($total === 0) return 0;
        $completed = Contract::where('is_completed', 1)->where('is_delete', 0)->count();
        return round(($completed / $total) * 100, 2);
    }

    public function getOrderTransferAnalytics(): array
    {
        $base = fn() => Contract::where('is_delete', 0);
        $transitions = [
            ['label_ar' => 'بانتظار اعتماد الطلب - تم الترحيل'],
            ['label_ar' => 'تم تاكيد العقار - بانتظار اعتماد الطلب'],
            ['label_ar' => 'مطلوب اجراء من العميل - تم تاكيد العقار'],
            ['label_ar' => 'بانتظار تاكيد بيانات المالك - مطلوب اجراء من العميل'],
            ['label_ar' => 'قيد المعالجة - بانتظار تاكيد بيانات المالك'],
            ['label_ar' => 'طلب جديد - قيد المعالجة'],
        ];
        $conditions = [
            fn($q) => $q->where('is_completed', 1)->where('step', '>=', 6),
            fn($q) => $q->where('step', 5)->where('is_completed', 0),
            fn($q) => $q->where('step', 4)->where('is_completed', 0),
            fn($q) => $q->where('step', 3)->where('is_completed', 0),
            fn($q) => $q->where('step', 2)->where('is_completed', 0),
            fn($q) => $q->whereIn('step', [0, 1])->where('is_completed', 0),
        ];
        $result = [];
        foreach ($transitions as $i => $t) {
            $count = $conditions[$i]($base())->count();
            $result[] = ['label_ar' => $t['label_ar'], 'value' => $count, 'type' => 'count'];
        }
        return $result;
    }

    public function getOrderAnalyticsData(): array
    {
        return [
            ['label_ar' => 'الطلبات المكتملة', 'value' => Contract::where('is_completed', 1)->where('is_delete', 0)->count(), 'type' => 'count'],
            ['label_ar' => 'الطلبات غير المكتملة', 'value' => Contract::where('is_completed', 0)->where('is_delete', 0)->count(), 'type' => 'count'],
            ['label_ar' => 'طلبات واتساب مكتملة', 'value' => ContractWhatsApp::where('is_complete', true)->count(), 'type' => 'count'],
            ['label_ar' => 'طلبات واتساب غير مكتملة', 'value' => ContractWhatsApp::where('is_complete', false)->count(), 'type' => 'count'],
            ['label_ar' => 'معدل إكمال الطلبات', 'value' => $this->getOrderCompletionRate(), 'type' => 'percentage'],
            ['label_ar' => 'طلبات مسترجعه', 'value' => Payment::where('status', 'failed')->count(), 'type' => 'count'],
        ];
    }
}
