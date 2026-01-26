<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Contracts;

use Benyaminrmb\LaravelDynamicResources\DTO\ModeConfiguration;

/**
 * Interface for resolving and validating resource modes.
 */
interface ModeResolver
{
    /**
     * Resolve the requested modes into a valid ModeConfiguration.
     * Invalid modes may be filtered out or cause exceptions depending on strict mode.
     *
     * @param list<string> $requestedModes
     */
    public function resolve(array $requestedModes): ModeConfiguration;

    /**
     * Check if a mode exists in the field definition.
     */
    public function hasMode(string $mode): bool;

    /**
     * Get all available mode names.
     *
     * @return list<string>
     */
    public function availableModes(): array;

    /**
     * Get the default mode name.
     */
    public function getDefaultMode(): string;
}
