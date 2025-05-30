<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $project_id
 * @property int|null $task_id
 * @property int|null $sprint_id
 * @property string $action
 * @property string|null $description
 * @property array<array-key, mixed>|null $changes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $color
 * @property-read string $icon
 * @property-read \App\Models\Project|null $project
 * @property-read \App\Models\Sprint|null $sprint
 * @property-read \App\Models\Task|null $task
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereChanges($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereSprintId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereUserId($value)
 * @mixin \Eloquent
 */
class Activity extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'project_id',
        'task_id',
        'sprint_id',
        'action',
        'description',
        'changes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'changes' => 'array',
    ];

    /**
     * Get the user that performed the activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the project associated with the activity.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the task associated with the activity.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the sprint associated with the activity.
     */
    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class);
    }

    /**
     * Get the icon for this activity type
     */
    public function getIconAttribute(): string
    {
        return match($this->action) {
            'created' => 'fas.plus',
            'updated' => 'fas.pen',
            'deleted' => 'fas.trash',
            'status_changed' => 'fas.arrows-rotate',
            'comment_added' => 'fas.comment',
            'assigned' => 'fas.user-plus',
            'unassigned' => 'fas.user-minus',
            default => 'fas.circle-info'
        };
    }

    /**
     * Get the color for the activity type.
     */
    public function getColorAttribute(): string
    {
        return match ($this->action) {
            'created' => 'success',
            'updated' => 'info',
            'deleted' => 'error',
            'status_changed' => 'warning',
            'comment_added' => 'info',
            'assigned' => 'primary',
            'sprint_added' => 'success',
            'sprint_removed' => 'warning',
            default => 'neutral',
        };
    }
}
