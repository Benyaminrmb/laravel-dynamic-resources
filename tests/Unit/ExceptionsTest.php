<?php

declare(strict_types=1);

use Benyaminrmb\LaravelDynamicResources\Exceptions\CircularReferenceException;
use Benyaminrmb\LaravelDynamicResources\Exceptions\ForbiddenModeException;
use Benyaminrmb\LaravelDynamicResources\Exceptions\InvalidFieldException;
use Benyaminrmb\LaravelDynamicResources\Exceptions\UndefinedModeException;

describe('UndefinedModeException', function (): void {
    it('creates exception with proper message', function (): void {
        $exception = new UndefinedModeException(
            mode: 'unknownMode',
            resourceClass: 'App\\Http\\Resources\\UserResource',
            availableModes: ['minimal', 'default', 'detailed'],
        );

        expect($exception->getMessage())
            ->toContain("Mode 'unknownMode' is not defined")
            ->toContain('UserResource')
            ->toContain('minimal, default, detailed');
    });

    it('shows "none defined" when no modes available', function (): void {
        $exception = new UndefinedModeException(
            mode: 'test',
            resourceClass: 'TestResource',
            availableModes: [],
        );

        expect($exception->getMessage())->toContain('none defined');
    });

    it('suggests similar mode using Levenshtein distance', function (): void {
        $exception = new UndefinedModeException(
            mode: 'mnimal',
            resourceClass: 'TestResource',
            availableModes: ['minimal', 'default', 'detailed'],
        );

        expect($exception->getSuggestion())->toBe("Did you mean 'minimal'?");
    });

    it('suggests mode for typo', function (): void {
        $exception = new UndefinedModeException(
            mode: 'detailedd',
            resourceClass: 'TestResource',
            availableModes: ['minimal', 'default', 'detailed'],
        );

        expect($exception->getSuggestion())->toBe("Did you mean 'detailed'?");
    });

    it('returns null suggestion when no close match', function (): void {
        $exception = new UndefinedModeException(
            mode: 'completely_different',
            resourceClass: 'TestResource',
            availableModes: ['minimal', 'default', 'detailed'],
        );

        expect($exception->getSuggestion())->toBeNull();
    });

    it('includes suggestion in message', function (): void {
        $exception = new UndefinedModeException(
            mode: 'defualt',
            resourceClass: 'TestResource',
            availableModes: ['minimal', 'default', 'detailed'],
        );

        expect($exception->getMessage())->toContain("Did you mean 'default'?");
    });

    it('converts to array for API responses', function (): void {
        $exception = new UndefinedModeException(
            mode: 'test',
            resourceClass: 'UserResource',
            availableModes: ['minimal', 'default'],
        );

        $array = $exception->toArray();

        expect($array)
            ->toHaveKey('type', 'undefined_mode')
            ->toHaveKey('mode', 'test')
            ->toHaveKey('resource', 'UserResource')
            ->toHaveKey('available_modes', ['minimal', 'default']);
    });

    it('exposes properties', function (): void {
        $exception = new UndefinedModeException(
            mode: 'test',
            resourceClass: 'UserResource',
            availableModes: ['minimal'],
        );

        expect($exception->mode)->toBe('test')
            ->and($exception->resourceClass)->toBe('UserResource')
            ->and($exception->availableModes)->toBe(['minimal']);
    });
});

