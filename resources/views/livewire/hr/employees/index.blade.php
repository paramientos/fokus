<?php
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Employee;
use App\Models\User;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $department = '';
    public $position = '';
    public $status = '';
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function sortBy($field)
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
        $this->department = '';
        $this->position = '';
        $this->status = '';
        $this->resetPage();
    }

    public function with()
    {
        $workspaceId = session('workspace_id');

        $query = Employee::where('workspace_id', $workspaceId)
            ->with(['user', 'payrolls', 'certifications']);

        // Apply filters
        if ($this->search) {
            $query->whereHas('user', function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            })->orWhere('employee_id', 'like', '%' . $this->search . '%')
              ->orWhere('position', 'like', '%' . $this->search . '%');
        }

        if ($this->department) {
            $query->where('department', $this->department);
        }

        if ($this->position) {
            $query->where('position', $this->position);
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        $employees = $query
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(15);

        $departments = Employee::where('workspace_id', $workspaceId)
            ->distinct()
            ->pluck('department')
            ->filter()
            ->sort();

        $positions = Employee::where('workspace_id', $workspaceId)
            ->distinct()
            ->pluck('position')
            ->filter()
            ->sort();

        return [
            'employees' => $employees,
            'departments' => $departments,
            'positions' => $positions,
            'sortBy' => $this->sortBy,
            'sortDirection' => $this->sortDirection
        ];
    }
}; ?>

