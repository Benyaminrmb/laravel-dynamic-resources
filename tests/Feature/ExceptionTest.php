<?php

declare(strict_types=1);

use Benyaminrmb\LaravelDynamicResources\Exceptions\InvalidResourceQueryException;
use Benyaminrmb\LaravelDynamicResources\Exceptions\UndefinedViewException;

it('suggests the closest available view and serializes the error', function (): void {
    $exception = new UndefinedViewException('detaled', stdClass::class, ['minimal', 'detailed']);

    expect($exception->getSuggestion())->toBe("Did you mean 'detailed'?")
        ->and($exception->getMessage())->toContain('Available views: minimal, detailed')
        ->and($exception->toArray())->toMatchArray([
            'type' => 'undefined_view',
            'view' => 'detaled',
            'resource' => stdClass::class,
            'available_views' => ['minimal', 'detailed'],
            'suggestion' => "Did you mean 'detailed'?",
        ]);
});

it('does not suggest unrelated or unavailable views', function (array $views): void {
    $exception = new UndefinedViewException('completely-different', stdClass::class, $views);

    expect($exception->getSuggestion())->toBeNull()
        ->and($exception->getMessage())->not->toContain('Did you mean');
})->with([
    'no views' => [[]],
    'unrelated views' => [['default', 'minimal']],
]);

it('constructs and serializes invalid query errors', function (InvalidResourceQueryException $exception, string $parameter): void {
    expect($exception->parameter)->toBe($parameter)
        ->and($exception->getSuggestion())->toContain('comma-separated')
        ->and($exception->toArray())->toBe([
            'type' => 'invalid_resource_query',
            'parameter' => $parameter,
            'message' => $exception->getMessage(),
        ]);
})->with([
    'disallowed view' => [fn () => InvalidResourceQueryException::viewNotAllowed('private'), 'view'],
    'malformed fields' => [fn () => InvalidResourceQueryException::malformed('fields'), 'fields'],
    'field limit' => [fn () => InvalidResourceQueryException::limitExceeded('fields', 10), 'fields'],
]);
