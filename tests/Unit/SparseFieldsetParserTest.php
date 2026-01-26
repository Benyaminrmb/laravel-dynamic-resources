<?php

declare(strict_types=1);

use Benyaminrmb\LaravelDynamicResources\Services\SparseFieldsetParser;
use Illuminate\Http\Request;

describe('SparseFieldsetParser', function (): void {
    beforeEach(function (): void {
        $this->parser = new SparseFieldsetParser();
    });

    describe('parseFieldList', function (): void {
        it('parses comma-separated fields', function (): void {
            $result = $this->parser->parseFieldList('id,name,email');

            expect($result)->toBe(['id', 'name', 'email']);
        });

        it('trims whitespace from fields', function (): void {
            $result = $this->parser->parseFieldList('id , name , email');

            expect($result)->toBe(['id', 'name', 'email']);
        });

        it('filters empty fields', function (): void {
            $result = $this->parser->parseFieldList('id,,name,,,email');

            expect($result)->toBe(['id', 'name', 'email']);
        });

        it('returns empty array for empty string', function (): void {
            $result = $this->parser->parseFieldList('');

            expect($result)->toBe([]);
        });

        it('handles single field', function (): void {
            $result = $this->parser->parseFieldList('id');

            expect($result)->toBe(['id']);
        });
    });

    describe('parse', function (): void {
        it('returns empty array when no fields parameter', function (): void {
            $request = Request::create('/api/users');

            $result = $this->parser->parse($request);

            expect($result)->toBe([]);
        });

        it('parses simple string format', function (): void {
            $request = Request::create('/api/users', 'GET', ['fields' => 'id,name,email']);

            $result = $this->parser->parse($request);

            expect($result)->toBe(['_default' => ['id', 'name', 'email']]);
        });

        it('parses typed array format', function (): void {
            $request = Request::create('/api/users', 'GET', [
                'fields' => [
                    'users' => 'id,name',
                    'posts' => 'title,body',
                ],
            ]);

            $result = $this->parser->parse($request);

            expect($result)->toBe([
                'users' => ['id', 'name'],
                'posts' => ['title', 'body'],
            ]);
        });

        it('uses custom parameter name', function (): void {
            $request = Request::create('/api/users', 'GET', ['include' => 'id,name']);

            $result = $this->parser->parse($request, 'include');

            expect($result)->toBe(['_default' => ['id', 'name']]);
        });
    });

    describe('getFieldsFor', function (): void {
        it('returns fields for specific type', function (): void {
            $request = Request::create('/api/users', 'GET', [
                'fields' => [
                    'users' => 'id,name',
                    'posts' => 'title',
                ],
            ]);

            $result = $this->parser->getFieldsFor($request, 'users');

            expect($result)->toBe(['id', 'name']);
        });

        it('falls back to default when type not found', function (): void {
            $request = Request::create('/api/users', 'GET', ['fields' => 'id,name']);

            $result = $this->parser->getFieldsFor($request, 'users');

            expect($result)->toBe(['id', 'name']);
        });

        it('returns empty array when no matching fields', function (): void {
            $request = Request::create('/api/users', 'GET', [
                'fields' => [
                    'posts' => 'title',
                ],
            ]);

            $result = $this->parser->getFieldsFor($request, 'users');

            expect($result)->toBe([]);
        });
    });

    describe('parseNested', function (): void {
        it('parses simple fields without nesting', function (): void {
            $result = $this->parser->parseNested('id,name,email');

            expect($result)->toBe(['_root' => ['id', 'name', 'email']]);
        });

        it('parses nested fields', function (): void {
            $result = $this->parser->parseNested('id,name,posts.title');

            expect($result)->toHaveKey('_root')
                ->and($result['_root'])->toBe(['id', 'name'])
                ->and($result)->toHaveKey('posts')
                ->and($result['posts']['_root'])->toBe(['title']);
        });

        it('parses deeply nested fields', function (): void {
            $result = $this->parser->parseNested('id,posts.author.name');

            expect($result['_root'])->toBe(['id'])
                ->and($result['posts']['author']['_root'])->toBe(['name']);
        });

        it('groups fields by relation', function (): void {
            $result = $this->parser->parseNested('id,posts.title,posts.body,posts.author.name');

            expect($result['posts']['_root'])->toBe(['title', 'body'])
                ->and($result['posts']['author']['_root'])->toBe(['name']);
        });

        it('respects max depth', function (): void {
            $result = $this->parser->parseNested('posts.author.profile.avatar', 2);

            // With maxDepth 2, 'profile.avatar' becomes a single field
            expect($result['posts']['author']['_root'])->toContain('profile.avatar');
        });

        it('handles empty string', function (): void {
            $result = $this->parser->parseNested('');

            expect($result)->toBe(['_root' => []]);
        });
    });

    describe('isFieldAllowed', function (): void {
        it('allows all fields when list is empty', function (): void {
            expect($this->parser->isFieldAllowed('id', []))->toBeTrue()
                ->and($this->parser->isFieldAllowed('anything', []))->toBeTrue();
        });

        it('allows field when in list', function (): void {
            $allowed = ['id', 'name', 'email'];

            expect($this->parser->isFieldAllowed('id', $allowed))->toBeTrue()
                ->and($this->parser->isFieldAllowed('name', $allowed))->toBeTrue();
        });

        it('denies field when not in list', function (): void {
            $allowed = ['id', 'name'];

            expect($this->parser->isFieldAllowed('email', $allowed))->toBeFalse()
                ->and($this->parser->isFieldAllowed('password', $allowed))->toBeFalse();
        });
    });
});
