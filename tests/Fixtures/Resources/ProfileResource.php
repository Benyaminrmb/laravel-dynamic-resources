<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Tests\Fixtures\Resources;

use Benyaminrmb\LaravelDynamicResources\Attributes\View;
use Benyaminrmb\LaravelDynamicResources\DynamicResource;

class ProfileResource extends DynamicResource
{
    #[View]
    protected function minimalFields(): array
    {
        return ['avatar_url'];
    }

    #[View(default: true)]
    protected function standardFields(): array
    {
        return ['avatar_url', 'bio'];
    }

    #[View]
    protected function detailedFields(): array
    {
        return ['avatar_url', 'bio', 'website'];
    }
}
