<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Http\Middleware;

use Benyaminrmb\LaravelDynamicResources\Exceptions\ForbiddenModeException;
use Benyaminrmb\LaravelDynamicResources\Services\SparseFieldsetParser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware for request-based field and mode selection.
 *
 * Parses query parameters like:
 * - ?mode=minimal
 * - ?fields=id,name,email
 * - ?exclude=created_at,updated_at
 *
 * And stores them in request attributes for resources to access.
 *
 * ## Usage
 *
 * ```php
 * // In routes/api.php
 * Route::middleware('resource.fields')->group(function () {
 *     Route::get('/users', [UserController::class, 'index']);
 * });
 * ```
 */
class DynamicResourceFieldsMiddleware
{
    /**
     * Request attribute key for storing parsed context.
     */
    public const string CONTEXT_KEY = 'dynamic_resource_context';

    public function __construct(
        protected SparseFieldsetParser $fieldParser,
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $resourceClass = null): Response
    {
        /** @var array<string, mixed> $config */
        $config = config('dynamic-resources.request_selection', []);

        if (!($config['enabled'] ?? true)) {
            return $next($request);
        }

        $context = $this->parseContext($request, $config);

        if ($context !== []) {
            $request->attributes->set(self::CONTEXT_KEY, $context);
        }

        return $next($request);
    }

    /**
     * Parse the request context.
     *
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    protected function parseContext(Request $request, array $config): array
    {
        $context = [];

        // Parse mode
        $mode = $this->parseMode($request, $config);
        if ($mode !== null) {
            $context['mode'] = $mode;
        }

        // Parse fields
        $fields = $this->parseFields($request, $config);
        if ($fields !== null) {
            $context['fields'] = $fields;
        }

        // Parse excluded fields
        $exclude = $this->parseExclude($request, $config);
        if ($exclude !== null) {
            $context['exclude'] = $exclude;
        }

        return $context;
    }

    /**
     * Parse the mode parameter from the request.
     *
     * @param array<string, mixed> $config
     */
    protected function parseMode(Request $request, array $config): ?string
    {
        $paramName = (string) ($config['mode_parameter'] ?? 'mode');
        $mode = $request->query($paramName);

        if (!is_string($mode) || $mode === '') {
            return null;
        }

        // Check against forbidden modes
        $forbiddenModes = (array) ($config['forbidden_modes'] ?? []);
        if (in_array($mode, $forbiddenModes, true)) {
            throw new ForbiddenModeException($mode, $forbiddenModes);
        }

        // Check against allowed modes (if whitelist is set)
        $allowedModes = (array) ($config['allowed_modes'] ?? []);
        if ($allowedModes !== [] && !in_array($mode, $allowedModes, true)) {
            throw new ForbiddenModeException($mode, []);
        }

        return $mode;
    }

    /**
     * Parse the fields parameter from the request.
     *
     * @param array<string, mixed> $config
     * @return list<string>|null
     */
    protected function parseFields(Request $request, array $config): ?array
    {
        $paramName = (string) ($config['fields_parameter'] ?? 'fields');
        $fieldsParam = $request->query($paramName);

        if (!is_string($fieldsParam) || $fieldsParam === '') {
            return null;
        }

        return $this->fieldParser->parseFieldList($fieldsParam);
    }

    /**
     * Parse the exclude parameter from the request.
     *
     * @param array<string, mixed> $config
     * @return list<string>|null
     */
    protected function parseExclude(Request $request, array $config): ?array
    {
        $paramName = (string) ($config['exclude_parameter'] ?? 'exclude');
        $excludeParam = $request->query($paramName);

        if (!is_string($excludeParam) || $excludeParam === '') {
            return null;
        }

        return $this->fieldParser->parseFieldList($excludeParam);
    }
}
