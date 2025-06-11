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
use App\Models\Conversation;
use App\Models\Workspace;
use App\Models\WikiCategory;
use App\Models\WikiPage;

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
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Conversation> $conversations
 * @property-read int|null $conversations_count
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
 * @property-read \Illuminate\Database\Eloquent\Collection<int, WikiCategory> $wikiCategories
 * @property-read int|null $wiki_categories_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, WikiPage> $wikiPages
 * @property-read int|null $wiki_pages_count
 * @property-read Workspace|null $workspace
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project archived()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereIsArchived($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereWorkspaceId($value)
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
                // Eğer zaten eklenmediyse ekle
                if (!$project->teamMembers()->where('users.id', $project->user_id)->exists()) {
                    $project->teamMembers()->attach($project->user_id, ['role' => 'admin']);
                }
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
     * Scope a query to only include active projects.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('is_archived', false);
    }
    
    /**
     * Scope a query to only include archived projects.
     */
    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }
}
