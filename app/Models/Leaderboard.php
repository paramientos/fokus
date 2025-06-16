<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

/**
 * 
 *
 * @property int $id
 * @property int $workspace_id
 * @property int $user_id
 * @property string $period
 * @property string $category
 * @property int $total_points
 * @property int $achievements_count
 * @property int $tasks_completed
 * @property int $projects_completed
 * @property numeric $quality_score
 * @property int $streak_days
 * @property int $rank
 * @property \Illuminate\Support\Carbon $period_start
 * @property \Illuminate\Support\Carbon $period_end
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $rank_badge_color
 * @property-read string $rank_suffix
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Workspace $workspace
 * @method static Builder<static>|Leaderboard currentPeriod(string $period)
 * @method static Builder<static>|Leaderboard forCategory(string $category)
 * @method static Builder<static>|Leaderboard forPeriod(string $period)
 * @method static Builder<static>|Leaderboard newModelQuery()
 * @method static Builder<static>|Leaderboard newQuery()
 * @method static Builder<static>|Leaderboard query()
 * @method static Builder<static>|Leaderboard topRanked(int $limit = 10)
 * @method static Builder<static>|Leaderboard whereAchievementsCount($value)
 * @method static Builder<static>|Leaderboard whereCategory($value)
 * @method static Builder<static>|Leaderboard whereCreatedAt($value)
 * @method static Builder<static>|Leaderboard whereId($value)
 * @method static Builder<static>|Leaderboard wherePeriod($value)
 * @method static Builder<static>|Leaderboard wherePeriodEnd($value)
 * @method static Builder<static>|Leaderboard wherePeriodStart($value)
 * @method static Builder<static>|Leaderboard whereProjectsCompleted($value)
 * @method static Builder<static>|Leaderboard whereQualityScore($value)
 * @method static Builder<static>|Leaderboard whereRank($value)
 * @method static Builder<static>|Leaderboard whereStreakDays($value)
 * @method static Builder<static>|Leaderboard whereTasksCompleted($value)
 * @method static Builder<static>|Leaderboard whereTotalPoints($value)
 * @method static Builder<static>|Leaderboard whereUpdatedAt($value)
 * @method static Builder<static>|Leaderboard whereUserId($value)
 * @method static Builder<static>|Leaderboard whereWorkspaceId($value)
 * @mixin \Eloquent
 */
class Leaderboard extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'user_id',
        'period',
        'category',
        'total_points',
        'achievements_count',
        'tasks_completed',
        'projects_completed',
        'quality_score',
        'streak_days',
        'rank',
        'period_start',
        'period_end'
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'quality_score' => 'decimal:2'
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForPeriod(Builder $query, string $period): Builder
    {
        return $query->where('period', $period);
    }

    public function scopeForCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeCurrentPeriod(Builder $query, string $period): Builder
    {
        $dates = self::getPeriodDates($period);
        
        return $query->where('period_start', $dates['start'])
                    ->where('period_end', $dates['end']);
    }

    public function scopeTopRanked(Builder $query, int $limit = 10): Builder
    {
        return $query->orderBy('rank')->limit($limit);
    }

    public function getRankSuffixAttribute(): string
    {
        $rank = $this->rank;
        
        if ($rank >= 11 && $rank <= 13) {
            return $rank . 'th';
        }
        
        return match($rank % 10) {
            1 => $rank . 'st',
            2 => $rank . 'nd',
            3 => $rank . 'rd',
            default => $rank . 'th'
        };
    }

    public function getRankBadgeColorAttribute(): string
    {
        return match($this->rank) {
            1 => '#FFD700', // Gold
            2 => '#C0C0C0', // Silver
            3 => '#CD7F32', // Bronze
            default => '#6B7280' // Gray
        };
    }

    public static function getPeriodDates(string $period): array
    {
        $now = Carbon::now();
        
        return match($period) {
            'daily' => [
                'start' => $now->startOfDay()->toDateString(),
                'end' => $now->endOfDay()->toDateString()
            ],
            'weekly' => [
                'start' => $now->startOfWeek()->toDateString(),
                'end' => $now->endOfWeek()->toDateString()
            ],
            'monthly' => [
                'start' => $now->startOfMonth()->toDateString(),
                'end' => $now->endOfMonth()->toDateString()
            ],
            'quarterly' => [
                'start' => $now->startOfQuarter()->toDateString(),
                'end' => $now->endOfQuarter()->toDateString()
            ],
            'yearly' => [
                'start' => $now->startOfYear()->toDateString(),
                'end' => $now->endOfYear()->toDateString()
            ],
            'all_time' => [
                'start' => '1970-01-01',
                'end' => '2099-12-31'
            ]
        };
    }

    public static function updateRankings(int $workspaceId, string $period, string $category): void
    {
        $dates = self::getPeriodDates($period);
        
        $leaderboards = self::where('workspace_id', $workspaceId)
            ->where('period', $period)
            ->where('category', $category)
            ->where('period_start', $dates['start'])
            ->where('period_end', $dates['end'])
            ->orderByDesc('total_points')
            ->orderByDesc('achievements_count')
            ->orderByDesc('quality_score')
            ->get();

        foreach ($leaderboards as $index => $leaderboard) {
            $leaderboard->update(['rank' => $index + 1]);
        }
    }

    public static function calculateUserStats(User $user, int $workspaceId, string $period, string $category): array
    {
        $dates = self::getPeriodDates($period);
        $startDate = Carbon::parse($dates['start']);
        $endDate = Carbon::parse($dates['end']);

        $stats = [
            'total_points' => 0,
            'achievements_count' => 0,
            'tasks_completed' => 0,
            'projects_completed' => 0,
            'quality_score' => 0,
            'streak_days' => 0
        ];

        // Calculate achievements points
        $achievements = $user->userAchievements()
            ->whereBetween('earned_at', [$startDate, $endDate])
            ->with('achievement')
            ->get();

        $stats['achievements_count'] = $achievements->count();
        $stats['total_points'] = $achievements->sum('points_earned');

        // Calculate tasks completed
        $stats['tasks_completed'] = $user->tasks()
            ->where('workspace_id', $workspaceId)
            ->whereHas('status', function($query) {
                $query->where('name', 'Done')->orWhere('name', 'Completed');
            })
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->count();

        // Calculate projects completed
        $stats['projects_completed'] = $user->projects()
            ->where('workspace_id', $workspaceId)
            ->whereHas('status', function($query) {
                $query->where('name', 'Completed')->orWhere('name', 'Done');
            })
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->count();

        // Add task completion points
        $stats['total_points'] += $stats['tasks_completed'] * 5; // 5 points per task
        $stats['total_points'] += $stats['projects_completed'] * 50; // 50 points per project

        // Calculate quality score (placeholder logic)
        $stats['quality_score'] = min(100, ($stats['tasks_completed'] * 2) + ($stats['projects_completed'] * 10));

        return $stats;
    }
}
