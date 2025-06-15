<?php

use App\Models\Employee;
use App\Models\PerformanceReview;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $employee_id = '';
    public $status = '';
    public $review_period = '';
    public string $sortBy = 'created_at';
    public $sortDirection = 'desc';

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
        $this->status = '';
        $this->review_period = '';
        $this->resetPage();
    }

    public function with()
    {
        $workspaceId = session('workspace_id');

        $reviews = PerformanceReview::whereHas('employee', function ($query) use ($workspaceId) {
            $query->where('workspace_id', $workspaceId);
        })
            ->with(['employee.user', 'reviewer'])
            ->when($this->search, function ($query) {
                $query->whereHas('employee.user', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                })->orWhere('review_date', 'like', '%' . $this->search . '%');
            })
            ->when($this->employee_id, function ($query) {
                $query->where('employee_id', $this->employee_id);
            })
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })
            ->when($this->review_period, function ($query) {
                $query->whereRaw("YEAR(review_date) = " . substr($this->review_period, 0, 4) . " AND MONTH(review_date) = " . substr($this->review_period, 5, 2));
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(15);

        $employees = Employee::where('workspace_id', $workspaceId)
            ->with('user')
            ->get()
            ->map(fn($emp) => ['id' => $emp->id, 'name' => $emp->user->name]);

        $reviewPeriods = PerformanceReview::whereHas('employee', function ($query) use ($workspaceId) {
            $query->where('workspace_id', $workspaceId);
        })
            ->selectRaw('YEAR(review_date) as year, MONTH(review_date) as month')
            ->distinct()
            ->get()
            ->map(function ($item) {
                return $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);
            })
            ->filter()
            ->sort();

        return [
            'reviews' => $reviews,
            'employees' => $employees,
            'reviewPeriods' => $reviewPeriods,
            'sortDirection' => $this->sortDirection
        ];
    }
}; ?>

