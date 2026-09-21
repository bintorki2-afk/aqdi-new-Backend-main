<?php

namespace App\Shared\Routing;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

class ModuleRouteLoader
{
    /**
     * Route files a module may expose, mapped to the same middleware/prefix
     * as the legacy route files in RouteServiceProvider.
     *
     * @var array<string, array{middleware: string|list<string>, prefix: string|null}>
     */
    private const ROUTE_FILES = [
        'api.php' => ['middleware' => 'api', 'prefix' => 'api'],
        'api_v2.php' => ['middleware' => 'api', 'prefix' => 'api/v2'],
        'admin.php' => ['middleware' => ['api', 'employee.bearer'], 'prefix' => 'api/admin'],
        'web.php' => ['middleware' => 'web', 'prefix' => null],
    ];

    public function load(?string $modulesPath = null): void
    {
        $modulesPath ??= app_path('Modules');

        if (! is_dir($modulesPath)) {
            return;
        }

        foreach ($this->moduleDirectories($modulesPath) as $modulePath) {
            $this->loadModule($modulePath);
        }
    }

    /**
     * @return list<string>
     */
    public function moduleDirectories(?string $modulesPath = null): array
    {
        $modulesPath ??= app_path('Modules');

        if (! is_dir($modulesPath)) {
            return [];
        }

        $directories = File::directories($modulesPath);
        sort($directories, SORT_STRING);

        return array_values(array_filter(
            $directories,
            static fn (string $path): bool => ! str_starts_with(basename($path), '.')
        ));
    }

    private function loadModule(string $modulePath): void
    {
        $routesDir = $modulePath.DIRECTORY_SEPARATOR.'Routes';

        if (! is_dir($routesDir)) {
            return;
        }

        foreach (self::ROUTE_FILES as $file => $config) {
            $path = $routesDir.DIRECTORY_SEPARATOR.$file;

            if (! is_file($path)) {
                continue;
            }

            $this->loadRouteFile($path, $config['middleware'], $config['prefix']);
        }
    }

    /**
     * @param  string|list<string>  $middleware
     */
    private function loadRouteFile(string $path, string|array $middleware, ?string $prefix): void
    {
        $registrar = Route::middleware($middleware);

        if ($prefix !== null) {
            $registrar->prefix($prefix);
        }

        $registrar->group($path);
    }
}
