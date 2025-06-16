<?php

use App\Models\Achievement;
use App\Models\User;
use App\Services\GamificationService;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public User $user;
    public $userStats;
    public $recentAchievements;
    public $leaderboards;
    public $availableAchievements;
    public $selectedPeriod = 'weekly';
    public $selectedCategory = 'overall';

    public function mount()
    {
        $this->user = auth()->user();
        $this->loadUserStats();
        $this->loadRecentAchievements();
        $this->loadLeaderboards();
        $this->loadAvailableAchievements();
    }

    public function loadUserStats()
    {
        $this->userStats = [
            'total_points' => $this->user->total_points,
            'level' => $this->user->level,
            'level_progress' => $this->user->level_progress,
            'points_to_next_level' => $this->user->points_to_next_level,
            'current_streak' => $this->user->current_streak,
            'achievements_count' => $this->user->userAchievements()->count(),
            'rank' => app(GamificationService::class)->getUserRank($this->user, $this->selectedPeriod, $this->selectedCategory) ?? 'Unranked'
        ];
    }

    public function loadRecentAchievements()
    {
        $this->recentAchievements = $this->user->userAchievements()
            ->with('achievement')
            ->orderByDesc('earned_at')
            ->limit(5)
            ->get();
    }

    public function loadLeaderboards()
    {
        $gamificationService = app(GamificationService::class);
        $this->leaderboards = $gamificationService->getLeaderboard(
            get_workspace_id(),
            $this->selectedPeriod,
            $this->selectedCategory,
            10
        );
    }

    public function loadAvailableAchievements()
    {
        $earnedAchievementIds = $this->user->userAchievements()->pluck('achievement_id');

        $this->availableAchievements = Achievement::where('workspace_id', $this->user->current_workspace_id)
            ->where('is_active', true)
            ->whereNotIn('id', $earnedAchievementIds)
            ->orderBy('points')
            ->limit(6)
            ->get();
    }

    public function updatedSelectedPeriod()
    {
        $this->loadUserStats();
        $this->loadLeaderboards();
    }

    public function updatedSelectedCategory()
    {
        $this->loadUserStats();
        $this->loadLeaderboards();
    }

    public function refreshStats()
    {
        $gamificationService = app(GamificationService::class);
        $gamificationService->updateUserLeaderboards($this->user);

        $this->loadUserStats();
        $this->loadRecentAchievements();
        $this->loadLeaderboards();

        $this->success('Stats refreshed successfully!');
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Gamification Dashboard</h1>
            <p class="text-gray-600 dark:text-gray-400">Track your progress, achievements, and compete with your
                team</p>
        </div>
        <x-button icon="fas.rotate" wire:click="refreshStats" class="btn-outline">
            Refresh Stats
        </x-button>
    </div>

    <!-- User Level & Progress -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <x-card class="bg-gradient-to-r from-purple-500 to-pink-500 text-white">
            <div class="text-center">
                <div class="text-4xl font-bold">{{ $userStats['level'] }}</div>
                <div class="text-sm opacity-90">Current Level</div>
                <div class="mt-2">
                    <div class="w-full bg-white/20 rounded-full h-2">
                        <div class="bg-white h-2 rounded-full transition-all duration-300"
                             style="width: {{ $userStats['level_progress'] }}%"></div>
                    </div>
                    <div class="text-xs mt-1 opacity-75">
                        {{ $userStats['points_to_next_level'] }} points to next level
                    </div>
                </div>
            </div>
        </x-card>

        <x-card class="bg-gradient-to-r from-blue-500 to-cyan-500 text-white">
            <div class="text-center">
                <div class="text-4xl font-bold">{{ number_format($userStats['total_points']) }}</div>
                <div class="text-sm opacity-90">Total Points</div>
                <div class="text-xs mt-2 opacity-75">
                    <i class="fas fa-trophy mr-1"></i>
                    {{ $userStats['achievements_count'] }} achievements earned
                </div>
            </div>
        </x-card>

        <x-card class="bg-gradient-to-r from-orange-500 to-red-500 text-white">
            <div class="text-center">
                <div class="text-4xl font-bold">{{ $userStats['current_streak'] }}</div>
                <div class="text-sm opacity-90">Day Streak</div>
                <div class="text-xs mt-2 opacity-75">
                    <i class="fas fa-fire mr-1"></i>
                    Keep it up!
                </div>
            </div>
        </x-card>

        <x-card class="bg-gradient-to-r from-green-500 to-teal-500 text-white">
            <div class="text-center">
                <div class="text-4xl font-bold">#{{ $userStats['rank'] }}</div>
                <div class="text-sm opacity-90">Current Rank</div>
                <div class="text-xs mt-2 opacity-75">
                    <i class="fas fa-medal mr-1"></i>
                    {{ ucfirst($selectedPeriod) }} {{ ucfirst($selectedCategory) }}
                </div>
            </div>
        </x-card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Leaderboard -->
        <div class="lg:col-span-2">
            <x-card>
                <x-slot:title>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-trophy text-yellow-500"></i>
                            Leaderboard
                        </div>
                        <div class="flex gap-2">
                            <x-select wire:model.live="selectedPeriod" class="w-32">
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                                <option value="all_time">All Time</option>
                            </x-select>
                            <x-select wire:model.live="selectedCategory" class="w-32">
                                <option value="overall">Overall</option>
                                <option value="tasks">Tasks</option>
                                <option value="projects">Projects</option>
                                <option value="learning">Learning</option>
                            </x-select>
                        </div>
                    </div>
                </x-slot:title>

                <div class="space-y-3">
                    @forelse($leaderboards as $index => $entry)
                        <div class="flex items-center justify-between p-3 rounded-lg
                                    {{ $entry['user']['id'] == auth()->id() ? 'bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800' : 'bg-gray-50 dark:bg-gray-800' }}">
                            <div class="flex items-center gap-3">
                                <div class="flex-shrink-0">
                                    @if($entry['rank'] <= 3)
                                        <div
                                            class="w-8 h-8 rounded-full flex items-center justify-center text-white font-bold text-sm"
                                            style="background-color: {{ $entry['rank'] == 1 ? '#FFD700' : ($entry['rank'] == 2 ? '#C0C0C0' : '#CD7F32') }}">
                                            {{ $entry['rank'] }}
                                        </div>
                                    @else
                                        <div
                                            class="w-8 h-8 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center text-gray-700 dark:text-gray-300 font-bold text-sm">
                                            {{ $entry['rank'] }}
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">
                                        {{ $entry['user']['name'] }}
                                        @if($entry['user']['id'] == auth()->id())
                                            <span class="text-blue-600 dark:text-blue-400 text-sm">(You)</span>
                                        @endif
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $entry['achievements_count'] }} achievements
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-gray-900 dark:text-white">
                                    {{ number_format($entry['total_points']) }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">points</div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <i class="fas fa-trophy text-4xl mb-2 opacity-50"></i>
                            <p>No leaderboard data available</p>
                        </div>
                    @endforelse
                </div>
            </x-card>
        </div>

        <!-- Recent Achievements & Available -->
        <div class="space-y-6">
            <!-- Recent Achievements -->
            <x-card>
                <x-slot:title>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-medal text-yellow-500"></i>
                        Recent Achievements
                    </div>
                </x-slot:title>

                <div class="space-y-3">
                    @forelse($recentAchievements as $userAchievement)
                        <div class="flex items-center gap-3 p-2 rounded-lg bg-gray-50 dark:bg-gray-800">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center text-white"
                                     style="background-color: {{ $userAchievement->achievement->type_color }}">
                                    <i class="{{ $userAchievement->achievement->icon }} text-sm"></i>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-gray-900 dark:text-white text-sm">
                                    {{ $userAchievement->achievement->name }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $userAchievement->earned_at->diffForHumans() }}
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-bold text-green-600 dark:text-green-400">
                                    +{{ $userAchievement->points_earned }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-gray-500 dark:text-gray-400">
                            <i class="fas fa-medal text-2xl mb-1 opacity-50"></i>
                            <p class="text-sm">No achievements yet</p>
                        </div>
                    @endforelse
                </div>
            </x-card>

            <!-- Available Achievements -->
            <x-card>
                <x-slot:title>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-target text-blue-500"></i>
                        Available Achievements
                    </div>
                </x-slot:title>

                <div class="space-y-2">
                    @forelse($availableAchievements as $achievement)
                        <div
                            class="flex items-center gap-3 p-2 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-white opacity-60"
                                     style="background-color: {{ $achievement->type_color }}">
                                    <i class="{{ $achievement->icon }} text-xs"></i>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-gray-900 dark:text-white text-sm">
                                    {{ $achievement->name }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $achievement->description }}
                                </div>
                            </div>
                            <div class="text-xs font-bold text-gray-600 dark:text-gray-400">
                                {{ $achievement->points }}pt
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-gray-500 dark:text-gray-400">
                            <i class="fas fa-check-circle text-2xl mb-1 opacity-50"></i>
                            <p class="text-sm">All achievements unlocked!</p>
                        </div>
                    @endforelse
                </div>
            </x-card>
        </div>
    </div>
</div>
