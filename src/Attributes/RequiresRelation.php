<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Attributes;

use Attribute;

/**
 * Indicate that a field requires a specific relationship to be loaded.
 *
 * The package can use this to automatically eager-load required relationships.
 *
 * ## Usage
 *
 * ```php
 * class UserResource extends DynamicResource
 * {
 *     #[Field(modes: ['detailed'])]
 *     #[RequiresRelation('profile')]
 *     public function avatar(): ?string
 *     {
 *         return $this->resource->profile?->avatar_url;
 *     }
 *
 *     #[RequiresRelation('posts')]
 *     #[RequiresRelation('posts.comments')]  // Can stack for nested relations
 *     public function postCommentCount(): int
 *     {
 *         return $this->resource->posts->sum(fn($p) => $p->comments->count());
 *     }
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
readonly class RequiresRelation
{
    /**
     * @param string $relation The relationship name (supports dot notation for nested)
     */
    public function __construct(
        public string $relation,
    ) {}
}
