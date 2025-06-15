<?php
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\LeaveRequest;
use App\Models\Employee;
use App\Models\LeaveType;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $employee_id = '';
    public $leave_type_id = '';
    public $status = '';
    public $year = '';
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';

    public function mount()
    {
        $this->year = now()->year;
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
        $this->leave_type_id = '';
        $this->status = '';
        $this->resetPage();
    }

    public function approveLeave($leaveId)
    {
        $leave = LeaveRequest::findOrFail($leaveId);
        $leave->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now()
        ]);
        $this->success('Leave request approved successfully!');
    }

    public function rejectLeave($leaveId)
    {
        $leave = LeaveRequest::findOrFail($leaveId);
        $leave->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now()
        ]);
        $this->error('Leave request rejected.');
    }

    public function with()
    {
        $workspaceId = session('workspace_id');

        $leaves = LeaveRequest::whereHas('employee', function($query) use ($workspaceId) {
                $query->where('workspace_id', $workspaceId);
            })
            ->with(['employee.user', 'leaveType', 'approver'])
            ->when($this->search, function($query) {
                $query->whereHas('employee.user', function($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                })->orWhere('reason', 'like', '%' . $this->search . '%');
            })
            ->when($this->employee_id, function($query) {
                $query->where('employee_id', $this->employee_id);
            })
            ->when($this->leave_type_id, function($query) {
                $query->where('leave_type_id', $this->leave_type_id);
            })
            ->when($this->status, function($query) {
                $query->where('status', $this->status);
            })
            ->when($this->year, function($query) {
                $query->whereYear('start_date', $this->year);
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(15);

        $employees = Employee::where('workspace_id', $workspaceId)
            ->with('user')
            ->get()
            ->map(fn($emp) => ['id' => $emp->id, 'name' => $emp->user->name]);

        $leaveTypes = LeaveType::all()
            ->map(fn($type) => ['id' => $type->id, 'name' => $type->name]);

        $totalLeaves = LeaveRequest::whereHas('employee', fn($q) => $q->where('workspace_id', session('workspace_id')))->count();
        $pendingLeaves = LeaveRequest::whereHas('employee', fn($q) => $q->where('workspace_id', session('workspace_id')))->where('status', 'pending')->count();
        $approvedLeaves = LeaveRequest::whereHas('employee', fn($q) => $q->where('workspace_id', session('workspace_id')))->where('status', 'approved')->count();
        $rejectedLeaves = LeaveRequest::whereHas('employee', fn($q) => $q->where('workspace_id', session('workspace_id')))->where('status', 'rejected')->count();

        return [
            'leaves' => $leaves,
            'employees' => $employees,
            'leaveTypes' => $leaveTypes,
            'totalLeaves' => $totalLeaves,
            'pendingLeaves' => $pendingLeaves,
            'approvedLeaves' => $approvedLeaves,
            'rejectedLeaves' => $rejectedLeaves,
            'sortBy' => $this->sortBy,
            'sortDirection' => $this->sortDirection
        ];
    }
}; ?>

<div>
    <x-header title="Leave Management" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-button label="Request Leave" icon="fas.plus" link="/hr/leaves/create" class="btn-primary" />
        </x-slot:middle>
    </x-header>

    <!-- Filters -->
    <x-card class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
            <x-input
                placeholder="Search leaves..."
                wire:model.live.debounce.300ms="search"
                icon="fas.search"
            />

            <x-select
                placeholder="Employee"
                wire:model.live="employee_id"
                :options="$employees"
            />

            <x-select
                placeholder="Leave Type"
                wire:model.live="leave_type_id"
                :options="$leaveTypes"
            />

            <x-select
                placeholder="Status"
                wire:model.live="status"
                :options="[
                    ['id' => 'pending', 'name' => 'Pending'],
                    ['id' => 'approved', 'name' => 'Approved'],
                    ['id' => 'rejected', 'name' => 'Rejected'],
                    ['id' => 'cancelled', 'name' => 'Cancelled']
                ]"
            />

            <x-select
                placeholder="Year"
                wire:model.live="year"
                :options="[
                    ['id' => '2024', 'name' => '2024'],
                    ['id' => '2025', 'name' => '2025'],
                    ['id' => '2026', 'name' => '2026']
                ]"
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
            title="Total Requests"
            :value="$totalLeaves"
            icon="fas.calendar-alt"
            class="bg-gradient-to-r from-blue-500 to-blue-600 text-white"
        />

        <x-stat
            title="Pending Approval"
            :value="$pendingLeaves"
            icon="fas.clock"
            class="bg-gradient-to-r from-yellow-500 to-yellow-600 text-white"
        />

        <x-stat
            title="Approved This Month"
            :value="\App\Models\LeaveRequest::whereHas('employee', fn($q) => $q->where('workspace_id', session('workspace_id')))->where('status', 'approved')->whereMonth('start_date', now()->month)->count()"
            icon="fas.check-circle"
            class="bg-gradient-to-r from-green-500 to-green-600 text-white"
        />

        <x-stat
            title="Days Used This Year"
            :value="\App\Models\LeaveRequest::whereHas('employee', fn($q) => $q->where('workspace_id', session('workspace_id')))->where('status', 'approved')->whereYear('start_date', now()->year)->sum('days_requested')"
            icon="fas.calendar-times"
            class="bg-gradient-to-r from-purple-500 to-purple-600 text-white"
        />
    </div>

    <!-- Leave Requests Table -->
    <x-card>
        <x-table :headers="[
            ['key' => 'employee', 'label' => 'Employee', 'class' => 'w-48'],
            ['key' => 'leave_type', 'label' => 'Leave Type', 'class' => 'w-32'],
            ['key' => 'dates', 'label' => 'Dates', 'class' => 'w-40'],
            ['key' => 'days_requested', 'label' => 'Days', 'class' => 'w-20'],
            ['key' => 'reason', 'label' => 'Reason', 'class' => 'w-64'],
            ['key' => 'status', 'label' => 'Status', 'class' => 'w-24'],
            ['key' => 'approver', 'label' => 'Approver', 'class' => 'w-32'],
            ['key' => 'actions', 'label' => 'Actions', 'class' => 'w-32']
        ]" :rows="$leaves" with-pagination>

            @scope('cell_dates', $header)
                <th wire:click="sortByField('start_date')" class="cursor-pointer hover:bg-gray-50 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {{ $header['label'] }}
                    @if($this->sortBy === 'start_date')
                        <x-icon name="fas.sort-{{ $this->sortDirection === 'asc' ? 'up' : 'down' }}" class="w-3 h-3 inline ml-1"/>
                    @else
                        <x-icon name="fas.sort" class="w-3 h-3 inline ml-1 opacity-50" />
                    @endif
                </th>
            @endscope

            @scope('cell_days_requested', $header)
                <th wire:click="sortByField('days_requested')" class="cursor-pointer hover:bg-gray-50 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {{ $header['label'] }}
                    @if($this->sortBy === 'days_requested')
                        <x-icon name="fas.sort-{{ $this->sortDirection === 'asc' ? 'up' : 'down' }}" class="w-3 h-3 inline ml-1"/>
                    @else
                        <x-icon name="fas.sort" class="w-3 h-3 inline ml-1 opacity-50" />
                    @endif
                </th>
            @endscope

            @scope('cell_employee', $leave)
                <div class="flex items-center space-x-3">
                    <x-avatar :image="$leave->employee->user->avatar" class="!w-8 !h-8" />
                    <div>
                        <div class="font-medium">{{ $leave->employee->user->name }}</div>
                        <div class="text-sm text-gray-500">{{ $leave->employee->position }}</div>
                    </div>
                </div>
            @endscope

            @scope('cell_leave_type', $leave)
                <x-badge
                    :value="$leave->leaveType->name ?? $leave->leave_type"
                    class="badge-outline"
                />
            @endscope

            @scope('cell_dates', $leave)
                <div class="text-sm">
                    <div>{{ $leave->start_date->format('M d, Y') }}</div>
                    <div class="text-gray-500">{{ $leave->end_date->format('M d, Y') }}</div>
                </div>
            @endscope

            @scope('cell_days_requested', $leave)
                <span class="font-medium">{{ $leave->days_requested }}</span>
            @endscope

            @scope('cell_reason', $leave)
                <div class="max-w-xs">
                    <p class="text-sm line-clamp-2">{{ $leave->reason }}</p>
                </div>
            @endscope

            @scope('cell_status', $leave)
                <x-badge
                    :value="$leave->status"
                    class="badge-{{ $leave->status === 'approved' ? 'success' : ($leave->status === 'rejected' ? 'error' : ($leave->status === 'cancelled' ? 'ghost' : 'warning')) }}"
                />
            @endscope

            @scope('cell_approver', $leave)
                @if($leave->approver)
                    <div class="flex items-center space-x-2">
                        <x-avatar :image="$leave->approver->avatar" class="!w-6 !h-6" />
                        <span class="text-sm">{{ $leave->approver->name }}</span>
                    </div>
                @else
                    <span class="text-gray-400">-</span>
                @endif
            @endscope

            @scope('cell_actions', $leave)
                <div class="flex space-x-1">
                    <x-button
                        icon="fas.eye"
                        link="/hr/leaves/{{ $leave->id }}"
                        class="btn-ghost btn-sm"
                        tooltip="View Details"
                    />

                    @if($leave->status === 'pending')
                        <x-button
                            icon="fas.check"
                            wire:click="approveLeave({{ $leave->id }})"
                            class="btn-success btn-sm"
                            tooltip="Approve"
                        />

                        <x-button
                            icon="fas.times"
                            wire:click="rejectLeave({{ $leave->id }})"
                            class="btn-error btn-sm"
                            tooltip="Reject"
                        />
                    @endif

                    <x-dropdown>
                        <x-slot:trigger>
                            <x-button icon="fas.ellipsis-v" class="btn-ghost btn-sm" />
                        </x-slot:trigger>

                        <x-menu-item title="View Employee" link="/hr/employees/{{ $leave->employee->id }}" icon="fas.user" />
                        <x-menu-item title="Employee Leave History" link="/hr/leaves?employee={{ $leave->employee->id }}" icon="fas.history" />

                        @if($leave->status === 'pending')
                        <x-menu-separator />
                        <x-menu-item title="Edit Request" link="/hr/leaves/{{ $leave->id }}/edit" icon="fas.edit" />
                        @endif

                        @if(in_array($leave->status, ['pending', 'approved']) && $leave->start_date > now())
                        <x-menu-separator />
                        <x-menu-item title="Cancel Request" icon="fas.ban" class="text-red-600" />
                        @endif
                    </x-dropdown>
                </div>
            @endscope

        </x-table>
    </x-card>
</div>
