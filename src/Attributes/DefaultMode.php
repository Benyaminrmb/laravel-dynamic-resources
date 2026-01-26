<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Attributes;

use Attribute;

/**
 * Set the default mode for a resource.
 *
 * ## Usage
 *
 * ```php
 * #[DefaultMode('minimal')]
 * class UserResource extends DynamicResource
 * {
 *     // 'minimal' will be used when no mode is specified
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class DefaultMode
{
    /**
     * @param string $mode The default mode name
     */
    public function __construct(
        public string $mode = 'default',
    ) {}
}
