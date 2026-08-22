<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources;

use BadMethodCallException;
use Benyaminrmb\LaravelDynamicResources\Collections\DynamicResourceCollection;
use Benyaminrmb\LaravelDynamicResources\Concerns\HasConditionalFields;
use Benyaminrmb\LaravelDynamicResources\Concerns\HasFieldFiltering;
use Benyaminrmb\LaravelDynamicResources\Concerns\HasModes;
use Benyaminrmb\LaravelDynamicResources\DTO\FieldSelection;
use Benyaminrmb\LaravelDynamicResources\Exceptions\CircularReferenceException;
use Benyaminrmb\LaravelDynamicResources\Exceptions\InvalidResourceQueryException;
use Benyaminrmb\LaravelDynamicResources\Exceptions\UndefinedModeException;
use Benyaminrmb\LaravelDynamicResources\Services\ResourceQueryParser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MergeValue;
use Illuminate\Http\Resources\MissingValue;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Macroable;
use Override;

/**
 * A Laravel JSON resource with composable field modes and safe sparse fieldsets.
 */
#[\AllowDynamicProperties]
abstract class DynamicResource extends JsonResource
{
    use HasConditionalFields;
    use HasFieldFiltering;
    use HasModes;
    use Macroable {
        __call as private callMacro;
    }

    protected const string DEFAULT_MODE = 'default';

    /** @var list<string> */
    private array $definitionStack = [];

    private ?FieldSelection $requestOnly = null;

    private ?FieldSelection $requestExcept = null;

    /** @return array<string, array<int|string, mixed>|Closure> */
    abstract public function fields(): array;

    public function defaultMode(): string
    {
        return static::DEFAULT_MODE;
    }

    /** @return list<string> */
    protected function requestableModes(): array
    {
        return [];
    }

    public function fromRequest(?Request $request = null): static
    {
        $request ??= request();
        $selection = app(ResourceQueryParser::class)->parse($request);

        if ($selection->mode !== null) {
            if (! in_array($selection->mode, $this->requestableModes(), true)) {
                throw InvalidResourceQueryException::modeNotAllowed($selection->mode);
            }

            $this->mode($selection->mode);
        }

        $this->requestOnly = $selection->only;
        $this->requestExcept = $selection->except;

        return $this;
    }

    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        if ($this->resource === null || $this->resource instanceof MissingValue) {
            return [];
        }

        $fields = [];

        foreach ($this->getActiveModes() as $mode) {
            $fields = array_merge($fields, $this->fieldsFrom($mode));
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

    /** @return array<string, mixed> */
    protected function fieldsFrom(string $mode): array
    {
        if (in_array($mode, $this->definitionStack, true)) {
            throw new CircularReferenceException([...$this->definitionStack, $mode]);
        }

        $definitions = $this->fields();

        if (! array_key_exists($mode, $definitions)) {
            throw new UndefinedModeException($mode, static::class, array_keys($definitions));
        }

        $this->definitionStack[] = $mode;

        try {
            $definition = $definitions[$mode];
            $fields = $definition instanceof Closure ? $definition() : $definition;

            if (! is_array($fields)) {
                throw new \UnexpectedValueException(sprintf('Mode %s::%s must return an array.', static::class, $mode));
            }

            return $this->normalizeFields($fields);
        } finally {
            array_pop($this->definitionStack);
        }
    }

    /** @return list<string> */
    public function getAvailableModes(): array
    {
        return array_keys($this->fields());
    }

    public function isModeAvailable(string $mode): bool
    {
        return array_key_exists($mode, $this->fields());
    }

    /** @param array<int|string, mixed> $parameters */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->callMacro($method, $parameters);
        }

        try {
            return $this->handleModeCall((string) $method, array_values($parameters));
        } catch (BadMethodCallException $exception) {
            if ($this->resource !== null && is_object($this->resource) && method_exists($this->resource, (string) $method)) {
                return $this->resource->{$method}(...$parameters);
            }

            throw $exception;
        }
    }

    /** @internal */
    public function inheritRequestSelection(?FieldSelection $only, ?FieldSelection $except): void
    {
        $this->requestOnly = $only;
        $this->requestExcept = $except;
    }

    /**
     * @param  array<int|string, mixed>  $fields
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
                    throw new \UnexpectedValueException('Numeric field definitions must contain field names or merge values.');
                }

                $name = $value;
                $normalized[$name] = fn (): mixed => data_get($this->resource, $name);
            } else {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $fields
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
        if ($value instanceof Closure) {
            $value = $value();
        }

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

        if (is_array($value)) {
            return $this->resolveArray($value, $request);
        }

        return $value;
    }

    /**
     * @param  array<int|string, mixed>  $values
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
}
