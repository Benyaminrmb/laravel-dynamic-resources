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

test('resource returns default fields', function () {
    $resource = new TestResource($this->model);
    $array = $resource->toArray(request());

    expect($array)->toBe([
        'id' => 1,
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);
});

test('resource returns minimal fields', function () {
    $resource = (new TestResource($this->model))->minimal();
    $array = $resource->toArray(request());

    expect($array)->toBe([
        'id' => 1,
        'name' => 'John Doe',
    ]);
});

test('resource returns detailed fields', function () {
    $resource = (new TestResource($this->model))->detailed();
    $array = $resource->toArray(request());

    expect($array)->toBe([
        'id' => 1,
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'details' => 'Some details',
        'computed_field' => 'Computed: John Doe',
    ]);
});
