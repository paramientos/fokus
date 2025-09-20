<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 
 *
 * @property string $id
 * @property string $workspace_id
 * @property string $email
 * @property string $token
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon|null $accepted_at
 * @property string $invited_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $invitedBy
 * @property-read \App\Models\Workspace $workspace
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceInvitation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceInvitation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceInvitation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceInvitation whereAcceptedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceInvitation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceInvitation whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceInvitation whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceInvitation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceInvitation whereInvitedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceInvitation whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceInvitation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceInvitation whereWorkspaceId($value)
 * @mixin \Eloquent
 */
class WorkspaceInvitation extends Model
{
    use HasFactory,HasUuids;

    protected $fillable = [
        'workspace_id',
        'email',
        'role',
        'token',
        'expires_at',
        'accepted_at',
        'invited_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    /**
     * Get the workspace that owns the invitation.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the user who sent the invitation.
     */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Check if the invitation is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the invitation is accepted.
     */
    public function isAccepted(): bool
    {
        return !is_null($this->accepted_at);
    }

    /**
     * Check if the invitation is pending.
     */
    public function isPending(): bool
    {
        return !$this->isAccepted() && !$this->isExpired();
    }
}
