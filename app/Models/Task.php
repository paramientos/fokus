<?php

namespace App\Models;

use App\Enums\Priority;
use App\Enums\TaskType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * 
 *
 * @property string $id
 * @property string $title
 * @property string|null $description
 * @property string $project_id
 * @property int $status_id
 * @property string|null $sprint_id
 * @property string|null $user_id
 * @property string $reporter_id
 * @property TaskType $task_type
 * @property Priority $priority
 * @property int|null $story_points
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $time_spent Time spent in minutes
 * @property int|null $time_estimate Time estimate in minutes
 * @property \Illuminate\Support\Carbon|null $started_at When the task was started
 * @property \Illuminate\Support\Carbon|null $completed_at When the task was completed
 * @property string|null $workflow_id
 * @property int $order
 * @property string|null $parent_id
 * @property bool $is_subtask
 * @property int|null $task_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\User|null $assignee
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Attachment> $attachments
 * @property-read int|null $attachments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Comment> $comments
 * @property-read int|null $comments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Conversation> $conversations
 * @property-read int|null $conversations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Task> $dependencies
 * @property-read int|null $dependencies_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Task> $dependents
 * @property-read int|null $dependents_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Activity> $latestActivities
 * @property-read int|null $latest_activities_count
 * @property-read Task|null $parent
 * @property-read \App\Models\Project $project
 * @property-read \App\Models\User $reporter
 * @property-read \App\Models\Sprint|null $sprint
 * @property-read \App\Models\Status $status
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Task> $subtasks
 * @property-read int|null $subtasks_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Tag> $tags
 * @property-read int|null $tags_count
 * @property-read \App\Models\User|null $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WikiPage> $wikiPages
 * @property-read int|null $wiki_pages_count
 * @property-read \App\Models\Workflow|null $workflow
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereIsSubtask($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereReporterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereSprintId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereStoryPoints($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereTaskType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereTimeEstimate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereTimeSpent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereWorkflowId($value)
 * @mixin \Eloquent
 */
class Task extends Model
{
    use HasFactory,HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'project_id',
        'user_id',
        'reporter_id',
        'status_id',
        'priority',
        'due_date',
        'task_type',
        'sprint_id',
        'workflow_id',
        'time_spent',
        'time_estimate',
        'started_at',
        'completed_at',
        'order',
        'parent_id',
        'is_subtask',
        'task_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'due_date' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'task_type' => TaskType::class,
        'priority' => Priority::class,
        'story_points' => 'integer',
        'is_subtask' => 'boolean',
    ];

    /**
     * Get the project that owns the task.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the status of the task.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    /**
     * Get the sprint that owns the task.
     */
    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class);
    }

    /**
     * Get the user assigned to the task.
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Alias for assignee() for backward compatibility.
     */
    public function user(): BelongsTo
    {
        return $this->assignee();
    }

    /**
     * Get the user who reported the task.
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /**
     * Get the comments for the task.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Get the workflow that owns the task.
     */
    public function workflow(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Workflow::class);
    }

    /**
     * Görev ile ilişkili aktiviteler
     */
    public function activities()
    {
        return $this->hasMany(\App\Models\Activity::class, 'task_id');
    }

    public function latestActivities()
    {
        return $this->hasMany(\App\Models\Activity::class, 'task_id')->latest();
    }

    /**
     * Get all tags for the task.
     */
    public function tags(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    /**
     * Get all attachments for the task.
     */
    public function attachments(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Get all tasks that this task depends on.
     */
    public function dependencies(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'task_id', 'dependency_id')
            ->withPivot('type')
            ->withTimestamps();
    }

    /**
     * Get all tasks that depend on this task.
     */
    public function dependents(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'dependency_id', 'task_id')
            ->withPivot('type')
            ->withTimestamps();
    }

    /**
     * Bu task'tan oluşturulan wiki sayfaları
     */
    public function wikiPages()
    {
        return $this->morphToMany(WikiPage::class, 'source', 'wiki_source_references')
            ->withTimestamps();
    }

    /**
     * Görev ile ilişkili konuşmalar
     */
    public function conversations()
    {
        return $this->morphMany(Conversation::class, 'context');
    }

    /**
     * Get the parent task.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    /**
     * Get the subtasks for this task.
     */
    public function subtasks(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_id')->orderBy('order');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
