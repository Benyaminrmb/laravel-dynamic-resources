<?php

namespace Benyaminrmb\LaravelDynamicResources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use JsonSerializable;

abstract class ModularResource extends JsonResource
{
    /** @var array<string, mixed> */
    public $additional = [];

    /** @var array<string> */
    protected array $activeModes = ['default'];

    /** @var array<int|string, mixed> */
    protected array $except = [];

    /** @var array<int|string, mixed> */
    protected array $only = [];

    private bool $modeExplicitlySet = false;

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray(Request $request): array|JsonSerializable|Arrayable
    {
        $result = collect([]);

        // Get all fields structure
        $fields = $this->fields();

        // Process each active mode
        foreach ($this->activeModes as $mode) {
            if (!isset($fields[$mode])) {
                continue;
            }

            // Get mode fields
            $modeFields = $fields[$mode];

            // If the mode is a closure, evaluate it
            if ($modeFields instanceof \Closure) {
                $modeFields = $modeFields();
            }

            // Process each field in the mode
            foreach ($modeFields as $key => $value) {
                // Handle numeric keys (field names)
                if (is_numeric($key)) {
                    $result[$value] = $this->resolveField($this->{$value});
                    continue;
                }

                $result[$key] = $this->resolveField($value);
            }
        }

        // Apply field filters
        if (!empty($this->only)) {
            $result = $result->only($this->only);
        } elseif (!empty($this->except)) {
            $result = $result->except($this->except);
        }

        // Merge additional data
        return array_merge($result->toArray(), $this->additional);
    }

    /**
     * Resolve a field value
     *
     * @param mixed $value
     * @return mixed
     */
    protected function resolveField($value)
    {
        // Handle closure
        if ($value instanceof \Closure) {
            $value = $value();
        }

        // Handle nested ModularResource
        if ($value instanceof ModularResource && !$value->isModeExplicitlySet()) {
            $value->setActiveModes($this->activeModes);
        }
        // Handle nested collections
        elseif ($value instanceof Collection) {
            $value = $this->handleCollection($value);
        }

        return $value;
    }

    /**
     * Handle collection of resources
     */
    protected function handleCollection(Collection $collection): Collection|ModularResourceCollection
    {
        if ($collection->isEmpty()) {
            return $collection;
        }

        return static::collection($collection)
            ->when(!empty($this->activeModes), fn($c) => $c->setActiveModes($this->activeModes))
            ->when(!empty($this->except), fn($c) => $c->except($this->except))
            ->when(!empty($this->only), fn($c) => $c->only($this->only))
            ->when(!empty($this->additional), fn($c) => $c->additional($this->additional));
    }

    /**
     * Create new collection
     *
     * @param mixed $resource
     */
    public static function collection($resource): ModularResourceCollection
    {
        return new ModularResourceCollection($resource, static::class);
    }

    /**
     * Include only specific fields
     */
    public function only(array $fields): static
    {
        $this->only = $fields;
        return $this;
    }

    /**
     * Exclude specific fields
     */
    public function except(array $fields): static
    {
        $this->except = $fields;
        return $this;
    }

    /**
     * Add additional data
     */
    public function additional(array $data): static
    {
        $this->additional = array_merge($this->additional, $data);
        return $this;
    }

    /**
     * Set active modes
     */
    public function setActiveModes(array $modes): static
    {
        $this->activeModes = $modes;
        $this->modeExplicitlySet = true;
        return $this;
    }

    /**
     * Get active modes
     */
    public function getActiveModes(): array
    {
        return $this->activeModes;
    }

    /**
     * Add a mode
     */
    public function addMode(string $mode): static
    {
        if (!in_array($mode, $this->activeModes)) {
            $this->activeModes[] = $mode;
        }
        $this->modeExplicitlySet = true;
        return $this;
    }

    /**
     * Remove a mode
     */
    public function removeMode(string $mode): static
    {
        $this->activeModes = array_diff($this->activeModes, [$mode]);
        if (empty($this->activeModes)) {
            $this->activeModes = ['default'];
        }
        return $this;
    }

    public function isModeExplicitlySet(): bool
    {
        return $this->modeExplicitlySet;
    }

    /**
     * Magic method to handle dynamic mode methods
     */
    public function __call($method, $parameters)
    {
        // Check for mode-specific method patterns first
        if (str_starts_with($method, 'with')) {
            $mode = $this->normalizeMode(substr($method, 4));
            return $this->addMode($mode);
        }

        if (str_starts_with($method, 'without')) {
            $mode = $this->normalizeMode(substr($method, 7));
            return $this->removeMode($mode);
        }

        // Check if the method exists in the underlying model
        if (method_exists($this->resource, $method)) {
            return $this->resource->{$method}(...$parameters);
        }

        // Check if it's a mode name
        $mode = $this->normalizeMode($method);
        if (isset($this->fields()[$mode])) {
            return $this->setActiveModes([$mode]);
        }

        throw new BadMethodCallException(sprintf(
            'Method %s::%s does not exist.',
            static::class,
            $method
        ));
    }

    /**
     * Convert camelCase method name to kebab-case mode name
     */
    protected function normalizeMode(string $mode): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $mode));
    }

    /**
     * Define available fields for each mode
     */
    abstract protected function fields(): array;
}
