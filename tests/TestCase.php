<?php

namespace Benyaminrmb\LaravelDynamicResources\Tests;

use Benyaminrmb\LaravelDynamicResources\Providers\LaravelModularResourcesServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;


class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelModularResourcesServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        // Add any environment setup here
    }
}
