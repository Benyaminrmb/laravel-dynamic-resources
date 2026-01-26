<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Trait for automatic relationship loading based on active modes.
 *
 * @property mixed $resource The underlying resource
 * @method array getActiveModes() Get currently active modes
 */
trait HasRelationshipLoading
{
    /**
     * Relationships to explicitly auto-load.
     *
     * @var list<string>
     */
    protected array $autoloadRelations = [];

    /**
     * Specify relationships to auto-load for this resource.
     *
     * @param list<string> $relations
     * @return $this
     */
    public function loadRelations(array $relations): static
    {
        $this->autoloadRelations = array_values($relations);
        $this->loadMissingRelations();

        return $this;
    }

    /**
     * Load a single relationship if not already loaded.
     *
     * @return $this
     */
    public function loadRelation(string $relation): static
    {
        if (!in_array($relation, $this->autoloadRelations, true)) {
            $this->autoloadRelations[] = $relation;
        }

        $this->loadMissingRelations();

        return $this;
    }

    /**
     * Get relationships that have been marked for auto-loading.
     *
     * @return list<string>
     */
    public function getAutoloadRelations(): array
    {
        return $this->autoloadRelations;
    }

    /**
     * Get all relationships required for the current modes.
     * This includes both explicitly set relations and those detected from field definitions.
     *
     * @return list<string>
     */
    protected function getRequiredRelations(): array
    {
        $relations = $this->autoloadRelations;

        // If the using class has a method to get compiled field definitions,
        // extract required relations from them
        if (method_exists($this, 'getCompiledFieldDefinitions')) {
            /** @var array<string, \Benyaminrmb\LaravelDynamicResources\DTO\FieldSet> $fieldSets */
            $fieldSets = $this->getCompiledFieldDefinitions();

            foreach ($this->getActiveModes() as $mode) {
                if (isset($fieldSets[$mode])) {
                    $relations = array_merge($relations, $fieldSets[$mode]->requiredRelations);
                }
            }
        }

        return array_values(array_unique($relations));
    }

    /**
     * Load any missing relationships on the resource.
     */
    protected function loadMissingRelations(): void
    {
        if (!isset($this->resource) || !$this->resource instanceof Model) {
            return;
        }

        $required = $this->getRequiredRelations();

        if ($required === []) {
            return;
        }

        $missing = array_filter(
            $required,
            fn(string $relation): bool => !$this->resource->relationLoaded($relation)
        );

        if ($missing !== []) {
            $this->resource->loadMissing($missing);
        }
    }

    /**
     * Reload all specified relationships, even if already loaded.
     *
     * @param list<string>|null $relations Specific relations to reload, or null for all auto-load relations
     * @return $this
     */
    public function reloadRelations(?array $relations = null): static
    {
        if (!isset($this->resource) || !$this->resource instanceof Model) {
            return $this;
        }

        $toReload = $relations ?? $this->getRequiredRelations();

        if ($toReload !== []) {
            $this->resource->load($toReload);
        }

        return $this;
    }

    /**
     * Check if a relationship is loaded on the resource.
     */
    protected function isRelationLoaded(string $relation): bool
    {
        if (!isset($this->resource) || !$this->resource instanceof Model) {
            return false;
        }

        return $this->resource->relationLoaded($relation);
    }

    /**
     * Get a loaded relationship value, or null if not loaded.
     */
    protected function getLoadedRelation(string $relation): mixed
    {
        if (!$this->isRelationLoaded($relation)) {
            return null;
        }

        return $this->resource->{$relation};
    }
}