describe('InvalidFieldException', function (): void {
    it('creates exception with proper message', function (): void {
        $exception = new InvalidFieldException(
            field: 'unknown_field',
            resourceClass: 'App\\Http\\Resources\\UserResource',
            mode: 'default',
            availableFields: ['id', 'name', 'email'],
        );

        expect($exception->getMessage())
            ->toContain("Field 'unknown_field' is not available")
            ->toContain("mode 'default'")
            ->toContain('UserResource');
    });

    it('suggests similar field using Levenshtein distance', function (): void {
        $exception = new InvalidFieldException(
            field: 'emal',
            resourceClass: 'TestResource',
            mode: 'default',
            availableFields: ['id', 'name', 'email'],
        );

        expect($exception->getSuggestion())->toBe("Did you mean 'email'?");
    });

    it('returns null suggestion when no close match', function (): void {
        $exception = new InvalidFieldException(
            field: 'something_completely_different',
            resourceClass: 'TestResource',
            mode: 'default',
            availableFields: ['id', 'name', 'email'],
        );

        expect($exception->getSuggestion())->toBeNull();
    });

    it('converts to array for API responses', function (): void {
        $exception = new InvalidFieldException(
            field: 'test',
            resourceClass: 'UserResource',
            mode: 'minimal',
            availableFields: ['id', 'name'],
        );

        $array = $exception->toArray();

        expect($array)
            ->toHaveKey('type', 'invalid_field')
            ->toHaveKey('field', 'test')
            ->toHaveKey('mode', 'minimal')
            ->toHaveKey('resource', 'UserResource')
            ->toHaveKey('available_fields', ['id', 'name']);
    });

    it('exposes properties', function (): void {
        $exception = new InvalidFieldException(
            field: 'test',
            resourceClass: 'UserResource',
            mode: 'default',
            availableFields: ['id'],
        );

        expect($exception->field)->toBe('test')
            ->and($exception->resourceClass)->toBe('UserResource')
            ->and($exception->mode)->toBe('default')
            ->and($exception->availableFields)->toBe(['id']);
    });
});

describe('ForbiddenModeException', function (): void {
    it('creates exception with proper message', function (): void {
        $exception = new ForbiddenModeException(
            mode: 'admin',
            forbiddenModes: ['admin', 'internal'],
        );

        expect($exception->getMessage())
            ->toContain("Mode 'admin' cannot be selected via request parameters");
    });

    it('provides suggestion about server-side usage', function (): void {
        $exception = new ForbiddenModeException(mode: 'admin');

        expect($exception->getSuggestion())
            ->toContain('server-side usage only');
    });

    it('converts to array for API responses', function (): void {
        $exception = new ForbiddenModeException(
            mode: 'admin',
            forbiddenModes: ['admin', 'internal'],
        );

        $array = $exception->toArray();

        expect($array)
            ->toHaveKey('type', 'forbidden_mode')
            ->toHaveKey('mode', 'admin')
            ->toHaveKey('message');
    });

    it('exposes properties', function (): void {
        $exception = new ForbiddenModeException(
            mode: 'admin',
            forbiddenModes: ['admin', 'internal'],
        );

        expect($exception->mode)->toBe('admin')
            ->and($exception->forbiddenModes)->toBe(['admin', 'internal']);
    });
});

describe('CircularReferenceException', function (): void {
    it('creates exception with chain in message', function (): void {
        $exception = new CircularReferenceException(
            chain: ['UserResource', 'PostResource', 'UserResource'],
        );

        expect($exception->getMessage())
            ->toContain('Circular resource reference detected')
            ->toContain('UserResource -> PostResource -> UserResource');
    });

    it('provides suggestion about whenLoaded()', function (): void {
        $exception = new CircularReferenceException(chain: ['A', 'B', 'A']);

        expect($exception->getSuggestion())
            ->toContain('whenLoaded()')
            ->toContain('infinite recursion');
    });

    it('converts to array for API responses', function (): void {
        $exception = new CircularReferenceException(
            chain: ['UserResource', 'PostResource', 'UserResource'],
        );

        $array = $exception->toArray();

        expect($array)
            ->toHaveKey('type', 'circular_reference')
            ->toHaveKey('chain', ['UserResource', 'PostResource', 'UserResource'])
            ->toHaveKey('suggestion');
    });

    it('exposes chain property', function (): void {
        $chain = ['A', 'B', 'C', 'A'];
        $exception = new CircularReferenceException(chain: $chain);

        expect($exception->chain)->toBe($chain);
    });

    it('handles long chains', function (): void {
        $chain = ['A', 'B', 'C', 'D', 'E', 'A'];
        $exception = new CircularReferenceException(chain: $chain);

        expect($exception->getMessage())
            ->toContain('A -> B -> C -> D -> E -> A');
    });
});