<div>
    <x-header title="Employee Management" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-button label="Add Employee" icon="fas.user-plus" link="/hr/employees/create" class="btn-primary" />
        </x-slot:middle>
    </x-header>

    <!-- Filters -->
    <x-card class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <x-input
                placeholder="Search employees..."
                wire:model.live.debounce.300ms="search"
                icon="fas.search"
            />

            <x-select
                placeholder="Department"
                wire:model.live="department"
                :options="$departments->map(fn($dept) => ['id' => $dept, 'name' => $dept])"
            />

            <x-select
                placeholder="Position"
                wire:model.live="position"
                :options="$positions->map(fn($pos) => ['id' => $pos, 'name' => $pos])"
            />

            <x-select
                placeholder="Status"
                wire:model.live="status"
                :options="[
                    ['id' => 'active', 'name' => 'Active'],
                    ['id' => 'inactive', 'name' => 'Inactive']
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

    <!-- Employee Table -->
    <x-card>
        <x-table :headers="[
            ['key' => 'employee_id', 'label' => 'Employee ID', 'class' => 'w-32'],
            ['key' => 'name', 'label' => 'Name', 'class' => 'w-48'],
            ['key' => 'email', 'label' => 'Email', 'class' => 'w-64'],
            ['key' => 'department', 'label' => 'Department', 'class' => 'w-40'],
            ['key' => 'position', 'label' => 'Position', 'class' => 'w-40'],
            ['key' => 'hire_date', 'label' => 'Hire Date', 'class' => 'w-32'],
            ['key' => 'salary', 'label' => 'Salary', 'class' => 'w-32'],
            ['key' => 'status', 'label' => 'Status', 'class' => 'w-24'],
            ['key' => 'actions', 'label' => 'Actions', 'class' => 'w-32']
        ]" :rows="$employees" with-pagination>

            @scope('cell_employee_id', $header)
                <th wire:click="sortBy('employee_id')" class="cursor-pointer hover:bg-gray-50 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {{ $header['label'] }}
                    @if($this->sortBy === 'employee_id')
                        <x-icon name="{{ $sortDirection === 'asc' ? 'fas.sort-up' : 'fas.sort-down' }}" class="w-3 h-3 inline ml-1" />
                    @else
                        <x-icon name="fas.sort" class="w-3 h-3 inline ml-1 opacity-50" />
                    @endif
                </th>
            @endscope

            @scope('cell_name', $header)
                <th wire:click="sortBy('user.name')" class="cursor-pointer hover:bg-gray-50 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {{ $header['label'] }}
                    @if($this->sortBy === 'user.name')
                        <x-icon name="{{ $sortDirection === 'asc' ? 'fas.sort-up' : 'fas.sort-down' }}" class="w-3 h-3 inline ml-1" />
                    @else
                        <x-icon name="fas.sort" class="w-3 h-3 inline ml-1 opacity-50" />
                    @endif
                </th>
            @endscope

            @scope('cell_email', $header)
                <th wire:click="sortBy('user.email')" class="cursor-pointer hover:bg-gray-50 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {{ $header['label'] }}
                    @if($this->sortBy === 'user.email')
                        <x-icon name="{{ $sortDirection === 'asc' ? 'fas.sort-up' : 'fas.sort-down' }}" class="w-3 h-3 inline ml-1" />
                    @else
                        <x-icon name="fas.sort" class="w-3 h-3 inline ml-1 opacity-50" />
                    @endif
                </th>
            @endscope

            @scope('cell_department', $header)
                <th wire:click="sortBy('department')" class="cursor-pointer hover:bg-gray-50 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {{ $header['label'] }}
                    @if($this->sortBy === 'department')
                        <x-icon name="{{ $sortDirection === 'asc' ? 'fas.sort-up' : 'fas.sort-down' }}" class="w-3 h-3 inline ml-1" />
                    @else
                        <x-icon name="fas.sort" class="w-3 h-3 inline ml-1 opacity-50" />
                    @endif
                </th>
            @endscope

            @scope('cell_position', $header)
                <th wire:click="sortBy('position')" class="cursor-pointer hover:bg-gray-50 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {{ $header['label'] }}
                    @if($this->sortBy === 'position')
                        <x-icon name="{{ $sortDirection === 'asc' ? 'fas.sort-up' : 'fas.sort-down' }}" class="w-3 h-3 inline ml-1" />
                    @else
                        <x-icon name="fas.sort" class="w-3 h-3 inline ml-1 opacity-50" />
                    @endif
                </th>
            @endscope

            @scope('cell_hire_date', $header)
                <th wire:click="sortBy('hire_date')" class="cursor-pointer hover:bg-gray-50 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {{ $header['label'] }}
                    @if($this->sortBy === 'hire_date')
                        <x-icon name="{{ $sortDirection === 'asc' ? 'fas.sort-up' : 'fas.sort-down' }}" class="w-3 h-3 inline ml-1" />
                    @else
                        <x-icon name="fas.sort" class="w-3 h-3 inline ml-1 opacity-50" />
                    @endif
                </th>
            @endscope

            @scope('cell_status', $header)
                <th wire:click="sortBy('status')" class="cursor-pointer hover:bg-gray-50 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {{ $header['label'] }}
                    @if($this->sortBy === 'status')
                        <x-icon name="{{ $sortDirection === 'asc' ? 'fas.sort-up' : 'fas.sort-down' }}" class="w-3 h-3 inline ml-1" />
                    @else
                        <x-icon name="fas.sort" class="w-3 h-3 inline ml-1 opacity-50" />
                    @endif
                </th>
            @endscope

            @scope('cell_employee_id', $employee)
                <span class="font-mono text-sm">{{ $employee->employee_id }}</span>
            @endscope

            @scope('cell_name', $employee)
                <div class="flex items-center space-x-3">
                    <x-avatar :image="$employee->user->avatar" class="!w-8 !h-8" />
                    <div>
                        <div class="font-medium">{{ $employee->user->name }}</div>
                        <div class="text-sm text-gray-500">{{ $employee->position }}</div>
                    </div>
                </div>
            @endscope

            @scope('cell_email', $employee)
                <a href="mailto:{{ $employee->user->email }}" class="text-blue-600 hover:underline">
                    {{ $employee->user->email }}
                </a>
            @endscope

            @scope('cell_department', $employee)
                <x-badge :value="$employee->department" class="badge-outline" />
            @endscope

            @scope('cell_position', $employee)
                {{ $employee->position }}
            @endscope

            @scope('cell_hire_date', $employee)
                {{ $employee->hire_date?->format('M d, Y') }}
            @endscope

            @scope('cell_salary', $employee)
                @if($employee->latestPayroll)
                    <span class="font-medium">₺{{ number_format($employee->latestPayroll->base_salary, 0) }}</span>
                @else
                    <span class="text-gray-400">-</span>
                @endif
            @endscope

            @scope('cell_status', $employee)
                @if($employee->status === 'active')
                    <x-badge value="Active" class="badge-success" />
                @else
                    <x-badge value="Inactive" class="badge-error" />
                @endif
            @endscope

            @scope('cell_actions', $employee)
                <div class="flex space-x-1">
                    <x-button
                        icon="fas.eye"
                        link="/hr/employees/{{ $employee->id }}"
                        class="btn-ghost btn-sm"
                        tooltip="View Details"
                    />
                    <x-button
                        icon="fas.edit"
                        link="/hr/employees/{{ $employee->id }}/edit"
                        class="btn-ghost btn-sm"
                        tooltip="Edit Employee"
                    />
                    <x-dropdown>
                        <x-slot:trigger>
                            <x-button icon="fas.ellipsis-v" class="btn-ghost btn-sm" />
                        </x-slot:trigger>

                        <x-menu-item title="Performance Reviews" link="/hr/performance?employee={{ $employee->id }}" icon="fas.clipboard-check" />
                        <x-menu-item title="Leave History" link="/hr/leaves?employee={{ $employee->id }}" icon="fas.calendar-times" />
                        <x-menu-item title="Payroll History" link="/hr/payroll?employee={{ $employee->id }}" icon="fas.money-bill-wave" />
                        <x-menu-item title="Certifications" link="/hr/certifications?employee={{ $employee->id }}" icon="fas.certificate" />
                        <x-menu-separator />
                        <x-menu-item title="OKR Goals" link="/hr/performance/okr?employee={{ $employee->id }}" icon="fas.bullseye" />
                        <x-menu-item title="Training History" link="/hr/trainings?employee={{ $employee->id }}" icon="fas.graduation-cap" />
                    </x-dropdown>
                </div>
            @endscope

        </x-table>
    </x-card>
</div>
