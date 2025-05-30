<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;
use App\Models\Task;
use App\Models\Sprint;
use App\Models\Status;
use App\Models\Meeting;

/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string $key
 * @property string|null $description
 * @property int $user_id
 * @property string|null $avatar
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Meeting> $meetings
 * @property-read int|null $meetings_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Sprint> $sprints
 * @property-read int|null $sprints_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Status> $statuses
 * @property-read int|null $statuses_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Task> $tasks
 * @property-read int|null $tasks_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $teamMembers
 * @property-read int|null $team_members_count
 * @property-read User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereUserId($value)
 * @mixin \Eloquent
 */
class Project extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'key',
        'description',
        'user_id',
        'avatar',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the project.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the tasks for the project.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get the sprints for the project.
     */
    public function sprints(): HasMany
    {
        return $this->hasMany(Sprint::class);
    }

    /**
     * Get the statuses for the project.
     */
    public function statuses(): HasMany
    {
        return $this->hasMany(Status::class);
    }
    
    /**
     * Get the meetings for the project.
     */
    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class);
    }
    
    /**
     * Get the team members for the project.
     */
    public function teamMembers()
    {
        return $this->belongsToMany(User::class, 'project_members')
            ->withPivot(['role'])
            ->withTimestamps();
    }
}
