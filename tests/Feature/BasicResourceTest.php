<?php

declare(strict_types=1);

use Benyaminrmb\LaravelDynamicResources\Tests\Fixtures\Models\User;
use Benyaminrmb\LaravelDynamicResources\Tests\Fixtures\Resources\UserResource;

it('returns default mode fields when no mode is specified', function (): void {
    $user = new User([
        'id' => 1,
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'bio' => 'Developer',
    ]);

    $result = UserResource::make($user)->toArray(request());

    expect($result)
        ->toHaveKey('id', 1)
        ->toHaveKey('name', 'John Doe')
        ->toHaveKey('email', 'john@example.com')
        ->not->toHaveKey('bio');
});

it('returns minimal mode fields', function (): void {
    $user = new User([
        'id' => 1,
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $result = UserResource::make($user)->minimal()->toArray(request());

    expect($result)
        ->toHaveKey('id', 1)
        ->toHaveKey('name', 'John Doe')
        ->not->toHaveKey('email');
});

it('returns detailed mode fields with inheritance', function (): void {
    $user = new User([
        'id' => 1,
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'bio' => 'Developer',
        'created_at' => now(),
    ]);

    $result = UserResource::make($user)->detailed()->toArray(request());

    expect($result)
        ->toHaveKey('id', 1)
        ->toHaveKey('name', 'John Doe')
        ->toHaveKey('email', 'john@example.com')
        ->toHaveKey('bio', 'Developer')
        ->toHaveKey('created_at');
});

it('handles null resource', function (): void {
    $result = UserResource::make(null)->toArray(request());

    expect($result)->toBe([]);
});

it('handles null attribute values', function (): void {
    $user = new User([
        'id' => 1,
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'bio' => null,
    ]);

    $result = UserResource::make($user)->detailed()->toArray(request());

    expect($result)
        ->toHaveKey('bio')
        ->and($result['bio'])->toBeNull();
});

it('merges additional data', function (): void {
    $user = new User([
        'id' => 1,
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $result = UserResource::make($user)
        ->minimal()
        ->additional(['meta' => ['version' => '2.0']])
        ->toArray(request());

    expect($result)
        ->toHaveKey('id', 1)
        ->toHaveKey('name', 'John Doe')
        ->toHaveKey('meta')
        ->and($result['meta'])->toBe(['version' => '2.0']);
});

it('returns available modes', function (): void {
    $user = new User(['id' => 1, 'name' => 'Test']);
    $resource = UserResource::make($user);

    expect($resource->getAvailableModes())
        ->toContain('minimal')
        ->toContain('default')
        ->toContain('detailed')
        ->toContain('timestamps')
        ->toContain('avatar')
        ->toContain('posts')
        ->toContain('profile');
});
