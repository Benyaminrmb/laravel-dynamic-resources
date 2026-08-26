<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\DTO;

use ReflectionMethod;

final readonly class ViewDefinition
{
    public function __construct(
        public string $name,
        public ReflectionMethod $method,
        public bool $default,
        public bool $requestable,
    ) {}
}
