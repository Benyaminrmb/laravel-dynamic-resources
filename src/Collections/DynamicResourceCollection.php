<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Collections;

use Benyaminrmb\LaravelDynamicResources\Concerns\HasFieldFiltering;
use Benyaminrmb\LaravelDynamicResources\Concerns\HasModes;
use Benyaminrmb\LaravelDynamicResources\DTO\FieldSelection;
use Benyaminrmb\LaravelDynamicResources\DynamicResource;
use Benyaminrmb\LaravelDynamicResources\Services\ResourceQueryParser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Resources\MissingValue;
use Override;

class DynamicResourceCollection extends ResourceCollection
{
    use HasFieldFiltering;
    use HasModes;

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

    public function defaultMode(): string
    {
        return (new $this->resourceClass(null))->defaultMode();
    }

    /** @return array<string, array<int|string, mixed>|\Closure> */
    public function fields(): array
    {
        return (new $this->resourceClass(null))->fields();
    }

    public function fromRequest(?Request $request = null): static
    {
        $request ??= request();
        $selection = app(ResourceQueryParser::class)->parse($request);

        if ($selection->mode !== null) {
            $probe = new $this->resourceClass(null);
            $probe->fromRequest($request);
            $this->mode($selection->mode);
        }

        $this->requestOnly = $selection->only;
        $this->requestExcept = $selection->except;

        return $this;
    }

    /** @return list<array<string, mixed>> */
    #[Override]
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof MissingValue || $this->collection === null) {
            return [];
        }

        return array_values($this->collection
            ->map(function (mixed $item) use ($request): ?array {
                if ($item === null || $item instanceof MissingValue) {
                    return null;
                }

                $resource = $item instanceof $this->resourceClass ? $item : new $this->resourceClass($item);

                if ($resource->resource === null || $resource->resource instanceof MissingValue) {
                    return null;
                }

                if ($this->hasModeExplicitlySet() && ! $resource->hasModeExplicitlySet()) {
                    $resource->inheritModes($this->getActiveModes(), true);
                }

                if ($this->onlyFields !== []) {
                    $resource->only($this->onlyFields);
                } elseif ($this->exceptFields !== []) {
                    $resource->except($this->exceptFields);
                }

                $resource->inheritRequestSelection($this->requestOnly, $this->requestExcept);

                return $resource->toArray($request);
            })
            ->filter(static fn (?array $item): bool => $item !== null)
            ->all());
    }

    /** @param array<int|string, mixed> $parameters */
    public function __call($method, $parameters)
    {
        return $this->handleModeCall((string) $method, array_values($parameters));
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
