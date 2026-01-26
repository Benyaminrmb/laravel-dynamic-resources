<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Exceptions;

use RuntimeException;

/**
 * Thrown when an invalid field is requested.
 */
class InvalidFieldException extends RuntimeException implements DynamicResourceException
{
    /**
     * @param string $field The invalid field name
     * @param class-string $resourceClass The resource class
     * @param string $mode The mode being used
     * @param list<string> $availableFields Available fields in this mode
     */
    public function __construct(
        public readonly string $field,
        public readonly string $resourceClass,
        public readonly string $mode,
        public readonly array $availableFields = [],
    ) {
        parent::__construct(sprintf(
            "Field '%s' is not available in mode '%s' of %s.",
            $this->field,
            $this->mode,
            $this->resourceClass,
        ));
    }

    public function getSuggestion(): ?string
    {
        if ($this->availableFields === []) {
            return null;
        }

        $closest = null;
        $minDistance = PHP_INT_MAX;

        foreach ($this->availableFields as $available) {
            $distance = levenshtein($this->field, $available);
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
            'type' => 'invalid_field',
            'field' => $this->field,
            'mode' => $this->mode,
            'resource' => $this->resourceClass,
            'available_fields' => $this->availableFields,
            'suggestion' => $this->getSuggestion(),
        ];
    }
}
