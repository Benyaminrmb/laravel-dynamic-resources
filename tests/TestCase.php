<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Tests;

use Benyaminrmb\LaravelDynamicResources\Providers\DynamicResourceServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            DynamicResourceServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        // Set up config for testing
        $app['config']->set('dynamic-resources.strict_mode', false);
        $app['config']->set('dynamic-resources.debug_mode', false);
    }
}
