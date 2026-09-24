<?php

namespace App\Http\Middleware;

use App\Http\Traits\Responser;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;

/**
 * Ensure the authenticated Sanctum token belongs to a customer (User), not an
 * Employee. User and Employee both issue Sanctum personal-access tokens, so a
 * bare `auth:sanctum` on a customer route would also accept an employee token
 * and resolve auth()->user() to an Employee. This guard rejects that so the
 * public customer API can only ever be driven by a customer account.
 */
class EnsureCustomer
{
    use Responser;

    public function handle(Request $request, Closure $next)
    {
        if (! $request->user() instanceof User) {
            return $this->errorMessage(trans('api.unauthenticated'), 401);
        }

        return $next($request);
    }
}
