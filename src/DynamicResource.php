<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources;

use Benyaminrmb\LaravelDynamicResources\Collections\DynamicResourceCollection;
use Benyaminrmb\LaravelDynamicResources\Concerns\HasConditionalFields;
use Benyaminrmb\LaravelDynamicResources\Concerns\HasFieldFiltering;
use Benyaminrmb\LaravelDynamicResources\Concerns\HasModes;
use Benyaminrmb\LaravelDynamicResources\Concerns\HasRelationshipLoading;
use Benyaminrmb\LaravelDynamicResources\Contracts\FieldDefinition;
use Benyaminrmb\LaravelDynamicResources\DTO\FieldSet;
use Benyaminrmb\LaravelDynamicResources\DTO\ModeConfiguration;
use Benyaminrmb\LaravelDynamicResources\DTO\TransformationContext;
use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Macroable;
use JsonSerializable;
use Override;

/**
 * Dynamic API Resource with mode-based field selection.
 *
 * This class extends Laravel's JsonResource to provide powerful mode-based
 * field management. Define different sets of fields for different use cases
 * (minimal, default, detailed, etc.) and compose them dynamically.
 *
 * ## Quick Start
 *
 * ```php
 * class UserResource extends DynamicResource
 * {
 *     public function fields(): array
 *     {
 *         return [
 *             'minimal' => ['id', 'name'],
 *             'default' => ['id', 'name', 'email'],
 *             'detailed' => fn() => [
 *                 ...$this->mode('default'),
 *                 'created_at' => $this->created_at->toISOString(),
 *                 'posts' => PostResource::collection($this->whenLoaded('posts')),
 *             ],
 *         ];
 *     }
 * }
 *
 * // Usage in controller:
 * return UserResource::make($user)->minimal();
 * return UserResource::make($user)->detailed()->withAvatar();
 * return UserResource::collection($users)->minimal();
 * ```
 *
 * @template TResource of \Illuminate\Database\Eloquent\Model|object|array<string, mixed>
 * @extends JsonResource<TResource>
 *
 * @method static static make(mixed $resource) Create a new resource instance
 * @method $this withMeta(array $meta) Add meta information to the response
 */
#[\AllowDynamicProperties]
abstract class DynamicResource extends JsonResource implements FieldDefinition
{
    /** @use HasModes<static> */
    use HasModes {
        HasModes::__call as private handleModeCall;
    }
    use HasFieldFiltering;
    use HasConditionalFields;
    use HasRelationshipLoading;
    use Macroable {
        Macroable::__call as private handleMacroCall;
    }

    /**
     * The default mode name.
     */
    protected const string DEFAULT_MODE = 'default';

    /**
     * Cached compiled field definitions per resource class.
     *
     * @var array<class-string<static>, array<string, FieldSet>>
     */
    private static array $fieldDefinitionCache = [];

    /**
     * The current transformation context.
     */
    private ?TransformationContext $transformContext = null;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array|JsonSerializable|Arrayable
    {
        if ($this->resource === null) {
            return [];
        }

        $context = $this->getTransformationContext($request);
        $fieldSet = $this->resolveFieldSet($context);
        $result = $this->transformFields($fieldSet, $context);

        // Merge additional data if any
        if ($this->additional !== []) {
            $result = array_merge($result, $this->additional);
        }

        return $result;
    }

    /**
     * Define fields for each mode.
     *
     * Override this method to define your resource's field structure.
     * Each key is a mode name, and the value is either:
     * - An array of field names (simple fields)
     * - A closure returning an array of field definitions (for computed fields)
     *
     * ## Simple Fields
     * Just list the field names to include from the model:
     * ```php
     * 'minimal' => ['id', 'name'],
     * ```
     *
     * ## Computed Fields
     * Use closures for computed or transformed values:
     * ```php
     * 'detailed' => fn() => [
     *     'id',
     *     'name',
     *     'full_name' => "{$this->first_name} {$this->last_name}",
     *     'created_at' => $this->created_at->toISOString(),
     * ],
     * ```
     *
     * ## Mode Inheritance
     * Reference other modes to build upon them:
     * ```php
     * 'detailed' => fn() => [
     *     ...$this->mode('default'),
     *     'extra_field' => $this->extra,
     * ],
     * ```
     *
     * @return array<string, array<int|string, mixed>|\Closure>
     */
    abstract public function fields(): array;

