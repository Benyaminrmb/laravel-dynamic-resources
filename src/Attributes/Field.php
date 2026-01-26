<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Attributes;

use Attribute;

/**
 * Mark a property or method as a resource field.
 *
 * ## Usage on Properties
 *
 * ```php
 * class UserResource extends DynamicResource
 * {
 *     #[Field(modes: ['minimal', 'default', 'detailed'])]
 *     public int $id;
 *
 *     #[Field(modes: ['detailed'], as: 'profile_picture')]
 *     public ?string $avatar;
 * }
 * ```
 *
 * ## Usage on Methods
 *
 * ```php
 * class UserResource extends DynamicResource
 * {
 *     #[Field(modes: ['detailed'], computed: true)]
 *     public function fullName(): string
 *     {
 *         return "{$this->resource->first_name} {$this->resource->last_name}";
 *     }
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
readonly class Field
{
    /**
     * @param list<string>|string $modes Modes where this field should be included
     * @param string|null $as Rename field in output (null = use property/method name)
     * @param bool $computed Mark as computed field (for documentation)
     * @param bool $lazy Lazy-load this field (for relationships)
     * @param string|null $transformer Custom transformer class name
     */
    public function __construct(
        public array|string $modes = ['default'],
        public ?string $as = null,
        public bool $computed = false,
        public bool $lazy = false,
        public ?string $transformer = null,
    ) {}

    /**
     * Get modes as an array.
     *
     * @return list<string>
     */
    public function getModes(): array
    {
        return is_array($this->modes) ? $this->modes : [$this->modes];
    }
}
