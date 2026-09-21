<?php

namespace App\Modules\Contracts\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContractResource;
use App\Http\Traits\Responser;
use App\Models\Contract;
use Illuminate\Http\Request;

class FilterContract extends Controller
{
    use Responser;

    public function filter(Request $request)
    {
        return app(OrderController::class)->orders($request);
    }

    public function allcontracts(Request $request)
    {
        $contractsQuery = Contract::query()->notDeleted()->reachedAdminOrderStep();

        $statusId = null;
        foreach (['status_id', 'contract_status_id', 'status'] as $key) {
            if ($request->filled($key) && is_numeric($request->input($key))) {
                $statusId = (int) $request->input($key);
                break;
            }
        }

        if ($statusId !== null) {
            $contractsQuery->where('contract_status_id', $statusId);
        }

        $isCompleted = null;
        if ($request->filled('incomplete') && in_array(strtolower((string) $request->input('incomplete')), ['1', 'true', 'yes', 'on'], true)) {
            $isCompleted = false;
        } elseif ($request->filled('complete') && in_array(strtolower((string) $request->input('complete')), ['1', 'true', 'yes', 'on'], true)) {
            $isCompleted = true;
        } elseif ($request->has('is_completed') && $request->query('is_completed') !== null && $request->query('is_completed') !== '') {
            $isCompleted = $request->boolean('is_completed');
        } elseif ($statusId === null && $request->filled('status')) {
            $status = strtolower((string) $request->status);
            if (in_array($status, ['incomplete', 'uncompleted', 'not_completed'], true)) {
                $isCompleted = false;
            } elseif (in_array($status, ['complete', 'completed'], true)) {
                $isCompleted = true;
            }
        }

        if ($isCompleted !== null) {
            $contractsQuery->where('is_completed', $isCompleted ? 1 : 0);
        }

        $createdAtFilter = $request->query('created_at');
        if ($createdAtFilter) {
            $contractsQuery = $contractsQuery->when(
                in_array($createdAtFilter, ['today', 'week', 'month', 'year']),
                function ($query) use ($createdAtFilter) {
                    $now = now();

                    $ranges = [
                        'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
                        'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
                        'month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
                        'year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
                    ];

                    [$start, $end] = $ranges[$createdAtFilter] ?? [null, null];

                    if ($start && $end) {
                        $query->whereBetween('created_at', [$start, $end]);
                    }
                }
            );
        }

        if ($request->filled('search')) {
            $contractsQuery->adminSearch($request->string('search')->toString());
        }

        $contracts = $contractsQuery->latest()->paginate($this->perPageFromRequest($request));

        return $this->paginatedApiResponse(
            $contracts,
            ContractResource::collection($contracts)
        );
    }
}
