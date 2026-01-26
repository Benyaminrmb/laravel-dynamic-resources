<?php

declare(strict_types=1);

use Benyaminrmb\LaravelDynamicResources\DTO\FieldSet;

describe('FieldSet', function (): void {
    it('creates from array with string keys', function (): void {
        $fields = FieldSet::fromArray([
            'id' => 1,
            'name' => 'John',
        ]);

        expect($fields->count())->toBe(2)
            ->and($fields->has('id'))->toBeTrue()
            ->and($fields->has('name'))->toBeTrue();
    });

    it('creates from array with numeric keys', function (): void {
        $fields = FieldSet::fromArray(['id', 'name']);

        expect($fields->count())->toBe(2)
            ->and($fields->has('id'))->toBeTrue()
            ->and($fields->has('name'))->toBeTrue();
    });

    it('creates from mixed array keys', function (): void {
        $fields = FieldSet::fromArray([
            'id',
            'name' => 'John Doe',
            'email',
        ]);

        expect($fields->count())->toBe(3)
            ->and($fields->has('id'))->toBeTrue()
            ->and($fields->has('name'))->toBeTrue()
            ->and($fields->has('email'))->toBeTrue();
    });

    it('merges two field sets', function (): void {
        $set1 = FieldSet::fromArray(['id', 'name']);
        $set2 = FieldSet::fromArray(['email', 'avatar']);
        $merged = $set1->merge($set2);

        expect($merged->count())->toBe(4)
            ->and($merged->keys())->toBe(['id', 'name', 'email', 'avatar']);
    });

    it('merges with required relations', function (): void {
        $set1 = new FieldSet(['id' => null], ['posts']);
        $set2 = new FieldSet(['name' => null], ['profile', 'posts']);
        $merged = $set1->merge($set2);

        expect($merged->requiredRelations)->toBe(['posts', 'profile']);
    });

    it('overrides fields when merging', function (): void {
        $set1 = new FieldSet(['id' => 1, 'name' => 'John']);
        $set2 = new FieldSet(['name' => 'Jane', 'email' => 'jane@example.com']);
        $merged = $set1->merge($set2);

        expect($merged->get('name'))->toBe('Jane')
            ->and($merged->get('id'))->toBe(1)
            ->and($merged->get('email'))->toBe('jane@example.com');
    });

    it('filters with only()', function (): void {
        $fields = FieldSet::fromArray(['id', 'name', 'email']);
        $filtered = $fields->only(['id', 'name']);

        expect($filtered->count())->toBe(2)
            ->and($filtered->has('id'))->toBeTrue()
            ->and($filtered->has('name'))->toBeTrue()
            ->and($filtered->has('email'))->toBeFalse();
    });

    it('returns same fieldset when only() with empty array', function (): void {
        $fields = FieldSet::fromArray(['id', 'name', 'email']);
        $filtered = $fields->only([]);

        expect($filtered->count())->toBe(3);
    });

    it('filters with except()', function (): void {
        $fields = FieldSet::fromArray(['id', 'name', 'email']);
        $filtered = $fields->except(['email']);

        expect($filtered->count())->toBe(2)
            ->and($filtered->has('id'))->toBeTrue()
            ->and($filtered->has('name'))->toBeTrue()
            ->and($filtered->has('email'))->toBeFalse();
    });

    it('returns same fieldset when except() with empty array', function (): void {
        $fields = FieldSet::fromArray(['id', 'name', 'email']);
        $filtered = $fields->except([]);

        expect($filtered->count())->toBe(3);
    });

    it('creates empty field set', function (): void {
        $empty = FieldSet::empty();

        expect($empty->isEmpty())->toBeTrue()
            ->and($empty->count())->toBe(0);
    });

    it('gets field value with default', function (): void {
        $fields = new FieldSet(['id' => 1, 'name' => null]);

        expect($fields->get('id'))->toBe(1)
            ->and($fields->get('name'))->toBeNull()
            ->and($fields->get('nonexistent'))->toBeNull()
            ->and($fields->get('nonexistent', 'default'))->toBe('default');
    });

    it('returns all keys', function (): void {
        $fields = FieldSet::fromArray(['id', 'name', 'email']);

        expect($fields->keys())->toBe(['id', 'name', 'email']);
    });
});