    /**
     * Get the default mode name.
     */
    public function defaultMode(): string
    {
        return static::DEFAULT_MODE;
    }

    /**
     * Create a resource collection.
     *
     * @param mixed $resource The collection data (array, Collection, or Paginator)
     */
    public static function collection(mixed $resource): DynamicResourceCollection
    {
        return new DynamicResourceCollection($resource, static::class);
    }

    /**
     * Get fields from another mode (for inheritance/composition).
     *
     * Use this within a mode's closure to inherit fields from another mode:
     * ```php
     * 'detailed' => fn() => [
     *     ...$this->mode('default'),
     *     'extra_field' => $this->extra,
     * ],
     * ```
     *
     * @return array<string, mixed>
     */
    protected function mode(string $name): array
    {
        $fields = $this->fields();

        if (!isset($fields[$name])) {
            return [];
        }

        $modeFields = $fields[$name];

        if ($modeFields instanceof Closure) {
            return $modeFields->call($this);
        }

        return $this->normalizeFieldDefinition($modeFields);
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
        // Check macros first
        if (static::hasMacro($method)) {
            return $this->handleMacroCall($method, $parameters);
        }

        // Then handle mode-related calls
        return $this->handleModeCall($method, $parameters);
    }

    /**
     * Get or create the transformation context.
     */
    protected function getTransformationContext(Request $request): TransformationContext
    {
        if ($this->transformContext !== null) {
            return $this->transformContext;
        }

        return $this->transformContext = new TransformationContext(
            modeConfig: $this->getModeConfiguration(),
            request: $request,
            metadata: ['resource_class' => static::class],
        );
    }

    /**
     * Resolve the complete field set for current modes.
     */
    protected function resolveFieldSet(TransformationContext $context): FieldSet
    {
        $compiledFields = $this->getCompiledFieldDefinitions();
        $result = FieldSet::empty();

        // Merge fields from all active modes
        foreach ($context->modeConfig->activeModes as $mode) {
            if (isset($compiledFields[$mode])) {
                $result = $result->merge($compiledFields[$mode]);
            }
        }

        // Apply request-based field filtering
        if ($context->modeConfig->requestedFields !== []) {
            $result = $result->only($context->modeConfig->requestedFields);
        }

        if ($context->modeConfig->excludedFields !== []) {
            $result = $result->except($context->modeConfig->excludedFields);
        }

        // Apply instance-level filtering
        if ($this->onlyFields !== []) {
            $result = $result->only($this->onlyFields);
        } elseif ($this->exceptFields !== []) {
            $result = $result->except($this->exceptFields);
        }

        return $result;
    }

    /**
     * Get compiled field definitions for current resource.
     * Note: Closures are evaluated fresh each time for proper whenLoaded/when support.
     *
     * @return array<string, FieldSet>
     */
    protected function getCompiledFieldDefinitions(): array
    {
        $fields = $this->fields();
        $compiled = [];

        foreach ($fields as $mode => $definition) {
            // Evaluate closures now (at transform time, not cache time)
            $fieldArray = $definition instanceof Closure
                ? $definition->call($this)
                : $definition;

            $compiled[$mode] = FieldSet::fromArray(
                $this->normalizeFieldDefinition($fieldArray),
                $this->extractRequiredRelations($fieldArray),
            );
        }

        return $compiled;
    }

