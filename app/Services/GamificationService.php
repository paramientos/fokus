<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\Leaderboard;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\UserAchievement;
use Carbon\Carbon;

class GamificationService
{
    /**
     * Award achievement to user
     */
    public function awardAchievement(User $user, Achievement $achievement, array $metadata = []): ?UserAchievement
    {
        // Check if user already has this achievement
        $existingAchievement = $user->userAchievements()
            ->where('achievement_id', $achievement->id)
            ->first();

        if ($existingAchievement && !$achievement->is_repeatable) {
            return null;
        }

        // Create new achievement record
        $userAchievement = UserAchievement::create([
            'user_id' => $user->id,
            'achievement_id' => $achievement->id,
            'level' => 1,
            'progress' => 0,
            'points_earned' => $achievement->points,
            'earned_at' => now(),
            'metadata' => $metadata,
        ]);

        // Update leaderboards
        $this->updateUserLeaderboards($user);

        return $userAchievement;
    }

    /**
     * Check and award achievements for user actions
     */
    public function checkAchievements(User $user, string $action, array $data = []): array
    {
        $awardedAchievements = [];
        $workspaceId = $user->current_workspace_id;

        if (!$workspaceId) {
            return $awardedAchievements;
        }

        $achievements = Achievement::where('workspace_id', $workspaceId)
            ->where('is_active', true)
            ->get();

        foreach ($achievements as $achievement) {
            if ($this->shouldAwardAchievement($user, $achievement, $action, $data)) {
                $userAchievement = $this->awardAchievement($user, $achievement, $data);
                if ($userAchievement) {
                    $awardedAchievements[] = $userAchievement;
                }
            }
        }

        return $awardedAchievements;
    }

    /**
     * Check if achievement should be awarded
     */
    private function shouldAwardAchievement(User $user, Achievement $achievement, string $action, array $data): bool
    {
        $criteria = $achievement->criteria;

        // Check if user already has this achievement (for non-repeatable)
        if (!$achievement->is_repeatable) {
            $existing = $user->userAchievements()
                ->where('achievement_id', $achievement->id)
                ->exists();
            if ($existing) {
                return false;
            }
        }

        // Action-based criteria checking
        switch ($action) {
            case 'task_completed':
                return $this->checkTaskCompletionCriteria($user, $criteria);
            case 'project_completed':
                return $this->checkProjectCompletionCriteria($user, $criteria);
            case 'training_completed':
                return $this->checkTrainingCompletionCriteria($user, $criteria);
            case 'streak_updated':
                return $this->checkStreakCriteria($user, $criteria, $data);
            default:
                return false;
        }
    }

    /**
     * Check task completion criteria
     */
    private function checkTaskCompletionCriteria(User $user, array $criteria): bool
    {
        if (!isset($criteria['tasks_completed'])) {
            return false;
        }

        $completedTasks = $user->tasks()
            ->where('workspace_id', $user->current_workspace_id)
            ->whereHas('status', function ($query) {
                $query->where('name', 'Done')->orWhere('name', 'Completed');
            })
            ->count();

        return $completedTasks >= $criteria['tasks_completed'];
    }

    /**
     * Check project completion criteria
     */
    private function checkProjectCompletionCriteria(User $user, array $criteria): bool
    {
        if (!isset($criteria['projects_completed'])) {
            return false;
        }

        $completedProjects = $user->projects()
            ->where('workspace_id', $user->current_workspace_id)
            ->whereHas('status', function ($query) {
                $query->where('name', 'Completed')->orWhere('name', 'Done');
            })
            ->count();

        return $completedProjects >= $criteria['projects_completed'];
    }

    /**
     * Check training completion criteria
     */
    private function checkTrainingCompletionCriteria(User $user, array $criteria): bool
    {
        if (!isset($criteria['trainings_completed'])) {
            return false;
        }

        $completedTrainings = $user->employeeTrainings()
            ->where('status', 'completed')
            ->count();

        return $completedTrainings >= $criteria['trainings_completed'];
    }

    /**
     * Check streak criteria
     */
    private function checkStreakCriteria(User $user, array $criteria, array $data): bool
    {
        if (!isset($criteria['streak_days'])) {
            return false;
        }

        $currentStreak = $data['streak_days'] ?? $user->current_streak;

        return $currentStreak >= $criteria['streak_days'];
    }

