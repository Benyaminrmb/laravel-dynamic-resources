<?php

declare(strict_types=1);

use Benyaminrmb\LaravelDynamicResources\DTO\FieldSet;
use Benyaminrmb\LaravelDynamicResources\DTO\ModeConfiguration;
use Benyaminrmb\LaravelDynamicResources\DTO\TransformationContext;
use Illuminate\Http\Request;

describe('ModeConfiguration', function (): void {
    it('creates with default values', function (): void {
        $config = new ModeConfiguration(activeModes: ['default']);

        expect($config->activeModes)->toBe(['default']);
        expect($config->requestedFields)->toBe([]);
        expect($config->excludedFields)->toBe([]);
        expect($config->explicitlySet)->toBeFalse();
    });

    it('adds mode immutably', function (): void {
        $config = new ModeConfiguration(activeModes: ['default']);
        $newConfig = $config->addMode('avatar');

        expect($config->activeModes)->toBe(['default']);
        expect($newConfig->activeModes)->toBe(['default', 'avatar']);
        expect($newConfig->explicitlySet)->toBeTrue();
    });

    it('does not add duplicate mode', function (): void {
        $config = new ModeConfiguration(activeModes: ['default', 'avatar']);
        $newConfig = $config->addMode('avatar');

        expect($newConfig->activeModes)->toBe(['default', 'avatar']);
    });

    it('removes mode immutably', function (): void {
        $config = new ModeConfiguration(activeModes: ['default', 'avatar']);
        $newConfig = $config->removeMode('avatar');

        expect($config->activeModes)->toBe(['default', 'avatar']);
        expect($newConfig->activeModes)->toBe(['default']);
    });

    it('falls back to default when removing last mode', function (): void {
        $config = new ModeConfiguration(activeModes: ['avatar']);
        $newConfig = $config->removeMode('avatar');

        expect($newConfig->activeModes)->toBe(['default']);
    });

    it('checks if mode is active', function (): void {
        $config = new ModeConfiguration(activeModes: ['default', 'avatar']);

        expect($config->hasMode('default'))->toBeTrue();
        expect($config->hasMode('avatar'))->toBeTrue();
        expect($config->hasMode('posts'))->toBeFalse();
    });

    it('replaces modes with withModes', function (): void {
        $config = new ModeConfiguration(activeModes: ['default']);
        $newConfig = $config->withModes(['minimal', 'avatar']);

        expect($newConfig->activeModes)->toBe(['minimal', 'avatar']);
        expect($newConfig->explicitlySet)->toBeTrue();
    });
});

describe('FieldSet', function (): void {
    it('creates from array with string keys', function (): void {
        $fields = FieldSet::fromArray([
            'id' => 1,
            'name' => 'John',
        ]);

        expect($fields->fields)->toHaveCount(2);
        expect($fields->has('id'))->toBeTrue();
        expect($fields->has('name'))->toBeTrue();
    });

    it('creates from array with numeric keys', function (): void {
        $fields = FieldSet::fromArray(['id', 'name']);

        expect($fields->fields)->toHaveCount(2);
        expect($fields->has('id'))->toBeTrue();
        expect($fields->has('name'))->toBeTrue();
    });

    it('merges two field sets', function (): void {
        $set1 = FieldSet::fromArray(['id', 'name']);
        $set2 = FieldSet::fromArray(['email', 'avatar']);
        $merged = $set1->merge($set2);

        expect($merged->count())->toBe(4);
        expect($merged->keys())->toBe(['id', 'name', 'email', 'avatar']);
    });

    it('filters with only()', function (): void {
        $fields = FieldSet::fromArray(['id', 'name', 'email']);
        $filtered = $fields->only(['id', 'name']);

        expect($filtered->count())->toBe(2);
        expect($filtered->has('id'))->toBeTrue();
        expect($filtered->has('name'))->toBeTrue();
        expect($filtered->has('email'))->toBeFalse();
    });

    it('filters with except()', function (): void {
        $fields = FieldSet::fromArray(['id', 'name', 'email']);
        $filtered = $fields->except(['email']);

        expect($filtered->count())->toBe(2);
        expect($filtered->has('id'))->toBeTrue();
        expect($filtered->has('name'))->toBeTrue();
        expect($filtered->has('email'))->toBeFalse();
    });

    it('creates empty field set', function (): void {
        $empty = FieldSet::empty();

        expect($empty->isEmpty())->toBeTrue();
        expect($empty->count())->toBe(0);
    });
});

describe('TransformationContext', function (): void {
    it('creates with default values', function (): void {
        $config = new ModeConfiguration(activeModes: ['default']);
        $request = Request::create('/api/users');
        $context = new TransformationContext($config, $request);

        expect($context->depth)->toBe(0);
        expect($context->parent)->toBeNull();
        expect($context->isRoot())->toBeTrue();
    });

    it('creates nested context', function (): void {
        $config = new ModeConfiguration(activeModes: ['default']);
        $request = Request::create('/api/users');
        $parent = new TransformationContext($config, $request);
        $child = $parent->forNested();

        expect($child->depth)->toBe(1);
        expect($child->parent)->toBe($parent);
        expect($child->isRoot())->toBeFalse();
    });

    it('checks max depth', function (): void {
        $config = new ModeConfiguration(activeModes: ['default']);
        $request = Request::create('/api/users');
        $context = new TransformationContext($config, $request, depth: 10);

        expect($context->hasExceededMaxDepth())->toBeTrue();
    });

    it('creates default context', function (): void {
        $request = Request::create('/api/users');
        $context = TransformationContext::createDefault($request);

        expect($context->modeConfig->activeModes)->toBe(['default']);
        expect($context->depth)->toBe(0);
    });
});