    /**
     * Normalize field definition to consistent key => value format.
     *
     * @param array<int|string, mixed> $fields
     * @return array<string, mixed>
     */
    protected function normalizeFieldDefinition(array $fields): array
    {
        $normalized = [];

        foreach ($fields as $key => $value) {
            if (is_int($key)) {
                // Numeric key: value is the field name, get from model
                $fieldName = (string) $value;
                $normalized[$fieldName] = fn() => $this->resource->{$fieldName} ?? null;
            } else {
                // String key: key is field name, value is the definition
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    /**
     * Transform fields to final array.
     *
     * @return array<string, mixed>
     */
    protected function transformFields(FieldSet $fieldSet, TransformationContext $context): array
    {
        $result = [];

        foreach ($fieldSet->fields as $key => $value) {
            $resolved = $this->resolveField($value, $context);

            // Skip missing values
            if ($resolved instanceof MissingValue) {
                continue;
            }

            $result[$key] = $resolved;
        }

        return $result;
    }

    /**
     * Resolve a single field value.
     */
    protected function resolveField(mixed $value, TransformationContext $context): mixed
    {
        // Handle closures (lazy evaluation)
        if ($value instanceof Closure) {
            $value = $value->call($this);
        }

        // Handle missing values
        if ($value instanceof MissingValue) {
            return $value;
        }

        // Handle nested DynamicResource - convert to array
        if ($value instanceof self) {
            // Inherit mode configuration if not explicitly set
            if (!$value->hasModeExplicitlySet()) {
                $value->setModeConfiguration($context->modeConfig);
            }

            return $value->toArray($context->request);
        }

        // Handle collections of resources - convert to array
        if ($value instanceof DynamicResourceCollection) {
            // Inherit mode configuration if not explicitly set
            if (!$value->hasModeExplicitlySet()) {
                $value->setModeConfiguration($context->modeConfig);
            }

            return $value->toArray($context->request);
        }

        // Handle Laravel collections - wrap them properly
        if ($value instanceof Collection) {
            return $value->map(fn($item) => $this->resolveField($item, $context));
        }

        // Handle arrays (merge results from mergeWhen, etc.)
        if (is_array($value)) {
            return $this->resolveArrayValue($value, $context);
        }

        return $value;
    }

    /**
     * Resolve an array value, handling nested structures.
     *
     * @param array<int|string, mixed> $values
     * @return array<int|string, mixed>
     */
    protected function resolveArrayValue(array $values, TransformationContext $context): array
    {
        $result = [];

        foreach ($values as $key => $value) {
            // Skip MissingValue
            if ($value instanceof MissingValue) {
                continue;
            }

            // Handle closures
            if ($value instanceof Closure) {
                $value = $value->call($this);
                if ($value instanceof MissingValue) {
                    continue;
                }
            }

            // Handle MergeValue from mergeWhen (these have numeric keys and should be merged)
            if ($value instanceof \Illuminate\Http\Resources\MergeValue) {
                foreach ($value->data as $mergeKey => $mergeValue) {
                    $result[$mergeKey] = $this->resolveField($mergeValue, $context);
                }
                continue;
            }

            // Handle nested resources
            if ($value instanceof self) {
                if (!$value->hasModeExplicitlySet()) {
                    $value->setModeConfiguration($context->modeConfig);
                }
                $result[$key] = $value->toArray($context->request);
                continue;
            }

            // Handle resource collections
            if ($value instanceof DynamicResourceCollection) {
                if (!$value->hasModeExplicitlySet()) {
                    $value->setModeConfiguration($context->modeConfig);
                }
                $result[$key] = $value->toArray($context->request);
                continue;
            }

            // For other values (including plain arrays), assign directly without recursive processing
            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * Extract required relations from field definitions.
     *
     * @param array<int|string, mixed> $fields
     * @return list<string>
     */
    protected function extractRequiredRelations(array $fields): array
    {
        $relations = [];

        foreach ($fields as $value) {
            // Check if value is a DynamicResource (nested)
            if ($value instanceof self) {
                // Could extract relation name from resource, but complex
                // For now, rely on explicit loadRelations() calls
            }
        }

        return $relations;
    }

    /**
     * Clear the static field definition cache.
     */
    public static function clearFieldDefinitionCache(): void
    {
        self::$fieldDefinitionCache = [];
    }

    /**
     * Clear cache for a specific resource class.
     *
     * @param class-string<self> $class
     */
    public static function clearFieldDefinitionCacheFor(string $class): void
    {
        unset(self::$fieldDefinitionCache[$class]);
    }

    /**
     * Get all available modes for this resource.
     *
     * @return list<string>
     */
    public function getAvailableModes(): array
    {
        return array_keys($this->fields());
    }

    /**
     * Check if a mode is defined in this resource.
     */
    public function isModeAvailable(string $mode): bool
    {
        return isset($this->fields()[$mode]);
    }
}
