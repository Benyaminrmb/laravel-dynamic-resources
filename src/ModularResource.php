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
        $fields = collect([]);

        // Combine fields from all active modes
        foreach ($this->activeModes as $mode) {
            $modeFields = collect($this->getFieldsForMode($mode));
            $fields = $fields->merge($modeFields);
        }

        // Apply field filters
        if (!empty($this->only)) {
            $fields = $fields->only($this->only);
        } elseif (!empty($this->except)) {
            $fields = $fields->except($this->except);
        }

        // Add additional fields
        $fields = $fields->merge($this->additional);

        // Transform the fields
        return $fields->map(function ($value, $key) {
            // Handle numeric keys (field names)
            if (is_numeric($key)) {
                $key = $value;
                $value = $this->{$value};
            }

            // Apply modes to nested resources
            if ($value instanceof ModularResource && !$value->isModeExplicitlySet()) {
                $value->setActiveModes($this->activeModes);
            } elseif ($value instanceof Collection) {
                $value = $this->handleCollection($value);
            }

            return [$key => $value];
        })->collapse()->toArray();
    }

    /**
     * Get fields for a specific mode
     *
     * @param string $mode
     * @return array<string|int, mixed>
     */
    protected function getFieldsForMode(string $mode): array
    {
        return $this->fields()[$mode] ?? [];
    }

    /**
     * Define available fields for each mode
     *
     * @return array<string, array<string|int, mixed>>
     */
    abstract protected function fields(): array;

    /**
     * Include only specific fields
     *
     * @param array<int|string, mixed> $fields
     */
    public function only(array $fields): static
    {
        $this->only = $fields;
        return $this;
    }

    /**
     * Exclude specific fields
     *
     * @param array<int|string, mixed> $fields
     */
    public function except(array $fields): static
    {
        $this->except = $fields;
        return $this;
    }

    /**
     * Set active modes
     *
     * @param array<string> $modes
     */
    public function setActiveModes(array $modes): static
    {
        $this->activeModes = $modes;
        $this->modeExplicitlySet = true;
        return $this;
    }

    /**
     * Get active modes
     *
     * @return array<string>
     */
    public function getActiveModes(): array
    {
        return $this->activeModes;
    }

    /**
     * Add a mode to active modes
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
     * Remove a mode from active modes
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
     * Handle collection of resources
     *
     * @param Collection<int|string, mixed> $collection
     */
    protected function handleCollection(Collection $collection): ModularResourceCollection
    {
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
     * Add additional fields
     *
     * @param array<string, mixed> $data
     */
    public function additional(array $data): static
    {
        $this->additional = array_merge($this->additional, $data);
        return $this;
    }

    /**
     * Magic method to handle dynamic mode methods
     */
    public function __call($method, $parameters)
    {
        if (str_starts_with($method, 'with')) {
            $mode = $this->normalizeMode(substr($method, 4));
            return $this->addMode($mode);
        }

        if (str_starts_with($method, 'without')) {
            $mode = $this->normalizeMode(substr($method, 7));
            return $this->removeMode($mode);
        }

        // Handle existing mode methods (minimal, detailed, etc.)
        $mode = $this->normalizeMode($method);
        return $this->setActiveModes([$mode]);
    }

    /**
     * Convert camelCase method name to kebab-case mode name
     */
    protected function normalizeMode(string $mode): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $mode));
    }
}
