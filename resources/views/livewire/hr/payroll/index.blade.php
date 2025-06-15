<?php

use App\Models\Employee;
use App\Models\Payroll;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $employee_id = '';
    public $status = '';
    public $month = '';
    public $year = '';
    public $sortBy = 'payroll_period';
    public $sortDirection = 'desc';

    public function mount()
    {
        $this->month = now()->month;
        $this->year = now()->year;
    }

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
        $this->employee_id = '';
        $this->status = '';
        $this->month = '';
        $this->year = '';
        $this->resetPage();
    }

    public function approvePayroll($payrollId)
    {
        try {
            $payroll = Payroll::where('workspace_id', session('workspace_id'))
                ->where('id', $payrollId)
                ->where('status', 'draft')
                ->firstOrFail();

            $payroll->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => auth()->id()
            ]);

            $this->success('Payroll approved successfully!');
        } catch (\Exception $e) {
            $this->error('Failed to approve payroll: ' . $e->getMessage());
        }
    }

    public function processPayroll($payrollId)
    {
        try {
            $payroll = Payroll::where('workspace_id', session('workspace_id'))
                ->where('id', $payrollId)
                ->where('status', 'approved')
                ->firstOrFail();

            $payroll->update([
                'status' => 'paid'
            ]);

            $this->success('Payroll processed successfully!');
        } catch (\Exception $e) {
            $this->error('Failed to process payroll: ' . $e->getMessage());
        }
    }

    public function deletePayroll($payrollId)
    {
        try {
            $payroll = Payroll::where('workspace_id', session('workspace_id'))
                ->where('id', $payrollId)
                ->where('status', 'draft')
                ->firstOrFail();

            $payroll->delete();

            $this->success('Payroll deleted successfully!');
        } catch (\Exception $e) {
            $this->error('Failed to delete payroll: ' . $e->getMessage());
        }
    }

    public function with()
    {
        $workspaceId = session('workspace_id');

        $totalPayrolls = Payroll::where('workspace_id', $workspaceId)->count();
        $thisMonthPayrolls = Payroll::where('workspace_id', $workspaceId)
            ->where('payroll_period', now()->format('Y-m'))
            ->count();
        $pendingApprovals = Payroll::where('workspace_id', $workspaceId)
            ->where('status', 'draft')
            ->count();
        $totalPaidAmount = Payroll::where('workspace_id', $workspaceId)
            ->where('status', 'paid')
            ->sum('net_pay');

        $payrolls = Payroll::where('workspace_id', $workspaceId)
            ->with(['employee.user'])
            ->when($this->search, function ($query) {
                $query->whereHas('employee.user', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->employee_id, function ($query) {
                $query->where('employee_id', $this->employee_id);
            })
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })
            ->when($this->month, function ($query) {
                $query->where('payroll_period', 'like', now()->year . '-' . str_pad($this->month, 2, '0', STR_PAD_LEFT) . '%');
            })
            ->when($this->year, function ($query) {
                $query->where('payroll_period', 'like', $this->year . '%');
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(15);

        $employees = Employee::where('workspace_id', $workspaceId)
            ->with('user')
            ->get()
            ->map(fn(Employee $emp) => ['id' => $emp->id, 'name' => $emp->user?->name]);

        return [
            'payrolls' => $payrolls,
            'employees' => $employees,
            'totalPayrolls' => $totalPayrolls,
            'thisMonthPayrolls' => $thisMonthPayrolls,
            'pendingApprovals' => $pendingApprovals,
            'totalPaidAmount' => $totalPaidAmount,
            'sortBy' => $this->sortBy,
            'sortDirection' => $this->sortDirection
        ];
    }
}; ?>

<div>
    <x-header title="Payroll Management" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-button label="Process Payroll" icon="fas.plus" link="/hr/payroll/create" class="btn-primary"/>
        </x-slot:middle>
    </x-header>

    <!-- Filters -->
    <x-card class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
            <x-input
                placeholder="Search payrolls..."
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
                    ['id' => 'pending', 'name' => 'Pending'],
                    ['id' => 'approved', 'name' => 'Approved'],
                    ['id' => 'processed', 'name' => 'Processed'],
                    ['id' => 'rejected', 'name' => 'Rejected']
                ]"
            />

            <x-select
                placeholder="Month"
                wire:model.live="month"
                :options="[
                    ['id' => '1', 'name' => 'January'],
                    ['id' => '2', 'name' => 'February'],
                    ['id' => '3', 'name' => 'March'],
                    ['id' => '4', 'name' => 'April'],
                    ['id' => '5', 'name' => 'May'],
                    ['id' => '6', 'name' => 'June'],
                    ['id' => '7', 'name' => 'July'],
                    ['id' => '8', 'name' => 'August'],
                    ['id' => '9', 'name' => 'September'],
                    ['id' => '10', 'name' => 'October'],
                    ['id' => '11', 'name' => 'November'],
                    ['id' => '12', 'name' => 'December']
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
            title="Total Payrolls"
            :value="$totalPayrolls"
            icon="fas.money-bill-wave"
            class="bg-gradient-to-r from-blue-500 to-blue-600 text-white"
        />

        <x-stat
            title="Pending Approval"
            :value="$pendingApprovals"
            icon="fas.clock"
            class="bg-gradient-to-r from-yellow-500 to-yellow-600 text-white"
        />

        <x-stat
            title="Processed This Month"
            :value="$thisMonthPayrolls"
            icon="fas.check-circle"
            class="bg-gradient-to-r from-green-500 to-green-600 text-white"
        />

        <x-stat
            title="Total Amount This Month"
            :value="'₺' . number_format($totalPaidAmount, 0)"
            icon="fas.calculator"
            class="bg-gradient-to-r from-purple-500 to-purple-600 text-white"
        />
    </div>

    <!-- Payroll Table -->
    <x-card>
        <x-table :headers="[
            ['key' => 'employee', 'label' => 'Employee', 'class' => 'w-48'],
            ['key' => 'period', 'label' => 'Pay Period', 'class' => 'w-40'],
            ['key' => 'base_salary', 'label' => 'Base Salary', 'class' => 'w-32'],
            ['key' => 'allowances', 'label' => 'Allowances', 'class' => 'w-32'],
            ['key' => 'deductions', 'label' => 'Deductions', 'class' => 'w-32'],
            ['key' => 'net_pay', 'label' => 'Net Pay', 'class' => 'w-32'],
            ['key' => 'status', 'label' => 'Status', 'class' => 'w-24'],
            ['key' => 'actions', 'label' => 'Actions', 'class' => 'w-32']
        ]" :rows="$payrolls" with-pagination>

            @scope('cell_base_salary', $header)
            <th wire:click="sortBy('base_salary')" class="cursor-pointer hover:bg-gray-50 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ $header['label'] }}
                @if($this->sortBy === 'base_salary')
                    <x-icon name="fas.sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}" class="w-3 h-3 inline ml-1"/>
                @else
                    <x-icon name="fas.sort" class="w-3 h-3 inline ml-1 opacity-50" />
                @endif
            </th>
            @endscope

            @scope('cell_net_pay', $header)
            <th wire:click="sortBy('net_pay')" class="cursor-pointer hover:bg-gray-50 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ $header['label'] }}
                @if($this->sortBy === 'net_pay')
                    <x-icon name="fas.sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}" class="w-3 h-3 inline ml-1"/>
                @else
                    <x-icon name="fas.sort" class="w-3 h-3 inline ml-1 opacity-50" />
                @endif
            </th>
            @endscope

            @scope('cell_employee', $payroll)
            <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                        <x-icon name="fas.user" class="w-4 h-4 text-blue-600" />
                    </div>
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-900">{{ $payroll->employee->user->name }}</div>
                    <div class="text-sm text-gray-500">{{ $payroll->employee->position ?? 'N/A' }}</div>
                </div>
            </div>
            @endscope

            @scope('cell_period', $payroll)
            <span class="text-sm text-gray-900">{{ $payroll->payroll_period }}</span>
            @endscope

            @scope('cell_base_salary', $payroll)
            <span class="font-medium text-blue-600">₺{{ number_format($payroll->base_salary, 0) }}</span>
            @endscope

            @scope('cell_allowances', $payroll)
            <span class="font-medium text-green-600">₺{{ number_format($payroll->allowances, 0) }}</span>
            @endscope

            @scope('cell_deductions', $payroll)
            <span class="font-medium text-red-600">₺{{ number_format($payroll->total_deductions, 0) }}</span>
            @endscope

            @scope('cell_net_pay', $payroll)
            <span class="font-bold text-purple-600">₺{{ number_format($payroll->net_pay, 0) }}</span>
            @endscope

            @scope('cell_status', $payroll)
            <x-badge
                :value="$payroll->status"
                class="badge-{{ $payroll->status === 'paid' ? 'success' : ($payroll->status === 'approved' ? 'info' : ($payroll->status === 'cancelled' ? 'error' : 'warning')) }}"
            />
            @endscope

            @scope('cell_actions', $payroll)
            <div class="flex space-x-1">
                <x-button
                    icon="fas.eye"
                    link="/hr/payroll/{{ $payroll->id }}"
                    class="btn-ghost btn-sm"
                    tooltip="View Details"
                />

                @if($payroll->status === 'draft')
                    <x-button
                        icon="fas.check"
                        wire:click="approvePayroll({{ $payroll->id }})"
                        class="btn-success btn-sm"
                        tooltip="Approve"
                    />
                @endif

                @if($payroll->status === 'approved')
                    <x-button
                        icon="fas.play"
                        wire:click="processPayroll({{ $payroll->id }})"
                        class="btn-primary btn-sm"
                        tooltip="Process"
                    />
                @endif

                <x-dropdown>
                    <x-slot:trigger>
                        <x-button icon="fas.ellipsis-v" class="btn-ghost btn-sm"/>
                    </x-slot:trigger>

                    <x-menu-item title="View Employee" link="/hr/employees/{{ $payroll->employee->id }}"
                                 icon="fas.user"/>
                    <x-menu-item title="Download Payslip" icon="fas.download"/>
                    <x-menu-item title="Email Payslip" icon="fas.envelope"/>

                    @if($payroll->status === 'draft')
                        <x-menu-separator />
                        <x-menu-item title="Edit" link="/hr/payroll/{{ $payroll->id }}/edit" icon="fas.edit"/>
                        <x-menu-item title="Delete" wire:click="deletePayroll({{ $payroll->id }})"
                                     icon="fas.trash" class="text-red-500"/>
                    @endif
                </x-dropdown>
            </div>
            @endscope

        </x-table>
    </x-card>
</div>
