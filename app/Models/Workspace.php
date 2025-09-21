<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * 
 *
 * @property string $id
 * @property string $name
 * @property string|null $description
 * @property string $owner_id
 * @property string $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Channel> $channels
 * @property-read int|null $channels_count
 * @property-read \App\Models\User $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WorkspaceInvitation> $invitations
 * @property-read int|null $invitations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $members
 * @property-read int|null $members_count
 * @property-read \App\Models\User $owner
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Project> $projects
 * @property-read int|null $projects_count
 * @property-read \App\Models\StorageUsage|null $storageUsage
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WorkspaceWorkflowInstance> $workflowInstances
 * @property-read int|null $workflow_instances_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WorkspaceWorkflow> $workflows
 * @property-read int|null $workflows_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Workspace newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Workspace newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Workspace query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Workspace whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Workspace whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Workspace whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Workspace whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Workspace whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Workspace whereOwnerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Workspace whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Workspace extends Model
{
    use HasFactory,HasUuids;

    protected $fillable = [
        'name',
        'description',
        'created_by',
        'owner_id'
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function channels()
    {
        return $this->hasMany(Channel::class);
    }

    public function members()
    {
        return $this->belongsToMany(User::class,WorkspaceMember::class)
            ->withTimestamps()
            ->withPivot(['role']);
    }

    /**
     * Get the projects for the workspace.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Get the invitations for the workspace.
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(WorkspaceInvitation::class);
    }

    /**
     * Get the storage usage for the workspace.
     */
    public function storageUsage(): HasOne
    {
        return $this->hasOne(StorageUsage::class);
    }

    /**
     * Get the workflows for the workspace.
     */
    public function workflows(): HasMany
    {
        return $this->hasMany(WorkspaceWorkflow::class);
    }

    /**
     * Get the workflow instances for the workspace.
     */
    public function workflowInstances(): HasMany
    {
        return $this->hasMany(WorkspaceWorkflowInstance::class);
    }

    /**
     * Get or create storage usage for the workspace.
     *
     * @return StorageUsage
     */
    public function getStorageUsage(): StorageUsage
    {
        if (!$this->storageUsage) {
            return $this->storageUsage()->create([
                'workspace_id' => $this->id,
                'used_bytes' => 0,
                'limit_bytes' => 1073741824, // Default 1GB
                'plan_name' => 'basic'
            ]);
        }

        return $this->storageUsage;
    }

    /**
     * Check if the workspace has enough storage space left
     *
     * @param int $bytes
     * @return bool
     */
    public function hasEnoughStorageSpace(int $bytes): bool
    {
        return $this->getStorageUsage()->hasEnoughSpace($bytes);
    }

    protected static function booted()
    {
        static::created(function (Workspace $workspace) {
            // Add creator/owner to workspace_members pivot as role 'owner' if not already.
            if (!$workspace->members()->where('user_id', $workspace->owner_id)->exists()) {
                $workspace->members()->attach($workspace->owner_id, [
                    'id'=> Str::uuid7()->toString(),
                    'role' => 'owner',
                ]);
            }

            // Create storage usage record for the workspace
            $workspace->storageUsage()->create([
                'workspace_id' => $workspace->id,
                'used_bytes' => 0,
                'limit_bytes' => 1073741824, // Default 1GB
                'plan_name' => 'basic'
            ]);
        });
    }
}
