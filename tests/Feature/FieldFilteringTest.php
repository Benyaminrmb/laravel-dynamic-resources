<?php

declare(strict_types=1);

use Benyaminrmb\LaravelDynamicResources\Tests\Fixtures\Models\User;
use Benyaminrmb\LaravelDynamicResources\Tests\Fixtures\Resources\UserResource;

it('includes only specified fields with only()', function (): void {
    $user = new User([
        'id' => 1,
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $result = UserResource::make($user)
        ->default()
        ->only(['id', 'name'])
        ->toArray(request());

    expect($result)
        ->toHaveKey('id', 1)
        ->toHaveKey('name', 'John Doe')
        ->not->toHaveKey('email');
});

it('excludes specified fields with except()', function (): void {
    $user = new User([
        'id' => 1,
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $result = UserResource::make($user)
        ->default()
        ->except(['email'])
        ->toArray(request());

    expect($result)
        ->toHaveKey('id', 1)
        ->toHaveKey('name', 'John Doe')
        ->not->toHaveKey('email');
});

it('only() clears except() and vice versa', function (): void {
    $user = new User([
        'id' => 1,
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $resource = UserResource::make($user)->default();

    $resource->except(['email']);
    expect($resource->getExceptFields())->toBe(['email']);
    expect($resource->getOnlyFields())->toBe([]);

    $resource->only(['id']);
    expect($resource->getOnlyFields())->toBe(['id']);
    expect($resource->getExceptFields())->toBe([]);
});

it('filters fields across composed modes', function (): void {
    $user = new User([
        'id' => 1,
        'name' => 'John Doe',
        'avatar' => 'avatar.jpg',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $result = UserResource::make($user)
        ->minimal()
        ->withAvatar()
        ->withTimestamps()
        ->only(['id', 'avatar', 'created_at'])
        ->toArray(request());

    expect($result)
        ->toHaveKey('id', 1)
        ->toHaveKey('avatar', 'avatar.jpg')
        ->toHaveKey('created_at')
        ->not->toHaveKey('name')
        ->not->toHaveKey('updated_at');
});

it('except() filters across composed modes', function (): void {
    $user = new User([
        'id' => 1,
        'name' => 'John Doe',
        'avatar' => 'avatar.jpg',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $result = UserResource::make($user)
        ->minimal()
        ->withAvatar()
        ->withTimestamps()
        ->except(['updated_at', 'name'])
        ->toArray(request());

    expect($result)
        ->toHaveKey('id', 1)
        ->toHaveKey('avatar', 'avatar.jpg')
        ->toHaveKey('created_at')
        ->not->toHaveKey('name')
        ->not->toHaveKey('updated_at');
});

it('handles only() with non-existent fields gracefully', function (): void {
    $user = new User([
        'id' => 1,
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $result = UserResource::make($user)
        ->default()
        ->only(['id', 'nonexistent'])
        ->toArray(request());

    expect($result)
        ->toHaveKey('id', 1)
        ->not->toHaveKey('nonexistent')
        ->not->toHaveKey('name')
        ->not->toHaveKey('email');
});

it('handles except() with non-existent fields gracefully', function (): void {
    $user = new User([
        'id' => 1,
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $result = UserResource::make($user)
        ->default()
        ->except(['nonexistent'])
        ->toArray(request());

    expect($result)
        ->toHaveKey('id', 1)
        ->toHaveKey('name', 'John Doe')
        ->toHaveKey('email', 'john@example.com');
});

it('keeps additional response data separate from field filtering', function (): void {
    $user = new User([
        'id' => 1,
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $result = UserResource::make($user)
        ->default()
        ->only(['id'])
        ->additional(['meta' => ['timestamp' => '2024-01-01']])
        ->response()
        ->getData(true);

    expect($result)
        ->toHaveKey('meta')
        ->and($result['meta'])->toBe(['timestamp' => '2024-01-01'])
        ->and($result['data'])->toBe(['id' => 1]);
});
