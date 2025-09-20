<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property string $id
 * @property string $pull_request_id
 * @property string|null $user_id
 * @property string $state
 * @property string|null $body
 * @property string $submitted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitPullRequestReview newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitPullRequestReview newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitPullRequestReview query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitPullRequestReview whereBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitPullRequestReview whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitPullRequestReview whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitPullRequestReview wherePullRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitPullRequestReview whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitPullRequestReview whereSubmittedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitPullRequestReview whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitPullRequestReview whereUserId($value)
 * @mixin \Eloquent
 */
class GitPullRequestReview extends Model
{
    use HasUuids;

    protected $guarded=[];
}
