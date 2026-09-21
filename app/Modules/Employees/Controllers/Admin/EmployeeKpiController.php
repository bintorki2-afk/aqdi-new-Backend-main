<?php

namespace App\Modules\Employees\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Models\Employee;
use App\Services\Admin\EmployeeKpiService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class EmployeeKpiController extends Controller
{
    use Responser;

    public function __construct(
        protected EmployeeKpiService $kpis
    ) {}

    public function index(Request $request)
    {
        try {
            $filter = $this->kpis->resolvePeriodFilter($request);
            $viewerId = $request->user() instanceof Employee ? (int) $request->user()->id : null;
            $items = $this->kpis->forAllEmployees($filter, $viewerId);

            return $this->apiResponse([
                'periods' => $this->kpis->periodTabs($filter['key']),
                'period' => $filter['key'],
                'date_from' => $filter['date_from'],
                'date_to' => $filter['date_to'],
                'summary' => $this->kpis->summarize($items),
                'items' => $items,
            ], trans('api.success'));
        } catch (InvalidArgumentException $e) {
            return $this->errorMessage($e->getMessage(), 422);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function me(Request $request)
    {
        try {
            $employee = $request->user();
            if (! $employee instanceof Employee) {
                return $this->errorMessage(trans('api.unauthorized'), 403);
            }

            return $this->showPayload($request, $employee);
        } catch (InvalidArgumentException $e) {
            return $this->errorMessage($e->getMessage(), 422);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function show(Request $request, int $id)
    {
        try {
            $employee = Employee::query()->with('roleRelation')->findOrFail($id);

            return $this->showPayload($request, $employee);
        } catch (InvalidArgumentException $e) {
            return $this->errorMessage($e->getMessage(), 422);
        } catch (ModelNotFoundException) {
            return $this->errorMessage(trans('api.employee_not_found'), 404);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function details(Request $request, int $id)
    {
        return $this->show($request, $id);
    }

    private function showPayload(Request $request, Employee $employee)
    {
        $employee->loadMissing('roleRelation');
        $filter = $this->kpis->resolvePeriodFilter($request);
        $viewerId = $request->user() instanceof Employee ? (int) $request->user()->id : null;
        $detail = $this->kpis->forEmployee($employee, $filter, $viewerId, true);

        return $this->apiResponse(array_merge([
            'periods' => $this->kpis->periodTabs($filter['key']),
            'date_from' => $filter['date_from'],
            'date_to' => $filter['date_to'],
        ], $detail), trans('api.success'));
    }
}
