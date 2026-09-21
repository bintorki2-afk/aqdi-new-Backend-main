<?php

namespace App\Http\Middleware;

use App\Http\Traits\Responser;
use App\Support\AuthenticatedEmployee;
use Closure;
use Illuminate\Http\Request;

class CheckEmployeePermission
{
    use Responser;

    /**
     * Require the authenticated employee's role to have every given "section.action" permission.
     * Admin employees bypass this check (full project access).
     */
    public function handle(Request $request, Closure $next, string ...$permissions)
    {
        $employee = AuthenticatedEmployee::from($request);

        if ($employee === null) {
            return $this->errorMessage(trans('api.unauthenticated'), 401);
        }

        foreach ($permissions as $permission) {
            if (! $employee->hasPermission($permission)) {
                return $this->errorMessage(trans('api.forbidden'), 403);
            }
        }

        return $next($request);
    }
}
