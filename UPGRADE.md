# Upgrade guide

## Upgrading from v2 to v3

Version 3 replaces modes and runtime magic with attributed, IDE-discoverable views.

### Resource definitions

Replace the outer `fields()` array:

```php
// v2
public function fields(): array
{
    return [
        'minimal' => ['id', 'name'],
        'detailed' => fn () => [
            ...$this->fieldsFrom('minimal'),
            'email',
        ],
    ];
}
```

With one method per view:

```php
// v3
#[View(default: true, requestable: true)]
protected function minimalFields(): array
{
    return ['id', 'name'];
}

#[View(requestable: true)]
protected function detailedFields(): array
{
    return $this->combine($this->minimalFields(), ['email']);
}
```

Remove `requestableModes()` and mark allowed methods with `requestable: true`.

### Selecting views

```php
// v2
$resource->mode('minimal')->withMode('avatar');

// v3: explicit and statically analyzable
$resource->view(UserResource::VIEW_MINIMAL)
    ->withView(UserResource::VIEW_AVATAR);

// v3: real generated methods
$resource->minimal()->withAvatar();
```

Run `php artisan dynamic-resources:sync` after migrating each resource. Add `dynamic-resources:sync --check` to CI.

### Request parameters and commands

- Rename the query parameter `mode` to `view`.
- Rename `dynamic-resources.request.mode_parameter` to `view_parameter`.
- Replace `--modes` with `--views` on package commands.
- Replace `UndefinedModeException` with `UndefinedViewException`.

### Relationships

Existing Laravel conditional helpers remain supported. The shorter loaded-only form is:

```php
'posts' => $this->relation('posts', view: PostResource::VIEW_MINIMAL),
```

`relation()` never loads a missing relationship or performs a database query.
