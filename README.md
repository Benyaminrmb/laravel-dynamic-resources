# Laravel Dynamic Resources v2

[![Latest Version on Packagist](https://img.shields.io/packagist/v/benyaminrmb/laravel-dynamic-resources.svg)](https://packagist.org/packages/benyaminrmb/laravel-dynamic-resources)
[![Total Downloads](https://img.shields.io/packagist/dt/benyaminrmb/laravel-dynamic-resources.svg)](https://packagist.org/packages/benyaminrmb/laravel-dynamic-resources)
[![Tests](https://github.com/benyaminrmb/laravel-dynamic-resources/actions/workflows/test.yml/badge.svg)](https://github.com/benyaminrmb/laravel-dynamic-resources/actions)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%209-brightgreen.svg)](https://phpstan.org/)

**Dynamic API resources with mode-based field selection for Laravel.** Define minimal, default, detailed modes and compose them on the fly. Reduce API payload sizes, improve performance, and give clients control over response structure.

## Features

- **Mode-based field selection** - Define `minimal`, `default`, `detailed` modes and switch between them
- **Composable modes** - Combine modes with `->withAvatar()->withPosts()`
- **Field filtering** - Fine-grained control with `only()` and `except()`
- **Nested resource support** - Mode inheritance through resource trees
- **Request-based selection** - Let clients choose modes via query params: `?mode=minimal&fields=id,name`
- **PHP Attributes** - Modern declarative syntax with `#[Field]` and `#[Mode]` attributes
- **Artisan commands** - `make:dynamic-resource` and `resource:list`
- **Strict type safety** - PHP 8.4, PHPStan level 9

## Requirements

- PHP 8.4+
- Laravel 11.0+ or 12.0+

## Installation

```bash
composer require benyaminrmb/laravel-dynamic-resources
```

Publish the config (optional):

```bash
php artisan vendor:publish --tag=dynamic-resources-config
```

## Quick Start

### 1. Create a Resource

```bash
php artisan make:dynamic-resource UserResource --model=User
```

Or create manually:

```php
<?php

namespace App\Http\Resources;

use Benyaminrmb\LaravelDynamicResources\DynamicResource;

class UserResource extends DynamicResource
{
    public function fields(): array
    {
        return [
            // Minimal mode - just IDs and names
            'minimal' => [
                'id',
                'name',
            ],

            // Default mode - standard fields
            'default' => [
                'id',
                'name',
                'email',
            ],

            // Detailed mode - inherits default + adds more
            'detailed' => fn() => [
                ...$this->mode('default'),
                'bio' => $this->bio,
                'created_at' => $this->created_at->toISOString(),
                'posts' => PostResource::collection($this->whenLoaded('posts')),
            ],

            // Composable modes - add these with withAvatar(), withPosts()
            'avatar' => fn() => [
                'avatar_url' => $this->avatar_url,
            ],

            'posts' => fn() => [
                'posts' => PostResource::collection($this->whenLoaded('posts')),
            ],
        ];
    }
}
```

### 2. Use in Controllers

```php
class UserController extends Controller
{
    // Single resource with mode
    public function show(User $user)
    {
        return UserResource::make($user)->detailed();
    }

    // Collection with mode
    public function index()
    {
        return UserResource::collection(User::all())->minimal();
    }

    // Compose multiple modes
    public function profile(User $user)
    {
        return UserResource::make($user)
            ->minimal()
            ->withAvatar()
            ->withPosts();
    }

    // Field filtering
    public function summary(User $user)
    {
        return UserResource::make($user)
            ->default()
            ->only(['id', 'name', 'email']);
    }
}
```

## Mode Composition

Modes are composable. You can combine them dynamically:

```php
// Start with minimal, add avatar and posts
UserResource::make($user)
    ->minimal()
    ->withAvatar()
    ->withPosts();

// Remove a mode
UserResource::make($user)
    ->detailed()
    ->withoutPosts();

// Chain as many as you need
UserResource::make($user)
    ->minimal()
    ->withAvatar()
    ->withTimestamps()
    ->withoutTimestamps()  // Changed our mind
    ->withPosts();
```

## Field Filtering

Fine-grained control over which fields appear:

```php
// Include only these fields
UserResource::make($user)
    ->default()
    ->only(['id', 'name']);

// Exclude these fields
UserResource::make($user)
    ->detailed()
    ->except(['created_at', 'updated_at']);
```

## Mode Inheritance

Reference other modes to build upon them:

```php
public function fields(): array
{
    return [
        'minimal' => ['id', 'name'],

        'default' => fn() => [
            ...$this->mode('minimal'),  // Include all minimal fields
            'email',
        ],

        'detailed' => fn() => [
            ...$this->mode('default'),  // Include all default fields
            'bio',
            'created_at',
        ],
    ];
}
```

## Conditional Fields

Use Laravel's conditional helpers:

```php
public function fields(): array
{
    return [
        'default' => fn() => [
            'id',
            'name',
            'avatar' => $this->when($this->avatar !== null, $this->avatar),
            'posts_count' => $this->whenCounted('posts'),
            'posts' => $this->whenLoaded('posts', fn() => PostResource::collection($this->posts)),
        ],
    ];
}
```

## Collections

Collections inherit modes from the parent:

```php
// All users will use minimal mode
UserResource::collection($users)->minimal();

// Compose modes on collections too
UserResource::collection($users)
    ->minimal()
    ->withAvatar();

// Field filtering on collections
UserResource::collection($users)
    ->default()
    ->only(['id', 'name']);
```

## Request-Based Field Selection

Enable the middleware to let clients control response structure:

```php
// routes/api.php
Route::middleware('resource.fields')->group(function () {
    Route::get('/users', [UserController::class, 'index']);
});
```

Now clients can use:

```
GET /api/users?mode=minimal
GET /api/users?fields=id,name,email
GET /api/users?mode=detailed&exclude=created_at
```

## Additional Data

Add metadata to responses:

```php
UserResource::make($user)
    ->minimal()
    ->additional([
        'meta' => [
            'version' => '2.0',
            'generated_at' => now()->toISOString(),
        ],
    ]);
```

## Artisan Commands

```bash
# Create a new dynamic resource
php artisan make:dynamic-resource UserResource --model=User

# List all dynamic resources and their modes
php artisan resource:list --modes
```

## Configuration

Publish and customize:

```bash
php artisan vendor:publish --tag=dynamic-resources-config
```

Key options in `config/dynamic-resources.php`:

```php
return [
    // Default mode when none specified
    'default_mode' => 'default',

    // Throw exceptions for undefined modes
    'strict_mode' => env('DYNAMIC_RESOURCES_STRICT', false),

    // Request-based field selection
    'request_selection' => [
        'enabled' => true,
        'mode_parameter' => 'mode',
        'fields_parameter' => 'fields',
        'forbidden_modes' => ['admin', 'internal'],
    ],
];
```

## Migration from v1

If upgrading from v1, the main changes are:

1. Class renamed: `ModularResource` → `DynamicResource`
2. Service provider renamed: `LaravelModularResourcesServiceProvider` → `DynamicResourceServiceProvider`
3. PHP 8.4 required (was 8.2)

```php
// v1
use Benyaminrmb\LaravelDynamicResources\ModularResource;

// v2
use Benyaminrmb\LaravelDynamicResources\DynamicResource;
```

## Testing

```bash
composer test
```

## Static Analysis

```bash
composer analyse
```

## Code Style

```bash
composer format
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security-related issues, please email benyaminrmb@gmail.com instead of using the issue tracker.

## Credits

- [Benyamin Rmb](https://github.com/benyaminrmb)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
