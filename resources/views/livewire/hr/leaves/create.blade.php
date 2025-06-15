<?php

use App\Models\LeaveRequest;
use App\Models\Employee;
use App\Models\LeaveType;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public $employee_id = '';
    public $leave_type_id = '';
    public $start_date = '';
    public $end_date = '';
    public $reason = '';
    public $notes = '';
    public $days_requested = 1;

    public function mount()
    {
        $this->start_date = now()->addDays(1)->format('Y-m-d');
        $this->end_date = now()->addDays(1)->format('Y-m-d');
    }

    public function updatedStartDate()
    {
        if ($this->start_date && $this->end_date && $this->start_date > $this->end_date) {
            $this->end_date = $this->start_date;
        }

        $this->calculateDays();
    }

    public function updatedEndDate()
    {
        $this->calculateDays();
    }

    public function calculateDays()
    {
        if ($this->start_date && $this->end_date) {
            $start = \Carbon\Carbon::parse($this->start_date);
            $end = \Carbon\Carbon::parse($this->end_date);
            $this->days_requested = $start->diffInDays($end) + 1;
        }
    }

    public function save()
    {
        $this->validate([
            'employee_id' => 'required|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date|after:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:500',
            'notes' => 'nullable|string|max:1000',
        ]);

        $workspaceId = session('workspace_id');

        // Check if employee belongs to workspace
        $employee = Employee::where('id', $this->employee_id)
            ->where('workspace_id', $workspaceId)
            ->first();

        if (!$employee) {
            $this->error('Invalid employee selection.');
            return;
        }

        LeaveRequest::create([
            'employee_id' => $this->employee_id,
            'leave_type_id' => $this->leave_type_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'days_requested' => $this->days_requested,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'status' => 'pending',
        ]);

        $this->success('Leave request submitted successfully!');
        return redirect()->route('hr.leaves.index');
    }

    public function cancel()
    {
        return redirect()->route('hr.leaves.index');
    }

    public function with()
    {
        $workspaceId = session('workspace_id');

        $employees = Employee::where('workspace_id', $workspaceId)
            ->with('user')
            ->get()
            ->map(fn($emp) => ['id' => $emp->id, 'name' => $emp->user->name . ' - ' . $emp->position]);

        $leaveTypes = LeaveType::all()
            ->map(fn($type) => ['id' => $type->id, 'name' => $type->name]);

        return [
            'employees' => $employees,
            'leaveTypes' => $leaveTypes
        ];
    }
}; ?>

<div>
    <x-header title="Request Leave" separator>
        <x-slot:middle class="!justify-end">
            <x-button label="Cancel" link="{{ route('hr.leaves.index') }}" />
        </x-slot:middle>
    </x-header>

    <x-form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Left Column -->
            <div class="space-y-6">
                <x-card title="Leave Request Details">
                    <div class="space-y-4">
                        <x-select
                            label="Employee"
                            wire:model="employee_id"
                            :options="$employees"
                            placeholder="Select employee"
                            required
                        />

                        <x-select
                            label="Leave Type"
                            wire:model="leave_type_id"
                            :options="$leaveTypes"
                            placeholder="Select leave type"
                            required
                        />

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <x-datetime
                                label="Start Date"
                                wire:model.live="start_date"
                                type="date"
                                required
                            />

                            <x-datetime
                                label="End Date"
                                wire:model.live="end_date"
                                type="date"
                                required
                            />
                        </div>

                        <x-input
                            label="Total Days"
                            wire:model="days_requested"
                            type="number"
                            readonly
                            class="bg-gray-50"
                        />

                        <x-textarea
                            label="Reason"
                            wire:model="reason"
                            placeholder="Please provide the reason for your leave request..."
                            rows="4"
                            required
                        />

                        <x-textarea
                            label="Additional Notes"
                            wire:model="notes"
                            placeholder="Any additional information or special requests..."
                            rows="3"
                        />
                    </div>
                </x-card>
            </div>

            <!-- Right Column -->
            <div class="space-y-6">
                <!-- Leave Request Summary -->
                <x-card title="Request Summary" class="bg-gray-50">
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Employee:</span>
                            <span class="font-medium">
                                {{ $employees->firstWhere('id', $employee_id)['name'] ?? 'Not selected' }}
                            </span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-gray-600">Leave Type:</span>
                            <span class="font-medium">
                                {{ $leaveTypes->firstWhere('id', $leave_type_id)['name'] ?? 'Not selected' }}
                            </span>
                        </div>

                        @if($start_date && $end_date)
                        <div class="flex justify-between">
                            <span class="text-gray-600">Duration:</span>
                            <span class="font-medium">
                                {{ \Carbon\Carbon::parse($start_date)->format('M d') }} -
                                {{ \Carbon\Carbon::parse($end_date)->format('M d, Y') }}
                            </span>
                        </div>
                        @endif

                        <div class="flex justify-between">
                            <span class="text-gray-600">Total Days:</span>
                            <span class="font-medium text-blue-600">{{ $days_requested }} {{ $days_requested == 1 ? 'day' : 'days' }}</span>
                        </div>

                        @if($reason)
                        <div class="pt-3 border-t">
                            <span class="text-gray-600 text-sm">Reason:</span>
                            <p class="text-sm text-gray-900 mt-1">{{ $reason }}</p>
                        </div>
                        @endif
                    </div>
                </x-card>

                <!-- Leave Policy Info -->
                <x-card title="Leave Policy" class="bg-blue-50">
                    <div class="space-y-2 text-sm text-blue-800">
                        <div class="flex items-center space-x-2">
                            <x-icon name="fas.info-circle" class="w-4 h-4" />
                            <span>Leave requests must be submitted at least 24 hours in advance</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <x-icon name="fas.info-circle" class="w-4 h-4" />
                            <span>Manager approval is required for all leave requests</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <x-icon name="fas.info-circle" class="w-4 h-4" />
                            <span>Emergency leave can be requested retroactively</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <x-icon name="fas.info-circle" class="w-4 h-4" />
                            <span>Check your leave balance before submitting</span>
                        </div>
                    </div>
                </x-card>
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Cancel" wire:click="cancel" />
            <x-button label="Submit Request" class="btn-primary" type="submit" spinner="save" />
        </x-slot:actions>
    </x-form>
</div>
