<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\DTO;

use Illuminate\Http\Request;

/**
 * Immutable context object passed through the resource transformation pipeline.
 * Carries request information, mode configuration, and transformation state.
 */
readonly class TransformationContext
{
    /**
     * Maximum nesting depth to prevent infinite recursion.
     */
    public const int MAX_DEPTH = 10;

    /**
     * @param ModeConfiguration $modeConfig Current mode configuration
     * @param Request $request The HTTP request being processed
     * @param int<0, max> $depth Current nesting depth (0 = root resource)
     * @param self|null $parent Parent context for nested resources
     * @param array<string, mixed> $metadata Additional metadata for the transformation
     */
    public function __construct(
        public ModeConfiguration $modeConfig,
        public Request $request,
        public int $depth = 0,
        public ?self $parent = null,
        public array $metadata = [],
    ) {}

    /**
     * Create a child context for nested resources.
     * Inherits mode configuration and increments depth.
     */
    public function forNested(?ModeConfiguration $modeConfig = null): self
    {
        return new self(
            modeConfig: $modeConfig ?? $this->modeConfig,
            request: $this->request,
            depth: $this->depth + 1,
            parent: $this,
            metadata: $this->metadata,
        );
    }

    /**
     * Create a new context with a different mode configuration.
     */
    public function withModeConfig(ModeConfiguration $modeConfig): self
    {
        return new self(
            modeConfig: $modeConfig,
            request: $this->request,
            depth: $this->depth,
            parent: $this->parent,
            metadata: $this->metadata,
        );
    }

    /**
     * Create a new context with additional metadata.
     *
     * @param array<string, mixed> $metadata
     */
    public function withMetadata(array $metadata): self
    {
        return new self(
            modeConfig: $this->modeConfig,
            request: $this->request,
            depth: $this->depth,
            parent: $this->parent,
            metadata: array_merge($this->metadata, $metadata),
        );
    }

    /**
     * Check if we've exceeded the maximum nesting depth.
     */
    public function hasExceededMaxDepth(): bool
    {
        return $this->depth >= self::MAX_DEPTH;
    }

    /**
     * Check if this is the root context (not nested).
     */
    public function isRoot(): bool
    {
        return $this->parent === null;
    }

    /**
     * Get metadata value by key.
     */
    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Get the full transformation chain (from root to current).
     *
     * @return list<string> List of resource class names in the chain
     */
    public function getTransformationChain(): array
    {
        $chain = [];
        $current = $this;

        while ($current !== null) {
            $resourceClass = $current->getMetadata('resource_class');
            if ($resourceClass !== null) {
                array_unshift($chain, (string) $resourceClass);
            }
            $current = $current->parent;
        }

        return $chain;
    }

    /**
     * Create a root context with default configuration.
     */
    public static function createDefault(Request $request): self
    {
        return new self(
            modeConfig: new ModeConfiguration(activeModes: ['default']),
            request: $request,
        );
    }
}
