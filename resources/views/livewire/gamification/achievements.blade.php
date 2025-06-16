<?php

use App\Models\Achievement;
use App\Models\UserAchievement;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new class extends Component {
    use Toast, WithPagination;

    public $search = '';
    public $selectedCategory = '';
    public $selectedType = '';
    public $showEarnedOnly = false;

    public function with(): array
    {
        $query = Achievement::query()
            ->where('workspace_id', auth()->user()->current_workspace_id)
            ->where('is_active', true)
            ->with(['userAchievements' => function($q) {
                $q->where('user_id', auth()->id());
            }]);

        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->selectedCategory) {
            $query->where('category', $this->selectedCategory);
        }

        if ($this->selectedType) {
            $query->where('type', $this->selectedType);
        }

        if ($this->showEarnedOnly) {
            $query->whereHas('userAchievements', function($q) {
                $q->where('user_id', auth()->id());
            });
        }

        $achievements = $query->orderBy('points')->paginate(12);

        $stats = [
            'total_achievements' => Achievement::where('workspace_id', auth()->user()->current_workspace_id)->where('is_active', true)->count(),
            'earned_achievements' => UserAchievement::where('user_id', auth()->id())->count(),
            'total_points_earned' => UserAchievement::where('user_id', auth()->id())->sum('points_earned'),
            'categories' => Achievement::where('workspace_id', auth()->user()->current_workspace_id)->distinct()->pluck('category'),
            'types' => Achievement::where('workspace_id', auth()->user()->current_workspace_id)->distinct()->pluck('type')
        ];

        return [
            'achievements' => $achievements,
            'stats' => $stats
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSelectedCategory()
    {
        $this->resetPage();
    }

    public function updatingSelectedType()
    {
        $this->resetPage();
    }

    public function updatingShowEarnedOnly()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->selectedCategory = '';
        $this->selectedType = '';
        $this->showEarnedOnly = false;
        $this->resetPage();
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Achievements</h1>
            <p class="text-gray-600 dark:text-gray-400">Unlock achievements by completing tasks and reaching milestones</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <x-card class="bg-gradient-to-r from-blue-500 to-blue-600 text-white">
            <div class="text-center">
                <div class="text-2xl font-bold">{{ $stats['earned_achievements'] }}/{{ $stats['total_achievements'] }}</div>
                <div class="text-sm opacity-90">Achievements Earned</div>
                <div class="mt-2">
                    <div class="w-full bg-white/20 rounded-full h-2">
                        <div class="bg-white h-2 rounded-full transition-all duration-300" 
                             style="width: {{ $stats['total_achievements'] > 0 ? ($stats['earned_achievements'] / $stats['total_achievements'] * 100) : 0 }}%"></div>
                    </div>
                </div>
            </div>
        </x-card>

        <x-card class="bg-gradient-to-r from-green-500 to-green-600 text-white">
            <div class="text-center">
                <div class="text-2xl font-bold">{{ number_format($stats['total_points_earned']) }}</div>
                <div class="text-sm opacity-90">Total Points</div>
                <div class="text-xs mt-2 opacity-75">
                    <i class="fas fa-trophy mr-1"></i>
                    Points earned from achievements
                </div>
            </div>
        </x-card>

        <x-card class="bg-gradient-to-r from-purple-500 to-purple-600 text-white">
            <div class="text-center">
                <div class="text-2xl font-bold">{{ $stats['categories']->count() }}</div>
                <div class="text-sm opacity-90">Categories</div>
                <div class="text-xs mt-2 opacity-75">
                    <i class="fas fa-tags mr-1"></i>
                    Different achievement types
                </div>
            </div>
        </x-card>

        <x-card class="bg-gradient-to-r from-orange-500 to-orange-600 text-white">
            <div class="text-center">
                <div class="text-2xl font-bold">
                    {{ $stats['total_achievements'] > 0 ? round(($stats['earned_achievements'] / $stats['total_achievements']) * 100) : 0 }}%
                </div>
                <div class="text-sm opacity-90">Completion Rate</div>
                <div class="text-xs mt-2 opacity-75">
                    <i class="fas fa-chart-line mr-1"></i>
                    Overall progress
                </div>
            </div>
        </x-card>
    </div>

    <!-- Filters -->
    <x-card>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <x-input 
                wire:model.live.debounce.300ms="search" 
                placeholder="Search achievements..." 
                icon="fas.search"
                clearable
            />
            
            <x-select wire:model.live="selectedCategory" placeholder="All Categories">
                <option value="">All Categories</option>
                @foreach($stats['categories'] as $category)
                    <option value="{{ $category }}">{{ ucfirst(str_replace('_', ' ', $category)) }}</option>
                @endforeach
            </x-select>

            <x-select wire:model.live="selectedType" placeholder="All Types">
                <option value="">All Types</option>
                @foreach($stats['types'] as $type)
                    <option value="{{ $type }}">{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                @endforeach
            </x-select>

            <x-checkbox wire:model.live="showEarnedOnly" label="Earned Only" />

            <x-button wire:click="clearFilters" class="btn-outline">
                <i class="fas fa-times mr-2"></i>
                Clear Filters
            </x-button>
        </div>
    </x-card>

    <!-- Achievements Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @forelse($achievements as $achievement)
            @php
                $isEarned = $achievement->userAchievements->isNotEmpty();
                $userAchievement = $achievement->userAchievements->first();
            @endphp
            
            <x-card class="relative overflow-hidden {{ $isEarned ? 'ring-2 ring-green-500 bg-green-50 dark:bg-green-900/20' : 'hover:shadow-lg transition-shadow' }}">
                <!-- Earned Badge -->
                @if($isEarned)
                    <div class="absolute top-2 right-2 bg-green-500 text-white px-2 py-1 rounded-full text-xs font-bold">
                        <i class="fas fa-check mr-1"></i>
                        EARNED
                    </div>
                @endif

                <div class="text-center p-4">
                    <!-- Achievement Icon -->
                    <div class="mx-auto w-16 h-16 rounded-full flex items-center justify-center text-white text-2xl mb-4 {{ $isEarned ? 'shadow-lg' : 'opacity-60' }}"
                         style="background-color: {{ $achievement->type_color }}">
                        <i class="{{ $achievement->icon }}"></i>
                    </div>

                    <!-- Achievement Info -->
                    <h3 class="font-bold text-lg text-gray-900 dark:text-white mb-2">
                        {{ $achievement->name }}
                    </h3>
                    
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        {{ $achievement->description }}
                    </p>

                    <!-- Achievement Details -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">Category:</span>
                            <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $achievement->category)) }}</span>
                        </div>
                        
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">Type:</span>
                            <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $achievement->type)) }}</span>
                        </div>
                        
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">Points:</span>
                            <span class="font-bold text-green-600 dark:text-green-400">{{ $achievement->points }}</span>
                        </div>

                        @if($isEarned && $userAchievement)
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Earned:</span>
                                <span class="font-medium">{{ $userAchievement->earned_at->format('M d, Y') }}</span>
                            </div>
                            
                            @if($userAchievement->level > 1)
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500 dark:text-gray-400">Level:</span>
                                    <span class="font-bold text-purple-600 dark:text-purple-400">{{ $userAchievement->level }}</span>
                                </div>
                            @endif
                        @endif
                    </div>

                    <!-- Progress Bar (for progressive achievements) -->
                    @if(!$isEarned && $achievement->type === 'progressive')
                        <div class="mt-4">
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="bg-blue-500 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Progress: 0%
                            </div>
                        </div>
                    @endif
                </div>
            </x-card>
        @empty
            <div class="col-span-full text-center py-12">
                <i class="fas fa-trophy text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-2">No achievements found</h3>
                <p class="text-gray-500 dark:text-gray-400">Try adjusting your filters or search terms.</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($achievements->hasPages())
        <div class="flex justify-center">
            {{ $achievements->links() }}
        </div>
    @endif
</div>
