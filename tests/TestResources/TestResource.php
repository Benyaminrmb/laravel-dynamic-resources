<?php

namespace Benyaminrmb\LaravelDynamicResources\Tests\TestResources;

use Benyaminrmb\LaravelDynamicResources\ModularResource;

class TestResource extends ModularResource
{
    protected function fields(): array
    {
        return match($this->mode) {
            'minimal' => [
                'id',
                'name',
            ],
            'detailed' => [
                'id',
                'name',
                'email',
                'details',
                'computed_field' => $this->getComputedField(),
            ],
            default => [
                'id',
                'name',
                'email',
            ],
        };
    }

    private function getComputedField(): string
    {
        return "Computed: {$this->name}";
    }
}
