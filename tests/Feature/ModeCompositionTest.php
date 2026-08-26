<?php

declare(strict_types=1);

use Benyaminrmb\LaravelDynamicResources\Tests\Fixtures\Models\User;
use Benyaminrmb\LaravelDynamicResources\Tests\Fixtures\Resources\UserResource;

it('composes multiple modes with withX methods', function (): void {
    $user = new User([
        'id' => 1,
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'avatar' => 'avatar.jpg',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $result = UserResource::make($user)
        ->view('minimal')
        ->withView('avatar')
        ->withView('timestamps')
        ->toArray(request());

    expect($result)
        ->toHaveKey('id', 1)
        ->toHaveKey('name', 'John Doe')
        ->toHaveKey('avatar', 'avatar.jpg')
        ->toHaveKey('created_at')
        ->toHaveKey('updated_at')
        ->not->toHaveKey('email');
});

it('removes modes with withoutX methods', function (): void {
    $user = new User([
        'id' => 1,
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'avatar' => 'avatar.jpg',
    ]);

    $result = UserResource::make($user)
        ->view('minimal')
        ->withView('avatar')
        ->withoutView('avatar')
        ->toArray(request());

    expect($result)
        ->toHaveKey('id', 1)
        ->toHaveKey('name', 'John Doe')
        ->not->toHaveKey('avatar')
        ->not->toHaveKey('email');
});

it('tracks active modes correctly', function (): void {
    $user = new User(['id' => 1, 'name' => 'Test']);
    $resource = UserResource::make($user);

    expect($resource->getActiveViews())->toBe(['standard']);

    $resource->view('minimal');
    expect($resource->getActiveViews())->toBe(['minimal']);

    $resource->withView('avatar');
    expect($resource->getActiveViews())->toBe(['minimal', 'avatar']);

    $resource->withoutView('minimal');
    expect($resource->getActiveViews())->toBe(['avatar']);
});

it('falls back to default when all modes removed', function (): void {
    $user = new User(['id' => 1, 'name' => 'Test']);
    $resource = UserResource::make($user)->view('minimal')->withoutView('minimal');

    expect($resource->getActiveViews())->toBe(['standard']);
});

it('marks mode as explicitly set', function (): void {
    $user = new User(['id' => 1, 'name' => 'Test']);
    $resource = UserResource::make($user);

    expect($resource->hasViewExplicitlySet())->toBeFalse();

    $resource->view('minimal');
    expect($resource->hasViewExplicitlySet())->toBeTrue();
});

it('allows chaining mode methods', function (): void {
    $user = new User([
        'id' => 1,
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'avatar' => 'avatar.jpg',
    ]);

    $resource = UserResource::make($user)
        ->view('minimal')
        ->withView('avatar')
        ->withView('timestamps')
        ->withoutView('timestamps');

    expect($resource->getActiveViews())
        ->toBe(['minimal', 'avatar']);
});

it('does not duplicate modes when adding same mode twice', function (): void {
    $user = new User(['id' => 1, 'name' => 'Test']);
    $resource = UserResource::make($user)
        ->view('minimal')
        ->withView('avatar')
        ->withView('avatar')
        ->withView('avatar');

    expect($resource->getActiveViews())->toBe(['minimal', 'avatar']);
});
