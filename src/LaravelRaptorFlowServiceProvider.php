<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaravelRaptorFlow;

use Callcocam\LaravelRaptorFlow\Commands\LaravelRaptorFlowCommand;
use Callcocam\LaravelRaptorFlow\Models\FlowExecution;
use Callcocam\LaravelRaptorFlow\Policies\FlowExecutionPolicy;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelRaptorFlowServiceProvider extends PackageServiceProvider
{
    public function register(): void
    {
        parent::register();
        $this->mergeConfigFrom(__DIR__.'/../config/flow.php', 'flow');

        // Flow no banco principal (landlord): não rodar migrations do flow nos bancos de cliente.
        Config::set('flow.client_migrations_path', null);
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

        $this->registerFlowRoutes();
        $this->registerPolicies();
    }

    protected function registerPolicies(): void
    {
        Gate::policy(FlowExecution::class, FlowExecutionPolicy::class);
    }

    protected function registerFlowRoutes(): void
    {
        $prefix = config('flow.route_prefix', 'flow');
        $middleware = config('flow.route_middleware', ['web', 'auth']);
        $routeFile = __DIR__.'/../routes/flow.php';

        if (! is_file($routeFile)) {
            return;
        }
        Route::middleware($middleware)
            ->prefix($prefix)
            ->group($routeFile);
    }
}
