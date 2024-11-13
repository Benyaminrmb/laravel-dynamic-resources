# Laravel Modular Resources

A flexible and powerful package for creating modular API resources in Laravel with support for different presentation modes, field filtering, and nested resources.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/your-vendor/laravel-modular-resources.svg?style=flat-square)](https://packagist.org/packages/your-vendor/laravel-modular-resources)
[![Total Downloads](https://img.shields.io/packagist/dt/your-vendor/laravel-modular-resources.svg?style=flat-square)](https://packagist.org/packages/your-vendor/laravel-modular-resources)

## Features

- 🔄 Multiple presentation modes (minimal, default, detailed, etc.)
- 🔍 Field filtering with `only()` and `except()`
- 🎯 Support for nested resources with independent modes
- 📦 Collection support with mode inheritance
- ⚡ Fluent interface for easy configuration
- 🛠️ Fully type-hinted for better IDE support

## Installation

You can install the package via composer:

```bash
composer require your-vendor/laravel-modular-resources
```

## Basic Usage

### 1. Create a Resource

Create a new resource by extending the `ModularResource` class:

```php
use YourVendor\LaravelModularResources\ModularResource;

class UserResource extends ModularResource
{
    protected function fields(): array
    {
        return match ($this->mode) {
            'minimal' => [
                'id',
                'username',
            ],
            'detailed' => [
                'id',
                'username',
                'email',
                'profile' => ProfileResource::make($this->profile)->minimal(),
                'posts' => PostResource::collection($this->posts)->detailed(),
            ],
            default => [
                'id',
                'username',
                'email',
            ],
        };
    }
}
```

### 2. Use the Resource

```php
// Basic usage
return UserResource::make($user);

// With specific mode
return UserResource::make($user)->detailed();

// Filter fields
return UserResource::make($user)
    ->detailed()
    ->only(['id', 'username']);

// Collection with mode
return UserResource::collection($users)->minimal();

// Add additional fields
return UserResource::make($user)
    ->detailed()
    ->additional(['meta' => ['timestamp' => now()]]);
```

## Advanced Features

### Custom Modes

You can define any custom modes in your resources:

```php
class ProductResource extends ModularResource
{
    protected function fields(): array
    {
        return match ($this->mode) {
            'cart' => [
                'id',
                'name',
                'price',
                'quantity',
            ],
            'wishlist' => [
                'id',
                'name',
                'price',
                'in_stock',
            ],
            default => [
                'id',
                'name',
            ],
        };
    }
}
```

### Nested Resources

Resources can be nested with independent modes:

```php
class OrderResource extends ModularResource
{
    protected function fields(): array
    {
        return match ($this->mode) {
            'detailed' => [
                'id',
                'total',
                'customer' => UserResource::make($this->user)->minimal(),
                'products' => ProductResource::collection($this->products)->cart(),
            ],
            default => [
                'id',
                'total',
            ],
        };
    }
}
```

### Field Filtering

Filter specific fields in or out:

```php
// Include only specific fields
UserResource::make($user)
    ->detailed()
    ->only(['id', 'username', 'posts']);

// Exclude specific fields
UserResource::make($user)
    ->detailed()
    ->except(['email', 'phone']);
```

### Additional Data

Add extra data to your resources:

```php
UserResource::make($user)
    ->additional([
        'meta' => [
            'server_time' => now(),
            'version' => '1.0',
        ],
    ]);
```

## Available Methods

### Resource Methods

- `minimal()` - Set minimal mode
- `detailed()` - Set detailed mode
- `default()` - Set default mode
- `setMode(string $mode)` - Set custom mode
- `only(array $fields)` - Include only specific fields
- `except(array $fields)` - Exclude specific fields
- `additional(array $data)` - Add additional data

### Collection Methods

All resource methods are available for collections too:

```php
UserResource::collection($users)
    ->minimal()
    ->except(['created_at'])
    ->additional(['meta' => ['total' => $users->count()]]);
```

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security Vulnerabilities

If you discover any security related issues, please email your@email.com instead of using the issue tracker.

## Credits

- [Your Name](https://github.com/yourusername)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
