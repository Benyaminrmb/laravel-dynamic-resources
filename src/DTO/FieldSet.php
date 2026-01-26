<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\DTO;

/**
 * Immutable value object representing a collection of fields for a resource mode.
 */
readonly class FieldSet
{
    /**
     * @param array<string, mixed> $fields Map of field names to their values/definitions
     * @param list<string> $requiredRelations Relationships that must be loaded for these fields
     */
    public function __construct(
        public array $fields,
        public array $requiredRelations = [],
    ) {}

    /**
     * Create a new FieldSet by merging another FieldSet into this one.
     * Fields from the other FieldSet will override fields with the same key.
     */
    public function merge(self $other): self
    {
        return new self(
            fields: array_merge($this->fields, $other->fields),
            requiredRelations: array_values(array_unique([
                ...$this->requiredRelations,
                ...$other->requiredRelations,
            ])),
        );
    }

    /**
     * Create a new FieldSet containing only the specified fields.
     *
     * @param list<string> $keys
     */
    public function only(array $keys): self
    {
        if ($keys === []) {
            return $this;
        }

        return new self(
            fields: array_intersect_key($this->fields, array_flip($keys)),
            requiredRelations: $this->requiredRelations,
        );
    }

    /**
     * Create a new FieldSet excluding the specified fields.
     *
     * @param list<string> $keys
     */
    public function except(array $keys): self
    {
        if ($keys === []) {
            return $this;
        }

        return new self(
            fields: array_diff_key($this->fields, array_flip($keys)),
            requiredRelations: $this->requiredRelations,
        );
    }

    /**
     * Check if a field exists in this set.
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->fields);
    }

    /**
     * Get a specific field value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->fields[$key] ?? $default;
    }

    /**
     * Get all field keys.
     *
     * @return list<string>
     */
    public function keys(): array
    {
        return array_keys($this->fields);
    }

    /**
     * Check if the field set is empty.
     */
    public function isEmpty(): bool
    {
        return $this->fields === [];
    }

    /**
     * Get the number of fields in this set.
     */
    public function count(): int
    {
        return count($this->fields);
    }

    /**
     * Create an empty FieldSet.
     */
    public static function empty(): self
    {
        return new self(fields: []);
    }

    /**
     * Create a FieldSet from an array of field definitions.
     *
     * @param array<int|string, mixed> $fields
     * @param list<string> $requiredRelations
     */
    public static function fromArray(array $fields, array $requiredRelations = []): self
    {
        $normalized = [];

        foreach ($fields as $key => $value) {
            if (is_int($key)) {
                // Numeric key: the value is the field name, use as-is
                $normalized[(string) $value] = null;
            } else {
                // String key: key is field name, value is the definition
                $normalized[$key] = $value;
            }
        }

        return new self(
            fields: $normalized,
            requiredRelations: $requiredRelations,
        );
    }
}
