<?php

declare(strict_types=1);

use Benyaminrmb\LaravelDynamicResources\DynamicResource;
use Benyaminrmb\LaravelDynamicResources\Exceptions\CircularReferenceException;
use Benyaminrmb\LaravelDynamicResources\Exceptions\UndefinedModeException;
use Benyaminrmb\LaravelDynamicResources\Tests\Fixtures\Models\User;
use Benyaminrmb\LaravelDynamicResources\Tests\Fixtures\Resources\UserResource;

it('supports explicit mode composition methods', function (): void {
    $resource = UserResource::make(new User(['id' => 1, 'name' => 'Taylor', 'avatar' => 'a.jpg']))
        ->mode('minimal')
        ->withMode('avatar')
        ->withoutMode('avatar');

    expect($resource->getActiveModes())->toBe(['minimal'])
        ->and($resource->toArray(request()))->toBe(['id' => 1, 'name' => 'Taylor']);
});

it('throws for undefined explicit modes', function (): void {
    expect(fn () => UserResource::make(new User)->mode('unknown'))
        ->toThrow(UndefinedModeException::class);
});

it('detects circular field inheritance', function (): void {
    $resource = new class(new User) extends DynamicResource
    {
        public function fields(): array
        {
            return [
                'default' => fn (): array => $this->fieldsFrom('loop'),
                'loop' => fn (): array => $this->fieldsFrom('default'),
            ];
        }
    };

    expect(fn () => $resource->toArray(request()))
        ->toThrow(CircularReferenceException::class);
});

it('does not leak a parent mode into a child resource', function (): void {
    $child = new class(new User(['id' => 2, 'name' => 'Child', 'email' => 'child@example.com'])) extends DynamicResource
    {
        public function fields(): array
        {
            return [
                'default' => ['id', 'name'],
                'private' => ['id', 'email'],
            ];
        }
    };

    $parent = new class(new User(['id' => 1]), $child) extends DynamicResource
    {
        public function __construct(mixed $resource, private readonly DynamicResource $child)
        {
            parent::__construct($resource);
        }

        public function fields(): array
        {
            return [
                'default' => ['id'],
                'private' => fn (): array => ['child' => $this->child],
            ];
        }
    };

    $result = $parent->mode('private')->toArray(request());

    expect($result['child'])->toBe(['id' => 2, 'name' => 'Child']);
});
