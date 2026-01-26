<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Exceptions;

use RuntimeException;

/**
 * Thrown when attempting to use a mode via request that is forbidden.
 */
class ForbiddenModeException extends RuntimeException implements DynamicResourceException
{
    /**
     * @param string $mode The forbidden mode
     * @param list<string> $forbiddenModes List of forbidden modes
     */
    public function __construct(
        public readonly string $mode,
        public readonly array $forbiddenModes = [],
    ) {
        parent::__construct(sprintf(
            "Mode '%s' cannot be selected via request parameters.",
            $this->mode,
        ));
    }

    public function getSuggestion(): ?string
    {
        return 'This mode is restricted to server-side usage only.';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => 'forbidden_mode',
            'mode' => $this->mode,
            'message' => $this->getMessage(),
        ];
    }
}
