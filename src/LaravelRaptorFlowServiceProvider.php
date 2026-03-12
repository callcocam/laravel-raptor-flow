<?php

namespace Callcocam\LaravelRaptorFlow;

use Callcocam\LaravelRaptorFlow\Commands\LaravelRaptorFlowCommand;
use Callcocam\LaravelRaptorFlow\Services\FlowManager;
use Illuminate\Support\Facades\Config;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelRaptorFlowServiceProvider extends PackageServiceProvider
{
    public function register(): void
    {
        parent::register();
        $this->mergeConfigFrom(__DIR__.'/../config/flow.php', 'flow');

        $migrationsPath = realpath(__DIR__.'/../database/migrations');
        if ($migrationsPath !== false) {
            Config::set('flow.client_migrations_path', $migrationsPath);
        }

        $this->app->singleton(FlowManager::class, fn () => new FlowManager);
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-raptor-flow')
            ->hasConfigFile()
            ->hasViews()
            ->hasCommand(LaravelRaptorFlowCommand::class);
    }

    public function boot(): void
    {
        parent::boot();

        $this->publishes([
            __DIR__.'/../config/flow.php' => config_path('flow.php'),
        ], 'flow-config');

        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations/clients'),
        ], 'raptor-flow-client-migrations');
    }
}
