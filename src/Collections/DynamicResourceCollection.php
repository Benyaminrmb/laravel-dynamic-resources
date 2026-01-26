<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Collections;

use Benyaminrmb\LaravelDynamicResources\Concerns\HasFieldFiltering;
use Benyaminrmb\LaravelDynamicResources\Concerns\HasModes;
use Benyaminrmb\LaravelDynamicResources\DynamicResource;
use Benyaminrmb\LaravelDynamicResources\DTO\ModeConfiguration;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Resources\MissingValue;
use Override;

/**
 * A collection of DynamicResource instances with shared mode configuration.
 *
 * This collection automatically applies mode settings and field filtering
 * to all items within it. Great for API list endpoints.
 *
 * ## Usage
 *
 * ```php
 * // In controller:
 * return UserResource::collection($users)->minimal();
 * return UserResource::collection($users)->detailed()->withAvatar();
 *
 * // With pagination:
 * return UserResource::collection($users->paginate(15))->minimal();
 *
 * // With field filtering:
 * return UserResource::collection($users)->only(['id', 'name']);
 * ```
 *
 * @template TResource of DynamicResource
 *
 * @method $this minimal() Set to minimal mode
 * @method $this default() Set to default mode
 * @method $this detailed() Set to detailed mode
 * @method $this withAvatar() Add avatar mode
 * @method $this withPosts() Add posts mode
 * @method $this withoutTimestamps() Remove timestamps mode
 */
class DynamicResourceCollection extends ResourceCollection
{
    use HasModes {
        HasModes::__call as private handleModeCall;
    }
    use HasFieldFiltering;

    /**
     * The default mode.
     */
    protected const string DEFAULT_MODE = 'default';

    /**
     * The resource class to instantiate for each item.
     *
     * @var class-string<TResource>
     */
    private readonly string $resourceClass;

    /**
     * Create a new resource collection.
     *
     * @param mixed $resource The collection data
     * @param class-string<TResource> $resourceClass The resource class name
     */
    public function __construct(mixed $resource, string $resourceClass)
    {
        $this->resourceClass = $resourceClass;
        $this->collects = $resourceClass;

        parent::__construct($resource);
    }

    /**
     * Transform the resource collection into an array.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        // Handle null or MissingValue collection
        if ($this->collection === null || $this->resource instanceof MissingValue) {
            return [];
        }

        return $this->collection->map(function (mixed $item) use ($request): ?array {
            // Skip null and missing values
            if ($item instanceof MissingValue || $item === null) {
                return null;
            }

            // If item is a resource with null underlying resource, skip it
            if ($item instanceof DynamicResource && $item->resource === null) {
                return null;
            }

            // Create resource instance if needed
            $resource = $this->ensureResourceInstance($item);

            // Apply mode configuration if set and not explicitly set on item
            if (!$resource->hasModeExplicitlySet() && $this->hasModeExplicitlySet()) {
                $resource->setModeConfiguration($this->getModeConfiguration());
            }

            // Apply field filters
            if ($this->onlyFields !== []) {
                $resource->only($this->onlyFields);
            } elseif ($this->exceptFields !== []) {
                $resource->except($this->exceptFields);
            }

            // Apply additional data
            if ($this->additional !== []) {
                $resource->additional($this->additional);
            }

            return $resource->toArray($request);
        })
            ->filter(static fn(?array $item): bool => $item !== null)
            ->values()
            ->all();
    }

    /**
     * Ensure the item is a resource instance.
     *
     * @return TResource
     */
    private function ensureResourceInstance(mixed $item): DynamicResource
    {
        if ($item instanceof $this->resourceClass) {
            return $item;
        }

        return new $this->resourceClass($item);
    }

    /**
     * Get fields definition from the resource class.
     *
     * @return array<string, mixed>
     */
    public function fields(): array
    {
        // Create a temporary instance to get fields
        $tempResource = new $this->resourceClass(null);

        return $tempResource->fields();
    }

    /**
     * Get the default mode.
     */
    public function defaultMode(): string
    {
        return static::DEFAULT_MODE;
    }

    /**
     * Handle dynamic method calls.
     *
     * @param string $method
     * @param array<int, mixed> $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->handleModeCall($method, $parameters);
    }

    /**
     * Get the resource class name.
     *
     * @return class-string<TResource>
     */
    public function getResourceClass(): string
    {
        return $this->resourceClass;
    }

    /**
     * Get the number of items in the collection.
     */
    public function count(): int
    {
        return $this->collection?->count() ?? 0;
    }
}
