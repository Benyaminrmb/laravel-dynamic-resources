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
}
