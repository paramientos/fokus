<?php

use Livewire\Volt\Component;
use App\Models\Employee;
use App\Models\User;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public Employee $employee;
    public $name = '';
    public $email = '';
    public $phone = '';
    public $position = '';
    public $department = '';
    public $hire_date = '';
    public $salary = '';
    public $status = 'active';
    public $address = '';
    public $emergency_contact = '';
    public $notes = '';

    public function mount(Employee $employee)
    {
        // Check workspace access
        if ($employee->workspace_id !== session('workspace_id')) {
            abort(403);
        }

        $this->employee = $employee->load('user');
        $this->name = $employee->user->name;
        $this->email = $employee->user->email;
        $this->phone = $employee->phone;
        $this->position = $employee->position;
        $this->department = $employee->department;
        $this->hire_date = $employee->hire_date->format('Y-m-d');
        $this->salary = $employee->salary;
        $this->status = $employee->status;
        $this->address = $employee->address;
        $this->emergency_contact = $employee->emergency_contact;
        $this->notes = $employee->notes;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->employee->user->id,
            'phone' => 'nullable|string|max:20',
            'position' => 'required|string|max:100',
            'department' => 'required|string|max:100',
            'hire_date' => 'required|date',
            'salary' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive,terminated',
            'address' => 'nullable|string|max:500',
            'emergency_contact' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Update user information
        $this->employee->user->update([
            'name' => $this->name,
            'email' => $this->email,
        ]);

        // Update employee information
        $this->employee->update([
            'phone' => $this->phone,
            'position' => $this->position,
            'department' => $this->department,
            'hire_date' => $this->hire_date,
            'salary' => $this->salary ?: null,
            'status' => $this->status,
            'address' => $this->address,
            'emergency_contact' => $this->emergency_contact,
            'notes' => $this->notes,
        ]);

        $this->success('Employee updated successfully!');
        return redirect()->route('hr.employees.show', $this->employee);
    }

    public function cancel()
    {
        return redirect()->route('hr.employees.show', $this->employee);
    }
};
?>

<div>
    <x-header title="Edit Employee" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-button label="Cancel" icon="fas.times" wire:click="cancel" class="btn-ghost" />
        </x-slot:middle>
    </x-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Form -->
        <div class="lg:col-span-2">
            <div class="space-y-6">
                <x-card title="Personal Information">
                    <div class="space-y-4">
                        <x-input
                            label="Full Name"
                            wire:model="name"
                            placeholder="Enter full name..."
                            required
                        />

                        <x-input
                            label="Email"
                            wire:model="email"
                            type="email"
                            placeholder="Enter email address..."
                            required
                        />

                        <x-input
                            label="Phone"
                            wire:model="phone"
                            placeholder="Enter phone number..."
                        />

                        <x-textarea
                            label="Address"
                            wire:model="address"
                            placeholder="Enter full address..."
                            rows="3"
                        />

                        <x-input
                            label="Emergency Contact"
                            wire:model="emergency_contact"
                            placeholder="Emergency contact person and phone..."
                        />
                    </div>
                </x-card>

                <x-card title="Employment Details">
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <x-input
                                label="Position"
                                wire:model="position"
                                placeholder="Enter job position..."
                                required
                            />

                            <x-input
                                label="Department"
                                wire:model="department"
                                placeholder="Enter department..."
                                required
                            />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <x-datetime
                                label="Hire Date"
                                wire:model="hire_date"
                                type="date"
                                required
                            />

                            <x-select
                                label="Status"
                                wire:model="status"
                                :options="[
                                    ['id' => 'active', 'name' => 'Active'],
                                    ['id' => 'inactive', 'name' => 'Inactive'],
                                    ['id' => 'terminated', 'name' => 'Terminated']
                                ]"
                                required
                            />
                        </div>

                        <x-input
                            label="Salary"
                            wire:model="salary"
                            type="number"
                            step="0.01"
                            placeholder="Enter monthly salary..."
                        />
                    </div>
                </x-card>

                <x-card title="Additional Notes">
                    <x-textarea
                        label="Notes"
                        wire:model="notes"
                        placeholder="Any additional notes about the employee..."
                        rows="4"
                    />
                </x-card>
            </div>
        </div>

        <!-- Summary Sidebar -->
        <div class="lg:col-span-1">
            <x-card title="Employee Summary" class="sticky top-6">
                <div class="space-y-4">
                    <div class="text-center">
                        <div class="w-20 h-20 bg-gray-300 rounded-full mx-auto mb-3 flex items-center justify-center">
                            <x-icon name="fas.user" class="w-8 h-8 text-gray-600" />
                        </div>
                        <h3 class="font-semibold text-lg">{{ $name ?: 'Employee Name' }}</h3>
                        <p class="text-gray-600">{{ $position ?: 'Position' }}</p>
                    </div>

                    <div class="space-y-3 pt-4 border-t">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Department:</span>
                            <span class="font-medium">{{ $department ?: 'Not set' }}</span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-gray-600">Status:</span>
                            <span class="font-medium capitalize
                                {{ $status === 'active' ? 'text-green-600' :
                                   ($status === 'inactive' ? 'text-yellow-600' : 'text-red-600') }}">
                                {{ $status }}
                            </span>
                        </div>

                        @if($hire_date)
                        <div class="flex justify-between">
                            <span class="text-gray-600">Hire Date:</span>
                            <span class="font-medium">{{ \Carbon\Carbon::parse($hire_date)->format('M d, Y') }}</span>
                        </div>
                        @endif

                        @if($salary)
                        <div class="flex justify-between">
                            <span class="text-gray-600">Salary:</span>
                            <span class="font-medium">${{ number_format($salary, 2) }}</span>
                        </div>
                        @endif

                        @if($email)
                        <div class="flex justify-between">
                            <span class="text-gray-600">Email:</span>
                            <span class="font-medium text-sm">{{ $email }}</span>
                        </div>
                        @endif

                        @if($phone)
                        <div class="flex justify-between">
                            <span class="text-gray-600">Phone:</span>
                            <span class="font-medium">{{ $phone }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="mt-6 space-y-3">
                    <x-button
                        label="Update Employee"
                        icon="fas.save"
                        wire:click="save"
                        class="btn-primary w-full"
                        spinner="save"
                    />

                    <x-button
                        label="Cancel"
                        icon="fas.times"
                        wire:click="cancel"
                        class="btn-ghost w-full"
                    />
                </div>
            </x-card>
        </div>
    </div>
</div>
