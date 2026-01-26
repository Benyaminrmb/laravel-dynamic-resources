<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Tests\Fixtures\Resources;

use Benyaminrmb\LaravelDynamicResources\DynamicResource;

/**
 * Test UserResource with multiple modes.
 */
class UserResource extends DynamicResource
{
    public function fields(): array
    {
        return [
            'minimal' => [
                'id',
                'name',
            ],

            'default' => [
                'id',
                'name',
                'email',
            ],

            'detailed' => fn() => [
                ...$this->mode('default'),
                'bio' => $this->bio,
                'created_at' => $this->created_at?->toISOString(),
                'posts' => PostResource::collection($this->whenLoaded('posts')),
            ],

            'timestamps' => fn() => [
                'created_at' => $this->created_at?->toISOString(),
                'updated_at' => $this->updated_at?->toISOString(),
            ],

            'avatar' => fn() => [
                'avatar' => $this->avatar,
            ],

            'posts' => fn() => [
                'posts' => PostResource::collection($this->whenLoaded('posts')),
            ],

            'profile' => fn() => [
                'profile' => $this->whenLoaded('profile', fn() => ProfileResource::make($this->profile)),
            ],
        ];
    }
}
