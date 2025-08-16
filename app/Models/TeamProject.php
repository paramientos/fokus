<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 
 *
 * @property int $id
 * @property int $team_id
 * @property int $project_id
 * @property int|null $assigned_by
 * @property \Illuminate\Support\Carbon $assigned_at
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $assignedBy
 * @property-read \App\Models\Project $project
 * @property-read \App\Models\Team $team
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TeamProject newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TeamProject newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TeamProject query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TeamProject whereAssignedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TeamProject whereAssignedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TeamProject whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TeamProject whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TeamProject whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TeamProject whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TeamProject whereTeamId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TeamProject whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class TeamProject extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'team_id',
        'project_id',
        'assigned_by',
        'assigned_at',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    /**
     * Get the team that owns the project assignment.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the project that is assigned to the team.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user who assigned the project to the team.
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