    /**
     * Update user leaderboards
     */
    public function updateUserLeaderboards(User $user): void
    {
        $workspaceId = $user->current_workspace_id;
        if (!$workspaceId) {
            return;
        }

        $periods = ['daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'all_time'];
        $categories = ['overall', 'tasks', 'projects', 'collaboration', 'learning', 'quality'];

        foreach ($periods as $period) {
            foreach ($categories as $category) {
                $this->updateUserLeaderboard($user, $workspaceId, $period, $category);
            }
        }
    }

    /**
     * Update specific leaderboard entry
     */
    private function updateUserLeaderboard(User $user, int $workspaceId, string $period, string $category): void
    {
        $dates = Leaderboard::getPeriodDates($period);
        $stats = Leaderboard::calculateUserStats($user, $workspaceId, $period, $category);

        Leaderboard::updateOrCreate([
            'workspace_id' => $workspaceId,
            'user_id' => $user->id,
            'period' => $period,
            'category' => $category,
            'period_start' => $dates['start'],
            'period_end' => $dates['end'],
        ], $stats);

        // Update rankings for this period and category
        Leaderboard::updateRankings($workspaceId, $period, $category);
    }

    /**
     * Get user's current rank in leaderboard
     */
    public function getUserRank(User $user, string $period = 'all_time', string $category = 'overall'): ?int
    {
        $workspaceId = $user->current_workspace_id;
        if (!$workspaceId) {
            return null;
        }

        $dates = Leaderboard::getPeriodDates($period);

        $leaderboard = Leaderboard::where('workspace_id', $workspaceId)
            ->where('user_id', $user->id)
            ->where('period', $period)
            ->where('category', $category)
            ->where('period_start', $dates['start'])
            ->where('period_end', $dates['end'])
            ->first();

        return $leaderboard?->rank;
    }

    /**
     * Get leaderboard for workspace
     */
    public function getLeaderboard(string $workspaceId, string $period = 'all_time', string $category = 'overall', int $limit = 10): array
    {
        $dates = Leaderboard::getPeriodDates($period);

        return Leaderboard::with('user')
            ->where('workspace_id', $workspaceId)
            ->where('period', $period)
            ->where('category', $category)
            ->where('period_start', $dates['start'])
            ->where('period_end', $dates['end'])
            ->orderBy('rank')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Initialize default achievements for workspace
     */
    public function initializeDefaultAchievements(int $workspaceId): void
    {
        $defaultAchievements = Achievement::getDefaultAchievements();

        foreach ($defaultAchievements as $achievementData) {
            Achievement::firstOrCreate([
                'workspace_id' => $workspaceId,
                'name' => $achievementData['name'],
            ], array_merge($achievementData, ['workspace_id' => $workspaceId]));
        }
    }

    /**
     * Calculate user's activity streak
     */
    public function calculateUserStreak(User $user): int
    {
        $workspaceId = $user->current_workspace_id;
        if (!$workspaceId) {
            return 0;
        }

        // Get user's daily activities (tasks completed, projects worked on, etc.)
        $activities = collect();

        // Add task completion dates
        $taskDates = $user->tasks()
            ->where('workspace_id', $workspaceId)
            ->whereHas('status', function ($query) {
                $query->where('name', 'Done')->orWhere('name', 'Completed');
            })
            ->where('updated_at', '>=', now()->subDays(365))
            ->pluck('updated_at')
            ->map(fn ($date) => Carbon::parse($date)->format('Y-m-d'))
            ->unique();

        $activities = $activities->merge($taskDates);

        // Add project activity dates (could be comments, updates, etc.)
        $projectDates = $user->projects()
            ->where('workspace_id', $workspaceId)
            ->where('updated_at', '>=', now()->subDays(365))
            ->pluck('updated_at')
            ->map(fn ($date) => Carbon::parse($date)->format('Y-m-d'))
            ->unique();

        $activities = $activities->merge($projectDates);

        // Sort dates and calculate streak
        $uniqueDates = $activities->unique()->sort()->values();

        if ($uniqueDates->isEmpty()) {
            return 0;
        }

        $streak = 0;
        $currentDate = Carbon::now();

        // Check if there's activity today or yesterday
        $today = $currentDate->format('Y-m-d');
        $yesterday = $currentDate->subDay()->format('Y-m-d');

        if (!$uniqueDates->contains($today) && !$uniqueDates->contains($yesterday)) {
            return 0;
        }

        // Calculate consecutive days
        $checkDate = Carbon::now();
        foreach ($uniqueDates->reverse() as $activityDate) {
            $activityCarbon = Carbon::parse($activityDate);
            $daysDiff = $checkDate->diffInDays($activityCarbon);

            if ($daysDiff <= 1) {
                $streak++;
                $checkDate = $activityCarbon;
            } else {
                break;
            }
        }

        return $streak;
    }

    /**
     * Process gamification events
     */
    public function processEvent(User $user, string $event, array $data = []): array
    {
        $results = [];

        // Update user streak if needed
        if (in_array($event, ['task_completed', 'project_updated'])) {
            $streak = $this->calculateUserStreak($user);
            $data['streak_days'] = $streak;

            // Check streak achievements
            $streakAchievements = $this->checkAchievements($user, 'streak_updated', $data);
            $results = array_merge($results, $streakAchievements);
        }

        // Check event-specific achievements
        $eventAchievements = $this->checkAchievements($user, $event, $data);
        $results = array_merge($results, $eventAchievements);

        return $results;
    }
}
