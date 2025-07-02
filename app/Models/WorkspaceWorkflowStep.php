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
 * @property int $workspace_workflow_id
 * @property int $order
 * @property string $step_type
 * @property array<array-key, mixed>|null $step_config
 * @property int|null $assigned_to
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $assignee
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WorkspaceWorkflowStepInstance> $instances
 * @property-read int|null $instances_count
 * @property-read \App\Models\WorkspaceWorkflow $workflow
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowStep newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowStep newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowStep query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowStep whereAssignedTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowStep whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowStep whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowStep whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowStep whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowStep whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowStep whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowStep whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowStep whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowStep whereStepConfig($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowStep whereStepType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowStep whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowStep whereWorkspaceWorkflowId($value)
 * @mixin \Eloquent
 */
class WorkspaceWorkflowStep extends Model
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
        'workspace_workflow_id',
        'order',
        'step_type',
        'step_config',
        'assigned_to',
        'status',
        'started_at',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'step_config' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the workflow that owns the step.
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(WorkspaceWorkflow::class, 'workspace_workflow_id');
    }

    /**
     * Get the user assigned to this step.
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the instances of this step.
     */
    public function instances(): HasMany
    {
        return $this->hasMany(WorkspaceWorkflowStepInstance::class);
    }
}
