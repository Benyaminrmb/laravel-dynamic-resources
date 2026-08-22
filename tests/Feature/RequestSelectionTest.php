<?php

declare(strict_types=1);

use Benyaminrmb\LaravelDynamicResources\Exceptions\InvalidResourceQueryException;
use Benyaminrmb\LaravelDynamicResources\Tests\Fixtures\Models\Post;
use Benyaminrmb\LaravelDynamicResources\Tests\Fixtures\Models\User;
use Benyaminrmb\LaravelDynamicResources\Tests\Fixtures\Resources\UserResource;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

it('applies an allowlisted request mode and sparse fields explicitly', function (): void {
    $user = new User([
        'id' => 1,
        'name' => 'Taylor',
        'email' => 'taylor@example.com',
        'bio' => 'Developer',
    ]);
    $request = Request::create('/users', 'GET', [
        'mode' => 'detailed',
        'fields' => 'id,bio',
    ]);

    $result = UserResource::make($user)->fromRequest($request)->toArray($request);

    expect($result)->toBe(['id' => 1, 'bio' => 'Developer']);
});

it('rejects a mode that the resource does not expose to clients', function (): void {
    $request = Request::create('/users', 'GET', ['mode' => 'timestamps']);

    expect(fn () => UserResource::make(new User)->fromRequest($request))
        ->toThrow(InvalidResourceQueryException::class);
});

it('intersects request fields with server-side filters', function (): void {
    $user = new User(['id' => 1, 'name' => 'Taylor', 'email' => 'taylor@example.com']);
    $request = Request::create('/users', 'GET', ['fields' => 'id,name,email']);

    $result = UserResource::make($user)
        ->only(['id', 'name'])
        ->fromRequest($request)
        ->toArray($request);

    expect($result)->toBe(['id' => 1, 'name' => 'Taylor']);
});

it('applies dotted fields to nested resource collections without inheriting modes', function (): void {
    $posts = new Collection([
        new Post(['id' => 10, 'title' => 'First', 'body' => 'Secret body']),
    ]);
    $user = new User([
        'id' => 1,
        'name' => 'Taylor',
        'email' => 'taylor@example.com',
        'bio' => 'Developer',
    ]);
    $user->setRelation('posts', $posts);
    $request = Request::create('/users', 'GET', [
        'mode' => 'detailed',
        'fields' => 'id,posts.id',
    ]);

    $result = UserResource::make($user)->fromRequest($request)->toArray($request);

    expect($result)->toBe([
        'id' => 1,
        'posts' => [['id' => 10]],
    ]);
});

it('applies dotted exclusions only to the nested resource', function (): void {
    $posts = new Collection([
        new Post(['id' => 10, 'title' => 'First', 'body' => 'Body']),
    ]);
    $user = new User(['id' => 1, 'name' => 'Taylor', 'email' => 'taylor@example.com']);
    $user->setRelation('posts', $posts);
    $request = Request::create('/users', 'GET', [
        'mode' => 'detailed',
        'exclude' => 'posts.body',
    ]);

    $result = UserResource::make($user)->fromRequest($request)->toArray($request);

    expect($result['posts'][0])
        ->toHaveKey('id', 10)
        ->toHaveKey('title', 'First')
        ->not->toHaveKey('body');
});

it('rejects malformed and excessively deep field paths', function (string $fields): void {
    $request = Request::create('/users', 'GET', ['fields' => $fields]);

    expect(fn () => UserResource::make(new User)->fromRequest($request))
        ->toThrow(InvalidResourceQueryException::class);
})->with(['invalid characters' => 'id,$password', 'too deep' => 'posts.author.profile.name']);

it('supports request selection on collections', function (): void {
    $request = Request::create('/users', 'GET', ['mode' => 'minimal', 'fields' => 'id']);
    $users = collect([new User(['id' => 1, 'name' => 'Taylor'])]);

    $result = UserResource::collection($users)->fromRequest($request)->toArray($request);

    expect($result)->toBe([['id' => 1]]);
});

it('renders invalid resource queries as http 400 responses', function (): void {
    Route::get('/invalid-resource-query', fn () => UserResource::make(new User)->fromRequest(request()));

    $this->getJson('/invalid-resource-query?mode=internal')->assertBadRequest();
});

it('supports configured request parameter names', function (): void {
    config([
        'dynamic-resources.request.mode_parameter' => 'view',
        'dynamic-resources.request.fields_parameter' => 'select',
    ]);
    $request = Request::create('/users', 'GET', ['view' => 'minimal', 'select' => 'id']);

    $result = UserResource::make(new User(['id' => 1, 'name' => 'Taylor']))
        ->fromRequest($request)
        ->toArray($request);

    expect($result)->toBe(['id' => 1]);
});
