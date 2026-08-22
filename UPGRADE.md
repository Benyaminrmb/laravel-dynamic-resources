# Upgrade guide

## Upgrading from 0.x or the unfinished v2 branch to v2.0

Version 2 requires PHP 8.3+ and Laravel 12 or 13.

### Base resource

Replace the legacy base class:

```php
// Before
use Benyaminrmb\LaravelDynamicResources\ModularResource;

// After
use Benyaminrmb\LaravelDynamicResources\DynamicResource;
```

`fields()` is public and returns mode names mapped to field arrays or closures.

### Mode inheritance and selection

Inside `fields()`, replace `$this->mode('default')` with `$this->fieldsFrom('default')`.

In controllers, magic methods continue to work. Explicit methods are now available:

```php
$resource->mode('minimal')->withMode('avatar')->withoutMode('avatar');
```

Undefined explicit modes now throw `UndefinedModeException`.

### Nested resources

Nested resources no longer inherit parent modes. Select the child mode where it is defined:

```php
'posts' => PostResource::collection($this->whenLoaded('posts'))->mode('minimal'),
```

### Request fields

Remove the `resource.fields` route middleware. Call `fromRequest($request)` in the controller and allowlist modes on the resource:

```php
protected function requestableModes(): array
{
    return ['minimal', 'default'];
}
```

Client mode selection is denied by default. Invalid selections return HTTP 400.

### Relationships

Remove `loadRelations()`, `loadRelation()`, and relationship attributes. Eager-load relations in queries and serialize them with `whenLoaded()`.

### Additional data

`additional()` follows Laravel behavior and is no longer part of `toArray()` or repeated in collection items. Inspect the complete response when testing metadata.

### Removed APIs

Remove imports for the legacy provider, attribute classes, middleware, transformation DTOs/contracts, and old collection classes. The package provider remains auto-discovered.

Rename `php artisan resource:list` to `php artisan dynamic-resources:list`.
