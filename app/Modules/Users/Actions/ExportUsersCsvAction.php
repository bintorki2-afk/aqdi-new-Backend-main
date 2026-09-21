<?php

namespace App\Modules\Users\Actions;

use App\Modules\Users\Support\UsersDashboardQuery;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportUsersCsvAction
{
    public function __construct(private readonly UsersDashboardQuery $dashboard) {}

    public function execute(Request $request): StreamedResponse
    {
        $query = $this->dashboard->make($request, false)->latest('users.id');

        if ($request->filled('page')) {
            $users = $query->paginate($this->perPage($request))->getCollection();
        } else {
            $users = $query->limit(10000)->get();
        }

        $filename = 'clients-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($users) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'رقم العميل',
                'الاسم',
                'البريد',
                'الجوال',
                'المنصة',
                'الحالة',
                'مكتمل',
                'مسودة',
                'غير مكتمل',
                'العقارات',
                'الوحدات',
                'المدفوع',
                'المسترجع',
                'الصافي',
                'تاريخ الانضمام',
            ]);

            foreach ($users as $user) {
                $paid = round((float) ($user->total_paid_amount ?? 0), 2);
                $refunded = round((float) ($user->total_refunded_amount ?? 0), 2);
                fputcsv($handle, [
                    $user->customerNumber(),
                    $user->name,
                    $user->email,
                    $user->mobile,
                    $user->platformLabelAr(),
                    $user->is_active ? 'نشط' : 'محظور',
                    (int) ($user->completed_orders_count ?? 0),
                    (int) ($user->draft_orders_count ?? 0),
                    (int) ($user->incomplete_orders_count ?? 0),
                    (int) ($user->real_estate_count ?? 0),
                    (int) ($user->units_count ?? 0),
                    $paid,
                    $refunded,
                    round($paid - $refunded, 2),
                    $user->created_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function perPage(Request $request): int
    {
        return min(max((int) $request->input('per_page', 25), 1), 100);
    }
}
