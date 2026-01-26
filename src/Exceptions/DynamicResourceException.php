<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Exceptions;

/**
 * Base interface for all Dynamic Resource exceptions.
 */
interface DynamicResourceException
{
    /**
     * Get the exception message.
     */
    public function getMessage(): string;

    /**
     * Get a suggestion for fixing the issue, if available.
     */
    public function getSuggestion(): ?string;

    /**
     * Get the exception as an array for JSON responses.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
