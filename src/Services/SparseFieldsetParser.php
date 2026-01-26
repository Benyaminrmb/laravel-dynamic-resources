<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Services;

use Illuminate\Http\Request;

/**
 * Parser for JSON:API style sparse fieldsets from request parameters.
 *
 * Supports:
 * - Simple: ?fields=id,name,email
 * - Typed: ?fields[users]=id,name&fields[posts]=title
 * - Nested: ?fields=id,name,posts.id,posts.title
 */
class SparseFieldsetParser
{
    /**
     * Default query parameter name.
     */
    private const string DEFAULT_PARAM = 'fields';

    /**
     * Parse a comma-separated field list.
     *
     * @return list<string>
     */
    public function parseFieldList(string $fields): array
    {
        if ($fields === '') {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', explode(',', $fields)),
            static fn(string $field): bool => $field !== '',
        ));
    }

    /**
     * Parse sparse fieldsets from a request.
     *
     * @return array<string, list<string>> Map of type => fields
     */
    public function parse(Request $request, string $param = self::DEFAULT_PARAM): array
    {
        $fields = $request->query($param);

        if ($fields === null) {
            return [];
        }

        if (is_string($fields)) {
            // Simple format: ?fields=id,name
            return ['_default' => $this->parseFieldList($fields)];
        }

        if (is_array($fields)) {
            // Typed format: ?fields[users]=id,name&fields[posts]=title
            $result = [];
            foreach ($fields as $type => $fieldList) {
                if (is_string($fieldList)) {
                    $result[(string) $type] = $this->parseFieldList($fieldList);
                }
            }

            return $result;
        }

        return [];
    }

    /**
     * Get fields for a specific resource type.
     *
     * @return list<string>
     */
    public function getFieldsFor(Request $request, string $type, string $param = self::DEFAULT_PARAM): array
    {
        $parsed = $this->parse($request, $param);

        return $parsed[$type] ?? $parsed['_default'] ?? [];
    }

    /**
     * Parse nested field specifications.
     *
     * Input: "id,name,posts.id,posts.title,posts.author.name"
     * Output: [
     *     '_root' => ['id', 'name'],
     *     'posts' => [
     *         '_root' => ['id', 'title'],
     *         'author' => ['_root' => ['name']],
     *     ],
     * ]
     *
     * @return array<string, mixed>
     */
    public function parseNested(string $fields, int $maxDepth = 3): array
    {
        $fieldList = $this->parseFieldList($fields);
        $result = ['_root' => []];

        foreach ($fieldList as $field) {
            if (!str_contains($field, '.')) {
                $result['_root'][] = $field;
                continue;
            }

            $parts = explode('.', $field, $maxDepth + 1);
            $current = &$result;

            foreach ($parts as $i => $part) {
                $isLast = $i === count($parts) - 1;

                if ($isLast) {
                    $current['_root'][] = $part;
                } else {
                    $current[$part] ??= ['_root' => []];
                    $current = &$current[$part];
                }
            }
        }

        return $result;
    }

    /**
     * Check if a field should be included based on parsed fields.
     *
     * @param list<string> $allowedFields
     */
    public function isFieldAllowed(string $field, array $allowedFields): bool
    {
        // Empty list means all fields allowed
        if ($allowedFields === []) {
            return true;
        }

        return in_array($field, $allowedFields, true);
    }
}
