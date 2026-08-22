<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Concerns;

/**
 * Trait for filtering which fields are included in resource output.
 * Provides `only()` and `except()` methods for field selection.
 */
trait HasFieldFiltering
{
    /**
     * Fields to include in output (empty means include all).
     *
     * @var list<string>
     */
    protected array $onlyFields = [];

    /**
     * Fields to exclude from output.
     *
     * @var list<string>
     */
    protected array $exceptFields = [];

    /**
     * Include only the specified fields in the output.
     * When set, `except()` is cleared.
     *
     * @param  list<string>  $fields
     * @return $this
     */
    public function only(array $fields): static
    {
        $this->onlyFields = $fields;
        $this->exceptFields = []; // Clear except when only is set

        return $this;
    }

    /**
     * Exclude the specified fields from the output.
     * When set, `only()` is cleared.
     *
     * @param  list<string>  $fields
     * @return $this
     */
    public function except(array $fields): static
    {
        $this->exceptFields = $fields;
        $this->onlyFields = []; // Clear only when except is set

        return $this;
    }

    /**
     * Get the fields that should be included (empty means all).
     *
     * @return list<string>
     */
    public function getOnlyFields(): array
    {
        return $this->onlyFields;
    }

    /**
     * Get the fields that should be excluded.
     *
     * @return list<string>
     */
    public function getExceptFields(): array
    {
        return $this->exceptFields;
    }

    /**
     * Check if a field should be included in the output.
     */
    protected function shouldIncludeField(string $field): bool
    {
        // If only is set, field must be in the list
        if ($this->onlyFields !== []) {
            return in_array($field, $this->onlyFields, true);
        }

        // If except is set, field must not be in the list
        if ($this->exceptFields !== []) {
            return ! in_array($field, $this->exceptFields, true);
        }

        // No filtering, include all
        return true;
    }

    /**
     * Filter an array of fields based on current only/except settings.
     *
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    protected function filterFields(array $fields): array
    {
        if ($this->onlyFields !== []) {
            return array_intersect_key($fields, array_flip($this->onlyFields));
        }

        if ($this->exceptFields !== []) {
            return array_diff_key($fields, array_flip($this->exceptFields));
        }

        return $fields;
    }

    /**
     * Check if any field filtering is active.
     */
    protected function hasFieldFiltering(): bool
    {
        return $this->onlyFields !== [] || $this->exceptFields !== [];
    }

    /**
     * Clear all field filtering.
     *
     * @return $this
     */
    public function clearFieldFiltering(): static
    {
        $this->onlyFields = [];
        $this->exceptFields = [];

        return $this;
    }
}
