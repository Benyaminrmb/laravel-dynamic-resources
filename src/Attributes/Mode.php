<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Attributes;

use Attribute;

/**
 * Define a mode for a resource.
 *
 * Apply this attribute to a method that returns an array of fields for the mode.
 *
 * ## Usage
 *
 * ```php
 * class UserResource extends DynamicResource
 * {
 *     #[Mode('minimal', description: 'Basic user info')]
 *     protected function minimalMode(): array
 *     {
 *         return ['id', 'name'];
 *     }
 *
 *     #[Mode('detailed', extends: ['default'], description: 'Full user details')]
 *     protected function detailedMode(): array
 *     {
 *         return [
 *             'bio' => $this->bio,
 *             'created_at' => $this->created_at->toISOString(),
 *         ];
 *     }
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_METHOD)]
readonly class Mode
{
    /**
     * @param string $name The mode name (e.g., 'minimal', 'detailed')
     * @param string|null $description Human-readable description for documentation
     * @param list<string> $extends Modes to inherit/extend from
     * @param bool $default Whether this is the default mode
     * @param int $priority Order when combining modes (higher = later = override)
     */
    public function __construct(
        public string $name,
        public ?string $description = null,
        public array $extends = [],
        public bool $default = false,
        public int $priority = 0,
    ) {}
}
