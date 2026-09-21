<?php

namespace Tests\Feature\Employees;

use App\Modules\Employees\Controllers\Admin\EmployeeController;
use App\Modules\Employees\Controllers\Admin\EmployeeKpiController;
use App\Modules\Employees\Controllers\Admin\PermissionController;
use App\Modules\Employees\Controllers\Admin\RoleController;
use Tests\TestCase;

class EmployeesRouteContractTest extends TestCase
{
    public function test_employee_crud_and_kpi_routes_use_the_employees_module(): void
    {
        $crud = [
            'GET api/admin/employees' => ['employees.index', 'permission:employees.view', 'index', EmployeeController::class],
            'GET api/admin/employees/employee-salary' => ['employees.employee-salary', 'permission:employee_salaries.view', 'employeeSalary', EmployeeController::class],
            'POST api/admin/employees/{id}/salary' => ['employees.salary.store', 'permission:employee_salaries.create', 'storeSalary', EmployeeController::class],
            'GET api/admin/employees/kpis' => ['employees.kpis.index', 'permission:employee_kpis.view', 'index', EmployeeKpiController::class],
            'GET api/admin/employees/me/kpis' => ['employees.kpis.me', null, 'me', EmployeeKpiController::class],
            'GET api/admin/roles' => ['roles.index', 'permission:roles.view', 'index', RoleController::class],
            'GET api/admin/permissions' => ['permissions.index', 'permission:permissions.view', 'index', PermissionController::class],
        ];

        foreach ($crud as $key => [$name, $permission, $action, $controller]) {
            $this->assertSame(1, $this->countRouteKey($key), $key);
            $route = $this->routeByKey($key);
            $this->assertSame($name, $route->getName(), $key);
            $this->assertContains('auth:sanctum', $route->gatherMiddleware(), $key);
            if ($permission) {
                $this->assertContains($permission, $route->gatherMiddleware(), $key);
            }
            $this->assertSame($controller, $route->getControllerClass(), $key);
            $this->assertSame($action, $route->getActionMethod(), $key);
        }
    }

    public function test_employee_session_stays_on_auth(): void
    {
        $route = $this->routeByKey('POST api/admin/employees/login');
        $this->assertSame(
            \App\Modules\Auth\Controllers\Admin\EmployeeSessionController::class,
            $route->getControllerClass()
        );
        $this->assertNotContains('auth:sanctum', $route->gatherMiddleware());
    }

    private function countRouteKey(string $key): int
    {
        return collect(\Illuminate\Support\Facades\Route::getRoutes())
            ->filter(fn ($route) => $this->routeKey($route) === $key)
            ->count();
    }

    private function routeByKey(string $key)
    {
        foreach (\Illuminate\Support\Facades\Route::getRoutes() as $route) {
            if ($this->routeKey($route) === $key) {
                return $route;
            }
        }

        $this->fail("Route {$key} is not registered.");
    }

    private function routeKey($route): string
    {
        $method = collect($route->methods())->first(fn (string $method): bool => $method !== 'HEAD');

        return "{$method} {$route->uri()}";
    }
}
