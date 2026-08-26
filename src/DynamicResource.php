<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources;

use Benyaminrmb\LaravelDynamicResources\Collections\DynamicResourceCollection;
use Benyaminrmb\LaravelDynamicResources\Concerns\HasConditionalFields;
use Benyaminrmb\LaravelDynamicResources\Concerns\HasFieldFiltering;
use Benyaminrmb\LaravelDynamicResources\Concerns\HasViews;
use Benyaminrmb\LaravelDynamicResources\DTO\FieldSelection;
use Benyaminrmb\LaravelDynamicResources\Exceptions\InvalidResourceQueryException;
use Benyaminrmb\LaravelDynamicResources\Exceptions\UndefinedViewException;
use Benyaminrmb\LaravelDynamicResources\Services\ResourceQueryParser;
use Benyaminrmb\LaravelDynamicResources\Services\ViewRegistry;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MergeValue;
use Illuminate\Http\Resources\MissingValue;
use Illuminate\Support\Collection;
use Override;
use Throwable;
use UnexpectedValueException;

/** A Laravel JSON resource with composable, IDE-discoverable views. */
#[\AllowDynamicProperties]
abstract class DynamicResource extends JsonResource
{
    use HasConditionalFields;
    use HasFieldFiltering;
    use HasViews;

    private ?FieldSelection $requestOnly = null;

    private ?FieldSelection $requestExcept = null;

    public function defaultView(): string
    {
        return $this->registry()->defaultFor(static::class);
    }

    /** @return list<string> */
    public function getAvailableViews(): array
    {
        return array_keys($this->registry()->for(static::class));
    }

    public function isViewAvailable(string $view): bool
    {
        return in_array($view, $this->getAvailableViews(), true);
    }

    /** @return list<string> */
    public function getRequestableViews(): array
    {
        return $this->registry()->requestableFor(static::class);
    }

    public function fromRequest(?Request $request = null): static
    {
        $request ??= request();
        $selection = app(ResourceQueryParser::class)->parse($request);

        if ($selection->view !== null) {
            if (! in_array($selection->view, $this->getRequestableViews(), true)) {
                throw InvalidResourceQueryException::viewNotAllowed($selection->view);
            }
            $this->view($selection->view);
        }

        $this->requestOnly = $selection->only;
        $this->requestExcept = $selection->except;

        return $this;
    }

    /** @return array<string, mixed> */
    #[Override]
    final public function toArray(Request $request): array
    {
        if ($this->resource === null || $this->resource instanceof MissingValue) {
            return [];
        }

        $fields = [];
        foreach ($this->getActiveViews() as $view) {
            $fields = array_merge($fields, $this->fieldsForView($view));
        }

        $result = [];
        foreach ($this->applyRootFilters($fields) as $key => $value) {
            $resolved = $this->resolveField($value, $key, $request);
            if (! $resolved instanceof MissingValue) {
                $result[$key] = $resolved;
            }
        }

        return $result;
    }

    public static function collection(mixed $resource): DynamicResourceCollection
    {
        return new DynamicResourceCollection($resource, static::class);
    }

    /** @param array<int|string, mixed> ...$views
     * @return array<int|string, mixed>
     */
    protected function combine(array ...$views): array
    {
        return array_merge(...$views);
    }

    /**
     * @param  list<string>|string|null  $view
     * @param  class-string<DynamicResource>|null  $resourceClass
     */
    protected function relation(string $name, string|array|null $view = null, ?string $resourceClass = null): mixed
    {
        if (! $this->resource instanceof Model || ! $this->resource->relationLoaded($name)) {
            return new MissingValue;
        }

        $related = $this->resource->getRelation($name);
        if ($related === null || $related instanceof MissingValue) {
            return new MissingValue;
        }

        $resourceClass ??= $this->guessRelatedResource($related, $name);
        $resource = $related instanceof Collection || is_array($related)
            ? $resourceClass::collection($related)
            : $resourceClass::make($related);

        return $view === null ? $resource : $resource->view($view);
    }

    /** @internal */
    public function inheritRequestSelection(?FieldSelection $only, ?FieldSelection $except): void
    {
        $this->requestOnly = $only;
        $this->requestExcept = $except;
    }

