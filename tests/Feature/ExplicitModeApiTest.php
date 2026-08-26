<?php

declare(strict_types=1);

use Benyaminrmb\LaravelDynamicResources\Attributes\View;
use Benyaminrmb\LaravelDynamicResources\DynamicResource;
use Benyaminrmb\LaravelDynamicResources\Exceptions\UndefinedViewException;
use Benyaminrmb\LaravelDynamicResources\Tests\Fixtures\Models\User;
use Benyaminrmb\LaravelDynamicResources\Tests\Fixtures\Resources\UserResource;

it('supports explicit mode composition methods', function (): void {
    $resource = UserResource::make(new User(['id' => 1, 'name' => 'Taylor', 'avatar' => 'a.jpg']))
        ->view('minimal')
        ->withView('avatar')
        ->withoutView('avatar');

    expect($resource->getActiveViews())->toBe(['minimal'])
        ->and($resource->toArray(request()))->toBe(['id' => 1, 'name' => 'Taylor']);
});

it('throws for undefined explicit modes', function (): void {
    expect(fn () => UserResource::make(new User)->view('unknown'))
        ->toThrow(UndefinedViewException::class);
});

it('composes views through ordinary IDE-visible methods', function (): void {
    $resource = new class(new User) extends DynamicResource
    {
        #[View(default: true)]
        protected function standardFields(): array
        {
            return ['id'];
        }

        #[View]
        protected function namedFields(): array
        {
            return $this->combine($this->standardFields(), ['name']);
        }
    };

    expect($resource->view('named')->toArray(request()))->toHaveKeys(['id', 'name']);
});

it('does not leak a parent mode into a child resource', function (): void {
    $child = new class(new User(['id' => 2, 'name' => 'Child', 'email' => 'child@example.com'])) extends DynamicResource
    {
        #[View(default: true)]
        protected function standardFields(): array
        {
            return ['id', 'name'];
        }

        #[View]
        protected function privateFields(): array
        {
            return ['id', 'email'];
        }
    };

    $parent = new class(new User(['id' => 1]), $child) extends DynamicResource
    {
        public function __construct(mixed $resource, private readonly DynamicResource $child)
        {
            parent::__construct($resource);
        }

        #[View(default: true)]
        protected function standardFields(): array
        {
            return ['id'];
        }

        #[View]
        protected function privateFields(): array
        {
            return ['child' => $this->child];
        }
    };

    $result = $parent->view('private')->toArray(request());

    expect($result['child'])->toBe(['id' => 2, 'name' => 'Child']);
});
