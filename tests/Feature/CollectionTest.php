<?php

use Benyaminrmb\LaravelDynamicResources\Tests\TestModels\TestModel;
use Benyaminrmb\LaravelDynamicResources\Tests\TestResources\TestResource;

test('resource collection applies mode to all items', function () {
    $models = collect([
        new TestModel([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'details' => 'Details 1',
        ]),
        new TestModel([
            'id' => 2,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'details' => 'Details 2',
        ]),
    ]);

    $collection = TestResource::collection($models)->minimal();
    $array = $collection->toArray(request());

    expect($array)->toBe([
        [
            'id' => 1,
            'name' => 'John Doe',
        ],
        [
            'id' => 2,
            'name' => 'Jane Doe',
        ],
    ]);
});

test('resource collection handles empty collection', function () {
    $collection = TestResource::collection(collect([]));
    $array = $collection->toArray(request());

    expect($array)->toBe([]);
});
