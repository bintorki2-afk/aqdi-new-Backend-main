<?php

namespace App\Support;

use App\Modules\Employees\Models\Employee;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Throwable;

class AuthenticatedEmployee
{
    public static function from(Request $request): ?Employee
    {
        $employee = self::cast($request->user())
            ?? self::cast($request->user('sanctum'))
            ?? self::cast(Auth::guard('sanctum')->user())
            ?? self::cast(Auth::user());

        if ($employee === null) {
            $employee = self::fromBearerToken($request);
        }

        if ($employee === null) {
            return null;
        }

        $employee->loadMissing('roleRelation');
        self::bind($request, $employee);

        return $employee;
    }

    public static function bind(Request $request, Employee $employee): void
    {
        Auth::guard('sanctum')->setUser($employee);
        $request->setUserResolver(static fn () => $employee);
    }

    public static function cast(mixed $user): ?Employee
    {
        if ($user instanceof Employee) {
            return $user;
        }

        if ($user instanceof Authenticatable && method_exists($user, 'getTable') && $user->getTable() === 'employees') {
            return Employee::query()->find($user->getAuthIdentifier());
        }

        return null;
    }

    private static function fromBearerToken(Request $request): ?Employee
    {
        $bearer = $request->bearerToken();
        if (! is_string($bearer) || $bearer === '') {
            return null;
        }

        try {
            $token = PersonalAccessToken::findToken($bearer);
            if ($token === null) {
                return null;
            }

            if ($token->expires_at && $token->expires_at->isPast()) {
                return null;
            }

            $tokenable = $token->tokenable;
            $employee = self::cast($tokenable);
            if ($employee === null) {
                return null;
            }

            $employee->withAccessToken($token);

            return $employee;
        } catch (Throwable) {
            return null;
        }
    }
}
