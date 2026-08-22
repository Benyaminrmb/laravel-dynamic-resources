<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Concerns;

use Illuminate\Http\Resources\MissingValue;

/**
 * Trait for additional conditional field inclusion in resources.
 *
 * Note: Laravel's JsonResource already provides: when(), unless(), mergeWhen(),
 * mergeUnless(), whenLoaded(), whenCounted(), whenPivotLoaded(), whenHas(), etc.
 * This trait adds additional helpers not provided by Laravel.
 *
 * @property mixed $resource The underlying resource
 */
trait HasConditionalFields
{
    /**
     * Include a value when the resource has a non-empty attribute.
     * Different from whenHas() - this checks for truthy values (not just existence).
     *
     * @template TValue
     * @template TDefault
     *
     * @param  TValue|callable(): TValue|null  $value
     * @param  TDefault|callable(): TDefault  $default
     * @return TValue|TDefault|MissingValue|mixed
     */
    protected function whenAttributeFilled(string $attribute, mixed $value = null, mixed $default = null): mixed
    {
        if (! isset($this->resource)) {
            return new MissingValue;
        }

        $resource = $this->resource;

        if (! is_object($resource)) {
            return new MissingValue;
        }

        $attributeValue = method_exists($resource, 'getAttribute')
            ? $resource->getAttribute($attribute)
            : ($resource->{$attribute} ?? null);

        $isFilled = $attributeValue !== null && $attributeValue !== '' && $attributeValue !== [];

        if (! $isFilled) {
            return func_num_args() >= 3 ? value($default) : new MissingValue;
        }

        if ($value === null && func_num_args() === 1) {
            return $attributeValue;
        }

        return value($value);
    }
}
