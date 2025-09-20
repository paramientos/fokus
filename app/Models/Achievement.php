<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 *
 *
 * @property int $id
 * @property int $workspace_id
 * @property string $name
 * @property string $description
 * @property string $icon
 * @property string $category
 * @property string $type
 * @property int $points
 * @property array<array-key, mixed> $criteria
 * @property bool $is_active
 * @property bool $is_repeatable
 * @property int $max_level
 * @property string $badge_color
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $category_icon
 * @property-read string $type_color
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserAchievement> $userAchievements
 * @property-read int|null $user_achievements_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @property-read \App\Models\Workspace $workspace
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Achievement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Achievement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Achievement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Achievement whereBadgeColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Achievement whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Achievement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Achievement whereCriteria($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Achievement whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Achievement whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Achievement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Achievement whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Achievement whereIsRepeatable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Achievement whereMaxLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Achievement whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Achievement wherePoints($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Achievement whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Achievement whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Achievement whereWorkspaceId($value)
 * @mixin \Eloquent
 */
class Achievement extends Model
{
    use HasFactory,HasUuids;

    protected $fillable = [
        'workspace_id',
        'name',
        'description',
        'icon',
        'category',
        'type',
        'points',
        'criteria',
        'is_active',
        'is_repeatable',
        'max_level',
        'badge_color'
    ];

    protected $casts = [
        'criteria' => 'array',
        'is_active' => 'boolean',
        'is_repeatable' => 'boolean'
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function userAchievements(): HasMany
    {
        return $this->hasMany(UserAchievement::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_achievements')
            ->withPivot(['level', 'progress', 'points_earned', 'earned_at', 'metadata'])
            ->withTimestamps();
    }

    public function getTypeColorAttribute(): string
    {
        return match($this->type) {
            'bronze' => '#CD7F32',
            'silver' => '#C0C0C0',
            'gold' => '#FFD700',
            'platinum' => '#E5E4E2',
            'diamond' => '#B9F2FF',
            default => '#FFD700'
        };
    }

    public function getCategoryIconAttribute(): string
    {
        return match($this->category) {
            'task' => 'fas.check-circle',
            'project' => 'fas.project-diagram',
            'collaboration' => 'fas.users',
            'learning' => 'fas.graduation-cap',
            'leadership' => 'fas.crown',
            'quality' => 'fas.star',
            'streak' => 'fas.fire',
            'milestone' => 'fas.flag',
            default => 'fas.trophy'
        };
    }

    public function checkCriteria(User $user, array $data = []): bool
    {
        $criteria = $this->criteria;

        // Example criteria checking logic
        foreach ($criteria as $criterion => $value) {
            switch ($criterion) {
                case 'tasks_completed':
                    if ($user->tasks()->where('status', 'completed')->count() < $value) {
                        return false;
                    }
                    break;
                case 'projects_completed':
                    if ($user->projects()->where('status', 'completed')->count() < $value) {
                        return false;
                    }
                    break;
                case 'streak_days':
                    // Check daily activity streak
                    if (($data['streak_days'] ?? 0) < $value) {
                        return false;
                    }
                    break;
                // Add more criteria as needed
            }
        }

        return true;
    }

    public static function getDefaultAchievements(): array
    {
        return [
            [
                'name' => 'First Steps',
                'description' => 'Complete your first task',
                'icon' => 'fas.baby',
                'category' => 'task',
                'type' => 'bronze',
                'points' => 10,
                'criteria' => ['tasks_completed' => 1],
                'badge_color' => '#CD7F32'
            ],
            [
                'name' => 'Task Master',
                'description' => 'Complete 10 tasks',
                'icon' => 'fas.check-double',
                'category' => 'task',
                'type' => 'silver',
                'points' => 50,
                'criteria' => ['tasks_completed' => 10],
                'badge_color' => '#C0C0C0'
            ],
            [
                'name' => 'Project Pioneer',
                'description' => 'Complete your first project',
                'icon' => 'fas.rocket',
                'category' => 'project',
                'type' => 'gold',
                'points' => 100,
                'criteria' => ['projects_completed' => 1],
                'badge_color' => '#FFD700'
            ],
            [
                'name' => 'Team Player',
                'description' => 'Collaborate on 5 different projects',
                'icon' => 'fas.handshake',
                'category' => 'collaboration',
                'type' => 'silver',
                'points' => 75,
                'criteria' => ['collaborations' => 5],
                'badge_color' => '#C0C0C0'
            ],
            [
                'name' => 'Learning Enthusiast',
                'description' => 'Complete 3 training programs',
                'icon' => 'fas.book-open',
                'category' => 'learning',
                'type' => 'gold',
                'points' => 150,
                'criteria' => ['trainings_completed' => 3],
                'badge_color' => '#FFD700'
            ],
            [
                'name' => 'Streak Warrior',
                'description' => 'Maintain a 7-day activity streak',
                'icon' => 'fas.fire',
                'category' => 'streak',
                'type' => 'platinum',
                'points' => 200,
                'criteria' => ['streak_days' => 7],
                'badge_color' => '#E5E4E2'
            ]
        ];
    }
}
