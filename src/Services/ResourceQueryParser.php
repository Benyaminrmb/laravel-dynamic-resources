<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Services;

use Benyaminrmb\LaravelDynamicResources\DTO\FieldSelection;
use Benyaminrmb\LaravelDynamicResources\DTO\ResourceQuerySelection;
use Benyaminrmb\LaravelDynamicResources\Exceptions\InvalidResourceQueryException;
use Illuminate\Http\Request;

class ResourceQueryParser
{
    public function parse(Request $request): ResourceQuerySelection
    {
        $viewParameter = $this->stringConfig('view_parameter', 'view');
        $fieldsParameter = $this->stringConfig('fields_parameter', 'fields');
        $excludeParameter = $this->stringConfig('exclude_parameter', 'exclude');

        return new ResourceQuerySelection(
            view: $this->parseView($request, $viewParameter),
            only: $this->parseFields($request, $fieldsParameter, true),
            except: $this->parseFields($request, $excludeParameter, false),
        );
    }

    private function parseView(Request $request, string $parameter): ?string
    {
        $value = $request->query($parameter);

        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value) || preg_match('/^[a-z][a-z0-9-]*$/', $value) !== 1) {
            throw InvalidResourceQueryException::malformed($parameter);
        }

        return $value;
    }

    private function parseFields(Request $request, string $parameter, bool $includeParents): ?FieldSelection
    {
        $value = $request->query($parameter);

        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value)) {
            throw InvalidResourceQueryException::malformed($parameter);
        }

        $paths = array_values(array_unique(array_filter(array_map('trim', explode(',', $value)))));
        $maxFields = $this->integerConfig('max_fields', 50);
        $maxDepth = $this->integerConfig('max_depth', 3);

        if (count($paths) > $maxFields) {
            throw InvalidResourceQueryException::limitExceeded($parameter, $maxFields);
        }

        $selection = new FieldSelection;

        foreach ($paths as $path) {
            $segments = explode('.', $path);

            if (count($segments) > $maxDepth || ! $this->hasValidSegments($segments)) {
                throw InvalidResourceQueryException::malformed($parameter);
            }

            $selection->add($segments, $includeParents);
        }

        return $selection;
    }

    /** @param list<string> $segments */
    private function hasValidSegments(array $segments): bool
    {
        foreach ($segments as $segment) {
            if (preg_match('/^[A-Za-z_][A-Za-z0-9_-]*$/', $segment) !== 1) {
                return false;
            }
        }

        return true;
    }

    private function stringConfig(string $key, string $default): string
    {
        $value = config("dynamic-resources.request.{$key}", $default);

        return is_string($value) && $value !== '' ? $value : $default;
    }

    private function integerConfig(string $key, int $default): int
    {
        $value = config("dynamic-resources.request.{$key}", $default);

        return is_int($value) && $value > 0 ? $value : $default;
    }
}
