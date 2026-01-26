<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Exceptions;

use RuntimeException;

/**
 * Thrown when a circular resource reference is detected.
 */
class CircularReferenceException extends RuntimeException implements DynamicResourceException
{
    /**
     * @param list<string> $chain The reference chain showing the circular path
     */
    public function __construct(
        public readonly array $chain,
    ) {
        $chainStr = implode(' -> ', $this->chain);

        parent::__construct(sprintf(
            'Circular resource reference detected: %s',
            $chainStr,
        ));
    }

    public function getSuggestion(): ?string
    {
        return 'Check for resources that reference each other. ' .
               'Consider using whenLoaded() to prevent infinite recursion.';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => 'circular_reference',
            'chain' => $this->chain,
            'suggestion' => $this->getSuggestion(),
        ];
    }
}
