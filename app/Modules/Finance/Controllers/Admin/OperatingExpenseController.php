<?php

namespace App\Modules\Finance\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\V2\Api\OperatingExpenseResource;
use App\Http\Traits\Responser;
use App\Models\OperatingExpense;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class OperatingExpenseController extends Controller
{
    use Responser;

    public function index(Request $request)
    {
        try {
            $query = OperatingExpense::query();

            if ($request->filled('search')) {
                $search = $request->string('search');
                $query->where('expense', 'like', "%{$search}%");
            }

            $createdAtFilter = $request->query('created_at');
            if (in_array($createdAtFilter, ['today', 'week', 'month', 'year'], true)) {
                $now = now();
                $ranges = [
                    'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
                    'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
                    'month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
                    'year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
                ];
                [$start, $end] = $ranges[$createdAtFilter];
                $query->whereBetween('created_at', [$start, $end]);
            }

            $expenses = (clone $query)->latest()->paginate($this->perPageFromRequest($request));

            return $this->paginatedApiResponse(
                $expenses,
                OperatingExpenseResource::collection($expenses),
                trans('api.success'),
                [
                    'summary' => [
                        'count' => (int) (clone $query)->count(),
                        'total_amount' => (float) (clone $query)->sum('amount'),
                    ],
                ]
            );
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $this->normalizeExpenseName($request);
            $expense = OperatingExpense::query()->create(
                $request->validate($this->rules())
            );

            return $this->apiResponse(
                new OperatingExpenseResource($expense),
                trans('api.created_successfully'),
                201
            );
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function show(int $id)
    {
        try {
            $expense = OperatingExpense::query()->findOrFail($id);

            return $this->apiResponse(
                new OperatingExpenseResource($expense),
                trans('api.success')
            );
        } catch (ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function update(Request $request, int $id)
    {
        try {
            $expense = OperatingExpense::query()->findOrFail($id);
            $this->normalizeExpenseName($request);
            $expense->update($request->validate($this->rules(true)));

            return $this->apiResponse(
                new OperatingExpenseResource($expense->fresh()),
                trans('api.updated_successfully')
            );
        } catch (ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function destroy(int $id)
    {
        try {
            $expense = OperatingExpense::query()->findOrFail($id);
            $expense->delete();

            return $this->apiResponse([], trans('api.deleted_successfully'));
        } catch (ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(bool $isUpdate = false): array
    {
        return [
            'expense' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'amount' => [$isUpdate ? 'sometimes' : 'required', 'numeric', 'min:0'],
        ];
    }

    private function normalizeExpenseName(Request $request): void
    {
        if (! $request->filled('expense') && $request->filled('name')) {
            $request->merge(['expense' => $request->input('name')]);
        }
    }
}
