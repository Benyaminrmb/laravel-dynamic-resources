<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Attributes;

use Attribute;

/**
 * Define schema metadata for a resource.
 *
 * ## Usage
 *
 * ```php
 * #[ResourceSchema(
 *     model: User::class,
 *     defaultMode: 'default',
 *     strict: true,  // Throw exception for undefined modes
 * )]
 * class UserResource extends DynamicResource
 * {
 *     // ...
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class ResourceSchema
{
    /**
     * @param class-string $model The Eloquent model class this resource represents
     * @param string|null $defaultMode Override the default mode (null = use 'default')
     * @param bool $strict Throw exception when using undefined modes
     * @param list<string> $globalExclude Fields to always exclude from output
     * @param string|null $description Human-readable description for documentation
     */
    public function __construct(
        public string $model,
        public ?string $defaultMode = null,
        public bool $strict = false,
        public array $globalExclude = [],
        public ?string $description = null,
    ) {}
}
