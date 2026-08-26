<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Collections;

use Benyaminrmb\LaravelDynamicResources\Concerns\HasFieldFiltering;
use Benyaminrmb\LaravelDynamicResources\Concerns\HasViews;
use Benyaminrmb\LaravelDynamicResources\DTO\FieldSelection;
use Benyaminrmb\LaravelDynamicResources\DynamicResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Resources\MissingValue;
use Override;

class DynamicResourceCollection extends ResourceCollection
{
    use HasFieldFiltering;
    use HasViews;

    /** @var class-string<DynamicResource> */
    private readonly string $resourceClass;

    private ?FieldSelection $requestOnly = null;

    private ?FieldSelection $requestExcept = null;

    /** @param class-string<DynamicResource> $resourceClass */
    public function __construct(mixed $resource, string $resourceClass)
    {
        $this->resourceClass = $resourceClass;
        $this->collects = $resourceClass;
        parent::__construct($resource);
    }

    public function defaultView(): string
    {
        return (new $this->resourceClass(null))->defaultView();
    }

    /** @return list<string> */
    public function getAvailableViews(): array
    {
        return (new $this->resourceClass(null))->getAvailableViews();
    }

    public function fromRequest(?Request $request = null): static
    {
        $probe = new $this->resourceClass(null);
        $probe->fromRequest($request);
        if ($probe->hasViewExplicitlySet()) {
            $this->view($probe->getActiveViews());
        }
        $probe->passRequestSelectionTo($this);

        return $this;
    }

    /** @return list<array<string, mixed>> */
    #[Override]
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof MissingValue || $this->collection === null) {
            return [];
        }

        return array_values($this->collection->map(function (mixed $item) use ($request): ?array {
            if ($item === null || $item instanceof MissingValue) {
                return null;
            }
            $resource = $item instanceof $this->resourceClass ? $item : new $this->resourceClass($item);
            if ($resource->resource === null || $resource->resource instanceof MissingValue) {
                return null;
            }
            if ($this->hasViewExplicitlySet() && ! $resource->hasViewExplicitlySet()) {
                $resource->inheritViews($this->getActiveViews(), true);
            }
            if ($this->onlyFields !== []) {
                $resource->only($this->onlyFields);
            } elseif ($this->exceptFields !== []) {
                $resource->except($this->exceptFields);
            }
            $resource->inheritRequestSelection($this->requestOnly, $this->requestExcept);

            return $resource->toArray($request);
        })->filter(static fn (?array $item): bool => $item !== null)->all());
    }

    /** @internal */
    public function inheritRequestSelection(?FieldSelection $only, ?FieldSelection $except): void
    {
        $this->requestOnly = $only;
        $this->requestExcept = $except;
    }

    /** @return class-string<DynamicResource> */
    public function getResourceClass(): string
    {
        return $this->resourceClass;
    }
}
