<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Exceptions;

use RuntimeException;

/**
 * Thrown when attempting to use an undefined mode on a resource.
 */
class UndefinedModeException extends RuntimeException implements DynamicResourceException
{
    /**
     * @param string $mode The undefined mode name
     * @param class-string $resourceClass The resource class
     * @param list<string> $availableModes List of available modes
     */
    public function __construct(
        public readonly string $mode,
        public readonly string $resourceClass,
        public readonly array $availableModes = [],
    ) {
        $available = $this->availableModes !== []
            ? implode(', ', $this->availableModes)
            : 'none defined';

        $suggestion = $this->getSuggestion();
        $suggestionText = $suggestion !== null ? " {$suggestion}" : '';

        parent::__construct(sprintf(
            "Mode '%s' is not defined in %s. Available modes: %s.%s",
            $this->mode,
            $this->resourceClass,
            $available,
            $suggestionText,
        ));
    }

    /**
     * Get a suggestion for similar mode names.
     */
    public function getSuggestion(): ?string
    {
        if ($this->availableModes === []) {
            return null;
        }

        $closest = null;
        $minDistance = PHP_INT_MAX;

        foreach ($this->availableModes as $available) {
            $distance = levenshtein($this->mode, $available);
            // Only suggest if reasonably close (distance <= 3)
            if ($distance < $minDistance && $distance <= 3) {
                $minDistance = $distance;
                $closest = $available;
            }
        }

        return $closest !== null ? "Did you mean '{$closest}'?" : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => 'undefined_mode',
            'mode' => $this->mode,
            'resource' => $this->resourceClass,
            'available_modes' => $this->availableModes,
            'suggestion' => $this->getSuggestion(),
        ];
    }
}
