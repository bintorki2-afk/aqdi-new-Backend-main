<?php

namespace App\Providers;

use App\Shared\Routing\ModuleRouteLoader;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleRouteLoader::class);
    }

    public function boot(): void
    {
        // Module HTTP routes are loaded from RouteServiceProvider so they
        // participate in the same `$this->routes()` cycle as legacy files
        // (required for `route:cache` and consistent middleware/prefix).
    }
}
