<?php

declare(strict_types=1);

use Benyaminrmb\LaravelDynamicResources\DynamicResource;
use Benyaminrmb\LaravelDynamicResources\Tests\Fixtures\Models\Post;
use Benyaminrmb\LaravelDynamicResources\Tests\Fixtures\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Test resource with conditional fields.
 */
class ConditionalFieldsResource extends DynamicResource
{
    public function fields(): array
    {
        return [
            'default' => fn () => [
                'id' => $this->id,
                'name' => $this->name,
                'avatar' => $this->when($this->avatar !== null, $this->avatar),
                'bio' => $this->when($this->bio !== null, fn () => $this->bio, 'No bio provided'),
            ],

            // Mode names without 'with' prefix to match method calls
            'counts' => fn () => [
                'id' => $this->id,
                'name' => $this->name,
                'posts_count' => $this->whenCounted('posts'),
            ],

            'relations' => fn () => [
                'id' => $this->id,
                'name' => $this->name,
                'posts' => $this->whenLoaded('posts', fn () => $this->posts->toArray()),
            ],

            'merge' => fn () => array_merge(
                ['id' => $this->id],
                ($this->isAdmin ?? false) ? [
                    'admin_since' => $this->admin_since ?? null,
                    'permissions' => $this->permissions ?? [],
                ] : []
            ),
        ];
    }
}

describe('Conditional Fields', function (): void {
    describe('when()', function (): void {
        it('includes field when condition is true', function (): void {
            $user = new User([
                'id' => 1,
                'name' => 'John',
                'avatar' => 'avatar.jpg',
            ]);

            $result = ConditionalFieldsResource::make($user)->toArray(request());

            expect($result)
                ->toHaveKey('avatar', 'avatar.jpg');
        });

        it('excludes field when condition is false and no default', function (): void {
            $user = new User([
                'id' => 1,
                'name' => 'John',
                'avatar' => null,
            ]);

            $result = ConditionalFieldsResource::make($user)->toArray(request());

            expect($result)->not->toHaveKey('avatar');
        });

        it('uses default value when condition is false', function (): void {
            $user = new User([
                'id' => 1,
                'name' => 'John',
                'bio' => null,
            ]);

            $result = ConditionalFieldsResource::make($user)->toArray(request());

            expect($result)
                ->toHaveKey('bio', 'No bio provided');
        });

        it('executes closure for value when condition is true', function (): void {
            $user = new User([
                'id' => 1,
                'name' => 'John',
                'bio' => 'Developer',
            ]);

            $result = ConditionalFieldsResource::make($user)->toArray(request());

            expect($result)
                ->toHaveKey('bio', 'Developer');
        });
    });

    describe('whenLoaded()', function (): void {
        it('includes relation when loaded', function (): void {
            $user = new User([
                'id' => 1,
                'name' => 'John',
            ]);

            // Simulate loaded relation
            $user->setRelation('posts', new Collection([
                new Post(['id' => 1, 'title' => 'First Post']),
            ]));

            $result = ConditionalFieldsResource::make($user)
                ->relations()
                ->toArray(request());

            expect($result)
                ->toHaveKey('posts')
                ->and($result['posts'])->toHaveCount(1);
        });

        it('excludes relation when not loaded', function (): void {
            $user = new User([
                'id' => 1,
                'name' => 'John',
            ]);

            $result = ConditionalFieldsResource::make($user)
                ->relations()
                ->toArray(request());

            expect($result)->not->toHaveKey('posts');
        });
    });

    describe('whenCounted()', function (): void {
        it('includes count when available', function (): void {
            $user = new User([
                'id' => 1,
                'name' => 'John',
            ]);

            // Simulate withCount
            $user->posts_count = 5;

            $result = ConditionalFieldsResource::make($user)
                ->counts()
                ->toArray(request());

            expect($result)
                ->toHaveKey('posts_count', 5);
        });

        it('excludes count when not available', function (): void {
            $user = new User([
                'id' => 1,
                'name' => 'John',
            ]);

            $result = ConditionalFieldsResource::make($user)
                ->counts()
                ->toArray(request());

            expect($result)->not->toHaveKey('posts_count');
        });
    });

    describe('conditional merging', function (): void {
        it('merges fields when condition is true', function (): void {
            $user = new User([
                'id' => 1,
                'name' => 'John',
                'isAdmin' => true,
                'admin_since' => '2023-01-01',
                'permissions' => ['create', 'delete'],
            ]);

            $result = ConditionalFieldsResource::make($user)
                ->merge()
                ->toArray(request());

            expect($result)
                ->toHaveKey('id', 1)
                ->toHaveKey('admin_since', '2023-01-01')
                ->toHaveKey('permissions', ['create', 'delete']);
        });

        it('does not merge fields when condition is false', function (): void {
            $user = new User([
                'id' => 1,
                'name' => 'John',
                'isAdmin' => false,
            ]);

            $result = ConditionalFieldsResource::make($user)
                ->merge()
                ->toArray(request());

            expect($result)
                ->toHaveKey('id', 1)
                ->not->toHaveKey('admin_since')
                ->not->toHaveKey('permissions');
        });
    });
});
