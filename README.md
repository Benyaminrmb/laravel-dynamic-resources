# Laravel Dynamic Resources

Define every API representation of a model in one Laravel resource. Views are composable, safe for loaded relationships, and represented by real PHP constants and methods that IDEs can discover.

## Installation

```bash
composer require benyaminrmb/laravel-dynamic-resources
php artisan make:dynamic-resource UserResource --model=User
```

Laravel 12 and 13 and PHP 8.3–8.5 are supported.

## One resource, every response

```php
<?php

namespace App\Http\Resources;

use Benyaminrmb\LaravelDynamicResources\Attributes\View;
use Benyaminrmb\LaravelDynamicResources\DynamicResource;

class UserResource extends DynamicResource
{
    #[View(default: true, requestable: true)]
    protected function standardFields(): array
    {
        return ['id', 'name', 'family', 'email'];
    }

    #[View(requestable: true)]
    protected function minimalFields(): array
    {
        return ['id', 'name', 'family'];
    }

    #[View]
    protected function avatarFields(): array
    {
        return ['avatar'];
    }

    #[View(requestable: true)]
    protected function minimalWithAvatarFields(): array
    {
        return $this->combine(
            $this->minimalFields(),
            $this->avatarFields(),
        );
    }

    #[View]
    protected function detailedFields(): array
    {
        return $this->combine($this->standardFields(), [
            'bio',
            'posts' => $this->relation(
                'posts',
                view: PostResource::VIEW_MINIMAL,
            ),
        ]);
    }

    // <dynamic-resource-views>
    // Constants and fluent selector methods are generated here.
    // </dynamic-resource-views>
}
```

The method name becomes the kebab-case view name: `minimalWithAvatarFields()` defines `minimal-with-avatar`. Use `name:` on `#[View]` when an explicit name is clearer.

Exactly one view must have `default: true`. Only views with `requestable: true` may be selected through HTTP query parameters.

## IDE-discoverable selection

Synchronize real constants and fluent methods after adding or renaming a view:

```bash
php artisan dynamic-resources:sync
```

Both forms are real PHP—not runtime magic:

```php
return UserResource::make($user)
    ->view(UserResource::VIEW_MINIMAL);

return UserResource::make($user)
    ->minimal()
    ->withAvatar();
```

Use the check command in CI:

```bash
php artisan dynamic-resources:sync --check
```

## Composing views

Pass one view or several:

```php
return UserResource::make($user)->view([
    UserResource::VIEW_MINIMAL,
    UserResource::VIEW_AVATAR,
]);

return UserResource::collection($users)
    ->view(UserResource::VIEW_MINIMAL)
    ->withView([UserResource::VIEW_AVATAR, UserResource::VIEW_TIMESTAMPS]);
```

Views resolve from left to right; later fields replace matching earlier fields. `withoutView()` accepts a string or array too.

For a combination used throughout the application, define a named view and call the component methods directly with `combine()`, as shown by `minimalWithAvatarFields()`. This gives PHPStorm and refactoring tools real method references.

## Fields and relationships

String entries lazily read the corresponding model attribute. Named entries transform or condition values using the normal Laravel resource API:

```php
return [
    'id',
    'display_name' => fn () => trim("{$this->name} {$this->family}"),
    'avatar' => $this->when($this->avatar !== null, $this->avatar),
    'posts_count' => $this->whenCounted('posts'),
];
```

`relation()` only serializes an already-loaded relationship. It never runs a query:

```php
'posts' => $this->relation('posts', view: PostResource::VIEW_MINIMAL),
```

It resolves the related resource through Laravel conventions, supports one/many relations, and accepts `resourceClass:` when a project uses a custom location.

## Filtering and safe request views

```php
return UserResource::make($user)
    ->view(UserResource::VIEW_DETAILED)
    ->only(['id', 'name']);

return UserResource::collection($users)
    ->except(['email']);
```

Request selection is explicit:

```php
return UserResource::make($user)->fromRequest($request);
```

Clients may request allowlisted views and sparse fields:

```text
GET /api/users/1?view=minimal
GET /api/users/1?fields=id,name
GET /api/users/1?exclude=email
GET /api/users/1?fields=id,posts.id,posts.title
```

Malformed, oversized, too-deep, or non-requestable selections produce HTTP 400 responses. Parameter names and limits are configurable in `config/dynamic-resources.php`.

## Commands

```bash
php artisan make:dynamic-resource UserResource --model=User
php artisan make:dynamic-resource UserResource --views=minimal,default,detailed
php artisan dynamic-resources:list --views
php artisan dynamic-resources:sync
php artisan dynamic-resources:sync --check
```

See [UPGRADE.md](UPGRADE.md) for migration from v2.

## Quality

```bash
composer test
composer test:coverage
composer analyse
composer format:check
```

Laravel Dynamic Resources is open-source software licensed under the [MIT license](LICENSE.md).
