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
 * @property int $id
 * @property string|null $name
 * @property int $workspace_workflow_id
 * @property int $workspace_id
 * @property int $initiated_by
 * @property string $title
 * @property string|null $description
 * @property array<array-key, mixed>|null $custom_fields
 * @property string $status
 * @property string|null $data
 * @property int $current_step
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $initiator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WorkspaceWorkflowStepInstance> $stepInstances
 * @property-read int|null $step_instances_count
 * @property-read \App\Models\WorkspaceWorkflow $workflow
 * @property-read \App\Models\Workspace $workspace
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowInstance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowInstance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowInstance query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowInstance whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowInstance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowInstance whereCurrentStep($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowInstance whereCustomFields($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowInstance whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowInstance whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowInstance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowInstance whereInitiatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowInstance whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowInstance whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowInstance whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowInstance whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowInstance whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowInstance whereWorkspaceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflowInstance whereWorkspaceWorkflowId($value)
 * @mixin \Eloquent
 */
class WorkspaceWorkflowInstance extends Model
{
    use HasFactory,HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workspace_workflow_id',
        'workspace_id',
        'initiated_by',
        'name',
        'description',
        'status',
        'custom_fields',
        'started_at',
        'completed_at',
        'title',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'custom_fields' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the workflow that this instance belongs to.
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(WorkspaceWorkflow::class, 'workspace_workflow_id');
    }

    /**
     * Get the workspace that this instance belongs to.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the user who initiated this workflow instance.
     */
    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    /**
     * Get the step instances for this workflow instance.
     */
    public function stepInstances(): HasMany
    {
        return $this->hasMany(WorkspaceWorkflowStepInstance::class);
    }

    /**
     * Get the current in-progress step instance.
     */
    public function currentStepInstance()
    {
        return $this->stepInstances()
            ->whereIn('status', ['active','pending'])
            ->first();
    }

    /**
     * Check if the workflow instance is completed.
     */
    public function isCompleted(): bool
    {
        // Eğer son adım admin onayı gerektiriyorsa ve henüz onaylanmadıysa tamamlanmış sayma
        $requiresAdminApproval = $this->stepInstances()
            ->where('requires_admin_approval', true)
            ->where('status', '!=', 'completed')
            ->exists();
        if ($requiresAdminApproval) {
            return false;
        }
        // Tüm adımlar tamamlanmış, reddedilmiş ya da iptal edilmişse tamamlandı
        return $this->stepInstances()
            ->whereNotIn('status', ['completed', 'rejected', 'cancelled'])
            ->count() === 0;
    }

    /**
     * Advance to the next step in the workflow.
     */
    public function advanceToNextStep()
    {
        // Get the current in-progress step
        $currentStep = $this->currentStepInstance();

        if (!$currentStep) {
            return false;
        }
        // Find the next pending step in order

        $nextStep = $this->stepInstances()
            ->where('id', '>', $currentStep->id)
            ->where('status', 'pending')
            ->orderBy('id')
            ->first();

        if ($nextStep) {
            $nextStep->update([
                'status' => 'in_progress',
                'started_at' => now()
            ]);

            return true;
        }
        // Son step admin onayı gerektiriyorsa workflow'u hemen tamamlatma
        $finalStep = $this->stepInstances()->orderBy('id', 'desc')->first();

        if ($finalStep && $finalStep->step && ($finalStep->step->step_config['requires_admin_approval'] ?? false)) {
            $this->update([
                'status' => 'waiting_admin_approval',
                'completed_at' => null
            ]);

            return false;
        }

        return false;
    }

    /**
     * Admin tarafından workflow'un tamamlanmasını onayla.
     */
    public function approveByAdmin(int $adminId, ?string $comments = null)
    {
        // Sadece waiting_admin_approval durumunda çalışsın
        if ($this->status !== 'waiting_admin_approval') {
            return false;
        }
        // Son stepInstance'ı bul
        $finalStepInstance = $this->stepInstances()->orderBy('id', 'desc')->first();
        if ($finalStepInstance && $finalStepInstance->status !== 'completed') {
            $finalStepInstance->complete($adminId, $comments);
        }
        $this->update([
            'status' => 'completed',
            'completed_at' => now()
        ]);
        return true;
    }
}
