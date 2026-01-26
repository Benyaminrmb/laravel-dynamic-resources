<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Concerns;

use BadMethodCallException;
use Benyaminrmb\LaravelDynamicResources\DTO\ModeConfiguration;

/**
 * Trait for managing resource modes.
 * Provides mode manipulation methods and magic method handling for dynamic mode calls.
 *
 * @method array fields() Returns the field definitions (must be implemented by using class)
 * @method string defaultMode() Returns the default mode name (must be implemented by using class)
 *
 * @property mixed $resource The underlying resource
 */
trait HasModes
{
    /**
     * The current mode configuration.
     */
    private ?ModeConfiguration $modeConfiguration = null;

    /**
     * Set the active modes, replacing any existing modes.
     *
     * @param list<string> $modes
     * @return $this
     */
    public function setActiveModes(array $modes): static
    {
        $this->modeConfiguration = $this->getModeConfiguration()->withModes($modes);

        return $this;
    }

    /**
     * Get the currently active modes.
     *
     * @return list<string>
     */
    public function getActiveModes(): array
    {
        return $this->getModeConfiguration()->activeModes;
    }

    /**
     * Add a mode to the currently active modes.
     *
     * @return $this
     */
    public function addMode(string $mode): static
    {
        $this->modeConfiguration = $this->getModeConfiguration()->addMode($mode);

        return $this;
    }

    /**
     * Remove a mode from the currently active modes.
     * Falls back to the default mode if no modes remain.
     *
     * @return $this
     */
    public function removeMode(string $mode): static
    {
        $this->modeConfiguration = $this->getModeConfiguration()->removeMode($mode);

        return $this;
    }

    /**
     * Check if the mode was explicitly set (vs inherited or default).
     */
    public function hasModeExplicitlySet(): bool
    {
        return $this->modeConfiguration?->explicitlySet ?? false;
    }

    /**
     * Check if a specific mode is currently active.
     */
    public function hasMode(string $mode): bool
    {
        return $this->getModeConfiguration()->hasMode($mode);
    }

    /**
     * Get or create the mode configuration.
     */
    public function getModeConfiguration(): ModeConfiguration
    {
        if ($this->modeConfiguration === null) {
            $this->modeConfiguration = new ModeConfiguration(
                activeModes: [$this->defaultMode()],
            );
        }

        return $this->modeConfiguration;
    }

    /**
     * Set the mode configuration directly.
     *
     * @return $this
     */
    public function setModeConfiguration(ModeConfiguration $config): static
    {
        $this->modeConfiguration = $config;

        return $this;
    }

    /**
     * Handle dynamic method calls for mode manipulation.
     *
     * Supports:
     * - `withXxx()` - adds mode 'xxx'
     * - `withoutXxx()` - removes mode 'xxx'
     * - `xxx()` - sets active modes to ['xxx'] if xxx is a defined mode
     *
     * @param list<mixed> $parameters
     * @throws BadMethodCallException
     */
    public function __call(string $method, array $parameters): mixed
    {
        // Handle with* methods (add mode)
        if (str_starts_with($method, 'with') && !str_starts_with($method, 'without')) {
            $mode = $this->normalizeModeName(substr($method, 4));

            return $this->addMode($mode);
        }

        // Handle without* methods (remove mode)
        if (str_starts_with($method, 'without')) {
            $mode = $this->normalizeModeName(substr($method, 7));

            return $this->removeMode($mode);
        }

        // Check if it's a mode name
        $mode = $this->normalizeModeName($method);
        $fields = $this->fields();

        if (isset($fields[$mode])) {
            return $this->setActiveModes([$mode]);
        }

        // Forward to resource if applicable
        if (isset($this->resource) && $this->resource !== null && method_exists($this->resource, $method)) {
            return $this->resource->{$method}(...$parameters);
        }

        throw new BadMethodCallException(sprintf(
            'Method %s::%s does not exist. Available modes: %s',
            static::class,
            $method,
            implode(', ', array_keys($fields)),
        ));
    }

    /**
     * Convert camelCase or PascalCase method name to kebab-case mode name.
     *
     * Examples:
     * - 'Minimal' -> 'minimal'
     * - 'WithAvatar' -> 'avatar' (after stripping 'with' prefix)
     * - 'UserProfile' -> 'user-profile'
     * - 'myCustomMode' -> 'my-custom-mode'
     */
    protected function normalizeModeName(string $name): string
    {
        // Convert to kebab-case
        $kebab = (string) preg_replace('/([a-z])([A-Z])/', '$1-$2', $name);

        return strtolower($kebab);
    }
}
