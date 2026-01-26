<?php

declare(strict_types=1);

use Benyaminrmb\LaravelDynamicResources\Tests\Fixtures\Models\User;
use Benyaminrmb\LaravelDynamicResources\Tests\Fixtures\Resources\UserResource;

it('applies mode to all collection items', function (): void {
    $users = collect([
        new User(['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com']),
        new User(['id' => 2, 'name' => 'Bob', 'email' => 'bob@example.com']),
    ]);

    $result = UserResource::collection($users)->minimal()->toArray(request());

    expect($result)->toHaveCount(2);
    expect($result[0])
        ->toHaveKey('id', 1)
        ->toHaveKey('name', 'Alice')
        ->not->toHaveKey('email');
    expect($result[1])
        ->toHaveKey('id', 2)
        ->toHaveKey('name', 'Bob')
        ->not->toHaveKey('email');
});

it('handles empty collection', function (): void {
    $result = UserResource::collection(collect())->minimal()->toArray(request());

    expect($result)->toBe([]);
});

it('handles single item collection', function (): void {
    $users = collect([
        new User(['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com']),
    ]);

    $result = UserResource::collection($users)->minimal()->toArray(request());

    expect($result)->toHaveCount(1);
    expect($result[0])
        ->toHaveKey('id', 1)
        ->toHaveKey('name', 'Alice')
        ->not->toHaveKey('email');
});

it('applies composed modes to collection', function (): void {
    $users = collect([
        new User([
            'id' => 1,
            'name' => 'Alice',
            'avatar' => 'alice.jpg',
            'created_at' => now(),
        ]),
    ]);

    $result = UserResource::collection($users)
        ->minimal()
        ->withAvatar()
        ->toArray(request());

    expect($result[0])
        ->toHaveKey('id', 1)
        ->toHaveKey('name', 'Alice')
        ->toHaveKey('avatar', 'alice.jpg');
});

it('applies field filtering to collection', function (): void {
    $users = collect([
        new User(['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com']),
        new User(['id' => 2, 'name' => 'Bob', 'email' => 'bob@example.com']),
    ]);

    $result = UserResource::collection($users)
        ->default()
        ->only(['id', 'name'])
        ->toArray(request());

    expect($result[0])
        ->toHaveKey('id', 1)
        ->toHaveKey('name', 'Alice')
        ->not->toHaveKey('email');
});

it('applies additional data to collection', function (): void {
    $users = collect([
        new User(['id' => 1, 'name' => 'Alice']),
    ]);

    $result = UserResource::collection($users)
        ->minimal()
        ->additional(['meta' => 'data'])
        ->toArray(request());

    expect($result[0])
        ->toHaveKey('meta', 'data');
});

it('works with array input', function (): void {
    $users = [
        new User(['id' => 1, 'name' => 'Alice']),
        new User(['id' => 2, 'name' => 'Bob']),
    ];

    $result = UserResource::collection($users)->minimal()->toArray(request());

    expect($result)->toHaveCount(2);
});

it('filters out null items', function (): void {
    $users = collect([
        new User(['id' => 1, 'name' => 'Alice']),
        null,
        new User(['id' => 2, 'name' => 'Bob']),
    ]);

    $result = UserResource::collection($users)->minimal()->toArray(request());

    expect($result)->toHaveCount(2);
    expect($result[0]['name'])->toBe('Alice');
    expect($result[1]['name'])->toBe('Bob');
});
