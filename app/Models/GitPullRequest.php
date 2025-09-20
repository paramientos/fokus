<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 
 *
 * @property string $id
 * @property string $repository_id
 * @property string|null $task_id
 * @property int $number
 * @property string $title
 * @property string|null $description
 * @property string $status
 * @property string $source_branch
 * @property string $target_branch
 * @property string|null $author_id
 * @property \Illuminate\Support\Carbon|null $merged_at
 * @property \Illuminate\Support\Carbon|null $closed_at
 * @property string $url
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $author
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\GitPullRequestComment> $comments
 * @property-read int|null $comments_count
 * @property-read \App\Models\GitRepository $repository
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\GitPullRequestReview> $reviews
 * @property-read int|null $reviews_count
 * @property-read \App\Models\Task|null $task
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitPullRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitPullRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitPullRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitPullRequest whereAuthorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitPullRequest whereClosedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitPullRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitPullRequest whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitPullRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitPullRequest whereMergedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitPullRequest whereNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitPullRequest whereRepositoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitPullRequest whereSourceBranch($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitPullRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitPullRequest whereTargetBranch($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitPullRequest whereTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitPullRequest whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitPullRequest whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitPullRequest whereUrl($value)
 * @mixin \Eloquent
 */
class GitPullRequest extends Model
{
    use HasFactory,HasUuids;

    protected $fillable = [
        'repository_id',
        'task_id',
        'number',
        'title',
        'description',
        'status', // open, closed, merged
        'source_branch',
        'target_branch',
        'author_id',
        'created_at',
        'updated_at',
        'merged_at',
        'closed_at',
        'url',
    ];

    protected $casts = [
        'merged_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    /**
     * Get the repository that owns the pull request.
     */
    public function repository(): BelongsTo
    {
        return $this->belongsTo(GitRepository::class, 'repository_id');
    }

    /**
     * Get the task associated with the pull request.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the author of the pull request.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Get the reviews for the pull request.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(GitPullRequestReview::class, 'pull_request_id');
    }

    /**
     * Get the comments for the pull request.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(GitPullRequestComment::class, 'pull_request_id');
    }

    /**
     * Check if the pull request is open.
     */
    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    /**
     * Check if the pull request is closed.
     */
    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /**
     * Check if the pull request is merged.
     */
    public function isMerged(): bool
    {
        return $this->status === 'merged';
    }
}
