<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Tests\Fixtures\Resources;

use Benyaminrmb\LaravelDynamicResources\Attributes\View;
use Benyaminrmb\LaravelDynamicResources\DynamicResource;

class PostResource extends DynamicResource
{
    public const string VIEW_MINIMAL = 'minimal';

    public const string VIEW_STANDARD = 'standard';

    public const string VIEW_DETAILED = 'detailed';

    public function minimal(): static
    {
        return $this->view(self::VIEW_MINIMAL);
    }

    public function detailed(): static
    {
        return $this->view(self::VIEW_DETAILED);
    }

    #[View]
    protected function minimalFields(): array
    {
        return ['id', 'title'];
    }

    #[View(default: true)]
    protected function standardFields(): array
    {
        return ['id', 'title', 'body'];
    }

    #[View]
    protected function detailedFields(): array
    {
        return $this->combine($this->standardFields(), [
            'created_at' => $this->created_at?->toISOString(),
            'author' => $this->relation('user', self::VIEW_MINIMAL, UserResource::class),
        ]);
    }
}
