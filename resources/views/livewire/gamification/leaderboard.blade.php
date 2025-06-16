<?php

use App\Models\Leaderboard;
use App\Services\GamificationService;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public $selectedPeriod = 'weekly';
    public $selectedCategory = 'overall';
    public $leaderboards = [];
    public $userRank = null;
    public $userStats = [];

    public function mount()
    {
        $this->loadLeaderboard();
        $this->loadUserStats();
    }

    public function loadLeaderboard(): void
    {
        $gamificationService = app(GamificationService::class);
        $workspaceId = get_workspace_id();

        $this->leaderboards = $gamificationService->getLeaderboard(
            $workspaceId,
            $this->selectedPeriod,
            $this->selectedCategory,
            50
        );

        $this->userRank = $gamificationService->getUserRank(
            auth()->user(),
            $this->selectedPeriod,
            $this->selectedCategory
        );
    }

    public function loadUserStats()
    {
        $workspaceId = auth()->user()->current_workspace_id;
        $dates = Leaderboard::getPeriodDates($this->selectedPeriod);

        $userLeaderboard = Leaderboard::where('workspace_id', $workspaceId)
            ->where('user_id', auth()->id())
            ->where('period', $this->selectedPeriod)
            ->where('category', $this->selectedCategory)
            ->where('period_start', $dates['start'])
            ->where('period_end', $dates['end'])
            ->first();

        $this->userStats = $userLeaderboard ? [
            'total_points' => $userLeaderboard->total_points,
            'achievements_count' => $userLeaderboard->achievements_count,
            'tasks_completed' => $userLeaderboard->tasks_completed,
            'projects_completed' => $userLeaderboard->projects_completed,
            'quality_score' => $userLeaderboard->quality_score,
            'streak_days' => $userLeaderboard->streak_days,
            'rank' => $userLeaderboard->rank
        ] : [
            'total_points' => 0,
            'achievements_count' => 0,
            'tasks_completed' => 0,
            'projects_completed' => 0,
            'quality_score' => 0,
            'streak_days' => 0,
            'rank' => null
        ];
    }

    public function updatedSelectedPeriod()
    {
        $this->loadLeaderboard();
        $this->loadUserStats();
    }

    public function updatedSelectedCategory()
    {
        $this->loadLeaderboard();
        $this->loadUserStats();
    }

    public function refreshLeaderboard()
    {
        $gamificationService = app(GamificationService::class);
        $gamificationService->updateUserLeaderboards(auth()->user());

        $this->loadLeaderboard();
        $this->loadUserStats();

        $this->success('Leaderboard refreshed successfully!');
    }

    public function getPeriodLabel(): string
    {
        return match($this->selectedPeriod) {
            'daily' => 'Today',
            'weekly' => 'This Week',
            'monthly' => 'This Month',
            'quarterly' => 'This Quarter',
            'yearly' => 'This Year',
            'all_time' => 'All Time',
            default => 'Unknown'
        };
    }

    public function getCategoryIcon(): string
    {
        return match($this->selectedCategory) {
            'overall' => 'fas.trophy',
            'tasks' => 'fas.tasks',
            'projects' => 'fas.project-diagram',
            'collaboration' => 'fas.users',
            'learning' => 'fas.graduation-cap',
            'quality' => 'fas.star',
            default => 'fas.trophy'
        };
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Leaderboard</h1>
            <p class="text-gray-600 dark:text-gray-400">See how you rank against your teammates</p>
        </div>
        <x-button icon="fas.rotate" wire:click="refreshLeaderboard" class="btn-outline">
            Refresh
        </x-button>
    </div>

    <!-- Filters & User Stats -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Filters -->
        <x-card>
            <x-slot:title>
                <div class="flex items-center gap-2">
                    <i class="fas fa-filter text-blue-500"></i>
                    Filters
                </div>
            </x-slot:title>

            <div class="space-y-4">
                <div>
                    <x-select wire:model.live="selectedPeriod" label="Period" id="period" class="w-full">
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                        <option value="quarterly">Quarterly</option>
                        <option value="yearly">Yearly</option>
                        <option value="all_time">All Time</option>
                    </x-select>
                </div>

                <div>
                    <x-select wire:model.live="selectedCategory" label="Category" id="category" class="w-full">
                        <option value="overall">Overall</option>
                        <option value="tasks">Tasks</option>
                        <option value="projects">Projects</option>
                        <option value="collaboration">Collaboration</option>
                        <option value="learning">Learning</option>
                        <option value="quality">Quality</option>
                    </x-select>
                </div>

                <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="{{ $this->getCategoryIcon() }} text-blue-500"></i>
                            <span class="font-medium">{{ ucfirst($this->selectedCategory) }} - {{ $this->getPeriodLabel() }}</span>
                        </div>
                        <p>Showing rankings for {{ strtolower($this->getPeriodLabel()) }} in {{ strtolower($this->selectedCategory) }} category.</p>
                    </div>
                </div>
            </div>
        </x-card>

        <!-- User Stats -->
        <div class="lg:col-span-2">
            <x-card>
                <x-slot:title>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-user text-green-500"></i>
                        Your Performance
                    </div>
                </x-slot:title>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                            {{ $userStats['rank'] ? '#' . $userStats['rank'] : 'Unranked' }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Current Rank</div>
                    </div>

                    <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                            {{ number_format($userStats['total_points']) }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Points</div>
                    </div>

                    <div class="text-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                        <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                            {{ $userStats['achievements_count'] }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Achievements</div>
                    </div>

                    <div class="text-center p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                        <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                            {{ $userStats['streak_days'] }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Day Streak</div>
                    </div>
                </div>

                @if($this->selectedCategory !== 'overall')
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="grid grid-cols-3 gap-4 text-center">
                            @if($this->selectedCategory === 'tasks' || $this->selectedCategory === 'overall')
                                <div>
                                    <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $userStats['tasks_completed'] }}</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">Tasks Completed</div>
                                </div>
                            @endif

                            @if($this->selectedCategory === 'projects' || $this->selectedCategory === 'overall')
                                <div>
                                    <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $userStats['projects_completed'] }}</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">Projects Completed</div>
                                </div>
                            @endif

                            @if($this->selectedCategory === 'quality' || $this->selectedCategory === 'overall')
                                <div>
                                    <div class="text-lg font-bold text-gray-900 dark:text-white">{{ number_format($userStats['quality_score'], 1) }}</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">Quality Score</div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </x-card>
        </div>
    </div>

    <!-- Leaderboard -->
    <x-card>
        <x-slot:title>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fas fa-trophy text-yellow-500"></i>
                    {{ ucfirst($this->selectedCategory) }} Leaderboard - {{ $this->getPeriodLabel() }}
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ count($this->leaderboards) }} participants
                </div>
            </div>
        </x-slot:title>

        @if(count($this->leaderboards) > 0)
            <div class="space-y-2">
                @foreach($this->leaderboards as $index => $entry)
                    <div class="flex items-center justify-between p-4 rounded-lg transition-colors
                                {{ $entry['user']['id'] == auth()->id() ? 'bg-blue-50 dark:bg-blue-900/20 border-2 border-blue-200 dark:border-blue-800' : 'bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        <div class="flex items-center gap-4">
                            <!-- Rank -->
                            <div class="flex-shrink-0">
                                @if($entry['rank'] <= 3)
                                    <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-lg"
                                         style="background: {{ $entry['rank'] == 1 ? 'linear-gradient(135deg, #FFD700, #FFA500)' : ($entry['rank'] == 2 ? 'linear-gradient(135deg, #C0C0C0, #A0A0A0)' : 'linear-gradient(135deg, #CD7F32, #B8860B)') }}">
                                        @if($entry['rank'] == 1)
                                            <i class="fas fa-crown"></i>
                                        @elseif($entry['rank'] == 2)
                                            <i class="fas fa-medal"></i>
                                        @elseif($entry['rank'] == 3)
                                            <i class="fas fa-award"></i>
                                        @endif
                                    </div>
                                @else
                                    <div class="w-12 h-12 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center text-gray-700 dark:text-gray-300 font-bold text-lg">
                                        {{ $entry['rank'] }}
                                    </div>
                                @endif
                            </div>

                            <!-- User Info -->
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white text-lg">
                                    {{ $entry['user']['name'] }}
                                    @if($entry['user']['id'] == auth()->id())
                                        <span class="text-blue-600 dark:text-blue-400 text-sm font-normal">(You)</span>
                                    @endif
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $entry['achievements_count'] }} achievements • {{ $entry['streak_days'] }} day streak
                                </div>
                            </div>
                        </div>

                        <!-- Stats -->
                        <div class="text-right">
                            <div class="font-bold text-xl text-gray-900 dark:text-white">
                                {{ number_format($entry['total_points']) }}
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">points</div>

                            @if($this->selectedCategory !== 'overall')
                                <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                    @if($this->selectedCategory === 'tasks')
                                        {{ $entry['tasks_completed'] }} tasks
                                    @elseif($this->selectedCategory === 'projects')
                                        {{ $entry['projects_completed'] }} projects
                                    @elseif($this->selectedCategory === 'quality')
                                        {{ number_format($entry['quality_score'], 1) }} quality
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <i class="fas fa-trophy text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-2">No leaderboard data</h3>
                <p class="text-gray-500 dark:text-gray-400">Complete some tasks or projects to appear on the leaderboard!</p>
            </div>
        @endif
    </x-card>
</div>
