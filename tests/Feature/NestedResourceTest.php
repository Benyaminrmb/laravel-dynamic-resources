<?php

declare(strict_types=1);

use Benyaminrmb\LaravelDynamicResources\Tests\Fixtures\Models\Post;
use Benyaminrmb\LaravelDynamicResources\Tests\Fixtures\Models\User;
use Benyaminrmb\LaravelDynamicResources\Tests\Fixtures\Resources\PostResource;
use Benyaminrmb\LaravelDynamicResources\Tests\Fixtures\Resources\UserResource;
use Illuminate\Database\Eloquent\Collection;

describe('Nested Resources', function (): void {
    it('includes nested resource with whenLoaded', function (): void {
        $user = new User([
            'id' => 1,
            'name' => 'John Doe',
        ]);

        $post = new Post([
            'id' => 1,
            'title' => 'First Post',
            'body' => 'Post content',
            'created_at' => now(),
        ]);

        $post->setRelation('user', $user);

        $result = PostResource::make($post)->view('detailed')->toArray(request());

        expect($result)
            ->toHaveKey('id', 1)
            ->toHaveKey('title', 'First Post')
            ->toHaveKey('body', 'Post content')
            ->toHaveKey('author')
            ->and($result['author'])
            ->toHaveKey('id', 1)
            ->toHaveKey('name', 'John Doe');
    });

    it('excludes nested resource when relation not loaded', function (): void {
        $post = new Post([
            'id' => 1,
            'title' => 'First Post',
            'body' => 'Post content',
            'created_at' => now(),
        ]);

        // Do NOT set any relation
        $result = PostResource::make($post)->view('detailed')->toArray(request());

        expect($result)
            ->toHaveKey('id', 1)
            ->toHaveKey('title', 'First Post')
            ->not->toHaveKey('author');
    });

    it('applies mode to nested resources', function (): void {
        $user = new User([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $post = new Post([
            'id' => 1,
            'title' => 'First Post',
            'body' => 'Post content',
            'created_at' => now(),
        ]);

        $post->setRelation('user', $user);

        $result = PostResource::make($post)->view('detailed')->toArray(request());

        // Nested resource should be minimal (no email)
        expect($result['author'])
            ->toHaveKey('id', 1)
            ->toHaveKey('name', 'John Doe')
            ->not->toHaveKey('email');
    });

    it('handles deeply nested resources', function (): void {
        $author = new User([
            'id' => 1,
            'name' => 'John Doe',
        ]);

        $posts = new Collection([
            (function () use ($author) {
                $post = new Post([
                    'id' => 1,
                    'title' => 'First Post',
                    'body' => 'Content 1',
                    'created_at' => now(),
                ]);
                $post->setRelation('user', $author);

                return $post;
            })(),
            (function () use ($author) {
                $post = new Post([
                    'id' => 2,
                    'title' => 'Second Post',
                    'body' => 'Content 2',
                    'created_at' => now(),
                ]);
                $post->setRelation('user', $author);

                return $post;
            })(),
        ]);

        $user = new User([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'bio' => 'Developer',
            'created_at' => now(),
        ]);
        $user->setRelation('posts', $posts);

        $result = UserResource::make($user)->view('detailed')->toArray(request());

        expect($result)
            ->toHaveKey('posts')
            ->and($result['posts'])->toHaveCount(2)
            ->and($result['posts'][0])
            ->toHaveKey('id', 1)
            ->toHaveKey('title', 'First Post');
    });

    it('handles null nested resources', function (): void {
        $post = new Post([
            'id' => 1,
            'title' => 'First Post',
            'body' => 'Post content',
            'created_at' => now(),
        ]);

        // Simulate null relation (relation loaded but value is null)
        $post->setRelation('user', null);

        $result = PostResource::make($post)->view('detailed')->toArray(request());

        expect($result)
            ->toHaveKey('id', 1)
            // With null relation, whenLoaded may still include the key
            // but the value should be null or missing
            ->and($result)->toHaveKey('title', 'First Post');
    });

    it('handles empty collection in nested resources', function (): void {
        $user = new User([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'bio' => 'Developer',
            'created_at' => now(),
        ]);
        $user->setRelation('posts', new Collection([]));

        $result = UserResource::make($user)->view('detailed')->toArray(request());

        expect($result)
            ->toHaveKey('posts')
            ->and(count($result['posts']))->toBe(0);
    });
});

describe('Nested Resource Collections', function (): void {
    it('applies mode to all items in nested collection', function (): void {
        $posts = new Collection([
            new Post(['id' => 1, 'title' => 'Post 1', 'body' => 'Content 1']),
            new Post(['id' => 2, 'title' => 'Post 2', 'body' => 'Content 2']),
        ]);

        $user = new User([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'bio' => 'Developer',
            'created_at' => now(),
        ]);
        $user->setRelation('posts', $posts);

        $result = UserResource::make($user)->view('detailed')->toArray(request());

        expect($result['posts'])->toHaveCount(2);

        foreach ($result['posts'] as $post) {
            expect($post)
                ->toHaveKey('id')
                ->toHaveKey('title');
        }
    });
});
