<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $pull_request_id
 * @property string|null $user_id
 * @property string $body
 * @property string|null $path
 * @property int|null $position
 * @property string $commented_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitPullRequestComment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitPullRequestComment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitPullRequestComment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitPullRequestComment whereBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitPullRequestComment whereCommentedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitPullRequestComment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitPullRequestComment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitPullRequestComment wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitPullRequestComment wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitPullRequestComment wherePullRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitPullRequestComment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitPullRequestComment whereUserId($value)
 *
 * @mixin \Eloquent
 */
class GitPullRequestComment extends Model
{
    use HasUuids;

    protected $guarded = [];
}
