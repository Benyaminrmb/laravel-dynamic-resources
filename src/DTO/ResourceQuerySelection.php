<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\DTO;

readonly class ResourceQuerySelection
{
    public function __construct(
        public ?string $mode,
        public ?FieldSelection $only,
        public ?FieldSelection $except,
    ) {}
}
