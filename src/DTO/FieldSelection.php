<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\DTO;

final class FieldSelection
{
    /** @var list<string> */
    private array $fields = [];

    /** @var array<string, self> */
    private array $children = [];

    /** @return list<string> */
    public function fields(): array
    {
        return $this->fields;
    }

    public function child(string $field): ?self
    {
        return $this->children[$field] ?? null;
    }

    /** @param non-empty-list<string> $segments */
    public function add(array $segments, bool $includeParents): void
    {
        $field = array_shift($segments);

        if ($segments === [] || $includeParents) {
            $this->fields[] = $field;
            $this->fields = array_values(array_unique($this->fields));
        }

        if ($segments !== []) {
            $this->children[$field] ??= new self;
            $this->children[$field]->add($segments, $includeParents);
        }
    }
}
