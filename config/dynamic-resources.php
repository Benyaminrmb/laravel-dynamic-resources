<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Mode
    |--------------------------------------------------------------------------
    |
    | The default mode to use when no mode is explicitly specified on a
    | resource. You can override this per-resource by defining a
    | DEFAULT_MODE constant or using the #[DefaultMode] attribute.
    |
    */
    'default_mode' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Strict Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, attempting to use an undefined mode will throw an
    | UndefinedModeException. When disabled, undefined modes are silently
    | ignored and fall back to the default mode.
    |
    | Recommended: Enable in development, disable in production.
    |
    */
    'strict_mode' => env('DYNAMIC_RESOURCES_STRICT', false),

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, adds debug information to responses including active modes,
    | transformation timing, and field counts. Automatically follows APP_DEBUG
    | unless explicitly set.
    |
    */
    'debug_mode' => env('DYNAMIC_RESOURCES_DEBUG', null),

    /*
    |--------------------------------------------------------------------------
    | Request-Based Field Selection
    |--------------------------------------------------------------------------
    |
    | Configure how clients can select fields and modes via HTTP request
    | parameters. This enables JSON:API style sparse fieldsets.
    |
    | Examples:
    | - ?mode=minimal
    | - ?fields=id,name,email
    | - ?exclude=created_at,updated_at
    |
    */
    'request_selection' => [
        // Enable/disable request-based selection entirely
        'enabled' => true,

        // Query parameter for mode selection: ?mode=minimal
        'mode_parameter' => 'mode',

        // Query parameter for field selection: ?fields=id,name,email
        'fields_parameter' => 'fields',

        // Query parameter for excluding fields: ?exclude=created_at
        'exclude_parameter' => 'exclude',

        // Allow nested field selection: ?fields=id,name,posts.id,posts.title
        'allow_nested' => true,

        // Maximum nesting depth for field selection
        'max_depth' => 3,

        // Whitelist of modes that can be selected via request (empty = all)
        'allowed_modes' => [],

        // Blacklist of modes that cannot be selected via request
        'forbidden_modes' => ['admin', 'internal'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Cache field definitions for better performance. The cache stores
    | compiled attribute and field definitions per resource class.
    |
    */
    'cache' => [
        'enabled' => env('DYNAMIC_RESOURCES_CACHE', true),
        'store' => env('DYNAMIC_RESOURCES_CACHE_STORE', null),
        'ttl' => 3600,
        'prefix' => 'dynamic_resources',
    ],

    /*
    |--------------------------------------------------------------------------
    | Automatic Relationship Loading
    |--------------------------------------------------------------------------
    |
    | When enabled, the package will automatically load missing relationships
    | that are required by the active modes (detected via #[RequiresRelation]
    | attributes or the loadRelations() method).
    |
    */
    'auto_load_relations' => true,

    /*
    |--------------------------------------------------------------------------
    | Clear Cache on Terminate
    |--------------------------------------------------------------------------
    |
    | For long-running processes (like Laravel Octane), you may want to clear
    | the static field definition cache at the end of each request.
    |
    */
    'clear_cache_on_terminate' => env('DYNAMIC_RESOURCES_CLEAR_CACHE', false),

    /*
    |--------------------------------------------------------------------------
    | Resource Discovery
    |--------------------------------------------------------------------------
    |
    | Configure paths for automatic resource discovery used by artisan commands
    | like resource:list.
    |
    */
    'discovery' => [
        'paths' => [
            app_path('Http/Resources'),
        ],
        'base_class' => Benyaminrmb\LaravelDynamicResources\DynamicResource::class,
    ],
];
