<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int $owner_id
 * @property int $created_by
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
    use HasFactory;

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
        return $this->belongsToMany(User::class, 'workspace_members')
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
