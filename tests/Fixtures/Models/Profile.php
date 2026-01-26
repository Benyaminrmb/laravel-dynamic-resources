<?php

declare(strict_types=1);

namespace Benyaminrmb\LaravelDynamicResources\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Test Profile model.
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $avatar_url
 * @property string|null $bio
 * @property string|null $website
 */
class Profile extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
