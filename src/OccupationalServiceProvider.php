<?php

namespace Platform\Occupational;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\Relation;
use Livewire\Livewire;
use Platform\Core\PlatformCore;
use Platform\Core\Routing\ModuleRouter;
use Platform\Occupational\Models\Employment;
use Platform\Occupational\Models\RiskAssessment;
use Platform\Occupational\Models\Hazard;
use Platform\Occupational\Models\Exposure;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class OccupationalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/occupational.php', 'occupational');
    }

    public function boot(): void
    {
        Relation::morphMap([
            'occupational_employment'      => Employment::class,
            'occupational_risk_assessment' => RiskAssessment::class,
            'occupational_hazard'          => Hazard::class,
            'occupational_exposure'        => Exposure::class,
        ]);

        if (
            config()->has('occupational.routing') &&
            config()->has('occupational.navigation') &&
            Schema::hasTable('modules')
        ) {
            PlatformCore::registerModule([
                'key'        => 'occupational',
                'title'      => 'Betriebsmedizin',
                'routing'    => config('occupational.routing'),
                'guard'      => config('occupational.guard'),
                'navigation' => config('occupational.navigation'),
                'sidebar'    => config('occupational.sidebar'),
            ]);
        }

        if (PlatformCore::getModule('occupational')) {
            ModuleRouter::group('occupational', function () {
                $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
            });
        }

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->publishes([
            __DIR__ . '/../config/occupational.php' => config_path('occupational.php'),
        ], 'config');

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'occupational');

        $this->registerLivewireComponents();

        $this->registerTools();
    }

    protected function registerTools(): void
    {
        try {
            $registry = resolve(\Platform\Core\Tools\ToolRegistry::class);

            $registry->register(new \Platform\Occupational\Tools\ListEmployeesTool());
            $registry->register(new \Platform\Occupational\Tools\GetEmployeeTool());
            $registry->register(new \Platform\Occupational\Tools\CreateEmployeeTool());
            $registry->register(new \Platform\Occupational\Tools\UpdateEmployeeTool());
            $registry->register(new \Platform\Occupational\Tools\DeleteEmployeeTool());
        } catch (\Throwable $e) {
            // ToolRegistry nicht verfügbar — ignorieren.
        }
    }

    /**
     * Registriert alle Livewire-Komponenten unter src/Livewire/ rekursiv.
     * Datei src/Livewire/Employee/Index.php → Alias occupational.employee.index
     */
    protected function registerLivewireComponents(): void
    {
        $basePath = __DIR__ . '/Livewire';
        $baseNamespace = 'Platform\\Occupational\\Livewire';
        $prefix = 'occupational';

        if (!is_dir($basePath)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace($basePath . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $classPath = str_replace(['/', '.php'], ['\\', ''], $relativePath);
            $class = $baseNamespace . '\\' . $classPath;

            if (!class_exists($class)) {
                continue;
            }

            $aliasPath = str_replace(['\\', '/'], '.', Str::kebab(str_replace('.php', '', $relativePath)));
            $alias = $prefix . '.' . $aliasPath;

            Livewire::component($alias, $class);
        }
    }
}
