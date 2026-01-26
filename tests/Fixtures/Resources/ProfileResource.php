<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Tests\Fixtures\Resources;

use Benyaminrmb\LaravelDynamicResources\DynamicResource;

/**
 * Test ProfileResource.
 */
class ProfileResource extends DynamicResource
{
    public function fields(): array
    {
        return [
            'minimal' => [
                'avatar_url',
            ],

            'default' => [
                'avatar_url',
                'bio',
            ],

            'detailed' => [
                'avatar_url',
                'bio',
                'website',
            ],
        ];
    }
}
