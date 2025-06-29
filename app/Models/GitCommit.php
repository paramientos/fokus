<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 
 *
 * @property int $id
 * @property int $repository_id
 * @property int|null $branch_id
 * @property int|null $task_id
 * @property string $hash
 * @property string $message
 * @property string $author_name
 * @property string $author_email
 * @property \Illuminate\Support\Carbon $committed_date
 * @property array<array-key, mixed>|null $files_changed
 * @property int $additions
 * @property int $deletions
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\GitBranch|null $branch
 * @property-read string $body
 * @property-read string $short_hash
 * @property-read string $title
 * @property-read \App\Models\GitRepository $repository
 * @property-read \App\Models\Task|null $task
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitCommit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitCommit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitCommit query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitCommit whereAdditions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitCommit whereAuthorEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitCommit whereAuthorName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitCommit whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitCommit whereCommittedDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitCommit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitCommit whereDeletions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitCommit whereFilesChanged($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitCommit whereHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitCommit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitCommit whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitCommit whereRepositoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitCommit whereTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GitCommit whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class GitCommit extends Model
{
    use HasFactory;

    protected $fillable = [
        'repository_id',
        'branch_id',
        'task_id',
        'hash',
        'message',
        'author_name',
        'author_email',
        'committed_date',
        'files_changed',
        'additions',
        'deletions',
    ];

    protected $casts = [
        'committed_date' => 'datetime',
        'files_changed' => 'json',
    ];

    /**
     * Get the repository that owns the commit.
     */
    public function repository(): BelongsTo
    {
        return $this->belongsTo(GitRepository::class, 'repository_id');
    }

    /**
     * Get the branch that owns the commit.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(GitBranch::class, 'branch_id');
    }

    /**
     * Get the task associated with the commit.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the short hash for the commit.
     */
    public function getShortHashAttribute(): string
    {
        return substr($this->hash, 0, 7);
    }

    /**
     * Get the commit title (first line of message).
     */
    public function getTitleAttribute(): string
    {
        $lines = explode("\n", $this->message);
        return $lines[0];
    }

    /**
     * Get the commit body (all lines except the first).
     */
    public function getBodyAttribute(): string
    {
        $lines = explode("\n", $this->message);
        array_shift($lines);
        return implode("\n", $lines);
    }
}
