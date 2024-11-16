# Laravel Dynamic Resources

[![Latest Version on Packagist](https://img.shields.io/packagist/v/benyaminrmb/laravel-dynamic-resources.svg)](https://packagist.org/packages/benyaminrmb/laravel-dynamic-resources)
[![Total Downloads](https://img.shields.io/packagist/dt/benyaminrmb/laravel-dynamic-resources.svg)](https://packagist.org/packages/benyaminrmb/laravel-dynamic-resources)


A flexible and powerful package for creating dynamic API resources in Laravel. This package extends Laravel's API Resources with features like modes (minimal, default, detailed), field filtering, and nested resource handling.

## Features

- 🔄 Multiple response modes (minimal, default, detailed)
- 🎯 Field filtering with `only()` and `except()`
- 🔗 Automatic nested resource handling
- 🎨 Additional field support
- 🌲 Collection support with consistent formatting
- ⚡ Fluent interface for easy chaining

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
        return match($this->mode) {
            'minimal' => [
                'id',
                'name',
            ],
            'detailed' => [
                'id',
                'name',
                'email',
                'created_at',
                'updated_at',
                'posts' => PostResource::collection($this->posts),
            ],
            default => [
                'id',
                'name',
                'email',
                'created_at',
            ],
        };
    }
}
```

### Using the Resource

```php
// Basic usage
return UserResource::make($user);

// With mode selection
return UserResource::make($user)->minimal();
return UserResource::make($user)->detailed();

// Filter specific fields
return UserResource::make($user)
    ->only(['id', 'name'])
    ->additional(['meta' => 'some value']);

// Collection usage
return UserResource::collection($users)
    ->detailed()
    ->except(['created_at', 'updated_at']);
```

### Available Modes

- `minimal()`: Returns minimal data
- `default()` or `basic()`: Returns default data set
- `detailed()`: Returns complete data set with relations

### Field Filtering

```php
// Include only specific fields
UserResource::make($user)->only(['id', 'name']);

// Exclude specific fields
UserResource::make($user)->except(['created_at', 'updated_at']);
```

### Additional Data

```php
UserResource::make($user)->additional([
    'meta' => [
        'version' => '1.0',
        'api_status' => 'stable'
    ]
]);
```

### Nested Resources

The package automatically handles nested resources and maintains the selected mode throughout the resource tree:

```php
class UserResource extends ModularResource
{
    protected function fields(): array
    {
        return [
            'id',
            'name',
            'posts' => PostResource::collection($this->posts),
            'profile' => ProfileResource::make($this->profile),
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