    /** @internal */
    public function passRequestSelectionTo(DynamicResourceCollection $collection): void
    {
        $collection->inheritRequestSelection($this->requestOnly, $this->requestExcept);
    }

    /** @return array<string, mixed> */
    private function fieldsForView(string $view): array
    {
        $definition = $this->registry()->for(static::class)[$view] ?? null;
        if ($definition === null) {
            throw new UndefinedViewException($view, static::class, $this->getAvailableViews());
        }

        $fields = $definition->method->invoke($this);
        if (! is_array($fields)) {
            throw new UnexpectedValueException(sprintf('View %s::%s must return an array.', static::class, $view));
        }

        return $this->normalizeFields($fields);
    }

    /** @param array<int|string, mixed> $fields
     * @return array<string, mixed>
     */
    private function normalizeFields(array $fields): array
    {
        $normalized = [];
        foreach ($fields as $key => $value) {
            if (is_int($key)) {
                if ($value instanceof MergeValue) {
                    $normalized = array_merge($normalized, $this->normalizeFields($value->data));

                    continue;
                }
                if (! is_string($value)) {
                    throw new UnexpectedValueException('Numeric field definitions must contain field names or merge values.');
                }
                $normalized[$value] = fn (): mixed => data_get($this->resource, $value);
            } else {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    /** @param array<string, mixed> $fields
     * @return array<string, mixed>
     */
    private function applyRootFilters(array $fields): array
    {
        if ($this->requestOnly !== null) {
            $fields = array_intersect_key($fields, array_flip($this->requestOnly->fields()));
        }
        if ($this->requestExcept !== null) {
            $fields = array_diff_key($fields, array_flip($this->requestExcept->fields()));
        }

        return $this->filterFields($fields);
    }

    private function resolveField(mixed $value, string|int $key, Request $request): mixed
    {
        $value = $value instanceof Closure ? $value() : $value;
        if ($value instanceof MissingValue) {
            return $value;
        }
        if ($value instanceof MergeValue) {
            return $this->resolveArray($value->data, $request);
        }

        $childOnly = is_string($key) ? $this->requestOnly?->child($key) : null;
        $childExcept = is_string($key) ? $this->requestExcept?->child($key) : null;

        if ($value instanceof self) {
            $value->inheritRequestSelection($childOnly, $childExcept);

            return $value->toArray($request);
        }
        if ($value instanceof DynamicResourceCollection) {
            $value->inheritRequestSelection($childOnly, $childExcept);

            return $value->toArray($request);
        }
        if ($value instanceof Collection) {
            return $value->map(fn (mixed $item): mixed => $this->resolveField($item, $key, $request));
        }

        return is_array($value) ? $this->resolveArray($value, $request) : $value;
    }

    /** @param array<int|string, mixed> $values
     * @return array<int|string, mixed>
     */
    private function resolveArray(array $values, Request $request): array
    {
        $result = [];
        foreach ($values as $key => $value) {
            if ($value instanceof MergeValue) {
                $result = array_merge($result, $this->resolveArray($value->data, $request));

                continue;
            }
            $resolved = $this->resolveField($value, $key, $request);
            if (! $resolved instanceof MissingValue) {
                $result[$key] = $resolved;
            }
        }

        return $result;
    }

    /** @return class-string<DynamicResource> */
    private function guessRelatedResource(mixed $related, string $relation): string
    {
        $model = $related instanceof Model ? $related : ($related instanceof Collection ? $related->first() : null);
        if (! $model instanceof Model && $this->resource instanceof Model && method_exists($this->resource, $relation)) {
            try {
                $relationship = $this->resource->{$relation}();
                $model = is_object($relationship) && method_exists($relationship, 'getRelated')
                    ? $relationship->getRelated()
                    : null;
            } catch (Throwable) {
                $model = null;
            }
        }

        if ($model instanceof Model) {
            foreach ($model::guessResourceName() as $candidate) {
                if (is_subclass_of($candidate, self::class)) {
                    return $candidate;
                }
            }
        }

        throw new UnexpectedValueException(sprintf(
            "Unable to discover a DynamicResource for loaded relation '%s'; pass resourceClass explicitly.",
            $relation,
        ));
    }

    private function registry(): ViewRegistry
    {
        return app(ViewRegistry::class);
    }
}
