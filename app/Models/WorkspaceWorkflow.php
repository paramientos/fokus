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
 * @property string $name
 * @property string|null $description
 * @property int $workspace_id
 * @property int $created_by
 * @property string $status
 * @property bool $is_active
 * @property array<array-key, mixed>|null $settings
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WorkspaceWorkflowInstance> $instances
 * @property-read int|null $instances_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WorkspaceWorkflowStep> $steps
 * @property-read int|null $steps_count
 * @property-read \App\Models\Workspace $workspace
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflow newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflow newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflow query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflow whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflow whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflow whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflow whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflow whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflow whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflow whereSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflow whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflow whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceWorkflow whereWorkspaceId($value)
 * @mixin \Eloquent
 */
class WorkspaceWorkflow extends Model
{
    use HasFactory,HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'workspace_id',
        'created_by',
        'status',
        'is_active',
        'settings',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * Get the workspace that owns the workflow.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the user that created the workflow.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the steps for the workflow.
     */
    public function steps(): HasMany
    {
        return $this->hasMany(WorkspaceWorkflowStep::class)->orderBy('order');
    }

    /**
     * Get the instances of this workflow.
     */
    public function instances(): HasMany
    {
        return $this->hasMany(WorkspaceWorkflowInstance::class);
    }
}
