<?php

namespace App\Modules\Finance\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Models\Employee;
use App\Models\Expense;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    use Responser;

    /**
     * List expenses with optional created_at quick filters.
     */
    public function index(Request $request)
    {
        $expensesQuery = Expense::query();

        $createdAtFilter = $request->query('created_at');
        if ($createdAtFilter) {
            $expensesQuery = $expensesQuery->when(
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

        $expenses = $expensesQuery->latest()->paginate(20);
        
        return $this->apiResponse(
            [
                'items' => $expenses->items(),
                'pagination' => $this->paginate($expenses),
            ],
            trans('api.success')
        );
    }

    /**
     * Store a new expense.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'employee_id' => ['nullable', 'exists:employees,id'],
        ]);

        $expense = Expense::create($validated);

        return $this->apiResponse(
            $expense,
            trans('api.success'),
            201
        );
    }

    /**
     * View a single expense.
     */
    public function show(Expense $expense)
    {
        return $this->apiResponse(
            $expense,
            trans('api.success')
        );
    }

    /**
     * Update an expense.
     */
    public function update(Request $request, Expense $expense)
    {
        $validated = $request->validate([
            'amount' => ['sometimes', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'employee_id' => ['nullable', 'exists:employees,id'],
        ]);

        $expense->update($validated);

        return $this->apiResponse(
            $expense->fresh(),
            trans('api.success')
        );
    }

  
    /**
     * Delete an expense.
     */
    public function destroy(Expense $expense)
    {
        $expense->delete();

        return $this->apiResponse([], trans('api.success'));
    }
}
