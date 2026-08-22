<?php

declare(strict_types=1);
use Benyaminrmb\LaravelDynamicResources\DynamicResource;

return [
    /*
    |--------------------------------------------------------------------------
    | Explicit Request Selection
    |--------------------------------------------------------------------------
    |
    | These options are only read after a resource or collection explicitly
    | calls fromRequest(). Modes must also be allowlisted by the resource.
    |
    */
    'request' => [
        'mode_parameter' => 'mode',
        'fields_parameter' => 'fields',
        'exclude_parameter' => 'exclude',
        'max_fields' => 50,
        'max_depth' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Resource Discovery
    |--------------------------------------------------------------------------
    */
    'discovery' => [
        'paths' => [app_path('Http/Resources')],
        'base_class' => DynamicResource::class,
    ],
];
