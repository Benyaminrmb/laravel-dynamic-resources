<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\DTO;

/**
 * Immutable value object representing the current mode configuration.
 *
 * @template-covariant TMode of string
 */
readonly class ModeConfiguration
{
    /**
     * @param list<string> $activeModes The currently active modes
     * @param list<string> $requestedFields Sparse fieldset from request (JSON:API style)
     * @param list<string> $excludedFields Fields to exclude from output
     * @param bool $explicitlySet Whether modes were explicitly set vs inherited
     */
    public function __construct(
        public array $activeModes,
        public array $requestedFields = [],
        public array $excludedFields = [],
        public bool $explicitlySet = false,
    ) {}

    /**
     * Create a new instance with the specified modes.
     *
     * @param list<string> $modes
     */
    public function withModes(array $modes): self
    {
        return new self(
            activeModes: array_values(array_unique($modes)),
            requestedFields: $this->requestedFields,
            excludedFields: $this->excludedFields,
            explicitlySet: true,
        );
    }

    /**
     * Create a new instance with an additional mode.
     */
    public function addMode(string $mode): self
    {
        if (in_array($mode, $this->activeModes, true)) {
            return $this;
        }

        return new self(
            activeModes: [...$this->activeModes, $mode],
            requestedFields: $this->requestedFields,
            excludedFields: $this->excludedFields,
            explicitlySet: true,
        );
    }

    /**
     * Create a new instance without the specified mode.
     * Falls back to ['default'] if removing the mode would leave no modes active.
     */
    public function removeMode(string $mode): self
    {
        $remaining = array_values(array_filter(
            $this->activeModes,
            static fn(string $m): bool => $m !== $mode
        ));

        return new self(
            activeModes: $remaining === [] ? ['default'] : $remaining,
            requestedFields: $this->requestedFields,
            excludedFields: $this->excludedFields,
            explicitlySet: $this->explicitlySet,
        );
    }

    /**
     * Create a new instance with the specified requested fields.
     *
     * @param list<string> $fields
     */
    public function withRequestedFields(array $fields): self
    {
        return new self(
            activeModes: $this->activeModes,
            requestedFields: $fields,
            excludedFields: $this->excludedFields,
            explicitlySet: $this->explicitlySet,
        );
    }

    /**
     * Create a new instance with the specified excluded fields.
     *
     * @param list<string> $fields
     */
    public function withExcludedFields(array $fields): self
    {
        return new self(
            activeModes: $this->activeModes,
            requestedFields: $this->requestedFields,
            excludedFields: $fields,
            explicitlySet: $this->explicitlySet,
        );
    }

    /**
     * Check if a specific mode is active.
     */
    public function hasMode(string $mode): bool
    {
        return in_array($mode, $this->activeModes, true);
    }

    /**
     * Check if there are any field restrictions (only or except).
     */
    public function hasFieldRestrictions(): bool
    {
        return $this->requestedFields !== [] || $this->excludedFields !== [];
    }
}
