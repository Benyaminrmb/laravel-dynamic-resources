<?php

namespace Benyaminrmb\LaravelDynamicResources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Resources\MissingValue;

class ModularResourceCollection extends ResourceCollection
{
    /** @var array<string> */
    protected array $activeModes = ['default'];

    protected array $except = [];

    protected array $only = [];

    public $additional = [];

    private bool $modeExplicitlySet = false;

    private string $resourceClass;

    public function __construct($resource, string $resourceClass)
    {
        $this->resourceClass = $resourceClass;
        $this->collects = $resourceClass;
        parent::__construct($resource);
    }

    public function toArray($request): array
    {
        return $this->collection->map(function ($resource) use ($request) {
            if ($resource instanceof MissingValue || is_null($resource)) {
                return null;
            }

            // Use the concrete resource class that was passed in
            if (!($resource instanceof $this->resourceClass)) {
                /** @var ModularResource $resourceInstance */
                $resourceInstance = new $this->resourceClass($resource);
                $resource = $resourceInstance;
            }

            if (!$resource->isModeExplicitlySet() && $this->modeExplicitlySet) {
                $resource->setActiveModes($this->activeModes);
            }

            if (!empty($this->except)) {
                $resource->except($this->except);
            }

            if (!empty($this->only)) {
                $resource->only($this->only);
            }

            if (!empty($this->additional)) {
                $resource->additional($this->additional);
            }

            return $resource->toArray($request);
        })->all();
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

    public function except(array $fields): static
    {
        $this->except = $fields;
        return $this;
    }

    public function only(array $fields): static
    {
        $this->only = $fields;
        return $this;
    }

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
        // Check for mode-specific method patterns first
        if (str_starts_with($method, 'with')) {
            $mode = $this->normalizeMode(substr($method, 4));
            return $this->addMode($mode);
        }

        if (str_starts_with($method, 'without')) {
            $mode = $this->normalizeMode(substr($method, 7));
            return $this->removeMode($mode);
        }

        // Try to call the method on the underlying collection
        if (method_exists($this->collection, $method)) {
            return $this->collection->{$method}(...$parameters);
        }

        // Check if it's a mode name by creating a temporary resource instance
        $tempResource = new $this->resourceClass(null);
        $mode = $this->normalizeMode($method);
        if (method_exists($tempResource, 'fields') && isset($tempResource->fields()[$mode])) {
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
}
