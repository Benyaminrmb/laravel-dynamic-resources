<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Providers;

use Benyaminrmb\LaravelDynamicResources\Console\Commands\MakeDynamicResourceCommand;
use Benyaminrmb\LaravelDynamicResources\Console\Commands\ResourceListCommand;
use Benyaminrmb\LaravelDynamicResources\Console\Commands\SyncDynamicResourceViewsCommand;
use Benyaminrmb\LaravelDynamicResources\Services\ResourceQueryParser;
use Benyaminrmb\LaravelDynamicResources\Services\ViewRegistry;
use Illuminate\Support\ServiceProvider;

class DynamicResourceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/dynamic-resources.php', 'dynamic-resources');
        $this->app->singleton(ResourceQueryParser::class);
        $this->app->singleton(ViewRegistry::class);
    }

    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../../config/dynamic-resources.php' => config_path('dynamic-resources.php'),
        ], 'dynamic-resources-config');

        $this->commands([
            MakeDynamicResourceCommand::class,
            ResourceListCommand::class,
            SyncDynamicResourceViewsCommand::class,
        ]);
    }
}
