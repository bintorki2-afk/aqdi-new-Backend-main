<?php

namespace Tests\Unit\Shared;

use App\Http\Traits\Responser as LegacyResponser;
use App\Shared\Helpers\SaudiMobile;
use App\Shared\Responses\JsonEncoding as SharedJsonEncoding;
use App\Shared\Responses\Responser as SharedResponser;
use App\Shared\Routing\ModuleRouteLoader;
use App\Support\JsonEncoding as LegacyJsonEncoding;
use App\Support\SaudiMobile as LegacySaudiMobile;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SharedInfrastructureTest extends TestCase
{
    public function test_shared_responser_and_legacy_alias_exist(): void
    {
        $this->assertTrue(trait_exists(SharedResponser::class));
        $this->assertTrue(trait_exists(LegacyResponser::class));
    }

    public function test_json_encoding_options_match_between_shared_and_legacy(): void
    {
        $this->assertSame(SharedJsonEncoding::OPTIONS, LegacyJsonEncoding::OPTIONS);
        $this->assertSame(JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES, SharedJsonEncoding::OPTIONS);
    }

    public function test_saudi_mobile_legacy_alias_uses_shared_implementation(): void
    {
        $this->assertSame('512345678', SaudiMobile::toNational('0512345678'));
        $this->assertSame('512345678', LegacySaudiMobile::toNational('966512345678'));
        $this->assertTrue(is_subclass_of(LegacySaudiMobile::class, SaudiMobile::class));
    }

    public function test_shared_helpers_are_loaded(): void
    {
        $this->assertTrue(function_exists('fileUploader'));
        $this->assertTrue(function_exists('getFilePath'));
        $this->assertTrue(function_exists('deleteFile'));
        $this->assertTrue(function_exists('localeSession'));
        $this->assertTrue(function_exists('getTransAttribute'));
        $this->assertTrue(function_exists('website_image'));
    }

    public function test_module_route_loader_discovers_migrated_modules(): void
    {
        $loader = $this->app->make(ModuleRouteLoader::class);
        $names = array_map('basename', $loader->moduleDirectories());

        foreach ([
            'Analytics', 'Auth', 'Catalog', 'Content', 'Contracts', 'Coupons',
            'Employees', 'Finance', 'Marketing', 'Notifications', 'Payments',
            'RealEstate', 'Seo', 'Settings', 'Users',
        ] as $module) {
            $this->assertContains($module, $names);
        }
    }

    public function test_api_and_utility_web_routes_remain_registered(): void
    {
        $this->assertFalse(Route::has('website.home'));
        $this->assertFalse(Route::has('website.login'));
        $this->assertFalse(Route::has('step1'));
        $this->assertFalse(Route::has('contract.choose'));

        $uris = collect(Route::getRoutes())->map(fn ($route) => $route->uri())->all();

        $this->assertContains('api/cities', $uris);
        $this->assertContains('api/v2/cities', $uris);
        $this->assertContains('api/auth/login', $uris);
        $this->assertContains('api/v2/auth/login', $uris);
        $this->assertContains('api/admin/employees', $uris);
        $this->assertContains('api/admin/employees/login', $uris);
        $this->assertNotContains('login', $uris);
        $this->assertNotContains('contract/step1/{uuid}', $uris);
        $this->assertContains('db', $uris);
        $this->assertContains('sitemap.xml', $uris);
    }
}
