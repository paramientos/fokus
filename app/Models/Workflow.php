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
 * @property string $name
 * @property string|null $description
 * @property int $project_id
 * @property bool $is_active
 * @property string|null $trigger_type
 * @property array<array-key, mixed>|null $trigger_condition
 * @property string|null $trigger_value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WorkflowAction> $actions
 * @property-read int|null $actions_count
 * @property-read \App\Models\Project $project
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Workflow newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Workflow newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Workflow query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Workflow whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Workflow whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Workflow whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Workflow whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Workflow whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Workflow whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Workflow whereTriggerCondition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Workflow whereTriggerType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Workflow whereTriggerValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Workflow whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Workflow extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'project_id',
        'is_active',
        'trigger_type',
        'trigger_condition',
        'trigger_value',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'trigger_condition' => 'array',
    ];

    /**
     * Get the project that owns the workflow.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the actions for the workflow.
     */
    public function actions(): HasMany
    {
        return $this->hasMany(WorkflowAction::class);
    }

    /**
     * Check if the workflow should be triggered for a given task.
     *
     * @param Task $task
     * @return bool
     */
    public function shouldTrigger(Task $task): bool
    {
        if (!$this->is_active) {
            return false;
        }

        return match ($this->trigger_type) {
            'status_change' => $this->checkStatusChangeTrigger($task),
            'priority_change' => $this->checkPriorityChangeTrigger($task),
            'assignment' => $this->checkAssignmentTrigger($task),
            'due_date_approaching' => $this->checkDueDateApproachingTrigger($task),
            'sprint_added' => $this->checkSprintAddedTrigger($task),
            default => false,
        };
    }

    /**
     * Check if the status change trigger condition is met.
     *
     * @param Task $task
     * @return bool
     */
    private function checkStatusChangeTrigger(Task $task): bool
    {
        if (!$task->isDirty('status_id')) {
            return false;
        }

        $newStatusId = $task->status_id;
        $targetStatusId = $this->trigger_value;

        return $newStatusId == $targetStatusId;
    }

    /**
     * Check if the priority change trigger condition is met.
     *
     * @param Task $task
     * @return bool
     */
    private function checkPriorityChangeTrigger(Task $task): bool
    {
        if (!$task->isDirty('priority')) {
            return false;
        }

        $newPriority = $task->priority;
        $targetPriority = $this->trigger_value;

        return $newPriority == $targetPriority;
    }

    /**
     * Check if the assignment trigger condition is met.
     *
     * @param Task $task
     * @return bool
     */
    private function checkAssignmentTrigger(Task $task): bool
    {
        if (!$task->isDirty('user_id')) {
            return false;
        }

        // Trigger when assigned to any user
        if ($this->trigger_value === 'any') {
            return $task->user_id !== null;
        }

        // Trigger when assigned to a specific user
        return $task->user_id == $this->trigger_value;
    }

    /**
     * Check if the due date approaching trigger condition is met.
     *
     * @param Task $task
     * @return bool
     */
    private function checkDueDateApproachingTrigger(Task $task): bool
    {
        if (!$task->due_date) {
            return false;
        }

        $daysUntilDue = now()->diffInDays($task->due_date, false);
        $targetDays = (int) $this->trigger_value;

        return $daysUntilDue <= $targetDays && $daysUntilDue >= 0;
    }

    /**
     * Check if the sprint added trigger condition is met.
     *
     * @param Task $task
     * @return bool
     */
    private function checkSprintAddedTrigger(Task $task): bool
    {
        if (!$task->isDirty('sprint_id')) {
            return false;
        }

        // Trigger when added to any sprint
        if ($this->trigger_value === 'any') {
            return $task->sprint_id !== null;
        }

        // Trigger when added to a specific sprint
        return $task->sprint_id == $this->trigger_value;
    }

    /**
     * Execute all actions for this workflow.
     *
     * @param Task $task
     * @return void
     */
    public function executeActions(Task $task): void
    {
        foreach ($this->actions as $action) {
            $action->execute($task);
        }

        // Log workflow execution
        Activity::create([
            'user_id' => auth()->id() ?? 1,
            'project_id' => $this->project_id,
            'task_id' => $task->id,
            'action' => 'workflow_executed',
            'description' => "Workflow '{$this->name}' executed",
        ]);
    }
}
