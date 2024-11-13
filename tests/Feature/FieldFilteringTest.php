<?php

use Benyaminrmb\LaravelDynamicResources\Tests\TestModels\TestModel;
use Benyaminrmb\LaravelDynamicResources\Tests\TestResources\TestResource;

beforeEach(function () {
    $this->model = new TestModel([
        'id' => 1,
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'details' => 'Some details',
    ]);
});

//todo fix this issue
/*test('resource only returns specified fields', function () {
    $resource = (new TestResource($this->model))->only(['name', 'email']);
    $array = $resource->toArray(request());

    expect($array)->toBe([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);
});

test('resource except excludes specified fields', function () {
    $resource = (new TestResource($this->model))->except(['email']);
    $array = $resource->toArray(request());

    expect($array)->toBe([
        'id' => 1,
        'name' => 'John Doe',
    ]);
});*/

test('resource additional fields', function () {
    $resource = (new TestResource($this->model))->additional([
        'meta' => ['timestamp' => '2024-01-01'],
    ]);

    $array = $resource->toArray(request());

    expect($array)->toBe([
        'id' => 1,
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'meta' => ['timestamp' => '2024-01-01'],
    ]);
});