<div>
    <x-header title="Performance Reviews" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-button label="Create Review" icon="fas.plus" link="/hr/performance/create" class="btn-primary"/>
        </x-slot:middle>
    </x-header>

    <!-- Filters -->
    <x-card class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <x-input
                placeholder="Search reviews..."
                wire:model.live.debounce.300ms="search"
                icon="fas.search"
            />

            <x-select
                placeholder="Employee"
                wire:model.live="employee_id"
                :options="$employees"
            />

            <x-select
                placeholder="Status"
                wire:model.live="status"
                :options="[
                    ['id' => 'draft', 'name' => 'Draft'],
                    ['id' => 'in_progress', 'name' => 'In Progress'],
                    ['id' => 'completed', 'name' => 'Completed'],
                    ['id' => 'approved', 'name' => 'Approved']
                ]"
            />

            <x-select
                placeholder="Review Period"
                wire:model.live="review_period"
                :options="$reviewPeriods->map(fn($period) => ['id' => $period, 'name' => $period])->toArray()"
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
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <x-stat
            title="Total Reviews"
            :value="$reviews->total()"
            icon="fas.clipboard-list"
            class="bg-gradient-to-r from-blue-500 to-blue-600 text-white"
        />

        <x-stat
            title="Pending Reviews"
            :value="\App\Models\PerformanceReview::whereHas('employee', fn($q) => $q->where('workspace_id', session('workspace_id')))->where('status', 'in_progress')->count()"
            icon="fas.clock"
            class="bg-gradient-to-r from-yellow-500 to-yellow-600 text-white"
        />

        <x-stat
            title="Completed This Month"
            :value="\App\Models\PerformanceReview::whereHas('employee', fn($q) => $q->where('workspace_id', session('workspace_id')))->where('status', 'completed')->whereMonth('updated_at', now()->month)->count()"
            icon="fas.check-circle"
            class="bg-gradient-to-r from-green-500 to-green-600 text-white"
        />

        <x-stat
            title="Overdue Reviews"
            :value="\App\Models\PerformanceReview::whereHas('employee', fn($q) => $q->where('workspace_id', session('workspace_id')))->where('next_review_date', '<', now())->where('status', '!=', 'completed')->count()"
            icon="fas.exclamation-triangle"
            class="bg-gradient-to-r from-red-500 to-red-600 text-white"
        />
    </div>

    <!-- Reviews Table -->
    <x-card>
        <x-table :headers="[
            ['key' => 'employee', 'label' => 'Employee', 'class' => 'w-48'],
            ['key' => 'review_date', 'label' => 'Review Period', 'class' => 'w-32'],
            ['key' => 'reviewer', 'label' => 'Reviewer', 'class' => 'w-40'],
            ['key' => 'overall_rating', 'label' => 'Rating', 'class' => 'w-24'],
            ['key' => 'status', 'label' => 'Status', 'class' => 'w-24'],
            ['key' => 'next_review_date', 'label' => 'Next Review', 'class' => 'w-32'],
            ['key' => 'created_at', 'label' => 'Created', 'class' => 'w-32'],
            ['key' => 'actions', 'label' => 'Actions', 'class' => 'w-32']
        ]" :rows="$reviews" with-pagination>

            @scope('cell_review_date', $header)
            <th wire:click="sortByField('review_date')" class="cursor-pointer hover:bg-gray-50 px-4 py-2 text-left">
                {{ $header['label'] }}
                @if($this->sortBy === 'review_date')
                    <x-icon name="fas.sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}" class="w-3 h-3 inline ml-1"/>
                @endif
            </th>
            @endscope

            @scope('cell_overall_rating', $header)
            <th wire:click="sortByField('overall_rating')" class="cursor-pointer hover:bg-gray-50 px-4 py-2 text-left">
                {{ $header['label'] }}
                @if($this->sortBy === 'overall_rating')
                    <x-icon name="fas.sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}" class="w-3 h-3 inline ml-1"/>
                @endif
            </th>
            @endscope

            @scope('cell_status', $header)
            <th wire:click="sortByField('status')" class="cursor-pointer hover:bg-gray-50 px-4 py-2 text-left">
                {{ $header['label'] }}
                @if($this->sortBy === 'status')
                    <x-icon name="fas.sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}" class="w-3 h-3 inline ml-1"/>
                @endif
            </th>
            @endscope

            @scope('cell_employee', $review)
            <div class="flex items-center space-x-3">
                <x-avatar :image="$review->employee->user->avatar" class="!w-8 !h-8"/>
                <div>
                    <div class="font-medium">{{ $review->employee->user->name }}</div>
                    <div class="text-sm text-gray-500">{{ $review->employee->position }}</div>
                </div>
            </div>
            @endscope

            @scope('cell_review_date', $review)
            <span class="font-medium">{{ $review->review_date->format('Y-m') }}</span>
            @endscope

            @scope('cell_reviewer', $review)
            @if($review->reviewer)
                <div class="flex items-center space-x-2">
                    <x-avatar :image="$review->reviewer->avatar" class="!w-6 !h-6"/>
                    <span class="text-sm">{{ $review->reviewer->name }}</span>
                </div>
            @else
                <span class="text-gray-400">-</span>
            @endif
            @endscope

            @scope('cell_overall_rating', $review)
            @if($review->overall_rating)
                <div class="flex items-center space-x-1">
                    <span class="font-medium">{{ $review->overall_rating }}/5</span>
                    <div class="flex">
                        @for($i = 1; $i <= 5; $i++)
                            <x-icon
                                name="fas.star"
                                class="w-3 h-3 {{ $i <= $review->overall_rating ? 'text-yellow-400' : 'text-gray-300' }}"
                            />
                        @endfor
                    </div>
                </div>
            @else
                <span class="text-gray-400">-</span>
            @endif
            @endscope

            @scope('cell_status', $review)
            <x-badge
                :value="$review->status"
                class="badge-{{ $review->status === 'completed' ? 'success' : ($review->status === 'approved' ? 'info' : ($review->status === 'in_progress' ? 'warning' : 'ghost')) }}"
            />
            @endscope

            @scope('cell_next_review_date', $review)
            @if($review->next_review_date)
                <span class="{{ $review->next_review_date < now() ? 'text-red-600 font-medium' : '' }}">
                        {{ $review->next_review_date->format('M d, Y') }}
                    </span>
            @else
                <span class="text-gray-400">-</span>
            @endif
            @endscope

            @scope('cell_created_at', $review)
            {{ $review->created_at->format('M d, Y') }}
            @endscope

            @scope('cell_actions', $review)
            <div class="flex space-x-1">
                <x-button
                    icon="fas.eye"
                    link="/hr/performance/{{ $review->id }}"
                    class="btn-ghost btn-sm"
                    tooltip="View Review"
                />

                @if($review->status !== 'completed')
                    <x-button
                        icon="fas.edit"
                        link="/hr/performance/{{ $review->id }}/edit"
                        class="btn-ghost btn-sm"
                        tooltip="Edit Review"
                    />
                @endif

                <x-dropdown>
                    <x-slot:trigger>
                        <x-button icon="fas.ellipsis-v" class="btn-ghost btn-sm"/>
                    </x-slot:trigger>

                    <x-menu-item title="View Employee" link="/hr/employees/{{ $review->employee->id }}"
                                 icon="fas.user"/>
                    <x-menu-item title="Employee OKRs" link="/hr/performance/okr?employee={{ $review->employee->id }}"
                                 icon="fas.bullseye"/>

                    @if($review->status === 'completed' && auth()->user()->can('approve-reviews'))
                        <x-menu-separator/>
                        <x-menu-item title="Approve Review" icon="fas.check"/>
                    @endif

                    @if($review->status === 'draft')
                        <x-menu-separator/>
                        <x-menu-item title="Delete Review" icon="fas.trash" class="text-red-600"/>
                    @endif
                </x-dropdown>
            </div>
            @endscope

        </x-table>
    </x-card>
</div>
