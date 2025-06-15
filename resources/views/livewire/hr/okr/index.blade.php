<?php
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\OkrGoal;
use App\Models\Employee;
use Carbon\Carbon;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $employee_id = '';
    public $type = '';
    public $status = '';
    public $startDate = '';
    public $endDate = '';
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';

    public function mount()
    {
        $this->resetFilters();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function sortByField($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->employee_id = '';
        $this->type = '';
        $this->status = '';
        $this->startDate = '';
        $this->endDate = '';
        $this->resetPage();
    }

    public function with()
    {
        $workspaceId = session('workspace_id');

        $goals = OkrGoal::where('workspace_id', $workspaceId)
            ->with(['employee.user'])
            ->when($this->search, function($query) {
                $query->where(function($q) {
                    $q->where('title', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->employee_id, function($query) {
                $query->where('employee_id', $this->employee_id);
            })
            ->when($this->status, function($query) {
                $query->where('status', $this->status);
            })
            ->when($this->type, function($query) {
                $query->where('type', $this->type);
            })
            ->when($this->startDate, function($query) {
                $query->whereDate('start_date', '>=', $this->startDate);
            })
            ->when($this->endDate, function($query) {
                $query->whereDate('end_date', '<=', $this->endDate);
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(12);

        $employees = Employee::where('workspace_id', $workspaceId)
            ->with('user')
            ->get()
            ->map(fn(Employee$emp) => ['id' => $emp->id, 'name' => $emp->user?->name]);

        // Statistics
        $totalGoals = OkrGoal::where('workspace_id', $workspaceId)->count();
        $activeGoals = OkrGoal::where('workspace_id', $workspaceId)->whereIn('status', ['in_progress', 'on_track'])->count();
        $completedGoals = OkrGoal::where('workspace_id', $workspaceId)->where('status', 'completed')->count();
        $atRiskGoals = OkrGoal::where('workspace_id', $workspaceId)->where('status', 'at_risk')->count();

        return [
            'goals' => $goals,
            'employees' => $employees,
            'totalGoals' => $totalGoals,
            'activeGoals' => $activeGoals,
            'completedGoals' => $completedGoals,
            'atRiskGoals' => $atRiskGoals,
            'sortBy' => $this->sortBy,
            'sortDirection' => $this->sortDirection
        ];
    }
}; ?>

<div>
    <x-header title="OKR Management" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-button label="Create OKR" icon="fas.plus" link="/hr/okr/create" class="btn-primary" />
        </x-slot:middle>
    </x-header>

    <!-- Filters -->
    <x-card class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
            <x-input
                placeholder="Search OKRs..."
                wire:model.live.debounce.300ms="search"
                icon="fas.search"
            />

            <x-select
                placeholder="Employee"
                wire:model.live="employee_id"
                :options="$employees"
            />

            <x-select
                placeholder="Type"
                wire:model.live="type"
                :options="[
                    ['id' => 'objective', 'name' => 'Objective'],
                    ['id' => 'key_result', 'name' => 'Key Result']
                ]"
            />

            <x-select
                placeholder="Status"
                wire:model.live="status"
                :options="[
                    ['id' => 'draft', 'name' => 'Draft'],
                    ['id' => 'active', 'name' => 'Active'],
                    ['id' => 'in_progress', 'name' => 'In Progress'],
                    ['id' => 'completed', 'name' => 'Completed'],
                    ['id' => 'cancelled', 'name' => 'Cancelled']
                ]"
            />

            <x-input
                type="date"
                placeholder="Start Date"
                wire:model.live="startDate"
            />

            <x-input
                type="date"
                placeholder="End Date"
                wire:model.live="endDate"
            />

            <x-button
                label="Reset Filters"
                icon="fas.times"
                wire:click="resetFilters"
                class="btn-ghost"
            />
        </div>
    </x-card>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <x-stat
            title="Total Goals"
            :value="$totalGoals"
            icon="fas.bullseye"
            class="bg-gradient-to-r from-blue-500 to-blue-600 text-white"
        />

        <x-stat
            title="Active Goals"
            :value="$activeGoals"
            icon="fas.play-circle"
            class="bg-gradient-to-r from-green-500 to-green-600 text-white"
        />

        <x-stat
            title="Completed Goals"
            :value="$completedGoals"
            icon="fas.check-circle"
            class="bg-gradient-to-r from-purple-500 to-purple-600 text-white"
        />

        <x-stat
            title="At Risk Goals"
            :value="$atRiskGoals"
            icon="fas.exclamation-triangle"
            class="bg-gradient-to-r from-red-500 to-red-600 text-white"
        />
    </div>

    <!-- OKR Cards Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
        @foreach($goals as $goal)
            <x-card class="border-l-4 {{ $goal->type === 'objective' ? 'border-blue-500' : 'border-green-500' }}">
                <div class="flex justify-between items-start mb-3">
                    <div class="flex items-center space-x-2">
                        <x-icon
                            name="{{ $goal->type === 'objective' ? 'fas.bullseye' : 'fas.key' }}"
                            class="w-5 h-5 {{ $goal->type === 'objective' ? 'text-blue-500' : 'text-green-500' }}"
                        />
                        <x-badge
                            :value="$goal->type === 'objective' ? 'Objective' : 'Key Result'"
                            class="badge-{{ $goal->type === 'objective' ? 'primary' : 'success' }} badge-sm"
                        />
                    </div>

                    <x-dropdown>
                        <x-slot:trigger>
                            <x-button icon="fas.ellipsis-v" class="btn-ghost btn-sm" />
                        </x-slot:trigger>

                        <x-menu-item title="View Details" link="/hr/okr/{{ $goal->id }}" icon="fas.eye" />
                        <x-menu-item title="Edit OKR" link="/hr/okr/{{ $goal->id }}/edit" icon="fas.edit" />
                        <x-menu-separator />
                        <x-menu-item title="View Employee" link="/hr/employees/{{ $goal->employee_id }}" icon="fas.user" />

                        @if($goal->status !== 'completed')
                        <x-menu-separator />
                        <x-menu-item title="Mark Complete" icon="fas.check" />
                        @endif
                    </x-dropdown>
                </div>

                <h3 class="font-semibold text-lg mb-2 line-clamp-2">{{ $goal->title }}</h3>

                @if($goal->description)
                <p class="text-gray-600 text-sm mb-4 line-clamp-3">{{ $goal->description }}</p>
                @endif

                <!-- Employee Info -->
                <div class="flex items-center space-x-2 mb-4">
                    <x-avatar :image="$goal->employee->user->avatar" class="!w-6 !h-6" />
                    <span class="text-sm text-gray-600">{{ $goal->employee->user->name }}</span>
                </div>

                <!-- Progress Bar -->
                <div class="mb-4">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium">Progress</span>
                        <span class="text-sm font-bold">{{ $goal->progress }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div
                            class="bg-gradient-to-r from-blue-500 to-green-500 h-2 rounded-full transition-all duration-300"
                            style="width: {{ $goal->progress }}%"
                        ></div>
                    </div>
                </div>

                <!-- Status and Priority -->
                <div class="flex justify-between items-center mb-4">
                    <x-badge
                        :value="$goal->status"
                        class="badge-{{ $goal->status === 'completed' ? 'success' : ($goal->status === 'active' ? 'info' : ($goal->status === 'in_progress' ? 'warning' : 'ghost')) }}"
                    />

                    <x-badge
                        :value="$goal->priority"
                        class="badge-{{ $goal->priority === 'high' ? 'error' : ($goal->priority === 'medium' ? 'warning' : 'ghost') }}"
                    />
                </div>

                <!-- Dates -->
                <div class="text-xs text-gray-500 space-y-1">
                    <div class="flex justify-between">
                        <span>Start:</span>
                        <span>{{ $goal->start_date->format('M d, Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>End:</span>
                        <span class="{{ $goal->end_date < now() && $goal->status !== 'completed' ? 'text-red-600 font-medium' : '' }}">
                            {{ $goal->end_date->format('M d, Y') }}
                        </span>
                    </div>
                </div>

                <!-- Quick Progress Update -->
                @if($goal->status !== 'completed')
                <x-slot:actions>
                    <div class="flex space-x-1">
                        <x-button
                            label="25%"
                            wire:click="updateProgress({{ $goal->id }}, 25)"
                            class="btn-xs btn-ghost"
                        />
                        <x-button
                            label="50%"
                            wire:click="updateProgress({{ $goal->id }}, 50)"
                            class="btn-xs btn-ghost"
                        />
                        <x-button
                            label="75%"
                            wire:click="updateProgress({{ $goal->id }}, 75)"
                            class="btn-xs btn-ghost"
                        />
                        <x-button
                            label="100%"
                            wire:click="updateProgress({{ $goal->id }}, 100)"
                            class="btn-xs btn-success"
                        />
                    </div>
                </x-slot:actions>
                @endif
            </x-card>
        @endforeach
    </div>

    <!-- Pagination -->
    @if($goals->hasPages())
    <div class="mt-6">
        {{ $goals->links() }}
    </div>
    @endif
</div>
