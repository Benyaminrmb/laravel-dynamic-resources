<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Tests\Fixtures\Resources;

use Benyaminrmb\LaravelDynamicResources\DynamicResource;

/**
 * Test PostResource.
 */
class PostResource extends DynamicResource
{
    public function fields(): array
    {
        return [
            'minimal' => [
                'id',
                'title',
            ],

            'default' => [
                'id',
                'title',
                'body',
            ],

            'detailed' => fn() => [
                ...$this->mode('default'),
                'created_at' => $this->created_at?->toISOString(),
                'author' => $this->whenLoaded('user', fn() => UserResource::make($this->user)->minimal()),
            ],
        ];
    }
}
