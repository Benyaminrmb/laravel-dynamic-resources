<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Contracts;

/**
 * Interface for resources that define mode-based fields.
 */
interface FieldDefinition
{
    /**
     * Define fields for each mode.
     *
     * Return format:
     * ```php
     * return [
     *     'minimal' => ['id', 'name'],
     *     'default' => fn() => ['id', 'name', 'email' => $this->email],
     *     'detailed' => fn() => [
     *         ...$this->mode('default'),
     *         'created_at' => $this->created_at->toISOString(),
     *     ],
     * ];
     * ```
     *
     * @return array<string, array<int|string, mixed>|\Closure>
     */
    public function fields(): array;

    /**
     * Get the default mode name used when no mode is explicitly specified.
     */
    public function defaultMode(): string;
}
