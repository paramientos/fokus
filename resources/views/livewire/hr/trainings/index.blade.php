<?php
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Training;
use App\Models\Employee;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $type = '';
    public $year = '';
    public $month = '';

    public function mount()
    {
        $this->year = now()->year;
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedType()
    {
        $this->resetPage();
    }

    public function updatedYear()
    {
        $this->resetPage();
    }

    public function updatedMonth()
    {
        $this->resetPage();
    }

    public function with()
    {
        $workspaceId = session('workspace_id');

        // Statistics
        $totalTrainings = Training::where('workspace_id', $workspaceId)->count();
        $upcomingTrainings = Training::where('workspace_id', $workspaceId)
            ->where('start_date', '>', now())
            ->count();
        $ongoingTrainings = Training::where('workspace_id', $workspaceId)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->count();
        $completedTrainings = Training::where('workspace_id', $workspaceId)
            ->where('end_date', '<', now())
            ->count();

        $trainings = Training::where('workspace_id', $workspaceId)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('title', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%')
                      ->orWhere('provider', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->type, function ($query) {
                $query->where('type', $this->type);
            })
            ->when($this->year, function ($query) {
                $query->whereYear('start_date', $this->year);
            })
            ->when($this->month, function ($query) {
                $query->whereMonth('start_date', $this->month);
            })
            ->orderBy('start_date', 'desc')
            ->paginate(12);

        return [
            'trainings' => $trainings,
            'totalTrainings' => $totalTrainings,
            'upcomingTrainings' => $upcomingTrainings,
            'ongoingTrainings' => $ongoingTrainings,
            'completedTrainings' => $completedTrainings
        ];
    }
}; ?>

<div>
    <x-header title="Training Management" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-button label="Schedule Training" icon="fas.plus" link="/hr/trainings/create" class="btn-primary" />
        </x-slot:middle>
    </x-header>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <x-stat
            title="Total Trainings"
            :value="$totalTrainings"
            icon="fas.graduation-cap"
            class="bg-gradient-to-r from-blue-500 to-blue-600 text-white"
        />
        
        <x-stat
            title="Upcoming"
            :value="$upcomingTrainings"
            icon="fas.calendar-plus"
            class="bg-gradient-to-r from-green-500 to-green-600 text-white"
        />
        
        <x-stat
            title="Ongoing"
            :value="$ongoingTrainings"
            icon="fas.play-circle"
            class="bg-gradient-to-r from-yellow-500 to-yellow-600 text-white"
        />
        
        <x-stat
            title="Completed"
            :value="$completedTrainings"
            icon="fas.check-circle"
            class="bg-gradient-to-r from-purple-500 to-purple-600 text-white"
        />
    </div>

    <!-- Filters -->
    <x-card class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <x-input 
                placeholder="Search trainings..." 
                wire:model.live="search"
                icon="fas.search"
            />
            
            <x-select 
                placeholder="Type" 
                wire:model.live="type"
                :options="[
                    ['id' => 'online', 'name' => 'Online'],
                    ['id' => 'classroom', 'name' => 'Classroom'],
                    ['id' => 'workshop', 'name' => 'Workshop'],
                    ['id' => 'conference', 'name' => 'Conference']
                ]"
            />
            
            <x-select 
                placeholder="Year" 
                wire:model.live="year"
                :options="collect(range(now()->year - 2, now()->year + 1))->map(fn($y) => ['id' => $y, 'name' => $y])"
            />
            
            <x-select 
                placeholder="Month" 
                wire:model.live="month"
                :options="collect(range(1, 12))->map(fn($m) => ['id' => $m, 'name' => \Carbon\Carbon::create()->month($m)->format('F')])"
            />
        </div>
    </x-card>

    <!-- Training Cards -->
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
        @foreach($trainings as $training)
            <x-card class="border-l-4 {{ $training->type === 'conference' ? 'border-purple-500' : ($training->type === 'online' ? 'border-blue-500' : 'border-green-500') }}">
                <div class="flex justify-between items-start mb-3">
                    <div class="flex items-center space-x-2">
                        <x-icon 
                            name="{{ $training->type === 'conference' ? 'fas.users' : ($training->type === 'online' ? 'fas.laptop' : 'fas.chalkboard-teacher') }}" 
                            class="w-5 h-5 text-gray-600"
                        />
                        <x-badge 
                            :value="ucfirst($training->type)" 
                            class="badge-{{ $training->type === 'conference' ? 'info' : ($training->type === 'online' ? 'primary' : 'success') }}"
                        />
                    </div>
                    
                    @php
                        $now = now();
                        $startDate = \Carbon\Carbon::parse($training->start_date);
                        $endDate = \Carbon\Carbon::parse($training->end_date);
                        
                        if ($now < $startDate) {
                            $status = 'upcoming';
                            $statusClass = 'warning';
                        } elseif ($now >= $startDate && $now <= $endDate) {
                            $status = 'ongoing';
                            $statusClass = 'success';
                        } else {
                            $status = 'completed';
                            $statusClass = 'info';
                        }
                    @endphp
                    
                    <x-badge 
                        :value="ucfirst($status)" 
                        class="badge-{{ $statusClass }}"
                    />
                </div>

                <h3 class="font-semibold text-lg text-gray-900 mb-2">{{ $training->title }}</h3>
                <p class="text-gray-600 text-sm mb-4 line-clamp-2">{{ $training->description }}</p>

                <div class="space-y-2 mb-4">
                    @if($training->provider)
                        <div class="flex items-center text-sm text-gray-600">
                            <x-icon name="fas.user-tie" class="w-4 h-4 mr-2" />
                            <span>{{ $training->provider }}</span>
                        </div>
                    @endif
                    
                    @if($training->location)
                        <div class="flex items-center text-sm text-gray-600">
                            <x-icon name="fas.map-marker-alt" class="w-4 h-4 mr-2" />
                            <span>{{ $training->location }}</span>
                        </div>
                    @endif
                    
                    @if($training->max_participants)
                        <div class="flex items-center text-sm text-gray-600">
                            <x-icon name="fas.users" class="w-4 h-4 mr-2" />
                            <span>Max {{ $training->max_participants }} participants</span>
                        </div>
                    @endif
                    
                    @if($training->cost)
                        <div class="flex items-center text-sm text-gray-600">
                            <x-icon name="fas.dollar-sign" class="w-4 h-4 mr-2" />
                            <span>${{ number_format($training->cost, 2) }}</span>
                        </div>
                    @endif
                </div>

                <div class="text-xs text-gray-500 space-y-1">
                    <div class="flex justify-between">
                        <span>Start:</span>
                        <span>{{ \Carbon\Carbon::parse($training->start_date)->format('M d, Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>End:</span>
                        <span>{{ \Carbon\Carbon::parse($training->end_date)->format('M d, Y') }}</span>
                    </div>
                    @if($training->is_mandatory)
                        <div class="flex justify-between">
                            <span>Type:</span>
                            <span class="text-red-600 font-medium">Mandatory</span>
                        </div>
                    @endif
                </div>

                <div class="flex justify-between items-center mt-4 pt-4 border-t">
                    <div class="flex space-x-2">
                        <x-button 
                            icon="fas.eye" 
                            link="/hr/trainings/{{ $training->id }}"
                            class="btn-ghost btn-sm"
                            tooltip="View Details"
                        />
                        
                        <x-button 
                            icon="fas.users" 
                            class="btn-ghost btn-sm"
                            tooltip="View Participants"
                        />
                    </div>
                    
                    <x-dropdown>
                        <x-slot:trigger>
                            <x-button icon="fas.ellipsis-v" class="btn-ghost btn-sm" />
                        </x-slot:trigger>
                        
                        <x-menu-item title="Edit Training" link="/hr/trainings/{{ $training->id }}/edit" icon="fas.edit" />
                        <x-menu-item title="Manage Participants" icon="fas.user-plus" />
                        <x-menu-item title="Training Materials" icon="fas.file-alt" />
                        <x-menu-separator />
                        <x-menu-item title="Delete Training" icon="fas.trash" class="text-red-500" />
                    </x-dropdown>
                </div>
            </x-card>
        @endforeach
    </div>

    <!-- Pagination -->
    @if($trainings->hasPages())
    <div class="mt-6">
        {{ $trainings->links() }}
    </div>
    @endif
</div>
