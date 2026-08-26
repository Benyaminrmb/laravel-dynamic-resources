<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Tests\Fixtures\Resources;

use Benyaminrmb\LaravelDynamicResources\Attributes\View;
use Benyaminrmb\LaravelDynamicResources\DynamicResource;

class UserResource extends DynamicResource
{
    public const string VIEW_MINIMAL = 'minimal';

    public const string VIEW_STANDARD = 'standard';

    public const string VIEW_DETAILED = 'detailed';

    public const string VIEW_TIMESTAMPS = 'timestamps';

    public const string VIEW_AVATAR = 'avatar';

    public const string VIEW_POSTS = 'posts';

    public const string VIEW_PROFILE = 'profile';

    public const string VIEW_MINIMAL_WITH_AVATAR = 'minimal-with-avatar';

    public function minimal(): static
    {
        return $this->view(self::VIEW_MINIMAL);
    }

    public function detailed(): static
    {
        return $this->view(self::VIEW_DETAILED);
    }

    public function withAvatar(): static
    {
        return $this->withView(self::VIEW_AVATAR);
    }

    public function withoutAvatar(): static
    {
        return $this->withoutView(self::VIEW_AVATAR);
    }

    public function withTimestamps(): static
    {
        return $this->withView(self::VIEW_TIMESTAMPS);
    }

    public function withoutTimestamps(): static
    {
        return $this->withoutView(self::VIEW_TIMESTAMPS);
    }

    #[View(requestable: true)]
    protected function minimalFields(): array
    {
        return ['id', 'name'];
    }

    #[View(default: true, requestable: true)]
    protected function standardFields(): array
    {
        return ['id', 'name', 'email'];
    }

    #[View(requestable: true)]
    protected function detailedFields(): array
    {
        return $this->combine($this->standardFields(), [
            'bio' => $this->bio,
            'created_at' => $this->created_at?->toISOString(),
            'posts' => $this->relation('posts', resourceClass: PostResource::class),
        ]);
    }

    #[View]
    protected function timestampsFields(): array
    {
        return ['created_at' => $this->created_at?->toISOString(), 'updated_at' => $this->updated_at?->toISOString()];
    }

    #[View]
    protected function avatarFields(): array
    {
        return ['avatar' => $this->avatar];
    }

    #[View]
    protected function postsFields(): array
    {
        return ['posts' => $this->relation('posts', resourceClass: PostResource::class)];
    }

    #[View]
    protected function profileFields(): array
    {
        return ['profile' => $this->relation('profile', resourceClass: ProfileResource::class)];
    }

    #[View]
    protected function minimalWithAvatarFields(): array
    {
        return $this->combine($this->minimalFields(), $this->avatarFields());
    }
}
