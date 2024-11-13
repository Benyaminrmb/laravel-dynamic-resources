# Laravel Dynamic Resources

[![Tests](https://github.com/benyaminrmb/laravel-dynamic-resources/actions/workflows/tests.yml/badge.svg)](https://github.com/benyaminrmb/laravel-dynamic-resources/actions/workflows/tests.yml)
[![Latest Stable Version](https://poser.pugx.org/benyaminrmb/laravel-dynamic-resources/v)](https://packagist.org/packages/benyaminrmb/laravel-dynamic-resources)
[![License](https://poser.pugx.org/benyaminrmb/laravel-dynamic-resources/license)](https://packagist.org/packages/benyaminrmb/laravel-dynamic-resources)

A flexible and powerful package for creating dynamic API resources in Laravel applications.

## Installation

You can install the package via composer:

```bash
composer require benyaminrmb/laravel-dynamic-resources
```

## Usage

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
                'phone',
                'address',
                'created_at',
            ],
            default => [
                'id',
                'name',
                'email',
            ],
        };
    }
}

// Usage in controller
return UserResource::collection($users)->minimal();
// or
return (new UserResource($user))->detailed();
```

### Available Modes

- `minimal()`: Returns only essential fields
- `default()`: Returns standard fields
- `detailed()`: Returns all available fields
- `basic()`: Alias for default mode

### Additional Methods

- `only(['field1', 'field2'])`: Include only specific fields
- `except(['field1', 'field2'])`: Exclude specific fields
- `additional(['meta' => [...]])`: Add extra fields

## Testing

```bash
composer test
```

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
