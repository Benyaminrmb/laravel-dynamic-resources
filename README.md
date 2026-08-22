# Laravel Dynamic Resources

[![Latest Version on Packagist](https://img.shields.io/packagist/v/benyaminrmb/laravel-dynamic-resources.svg)](https://packagist.org/packages/benyaminrmb/laravel-dynamic-resources)
[![Tests](https://github.com/Benyaminrmb/laravel-dynamic-resources/actions/workflows/test.yml/badge.svg)](https://github.com/Benyaminrmb/laravel-dynamic-resources/actions/workflows/test.yml)
[![License](https://img.shields.io/packagist/l/benyaminrmb/laravel-dynamic-resources.svg)](LICENSE.md)

Composable, mode-based Laravel API resources with predictable field filtering and explicit, safe sparse fieldsets.

## Why use it?

Laravel resources are excellent at describing one response shape. This package keeps that familiar API while making several intentional shapes easy to reuse:

- Define `minimal`, `default`, `detailed`, or domain-specific modes in one resource.
- Compose optional field groups with fluent or magic methods.
- Narrow output on the server with `only()` and `except()`.
- Explicitly opt in to client-selected modes and dotted sparse fields.
- Keep nested modes and database queries predictable.
- Preserve Laravel's native responses, pagination, wrapping, and metadata.

## Requirements

| Package | Supported versions |
|---|---|
| PHP | 8.3, 8.4, 8.5 |
| Laravel | 12, 13 |

## Installation

```bash
composer require benyaminrmb/laravel-dynamic-resources
```

Laravel discovers the service provider automatically. Publish the optional configuration when you need different request parameter names or limits:

```bash
php artisan vendor:publish --tag=dynamic-resources-config
```

## Quick start

Generate a resource:

```bash
php artisan make:dynamic-resource UserResource --model=User
```

Define its modes:

```php
<?php

namespace App\Http\Resources;

use Benyaminrmb\LaravelDynamicResources\DynamicResource;

class UserResource extends DynamicResource
{
    public function fields(): array
    {
        return [
            'minimal' => ['id', 'name'],

            'default' => ['id', 'name', 'email'],

            'detailed' => fn (): array => [
                ...$this->fieldsFrom('default'),
                'bio' => $this->bio,
                'created_at' => $this->created_at?->toISOString(),
                'posts' => PostResource::collection($this->whenLoaded('posts'))
                    ->mode('minimal'),
            ],

            'avatar' => fn (): array => [
                'avatar_url' => $this->avatar_url,
            ],
        ];
    }
}
```

Use explicit methods when discoverability matters:

```php
return UserResource::make($user)
    ->mode('minimal')
    ->withMode('avatar');
```

Or use the equivalent concise magic API:

```php
return UserResource::make($user)
    ->minimal()
    ->withAvatar();
```

Both APIs also work on collections and paginators:

```php
return UserResource::collection(
    User::query()->paginate()
)->mode('minimal');
```

## Defining fields

Numeric entries lazily read the matching model attribute. Named entries may contain transformed values, closures, nested resources, or Laravel's conditional helpers:

```php
'default' => fn (): array => [
    'id',
    'display_name' => fn () => trim("{$this->first_name} {$this->last_name}"),
    'avatar' => $this->when($this->avatar !== null, $this->avatar),
    'posts_count' => $this->whenCounted('posts'),
    'posts' => PostResource::collection($this->whenLoaded('posts')),
],
```

Use `fieldsFrom()` inside a field definition to inherit another mode. Circular inheritance throws a descriptive exception.

When composed modes define the same key, the later mode wins:

```php
UserResource::make($user)
    ->mode('minimal')
    ->withMode('avatar')
    ->withoutMode('avatar');
```

An unknown explicit mode throws `UndefinedModeException` instead of returning an accidental empty response.

## Server-side field filtering

```php
return UserResource::make($user)
    ->mode('detailed')
    ->only(['id', 'name', 'email']);

return UserResource::collection($users)
    ->mode('default')
    ->except(['email']);
```

Calling `only()` clears an earlier `except()`, and vice versa.

## Safe request selection

Request selection is never automatic. A controller must explicitly call `fromRequest()`:

```php
public function show(Request $request, User $user): UserResource
{
    return UserResource::make($user)->fromRequest($request);
}
```

Resources must separately allowlist every client-selectable mode:

```php
protected function requestableModes(): array
{
    return ['minimal', 'default', 'detailed'];
}
```

Clients may then request:

```text
GET /api/users/1?mode=minimal
GET /api/users/1?fields=id,name
GET /api/users/1?exclude=email
GET /api/users/1?fields=id,posts.id,posts.title
```

Field selection only narrows fields already present in the selected mode. It cannot add a field or override a server-side `only()`/`except()` restriction. Dotted paths are propagated to nested dynamic resources and collections.

Malformed, excessively deep, oversized, or non-allowlisted requests throw `InvalidResourceQueryException`, which Laravel renders as HTTP 400. Limits and parameter names live in `config/dynamic-resources.php`.

## Nested resources and relationships

Every nested resource uses its own default mode unless you choose one explicitly:

```php
'posts' => PostResource::collection($this->whenLoaded('posts'))
    ->mode('minimal'),
```

Resources never query the database. Eager-load relationships in the controller or query layer and use Laravel's conditional helpers:

```php
$user = User::query()->with('posts.author')->findOrFail($id);

return UserResource::make($user)->mode('detailed');
```

This makes serialization free of hidden N+1 queries.

## Responses, metadata, and pagination

The package follows Laravel's normal resource response behavior. `additional()` belongs at the response top level and is not inserted into every item:

```php
return UserResource::collection(User::query()->paginate())
    ->mode('minimal')
    ->additional(['meta' => ['api_version' => 2]]);
```

Laravel continues to provide the `data`, `links`, and pagination `meta` structures.

## Artisan commands

```bash
php artisan make:dynamic-resource UserResource --model=User
php artisan make:dynamic-resource UserResource --modes=minimal,default,detailed
php artisan dynamic-resources:list --modes
php artisan dynamic-resources:list --json
```

Generated mode names must be lowercase kebab-case and must include `default`.

## Upgrading from v1

Version 2 is a deliberate cleanup and contains breaking changes. See [UPGRADE.md](UPGRADE.md) for exact replacements.

## Quality and support

```bash
composer test
composer test:coverage
composer analyse
composer format:check
composer check
```

See [CONTRIBUTING.md](CONTRIBUTING.md) for development guidance and [SECURITY.md](SECURITY.md) for private vulnerability reporting.

## License

Laravel Dynamic Resources is open-source software licensed under the [MIT license](LICENSE.md).
