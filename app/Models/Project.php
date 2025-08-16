<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\GitRepository;

/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string $key
 * @property string|null $description
 * @property int $user_id
 * @property int|null $workspace_id
 * @property string|null $avatar
 * @property bool $is_active
 * @property bool $is_archived
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectAlert> $alerts
 * @property-read int|null $alerts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Conversation> $conversations
 * @property-read int|null $conversations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, GitRepository> $gitRepositories
 * @property-read int|null $git_repositories_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectHealthMetric> $healthMetrics
 * @property-read int|null $health_metrics_count
 * @property-read \App\Models\ProjectHealthMetric|null $latestHealthMetric
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Meeting> $meetings
 * @property-read int|null $meetings_count
 * @property-read \App\Models\User $owner
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Sprint> $sprints
 * @property-read int|null $sprints_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Status> $statuses
 * @property-read int|null $statuses_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Task> $tasks
 * @property-read int|null $tasks_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $teamMembers
 * @property-read int|null $team_members_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Team> $teams
 * @property-read int|null $teams_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectAlert> $unresolvedAlerts
 * @property-read int|null $unresolved_alerts_count
 * @property-read \App\Models\User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WikiCategory> $wikiCategories
 * @property-read int|null $wiki_categories_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WikiPage> $wikiPages
 * @property-read int|null $wiki_pages_count
 * @property-read \App\Models\Workspace|null $workspace
 * @method static Builder<static>|Project active()
 * @method static Builder<static>|Project archived()
 * @method static Builder<static>|Project newModelQuery()
 * @method static Builder<static>|Project newQuery()
 * @method static Builder<static>|Project query()
 * @method static Builder<static>|Project whereAvatar($value)
 * @method static Builder<static>|Project whereCreatedAt($value)
 * @method static Builder<static>|Project whereDescription($value)
 * @method static Builder<static>|Project whereId($value)
 * @method static Builder<static>|Project whereIsActive($value)
 * @method static Builder<static>|Project whereIsArchived($value)
 * @method static Builder<static>|Project whereKey($value)
 * @method static Builder<static>|Project whereName($value)
 * @method static Builder<static>|Project whereUpdatedAt($value)
 * @method static Builder<static>|Project whereUserId($value)
 * @method static Builder<static>|Project whereWorkspaceId($value)
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
        'workspace_id',
        'avatar',
        'is_active',
        'is_archived',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'is_archived' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::created(function (Project $project) {
            // Projeyi oluşturan kullanıcıyı otomatik olarak üyeler listesine ekle
            if ($project->user_id) {
                if (!$project->teamMembers()->where('users.id', $project->user_id)->exists()) {
                    $project->teamMembers()->attach($project->user_id, ['role' => 'admin']);
                }
            }

            $project->update([
                'workspace_id' => session('workspace_id')
            ]);
        });

        static::addGlobalScope('with_workspace', function (Builder $builder) {
            $workspaceId = session('workspace_id');

            if ($workspaceId) {
                $builder->where('workspace_id', $workspaceId);
            }
        });
    }

    /**
     * Get the user that owns the project.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the owner of the project (alias for user relationship).
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the workspace that the project belongs to.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
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

    /**
     * Get the wiki pages for the project.
     */
    public function wikiPages(): HasMany
    {
        return $this->hasMany(WikiPage::class);
    }

    /**
     * Get the wiki categories for the project.
     */
    public function wikiCategories(): HasMany
    {
        return $this->hasMany(WikiCategory::class);
    }

    /**
     * Get the conversations for the project.
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * Get the health metrics for the project.
     */
    public function healthMetrics(): HasMany
    {
        return $this->hasMany(ProjectHealthMetric::class);
    }

    /**
     * Get the alerts for the project.
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(ProjectAlert::class);
    }

    /**
     * Get the latest health metric for the project.
     */
    public function latestHealthMetric()
    {
        return $this->hasOne(ProjectHealthMetric::class)->latest('metric_date');
    }

    /**
     * Get unresolved alerts for the project.
     */
    public function unresolvedAlerts(): HasMany
    {
        return $this->hasMany(ProjectAlert::class)->where('is_resolved', false);
    }

    /**
     * Get Git repositories linked to the project.
     */
    public function gitRepositories(): HasMany
    {
        return $this->hasMany(GitRepository::class);
    }

    /**
     * Get members relationship (alias for teamMembers).
     */
    public function members()
    {
        return $this->teamMembers();
    }

    /**
     * Get the teams that are assigned to this project.
     */
    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_projects')
            ->withPivot(['assigned_by', 'assigned_at', 'notes'])
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('is_archived', false);
    }

    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }
}
