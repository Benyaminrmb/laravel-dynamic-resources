<?php

declare(strict_types=1);

use Benyaminrmb\LaravelDynamicResources\Tests\Fixtures\Models\User;
use Benyaminrmb\LaravelDynamicResources\Tests\Fixtures\Resources\UserResource;

it('selects multiple views using IDE-discoverable constants', function (): void {
    $user = new User([
        'id' => 1,
        'name' => 'Taylor',
        'email' => 'taylor@example.com',
        'avatar' => 'avatar.jpg',
    ]);

    $result = UserResource::make($user)
        ->view([UserResource::VIEW_MINIMAL, UserResource::VIEW_AVATAR])
        ->toArray(request());

    expect($result)->toBe([
        'id' => 1,
        'name' => 'Taylor',
        'avatar' => 'avatar.jpg',
    ]);
});

it('adds and removes arrays of views', function (): void {
    $resource = UserResource::make(new User(['id' => 1, 'name' => 'Taylor']))
        ->view(UserResource::VIEW_MINIMAL)
        ->withView([UserResource::VIEW_AVATAR, UserResource::VIEW_TIMESTAMPS])
        ->withoutView([UserResource::VIEW_AVATAR, UserResource::VIEW_TIMESTAMPS]);

    expect($resource->getActiveViews())->toBe([UserResource::VIEW_MINIMAL]);
});

it('provides real fluent selector methods', function (): void {
    $resource = UserResource::make(new User(['id' => 1, 'name' => 'Taylor', 'avatar' => 'avatar.jpg']))
        ->minimal()
        ->withAvatar();

    expect(method_exists($resource, 'minimal'))->toBeTrue()
        ->and(method_exists($resource, 'withAvatar'))->toBeTrue()
        ->and($resource->toArray(request()))->toHaveKeys(['id', 'name', 'avatar']);
});

it('reuses view methods in a named composition', function (): void {
    $result = UserResource::make(new User([
        'id' => 1,
        'name' => 'Taylor',
        'avatar' => 'avatar.jpg',
    ]))->view(UserResource::VIEW_MINIMAL_WITH_AVATAR)->toArray(request());

    expect($result)->toBe(['id' => 1, 'name' => 'Taylor', 'avatar' => 'avatar.jpg']);
});
