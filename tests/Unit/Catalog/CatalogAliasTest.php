<?php

namespace Tests\Unit\Catalog;

use App\Modules\Catalog\Models\City;
use App\Modules\Catalog\Models\PaymentType;
use App\Modules\Catalog\Models\TenantRole;
use App\Modules\Catalog\Policies\CityPolicy;
use App\Modules\Catalog\Policies\PaymentTypePolicy;
use App\Modules\Catalog\Resources\CityResource;
use App\Modules\Catalog\Resources\UnitUsageResource;
use App\Shared\Routing\ModuleRouteLoader;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class CatalogAliasTest extends TestCase
{
    public function test_legacy_model_and_resource_names_alias_the_module_classes(): void
    {
        $this->assertTrue(is_a(\App\Models\City::class, City::class, true));
        $this->assertTrue(is_a(\App\Models\TenantRole::class, TenantRole::class, true));
        $this->assertTrue(is_a(\App\Models\PaymentType::class, PaymentType::class, true));
        $this->assertTrue(is_a(\App\Http\Resources\CityResource::class, CityResource::class, true));
        $this->assertTrue(is_a(\App\Http\Resources\UnitUsageResource::class, UnitUsageResource::class, true));
        $this->assertTrue(is_a(
            \App\Http\Resources\Admin\V2\Api\TenantRoleResource::class,
            \App\Modules\Catalog\Resources\Admin\TenantRoleResource::class,
            true
        ));
    }

    public function test_catalog_policies_are_registered(): void
    {
        $this->assertInstanceOf(CityPolicy::class, Gate::getPolicyFor(City::class));
        $this->assertInstanceOf(PaymentTypePolicy::class, Gate::getPolicyFor(PaymentType::class));
    }

    public function test_catalog_module_exposes_api_v2_and_admin_route_files(): void
    {
        $loader = $this->app->make(ModuleRouteLoader::class);
        $catalog = collect($loader->moduleDirectories())
            ->first(fn (string $path): bool => basename($path) === 'Catalog');

        $this->assertNotNull($catalog);
        $this->assertFileExists($catalog.DIRECTORY_SEPARATOR.'Routes'.DIRECTORY_SEPARATOR.'api.php');
        $this->assertFileExists($catalog.DIRECTORY_SEPARATOR.'Routes'.DIRECTORY_SEPARATOR.'api_v2.php');
        $this->assertFileExists($catalog.DIRECTORY_SEPARATOR.'Routes'.DIRECTORY_SEPARATOR.'admin.php');
        $this->assertFileDoesNotExist($catalog.DIRECTORY_SEPARATOR.'Routes'.DIRECTORY_SEPARATOR.'web.php');
    }
}
