<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 *
 *
 * @property int $id
 * @property int $workspace_workflow_instance_id
 * @property int $workspace_workflow_step_id
 * @property int|null $assigned_to
 * @property int|null $completed_by
 * @property string $status
 * @property string|null $data
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property array<array-key, mixed>|null $form_data
 * @property string|null $comments
 * @property-read \App\Models\User|null $assignee
 * @property-read \App\Models\User|null $completedByUser
 * @property-read \App\Models\WorkspaceWorkflowStep $step
 * @property-read \App\Models\WorkspaceWorkflowInstance $workflowInstance
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowStepInstance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowStepInstance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowStepInstance query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowStepInstance whereAssignedTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowStepInstance whereComments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowStepInstance whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowStepInstance whereCompletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowStepInstance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowStepInstance whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowStepInstance whereFormData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowStepInstance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowStepInstance whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowStepInstance whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowStepInstance whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowStepInstance whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowStepInstance whereWorkspaceWorkflowInstanceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowStepInstance whereWorkspaceWorkflowStepId($value)
 * @mixin \Eloquent
 */
class WorkspaceWorkflowStepInstance extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workspace_workflow_instance_id',
        'workspace_workflow_step_id',
        'assigned_to',
        'completed_by',
        'status',
        'form_data',
        'comments',
        'started_at',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'form_data' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the workflow instance that owns this step instance.
     */
    public function workflowInstance(): BelongsTo
    {
        return $this->belongsTo(WorkspaceWorkflowInstance::class, 'workspace_workflow_instance_id');
    }

    /**
     * Get the workflow step that this instance is based on.
     */
    public function step(): BelongsTo
    {
        return $this->belongsTo(WorkspaceWorkflowStep::class, 'workspace_workflow_step_id');
    }

    /**
     * Get the user assigned to this step instance.
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the user who completed this step instance.
     */
    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * Start this step instance.
     */
    public function start(): static
    {
        $this->status = 'in_progress';
        $this->started_at = now();
        $this->save();

        return $this;
    }

    /**
     * Complete this step instance.
     */
    public function complete(int $userId, ?string $comments = null): void
    {
        $this->status = 'completed';
        $this->completed_by = $userId;
        $this->completed_at = now();

        if ($comments) {
            $this->comments = $comments;
        }

        $this->save();

        // If all step instances are completed, mark the related workflow instance as completed as well.
        if ($this->workflowInstance->stepInstances()->where('status', '!=', 'completed')->count() === 0) {
            $this->workflowInstance->status = 'completed';
            $this->workflowInstance->completed_at = now();
            $this->workflowInstance->save();
        }

    }

    /**
     * Reject this step instance.
     */
    public function reject(int $userId, string $reason): bool
    {
        $this->status = 'rejected';
        $this->completed_by = $userId;
        $this->completed_at = now();
        $this->comments = $reason;
        return $this->save();
    }

    /**
     * Check if this step instance is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Check if this step instance is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if this step instance is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if this step instance is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
