# Laravel Dynamic Resources

[![Latest Version on Packagist](https://img.shields.io/packagist/v/benyaminrmb/laravel-dynamic-resources.svg)](https://packagist.org/packages/benyaminrmb/laravel-dynamic-resources)
[![Total Downloads](https://img.shields.io/packagist/dt/benyaminrmb/laravel-dynamic-resources.svg)](https://packagist.org/packages/benyaminrmb/laravel-dynamic-resources)

A flexible and powerful package for creating dynamic API resources in Laravel. This package extends Laravel's API Resources with features like modular modes (minimal, detailed, etc.), field filtering, and nested resource handling.

## Features

- 🔄 Multiple response modes with chainable methods
- 🎯 Field filtering with `only()` and `except()`
- 🔗 Automatic nested resource handling
- 🎨 Additional field support
- 🌲 Collection support with consistent formatting
- ⚡ Fluent interface for easy chaining
- 🔌 Dynamic mode combinations using `with` and `without` methods

## Requirements

- PHP 8.2 or higher
- Laravel 11.0 or higher

## Installation

You can install the package via composer:

```bash
composer require benyaminrmb/laravel-dynamic-resources
```

## Usage

### Basic Resource Definition

Create a new resource by extending `ModularResource`:

```php
use Benyaminrmb\LaravelDynamicResources\ModularResource;

class UserResource extends ModularResource
{
    protected function fields(): array
    {
        return [
            'minimal' => [
                'id',
                'username',
            ],
            'avatar' => [
                'profile' => UploadResource::make($this->channel->profile)->minimal(),
            ],
            'rank' => [
                'rank' => (int) $this->rank?->rank ?? 0,
            ],
            'default' => [
                'id',
                'username',
            ]
        ];
    }
}
```

### Using the Resource

```php
// Basic usage with single mode
return UserResource::make($user)->minimal();

// Combining multiple modes
return UserResource::make($user)
    ->minimal()
    ->withAvatar()
    ->withRank();

// Remove specific modes
return UserResource::make($user)
    ->minimal()
    ->withAvatar()
    ->withoutRank();

// Collection usage with modes
return UserResource::collection($users)
    ->minimal()
    ->withAvatar()
    ->withRank();

// Filter specific fields
return UserResource::make($user)
    ->minimal()
    ->withAvatar()
    ->only(['id', 'username', 'profile'])
    ->additional(['meta' => 'some value']);
```

### Available Features

#### Mode Combinations
You can combine different modes using the following methods:
- Basic modes: `minimal()`, `default()`, `detailed()`
- Add modes: `withAvatar()`, `withRank()`, etc.
- Remove modes: `withoutAvatar()`, `withoutRank()`, etc.

#### Field Filtering

```php
// Include only specific fields
UserResource::make($user)->only(['id', 'name']);

// Exclude specific fields
UserResource::make($user)->except(['created_at', 'updated_at']);
```

#### Additional Data

```php
UserResource::make($user)->additional([
    'meta' => [
        'version' => '1.0',
        'api_status' => 'stable'
    ]
]);
```

### Nested Resources

The package automatically handles nested resources and maintains the selected modes throughout the resource tree:

```php
class UserResource extends ModularResource
{
    protected function fields(): array
    {
        return [
            'minimal' => [
                'id',
                'name',
            ],
            'posts' => [
                'posts' => PostResource::collection($this->posts),
            ],
            'profile' => [
                'profile' => ProfileResource::make($this->profile),
            ],
            'default' => [
                'id',
                'name',
            ]
        ];
    }
}
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

## Security Vulnerabilities

If you discover any security-related issues, please email benyaminrmb@gmail.com instead of using the issue tracker.

## Credits

- [Benyamin Rmb](https://github.com/benyaminrmb)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
