<?php

declare(strict_types=1);

use Benyaminrmb\LaravelDynamicResources\DTO\ModeConfiguration;
use Benyaminrmb\LaravelDynamicResources\DTO\TransformationContext;
use Illuminate\Http\Request;

describe('TransformationContext', function (): void {
    it('creates with default values', function (): void {
        $config = new ModeConfiguration(activeModes: ['default']);
        $request = Request::create('/api/users');
        $context = new TransformationContext($config, $request);

        expect($context->depth)->toBe(0)
            ->and($context->parent)->toBeNull()
            ->and($context->isRoot())->toBeTrue()
            ->and($context->metadata)->toBe([]);
    });

    it('creates nested context with incremented depth', function (): void {
        $config = new ModeConfiguration(activeModes: ['default']);
        $request = Request::create('/api/users');
        $parent = new TransformationContext($config, $request);
        $child = $parent->forNested();

        expect($child->depth)->toBe(1)
            ->and($child->parent)->toBe($parent)
            ->and($child->isRoot())->toBeFalse();
    });

    it('inherits mode config when creating nested context', function (): void {
        $config = new ModeConfiguration(activeModes: ['minimal', 'avatar']);
        $request = Request::create('/api/users');
        $parent = new TransformationContext($config, $request);
        $child = $parent->forNested();

        expect($child->modeConfig->activeModes)->toBe(['minimal', 'avatar']);
    });

    it('can override mode config when creating nested context', function (): void {
        $config = new ModeConfiguration(activeModes: ['default']);
        $childConfig = new ModeConfiguration(activeModes: ['minimal']);
        $request = Request::create('/api/users');
        $parent = new TransformationContext($config, $request);
        $child = $parent->forNested($childConfig);

        expect($child->modeConfig->activeModes)->toBe(['minimal']);
    });

    it('creates context with different mode config', function (): void {
        $config = new ModeConfiguration(activeModes: ['default']);
        $newConfig = new ModeConfiguration(activeModes: ['minimal']);
        $request = Request::create('/api/users');
        $context = new TransformationContext($config, $request);
        $newContext = $context->withModeConfig($newConfig);

        expect($context->modeConfig->activeModes)->toBe(['default'])
            ->and($newContext->modeConfig->activeModes)->toBe(['minimal']);
    });

    it('adds metadata immutably', function (): void {
        $config = new ModeConfiguration(activeModes: ['default']);
        $request = Request::create('/api/users');
        $context = new TransformationContext($config, $request);
        $newContext = $context->withMetadata(['resource_class' => 'UserResource']);

        expect($context->metadata)->toBe([])
            ->and($newContext->metadata)->toBe(['resource_class' => 'UserResource']);
    });

    it('merges metadata when adding', function (): void {
        $config = new ModeConfiguration(activeModes: ['default']);
        $request = Request::create('/api/users');
        $context = new TransformationContext($config, $request, metadata: ['key1' => 'value1']);
        $newContext = $context->withMetadata(['key2' => 'value2']);

        expect($newContext->metadata)->toBe(['key1' => 'value1', 'key2' => 'value2']);
    });

    it('checks max depth correctly', function (): void {
        $config = new ModeConfiguration(activeModes: ['default']);
        $request = Request::create('/api/users');

        $underLimit = new TransformationContext($config, $request, depth: 9);
        $atLimit = new TransformationContext($config, $request, depth: 10);
        $overLimit = new TransformationContext($config, $request, depth: 11);

        expect($underLimit->hasExceededMaxDepth())->toBeFalse()
            ->and($atLimit->hasExceededMaxDepth())->toBeTrue()
            ->and($overLimit->hasExceededMaxDepth())->toBeTrue();
    });

    it('creates default context', function (): void {
        $request = Request::create('/api/users');
        $context = TransformationContext::createDefault($request);

        expect($context->modeConfig->activeModes)->toBe(['default'])
            ->and($context->depth)->toBe(0)
            ->and($context->isRoot())->toBeTrue();
    });

    it('gets metadata with default', function (): void {
        $config = new ModeConfiguration(activeModes: ['default']);
        $request = Request::create('/api/users');
        $context = new TransformationContext($config, $request, metadata: ['key' => 'value']);

        expect($context->getMetadata('key'))->toBe('value')
            ->and($context->getMetadata('nonexistent'))->toBeNull()
            ->and($context->getMetadata('nonexistent', 'default'))->toBe('default');
    });

    it('builds transformation chain', function (): void {
        $config = new ModeConfiguration(activeModes: ['default']);
        $request = Request::create('/api/users');

        $root = new TransformationContext($config, $request, metadata: ['resource_class' => 'UserResource']);
        $child = $root->forNested()->withMetadata(['resource_class' => 'PostResource']);
        $grandchild = $child->forNested()->withMetadata(['resource_class' => 'CommentResource']);

        expect($grandchild->getTransformationChain())->toBe([
            'UserResource',
            'PostResource',
            'CommentResource',
        ]);
    });
});
