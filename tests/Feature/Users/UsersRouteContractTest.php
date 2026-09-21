<?php

namespace Tests\Feature\Users;

use App\Modules\Auth\Controllers\Api\AuthController as ApiAuthController;
use App\Modules\Users\Controllers\Admin\UserController;
use App\Modules\Users\Controllers\Api\AccountController;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class UsersRouteContractTest extends TestCase
{
    public function test_admin_user_routes_are_registered_once_with_sanctum_and_permissions(): void
    {
        $expected = [
            'GET api/admin/users/export' => ['users.export', 'permission:users.view', 'export'],
            'GET api/admin/users' => ['users.index', 'permission:users.view', 'allusers'],
            'GET api/admin/users/new' => ['users.new', 'permission:users.view', 'newcommersUser'],
            'GET api/admin/users/contracts-complete' => ['users.contracts-complete', 'permission:users.view', 'usersCompleteContracts'],
            'GET api/admin/users/{id}/properties/{propertyId}/deed' => ['users.properties.deed', 'permission:users.view', 'downloadDeed'],
            'GET api/admin/users/{id}/properties' => ['users.properties.index', 'permission:users.view', 'properties'],
            'DELETE api/admin/users/{id}/properties/{propertyId}' => ['users.properties.destroy', 'permission:users.delete', 'destroyProperty'],
            'DELETE api/admin/users/{id}/units/{unitId}' => ['users.units.destroy', 'permission:users.delete', 'destroyUnit'],
            'POST api/admin/users/{id}/discount' => ['users.discount', 'permission:users.edit', 'applyDiscount'],
            'GET api/admin/users/{id}/coupons' => ['users.coupons.index', 'permission:users.view', 'coupons'],
            'POST api/admin/users/{id}/coupons' => ['users.coupons.store', 'permission:users.create', 'storeCoupon'],
            'GET api/admin/users/{id}/coupons/{couponId}' => ['users.coupons.show', 'permission:users.view', 'showCoupon'],
            'POST api/admin/users/{id}/coupons/{couponId}/deactivate' => ['users.coupons.deactivate', 'permission:users.delete', 'deactivateCoupon'],
            'GET api/admin/users/{id}' => ['users.show', 'permission:users.view', 'show'],
            'POST api/admin/users/{id}/block' => ['users.block', 'permission:users.edit', 'block'],
            'POST api/admin/users/{id}/delete' => ['users.delete', 'permission:users.delete', 'deleteUser'],
        ];

        foreach ($expected as $key => [$name, $permission, $action]) {
            $this->assertSame(1, $this->countRouteKey($key), $key);
            $route = $this->routeByKey($key);
            $this->assertSame($name, $route->getName(), $key);
            $this->assertContains('auth:sanctum', $route->gatherMiddleware(), $key);
            $this->assertContains($permission, $route->gatherMiddleware(), $key);
            $this->assertSame(UserController::class, $route->getControllerClass(), $key);
            $this->assertSame($action, $route->getActionMethod(), $key);
        }
    }

    public function test_api_account_routes_are_registered_once_on_v1_and_v2(): void
    {
        $expected = [
            'GET api/profile' => 'profile',
            'POST api/profile' => 'updateProfile',
            'POST api/update/password' => 'updatePassword',
            'POST api/fcm' => 'updateFCMToken',
            'GET api/notifications' => 'notifications',
            'POST api/user/deactivate' => 'deactivateUser',
            'GET api/v2/profile' => 'profile',
            'POST api/v2/profile' => 'updateProfile',
            'POST api/v2/update/password' => 'updatePassword',
            'POST api/v2/fcm' => 'updateFCMToken',
            'GET api/v2/notifications' => 'notifications',
            'POST api/v2/user/deactivate' => 'deactivateUser',
        ];

        foreach ($expected as $key => $action) {
            $this->assertSame(1, $this->countRouteKey($key), $key);
            $route = $this->routeByKey($key);
            $this->assertContains('auth:sanctum', $route->gatherMiddleware(), $key);
            $this->assertSame(AccountController::class, $route->getControllerClass(), $key);
            $this->assertSame($action, $route->getActionMethod(), $key);
            $this->assertNull($route->getName(), $key);
        }
    }

    public function test_auth_logout_stays_on_the_auth_controller(): void
    {
        foreach (['POST api/auth/logout', 'POST api/v2/auth/logout'] as $key) {
            $this->assertSame(1, $this->countRouteKey($key), $key);
            $route = $this->routeByKey($key);
            $this->assertContains('auth:sanctum', $route->gatherMiddleware(), $key);
            $this->assertSame(ApiAuthController::class, $route->getControllerClass(), $key);
            $this->assertSame('logout', $route->getActionMethod(), $key);
        }
    }

    public function test_website_profile_routes_are_not_registered(): void
    {
        foreach (['profile', 'update.profile', 'update.profile.photo', 'RemoveProfile'] as $name) {
            $this->assertFalse(Route::has($name), $name);
        }
    }

    public function test_analytics_client_routes_stay_outside_users(): void
    {
        $route = $this->routeByKey('GET api/admin/analytics/user-activity-rate');
        $this->assertSame(
            \App\Modules\Analytics\Controllers\Admin\UserDashboardAnalyticsController::class,
            $route->getControllerClass()
        );
        $this->assertContains('permission:analytics.view', $route->gatherMiddleware());
    }

    private function countRouteKey(string $key): int
    {
        return collect(Route::getRoutes())
            ->filter(fn ($route) => $this->routeKey($route) === $key)
            ->count();
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
