<?php

declare(strict_types=1);

use Benyaminrmb\LaravelDynamicResources\Exceptions\ForbiddenModeException;
use Benyaminrmb\LaravelDynamicResources\Http\Middleware\DynamicResourceFieldsMiddleware;
use Benyaminrmb\LaravelDynamicResources\Services\SparseFieldsetParser;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

describe('DynamicResourceFieldsMiddleware', function (): void {
    beforeEach(function (): void {
        $this->parser = new SparseFieldsetParser();
        $this->middleware = new DynamicResourceFieldsMiddleware($this->parser);
        $this->nextHandler = fn (Request $request) => new Response('OK');
    });

    describe('mode parsing', function (): void {
        it('parses mode from query parameter', function (): void {
            config(['dynamic-resources.request_selection.enabled' => true]);
            $request = Request::create('/api/users', 'GET', ['mode' => 'minimal']);

            $this->middleware->handle($request, $this->nextHandler);

            $context = $request->attributes->get(DynamicResourceFieldsMiddleware::CONTEXT_KEY);
            expect($context)->toHaveKey('mode', 'minimal');
        });

        it('uses custom mode parameter name', function (): void {
            config([
                'dynamic-resources.request_selection.enabled' => true,
                'dynamic-resources.request_selection.mode_parameter' => 'format',
            ]);
            $request = Request::create('/api/users', 'GET', ['format' => 'detailed']);

            $this->middleware->handle($request, $this->nextHandler);

            $context = $request->attributes->get(DynamicResourceFieldsMiddleware::CONTEXT_KEY);
            expect($context)->toHaveKey('mode', 'detailed');
        });

        it('ignores empty mode parameter', function (): void {
            config(['dynamic-resources.request_selection.enabled' => true]);
            $request = Request::create('/api/users', 'GET', ['mode' => '']);

            $this->middleware->handle($request, $this->nextHandler);

            $context = $request->attributes->get(DynamicResourceFieldsMiddleware::CONTEXT_KEY);
            expect($context)->toBeNull();
        });

        it('throws exception for forbidden modes', function (): void {
            config([
                'dynamic-resources.request_selection.enabled' => true,
                'dynamic-resources.request_selection.forbidden_modes' => ['admin', 'internal'],
            ]);
            $request = Request::create('/api/users', 'GET', ['mode' => 'admin']);

            expect(fn () => $this->middleware->handle($request, $this->nextHandler))
                ->toThrow(ForbiddenModeException::class);
        });

        it('allows modes not in forbidden list', function (): void {
            config([
                'dynamic-resources.request_selection.enabled' => true,
                'dynamic-resources.request_selection.forbidden_modes' => ['admin'],
            ]);
            $request = Request::create('/api/users', 'GET', ['mode' => 'minimal']);

            $this->middleware->handle($request, $this->nextHandler);

            $context = $request->attributes->get(DynamicResourceFieldsMiddleware::CONTEXT_KEY);
            expect($context)->toHaveKey('mode', 'minimal');
        });

        it('throws exception for modes not in allowed list', function (): void {
            config([
                'dynamic-resources.request_selection.enabled' => true,
                'dynamic-resources.request_selection.allowed_modes' => ['minimal', 'default'],
            ]);
            $request = Request::create('/api/users', 'GET', ['mode' => 'detailed']);

            expect(fn () => $this->middleware->handle($request, $this->nextHandler))
                ->toThrow(ForbiddenModeException::class);
        });
    });

    describe('fields parsing', function (): void {
        it('parses fields from query parameter', function (): void {
            config(['dynamic-resources.request_selection.enabled' => true]);
            $request = Request::create('/api/users', 'GET', ['fields' => 'id,name,email']);

            $this->middleware->handle($request, $this->nextHandler);

            $context = $request->attributes->get(DynamicResourceFieldsMiddleware::CONTEXT_KEY);
            expect($context)->toHaveKey('fields', ['id', 'name', 'email']);
        });

        it('uses custom fields parameter name', function (): void {
            config([
                'dynamic-resources.request_selection.enabled' => true,
                'dynamic-resources.request_selection.fields_parameter' => 'include',
            ]);
            $request = Request::create('/api/users', 'GET', ['include' => 'id,name']);

            $this->middleware->handle($request, $this->nextHandler);

            $context = $request->attributes->get(DynamicResourceFieldsMiddleware::CONTEXT_KEY);
            expect($context)->toHaveKey('fields', ['id', 'name']);
        });

        it('ignores empty fields parameter', function (): void {
            config(['dynamic-resources.request_selection.enabled' => true]);
            $request = Request::create('/api/users', 'GET', ['fields' => '']);

            $this->middleware->handle($request, $this->nextHandler);

            $context = $request->attributes->get(DynamicResourceFieldsMiddleware::CONTEXT_KEY);
            expect($context)->toBeNull();
        });
    });

    describe('exclude parsing', function (): void {
        it('parses exclude from query parameter', function (): void {
            config(['dynamic-resources.request_selection.enabled' => true]);
            $request = Request::create('/api/users', 'GET', ['exclude' => 'created_at,updated_at']);

            $this->middleware->handle($request, $this->nextHandler);

            $context = $request->attributes->get(DynamicResourceFieldsMiddleware::CONTEXT_KEY);
            expect($context)->toHaveKey('exclude', ['created_at', 'updated_at']);
        });

        it('uses custom exclude parameter name', function (): void {
            config([
                'dynamic-resources.request_selection.enabled' => true,
                'dynamic-resources.request_selection.exclude_parameter' => 'except',
            ]);
            $request = Request::create('/api/users', 'GET', ['except' => 'password']);

            $this->middleware->handle($request, $this->nextHandler);

            $context = $request->attributes->get(DynamicResourceFieldsMiddleware::CONTEXT_KEY);
            expect($context)->toHaveKey('exclude', ['password']);
        });
    });

    describe('combined parameters', function (): void {
        it('parses mode, fields, and exclude together', function (): void {
            config(['dynamic-resources.request_selection.enabled' => true]);
            $request = Request::create('/api/users', 'GET', [
                'mode' => 'detailed',
                'fields' => 'id,name',
                'exclude' => 'created_at',
            ]);

            $this->middleware->handle($request, $this->nextHandler);

            $context = $request->attributes->get(DynamicResourceFieldsMiddleware::CONTEXT_KEY);
            expect($context)
                ->toHaveKey('mode', 'detailed')
                ->toHaveKey('fields', ['id', 'name'])
                ->toHaveKey('exclude', ['created_at']);
        });
    });

    describe('disabled middleware', function (): void {
        it('does nothing when disabled', function (): void {
            config(['dynamic-resources.request_selection.enabled' => false]);
            $request = Request::create('/api/users', 'GET', ['mode' => 'minimal']);

            $this->middleware->handle($request, $this->nextHandler);

            $context = $request->attributes->get(DynamicResourceFieldsMiddleware::CONTEXT_KEY);
            expect($context)->toBeNull();
        });
    });

    describe('no parameters', function (): void {
        it('does not set context when no parameters provided', function (): void {
            config(['dynamic-resources.request_selection.enabled' => true]);
            $request = Request::create('/api/users');

            $this->middleware->handle($request, $this->nextHandler);

            $context = $request->attributes->get(DynamicResourceFieldsMiddleware::CONTEXT_KEY);
            expect($context)->toBeNull();
        });
    });
});
