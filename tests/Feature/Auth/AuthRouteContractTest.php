<?php

namespace Tests\Feature\Auth;

use App\Modules\Auth\Controllers\Admin\EmployeeSessionController;
use App\Modules\Auth\Controllers\Api\AuthController as ApiAuthController;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthRouteContractTest extends TestCase
{
    /**
     * @return list<string>
     */
    private function publicAuthUris(): array
    {
        return [
            'auth/login',
            'auth/signup',
            'auth/verification',
            'auth/resend',
            'auth/forgot-password',
            'auth/reset-password-code',
            'auth/reset-password',
        ];
    }

    public function test_public_auth_posts_are_registered_once_on_v1_and_v2(): void
    {
        foreach ($this->publicAuthUris() as $path) {
            $this->assertSame(1, $this->countUriMethod("api/{$path}", 'POST'), "api/{$path}");
            $this->assertSame(1, $this->countUriMethod("api/v2/{$path}", 'POST'), "api/v2/{$path}");
        }
    }

    public function test_public_auth_posts_use_the_module_controller(): void
    {
        foreach (['api/auth/login', 'api/v2/auth/login', 'api/auth/signup'] as $uri) {
            $route = $this->routeByUriAndMethod($uri, 'POST');
            $this->assertSame(ApiAuthController::class, $route->getControllerClass(), $uri);
        }
    }

    public function test_otp_send_and_verify_routes_use_dedicated_throttles(): void
    {
        foreach (['api', 'api/v2'] as $prefix) {
            $this->assertContains(
                'throttle:login',
                $this->routeByUriAndMethod("{$prefix}/auth/login", 'POST')->gatherMiddleware(),
                "{$prefix}/auth/login"
            );

            foreach (['auth/signup', 'auth/resend', 'auth/forgot-password'] as $path) {
                $this->assertContains(
                    'throttle:otp-send',
                    $this->routeByUriAndMethod("{$prefix}/{$path}", 'POST')->gatherMiddleware(),
                    "{$prefix}/{$path}"
                );
            }

            foreach (['auth/verification', 'auth/reset-password-code', 'auth/reset-password'] as $path) {
                $this->assertContains(
                    'throttle:otp-verify',
                    $this->routeByUriAndMethod("{$prefix}/{$path}", 'POST')->gatherMiddleware(),
                    "{$prefix}/{$path}"
                );
            }
        }
    }

    public function test_google_login_callback_is_not_registered(): void
    {
        $this->assertSame(0, $this->countUriMethod('api/auth/google/callback', 'POST'));
        $this->assertSame(0, $this->countUriMethod('api/v2/auth/google/callback', 'POST'));
    }

    public function test_authenticated_logout_stays_on_the_auth_controller(): void
    {
        foreach (['POST api/auth/logout', 'POST api/v2/auth/logout'] as $key) {
            $this->assertSame(1, $this->countRouteKey($key), $key);
            $this->assertContains('auth:sanctum', $this->routeByKey($key)->gatherMiddleware(), $key);
            $this->assertSame(ApiAuthController::class, $this->routeByKey($key)->getControllerClass(), $key);
            $this->assertSame('logout', $this->routeByKey($key)->getActionMethod(), $key);
        }
    }

    public function test_v1_auth_group_name_prefix_and_v2_login_stay_unnamed(): void
    {
        $this->assertSame('auth.', $this->routeByUriAndMethod('api/auth/login', 'POST')->getName());
        $this->assertNull($this->routeByUriAndMethod('api/v2/auth/login', 'POST')->getName());
    }

    public function test_admin_employee_session_routes_are_registered_once(): void
    {
        $public = [
            'POST api/admin/employees/login' => [],
            'POST api/admin/employees/refresh-token' => [],
        ];
        $authed = [
            'GET api/admin/employees/me' => ['auth:sanctum'],
            'GET api/admin/employees/profile' => ['auth:sanctum'],
            'POST api/admin/employees/fcm' => ['auth:sanctum'],
            'POST api/admin/employees/logout' => ['auth:sanctum'],
        ];

        foreach ($public as $key => $middleware) {
            $this->assertSame(1, $this->countRouteKey($key), $key);
            $route = $this->routeByKey($key);
            $this->assertNotContains('auth:sanctum', $route->gatherMiddleware(), $key);
            $this->assertSame(EmployeeSessionController::class, $route->getControllerClass(), $key);
        }

        foreach ($authed as $key => $middleware) {
            $this->assertSame(1, $this->countRouteKey($key), $key);
            $route = $this->routeByKey($key);
            foreach ($middleware as $item) {
                $this->assertContains($item, $route->gatherMiddleware(), $key);
            }
            $this->assertSame(EmployeeSessionController::class, $route->getControllerClass(), $key);
        }

        $this->assertSame('employees.login', $this->routeByKey('POST api/admin/employees/login')->getName());
        $this->assertSame('employees.refresh-token', $this->routeByKey('POST api/admin/employees/refresh-token')->getName());
        $this->assertSame('employees.me', $this->routeByKey('GET api/admin/employees/me')->getName());
        $this->assertSame('employees.profile', $this->routeByKey('GET api/admin/employees/profile')->getName());
        $this->assertSame('employees.fcm', $this->routeByKey('POST api/admin/employees/fcm')->getName());
        $this->assertSame('employees.logout', $this->routeByKey('POST api/admin/employees/logout')->getName());
    }

    public function test_website_auth_routes_are_not_registered(): void
    {
        foreach ([
            'website.login',
            'website.login.post',
            'website.signup',
            'website.PostSignup',
            'website.logout',
        ] as $name) {
            $this->assertFalse(Route::has($name), $name);
        }

        $this->assertSame(0, $this->countUriMethod('login', 'GET'));
        $this->assertSame(0, $this->countUriMethod('login', 'POST'));
    }

    public function test_employee_crud_index_uses_the_employees_module_controller(): void
    {
        $route = $this->routeByKey('GET api/admin/employees');
        $this->assertSame(\App\Modules\Employees\Controllers\Admin\EmployeeController::class, $route->getControllerClass());
        $this->assertContains('permission:employees.view', $route->gatherMiddleware());
    }

    private function countUriMethod(string $uri, string $method): int
    {
        return collect(Route::getRoutes())
            ->filter(fn ($route) => $route->uri() === $uri && in_array($method, $route->methods(), true))
            ->count();
    }

    private function countRouteKey(string $key): int
    {
        return collect(Route::getRoutes())
            ->filter(fn ($route) => $this->routeKey($route) === $key)
            ->count();
    }

    private function routeByUriAndMethod(string $uri, string $method)
    {
        foreach (Route::getRoutes() as $route) {
            if ($route->uri() === $uri && in_array($method, $route->methods(), true)) {
                return $route;
            }
        }

        $this->fail("Route {$method} {$uri} is not registered.");
    }

    private function routeByKey(string $key)
    {
        foreach (Route::getRoutes() as $route) {
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
