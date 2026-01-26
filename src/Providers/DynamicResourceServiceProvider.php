<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Providers;

use Benyaminrmb\LaravelDynamicResources\Console\Commands\MakeDynamicResourceCommand;
use Benyaminrmb\LaravelDynamicResources\Console\Commands\ResourceListCommand;
use Benyaminrmb\LaravelDynamicResources\DynamicResource;
use Benyaminrmb\LaravelDynamicResources\Http\Middleware\DynamicResourceFieldsMiddleware;
use Benyaminrmb\LaravelDynamicResources\Services\SparseFieldsetParser;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for Laravel Dynamic Resources.
 *
 * Registers configuration, middleware, commands, and services.
 */
class DynamicResourceServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/dynamic-resources.php',
            'dynamic-resources'
        );

        // Register singletons
        $this->app->singleton(SparseFieldsetParser::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish config
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/dynamic-resources.php' => config_path('dynamic-resources.php'),
            ], 'dynamic-resources-config');

            // Register commands
            $this->commands([
                MakeDynamicResourceCommand::class,
                ResourceListCommand::class,
            ]);
        }

        // Register middleware alias
        $router = $this->app->make('router');
        $router->aliasMiddleware('resource.fields', DynamicResourceFieldsMiddleware::class);

        // Clear cache on terminate if configured (for Octane compatibility)
        $this->app->terminating(function (): void {
            if (config('dynamic-resources.clear_cache_on_terminate', false)) {
                DynamicResource::clearFieldDefinitionCache();
            }
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return list<class-string>
     */
    public function provides(): array
    {
        return [
            SparseFieldsetParser::class,
        ];
    }
}
