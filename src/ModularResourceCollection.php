<?php

namespace Benyaminrmb\LaravelDynamicResources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Resources\MissingValue;

class ModularResourceCollection extends ResourceCollection
{
    protected string $mode = 'default';

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
            if (! ($resource instanceof $this->resourceClass)) {
                /** @var ModularResource $resourceInstance */
                $resourceInstance = new $this->resourceClass($resource);
                $resource = $resourceInstance;
            }

            if (! $resource->isModeExplicitlySet() && $this->modeExplicitlySet) {
                $resource->setMode($this->mode);
            }

            if (! empty($this->except)) {
                $resource->except($this->except);
            }

            if (! empty($this->only)) {
                $resource->only($this->only);
            }

            if (! empty($this->additional)) {
                $resource->additional($this->additional);
            }

            return $resource->toArray($request);
        })->all();
    }

    public function setMode(string $mode): static
    {
        $this->mode = $mode;
        $this->modeExplicitlySet = true;

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
}
