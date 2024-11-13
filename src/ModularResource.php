<?php

namespace Benyaminrmb\LaravelDynamicResources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

abstract class ModularResource extends JsonResource
{
    public $additional = [];

    protected string $mode = 'default';

    protected array $except = [];

    protected array $only = [];

    private bool $modeExplicitlySet = false;

    public function toArray($request): array
    {
        $fields = collect($this->fields());

        // Apply field filters
        if (! empty($this->only)) {
            $fields = $fields->only($this->only);
        } elseif (! empty($this->except)) {
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

            // Apply mode to nested resources only if mode hasn't been explicitly set
            if ($value instanceof ModularResource && ! $value->isModeExplicitlySet()) {
                $value->setMode($this->mode);
            } elseif ($value instanceof Collection) {
                $value = $this->handleCollection($value);
            }

            return [$key => $value];
        })->collapse()->toArray();
    }

    abstract protected function fields(): array;

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

    public function isModeExplicitlySet(): bool
    {
        return $this->modeExplicitlySet;
    }

    public function setMode(string $mode): static
    {
        $this->mode = $mode;
        $this->modeExplicitlySet = true;

        return $this;
    }

    protected function handleCollection(Collection $collection)
    {
        return static::collection($collection)
            ->when(! $this->modeExplicitlySet, fn ($c) => $c->setMode($this->mode))
            ->when(! empty($this->except), fn ($c) => $c->except($this->except))
            ->when(! empty($this->only), fn ($c) => $c->only($this->only))
            ->when(! empty($this->additional), fn ($c) => $c->additional($this->additional));
    }

    /**
     * Create a new collection
     */
    public static function collection($resource): ModularResourceCollection
    {
        return new ModularResourceCollection($resource, static::class);
    }

    /**
     * Add additional fields
     */
    public function additional(array $data): static
    {
        $this->additional = array_merge($this->additional, $data);

        return $this;
    }

    public function minimal(): static
    {
        return $this->setMode('minimal');
    }

    public function basic(): static
    {
        return $this->setMode('default');
    }

    public function default(): static
    {
        return $this->setMode('default');
    }

    public function detailed(): static
    {
        return $this->setMode('detailed');
    }
}
