<?php

namespace Benyaminrmb\LaravelDynamicResources\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Benyaminrmb\LaravelDynamicResources\LaravelDynamicResourcesServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelDynamicResourcesServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        // Add any environment setup here
    }
}
