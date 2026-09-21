<?php

namespace App\Providers;

use App\Models\Contract;
use App\Modules\Catalog\Models\City;
use App\Modules\Catalog\Models\ContractPeriod;
use App\Modules\Catalog\Models\Paperwork;
use App\Modules\Catalog\Models\PaymentType;
use App\Modules\Catalog\Models\ReaEstatType;
use App\Modules\Catalog\Models\ReaEstatUsage;
use App\Modules\Catalog\Models\Region;
use App\Modules\Catalog\Models\TenantRole;
use App\Modules\Catalog\Models\UnitType;
use App\Modules\Catalog\Models\UnitUsage;
use App\Modules\Catalog\Policies\CityPolicy;
use App\Modules\Catalog\Policies\ContractPeriodPolicy;
use App\Modules\Catalog\Policies\PaperworkPolicy;
use App\Modules\Catalog\Policies\PaymentTypePolicy;
use App\Modules\Catalog\Policies\PropertyReferencePolicy;
use App\Modules\Catalog\Policies\RegionPolicy;
use App\Modules\Catalog\Policies\TenantRolePolicy;
use App\Modules\Contracts\Policies\ContractPolicy;
use App\Modules\Employees\Models\Employee;
use App\Modules\Employees\Models\Permission;
use App\Modules\Employees\Models\Role;
use App\Modules\Employees\Policies\EmployeePolicy;
use App\Modules\Employees\Policies\PermissionPolicy;
use App\Modules\Employees\Policies\RolePolicy;
use App\Modules\Users\Models\User;
use App\Modules\Users\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected $policies = [
        City::class => CityPolicy::class,
        Region::class => RegionPolicy::class,
        Paperwork::class => PaperworkPolicy::class,
        TenantRole::class => TenantRolePolicy::class,
        ContractPeriod::class => ContractPeriodPolicy::class,
        PaymentType::class => PaymentTypePolicy::class,
        ReaEstatType::class => PropertyReferencePolicy::class,
        ReaEstatUsage::class => PropertyReferencePolicy::class,
        UnitType::class => PropertyReferencePolicy::class,
        UnitUsage::class => PropertyReferencePolicy::class,
        User::class => UserPolicy::class,
        Employee::class => EmployeePolicy::class,
        Role::class => RolePolicy::class,
        Permission::class => PermissionPolicy::class,
        Contract::class => ContractPolicy::class,
    ];

    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            if ($user instanceof Employee && $user->isSystemAdmin()) {
                return true;
            }

            return null;
        });
    }
}
