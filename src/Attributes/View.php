<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class View
{
    public function __construct(
        public ?string $name = null,
        public bool $default = false,
        public bool $requestable = false,
    ) {}
}
