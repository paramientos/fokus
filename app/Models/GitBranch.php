<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 
 *
 * @property int $id
 * @property int $repository_id
 * @property int|null $task_id
 * @property string $name
 * @property string $status
 * @property int|null $created_by
 * @property string|null $last_commit_hash
 * @property \Illuminate\Support\Carbon|null $last_commit_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\GitCommit> $commits
 * @property-read int|null $commits_count
 * @property-read \App\Models\User|null $creator
 * @property-read \App\Models\GitRepository $repository
 * @property-read \App\Models\Task|null $task
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitBranch newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitBranch newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitBranch query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitBranch whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitBranch whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitBranch whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitBranch whereLastCommitDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitBranch whereLastCommitHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitBranch whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitBranch whereRepositoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitBranch whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitBranch whereTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitBranch whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class GitBranch extends Model
{
    use HasFactory;

    protected $fillable = [
        'repository_id',
        'task_id',
        'name',
        'status', // active, merged, deleted
        'created_by',
        'last_commit_hash',
        'last_commit_date',
    ];

    protected $casts = [
        'last_commit_date' => 'datetime',
    ];

    /**
     * Get the repository that owns the branch.
     */
    public function repository(): BelongsTo
    {
        return $this->belongsTo(GitRepository::class, 'repository_id');
    }

    /**
     * Get the task associated with the branch.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the commits for the branch.
     */
    public function commits(): HasMany
    {
        return $this->hasMany(GitCommit::class, 'branch_id');
    }

    /**
     * Get the user who created the branch.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if the branch is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the branch is merged.
     */
    public function isMerged(): bool
    {
        return $this->status === 'merged';
    }

    /**
     * Check if the branch is deleted.
     */
    public function isDeleted(): bool
    {
        return $this->status === 'deleted';
    }
}
