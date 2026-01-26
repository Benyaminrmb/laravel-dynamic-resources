<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Attributes;

use Attribute;

/**
 * Hide a field from specific modes or all modes.
 *
 * ## Usage
 *
 * ```php
 * class UserResource extends DynamicResource
 * {
 *     #[Hidden]  // Hidden from all modes
 *     public string $password;
 *
 *     #[Hidden(modes: ['public', 'minimal'])]  // Hidden only from these modes
 *     public string $email;
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
readonly class Hidden
{
    /**
     * @param list<string> $modes Modes to hide from (empty = all modes)
     */
    public function __construct(
        public array $modes = [],
    ) {}

    /**
     * Check if this field should be hidden for the given mode.
     */
    public function isHiddenFor(string $mode): bool
    {
        // Empty means hidden from all modes
        if ($this->modes === []) {
            return true;
        }

        return in_array($mode, $this->modes, true);
    }
}
