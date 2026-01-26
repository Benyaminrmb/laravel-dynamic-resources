<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Contracts;

use Benyaminrmb\LaravelDynamicResources\DTO\TransformationContext;

/**
 * Interface for transforming resources into arrays.
 */
interface ResourceTransformer
{
    /**
     * Transform the resource into an array representation.
     *
     * @return array<string, mixed>
     */
    public function transform(mixed $resource, TransformationContext $context): array;
}
