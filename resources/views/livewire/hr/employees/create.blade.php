<?php

use App\Models\Employee;
use App\Models\User;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public $user_id = '';
    public $employee_id = '';
    public $position = '';
    public $department = '';
    public $hire_date = '';
    public $salary = '';
    public $employment_type = 'full_time';
    public $status = 'active';
    public $manager_id = '';
    public $phone = '';
    public $emergency_contact = '';
    public $emergency_phone = '';
    public $address = '';
    public $notes = '';

    private array $employmentTypes = [
        'full_time' => 'Full Time',
        'part_time' => 'Part Time',
        'contract' => 'Contract',
        'intern' => 'Intern',
        'freelance' => 'Freelance'
    ];

    private array $statusOptions = [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'terminated' => 'Terminated',
        'on_leave' => 'On Leave'
    ];

    private array $departments = [
        'Engineering' => 'Engineering',
        'Product' => 'Product',
        'Design' => 'Design',
        'Marketing' => 'Marketing',
        'Sales' => 'Sales',
        'HR' => 'Human Resources',
        'Finance' => 'Finance',
        'Operations' => 'Operations',
        'Customer Success' => 'Customer Success',
        'Legal' => 'Legal',
        'Other' => 'Other'
    ];

    public function mount()
    {
        $this->hire_date = now()->format('Y-m-d');
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'employee_id' => 'nullable|string|max:50|unique:employees,employee_id',
            'position' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'hire_date' => 'required|date',
            'salary' => 'nullable|numeric|min:0',
            'employment_type' => 'required|in:full_time,part_time,contract,intern,freelance',
            'status' => 'required|in:active,inactive,terminated,on_leave',
            'manager_id' => 'nullable|exists:employees,id',
            'phone' => 'nullable|string|max:20',
            'emergency_contact' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'notes' => 'nullable|string'
        ];
    }

    public function save()
    {
        $this->validate();

        try {
            Employee::create([
                'workspace_id' => session('workspace_id'),
                'user_id' => $this->user_id,
                'employee_id' => $this->employee_id,
                'position' => $this->position,
                'department' => $this->department,
                'hire_date' => $this->hire_date,
                'salary' => $this->salary,
                'employment_type' => $this->employment_type,
                'status' => $this->status,
                'manager_id' => $this->manager_id,
                'phone' => $this->phone,
                'emergency_contact' => $this->emergency_contact,
                'emergency_phone' => $this->emergency_phone,
                'address' => $this->address,
                'notes' => $this->notes,
            ]);

            $this->success('Employee created successfully!');
            return redirect()->route('hr.employees.index');
        } catch (\Exception $e) {
            $this->error('Failed to create employee: ' . $e->getMessage());
        }
    }

    public function cancel()
    {
        return redirect()->route('hr.employees.index');
    }

    public function with()
    {
        $workspaceId = session('workspace_id');

        // Available users (not already employees)
        $existingEmployeeUserIds = Employee::where('workspace_id', $workspaceId)
            ->pluck('user_id')
            ->toArray();

        $users = User::whereHas('workspaceMembers', function($q) use ($workspaceId) {
                $q->where('workspace_id', $workspaceId);
            })
            ->whereNotIn('id', $existingEmployeeUserIds)
            ->get()
            ->map(fn($user) => ['id' => $user->id, 'name' => $user->name . ' (' . $user->email . ')']);

        // Potential managers (existing employees)
        $managers = Employee::where('workspace_id', $workspaceId)
            ->with('user')
            ->get()
            ->map(fn($emp) => ['id' => $emp->id, 'name' => $emp->user->name . ' - ' . ($emp->position ?? 'N/A')]);

        return [
            'users' => $users,
            'managers' => $managers,
            'employmentTypes' => collect($this->employmentTypes)->map(fn($name, $value) => ['id' => $value, 'name' => $name])->values()->toArray(),
            'statusOptions' => collect($this->statusOptions)->map(fn($name, $value) => ['id' => $value, 'name' => $name])->values()->toArray(),
            'departments' => collect($this->departments)->map(fn($name, $value) => ['id' => $value, 'name' => $name])->values()->toArray()
        ];
    }
}
?>

<div>
    <x-header title="Create Employee" separator>
        <x-slot:middle class="!justify-end">
            <x-button label="Cancel" link="{{ route('hr.employees.index') }}" />
        </x-slot:middle>
    </x-header>

    <x-form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Left Column -->
            <div class="space-y-6">
                <x-card title="Basic Information">
                    <div class="space-y-4">
                        <x-select
                            label="User Account"
                            wire:model="user_id"
                            :options="$users"
                            placeholder="Select user account"
                            required
                        />

                        <x-input
                            label="Employee ID"
                            wire:model="employee_id"
                            placeholder="e.g., EMP001 (optional)"
                        />

                        <x-input
                            label="Position"
                            wire:model="position"
                            placeholder="e.g., Senior Software Engineer"
                            required
                        />

                        <x-select
                            label="Department"
                            wire:model="department"
                            :options="$departments"
                            placeholder="Select department"
                            required
                        />

                        <x-input
                            label="Hire Date"
                            wire:model="hire_date"
                            type="date"
                            required
                        />
                    </div>
                </x-card>

                <x-card title="Employment Details">
                    <div class="space-y-4">
                        <x-select
                            label="Employment Type"
                            wire:model="employment_type"
                            :options="$employmentTypes"
                            required
                        />

                        <x-select
                            label="Status"
                            wire:model="status"
                            :options="$statusOptions"
                            required
                        />

                        <x-input
                            label="Salary"
                            wire:model="salary"
                            type="number"
                            step="0.01"
                            placeholder="0.00"
                            prefix="$"
                        />

                        <x-select
                            label="Manager"
                            wire:model="manager_id"
                            :options="$managers"
                            placeholder="Select manager (optional)"
                        />
                    </div>
                </x-card>
            </div>

            <!-- Right Column -->
            <div class="space-y-6">
                <x-card title="Contact Information">
                    <div class="space-y-4">
                        <x-input
                            label="Phone Number"
                            wire:model="phone"
                            placeholder="+1 (555) 123-4567"
                        />

                        <x-input
                            label="Emergency Contact"
                            wire:model="emergency_contact"
                            placeholder="Contact person name"
                        />

                        <x-input
                            label="Emergency Phone"
                            wire:model="emergency_phone"
                            placeholder="+1 (555) 987-6543"
                        />

                        <x-textarea
                            label="Address"
                            wire:model="address"
                            placeholder="Full address"
                            rows="3"
                        />
                    </div>
                </x-card>

                <x-card title="Additional Information">
                    <div class="space-y-4">
                        <x-textarea
                            label="Notes"
                            wire:model="notes"
                            placeholder="Any additional notes or comments..."
                            rows="4"
                        />

                        <div class="bg-blue-50 p-4 rounded-lg">
                            <div class="flex items-start space-x-2">
                                <x-icon name="fas.info-circle" class="w-5 h-5 text-blue-600 mt-0.5" />
                                <div class="text-sm text-blue-800">
                                    <p class="font-medium">Important Notes:</p>
                                    <ul class="list-disc list-inside mt-1 space-y-1">
                                        <li>Employee will be linked to the selected user account</li>
                                        <li>User must already exist in the workspace</li>
                                        <li>Employee ID should be unique if provided</li>
                                        <li>Salary information is optional and confidential</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-card>
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Cancel" wire:click="cancel" />
            <x-button label="Create Employee" class="btn-primary" type="submit" spinner="save" />
        </x-slot:actions>
    </x-form>
</div>
