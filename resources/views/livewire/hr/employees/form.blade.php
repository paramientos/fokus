<?php
use Livewire\Volt\Component;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

new class extends Component {
    public ?Employee $employee = null;
    public bool $isEdit = false;

    // User fields
    public $name = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';

    // Employee fields
    public $employee_id = '';
    public $department = '';
    public $position = '';
    public $hire_date = '';
    public $phone = '';
    public $address = '';
    public $emergency_contact_name = '';
    public $emergency_contact_phone = '';
    public $birth_date = '';
    public $national_id = '';
    public $bank_account = '';
    public $notes = '';

    public function mount(?Employee $employee = null)
    {
        if ($employee && $employee->exists) {
            $this->employee = $employee;
            $this->isEdit = true;
            $this->fillForm();
        } else {
            $this->generateEmployeeId();
        }
    }

    public function fillForm()
    {
        $this->name = $this->employee->user->name;
        $this->email = $this->employee->user->email;
        $this->employee_id = $this->employee->employee_id;
        $this->department = $this->employee->department;
        $this->position = $this->employee->position;
        $this->hire_date = $this->employee->hire_date?->format('Y-m-d');
        $this->phone = $this->employee->phone;
        $this->address = $this->employee->address;
        $this->emergency_contact_name = $this->employee->emergency_contact_name;
        $this->emergency_contact_phone = $this->employee->emergency_contact_phone;
        $this->birth_date = $this->employee->birth_date?->format('Y-m-d');
        $this->national_id = $this->employee->national_id;
        $this->bank_account = $this->employee->bank_account;
        $this->notes = $this->employee->notes;
    }

    public function generateEmployeeId()
    {
        $workspaceId = session('workspace_id');
        $lastEmployee = Employee::where('workspace_id', $workspaceId)
            ->orderBy('employee_id', 'desc')
            ->first();
        
        if ($lastEmployee && preg_match('/EMP(\d+)/', $lastEmployee->employee_id, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        } else {
            $nextNumber = 1;
        }
        
        $this->employee_id = 'EMP' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    public function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->employee?->user_id)
            ],
            'employee_id' => [
                'required',
                'string',
                'max:50',
                Rule::unique('employees', 'employee_id')->ignore($this->employee?->id)
            ],
            'department' => 'required|string|max:100',
            'position' => 'required|string|max:100',
            'hire_date' => 'required|date',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date|before:today',
            'national_id' => 'nullable|string|max:20',
            'bank_account' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:1000'
        ];

        if (!$this->isEdit) {
            $rules['password'] = 'required|string|min:8|confirmed';
            $rules['password_confirmation'] = 'required';
        } else {
            $rules['password'] = 'nullable|string|min:8|confirmed';
            $rules['password_confirmation'] = 'nullable';
        }

        return $rules;
    }

    public function save()
    {
        $this->validate();

        try {
            \DB::transaction(function () {
                if ($this->isEdit) {
                    // Update existing user
                    $user = $this->employee->user;
                    $user->update([
                        'name' => $this->name,
                        'email' => $this->email,
                    ]);

                    if ($this->password) {
                        $user->update(['password' => Hash::make($this->password)]);
                    }

                    // Update employee
                    $this->employee->update([
                        'employee_id' => $this->employee_id,
                        'department' => $this->department,
                        'position' => $this->position,
                        'hire_date' => $this->hire_date,
                        'phone' => $this->phone,
                        'address' => $this->address,
                        'emergency_contact_name' => $this->emergency_contact_name,
                        'emergency_contact_phone' => $this->emergency_contact_phone,
                        'birth_date' => $this->birth_date,
                        'national_id' => $this->national_id,
                        'bank_account' => $this->bank_account,
                        'notes' => $this->notes,
                    ]);

                } else {
                    // Create new user
                    $user = User::create([
                        'name' => $this->name,
                        'email' => $this->email,
                        'password' => Hash::make($this->password),
                    ]);

                    // Create employee
                    $this->employee = Employee::create([
                        'user_id' => $user->id,
                        'workspace_id' => session('workspace_id'),
                        'employee_id' => $this->employee_id,
                        'department' => $this->department,
                        'position' => $this->position,
                        'hire_date' => $this->hire_date,
                        'phone' => $this->phone,
                        'address' => $this->address,
                        'emergency_contact_name' => $this->emergency_contact_name,
                        'emergency_contact_phone' => $this->emergency_contact_phone,
                        'birth_date' => $this->birth_date,
                        'national_id' => $this->national_id,
                        'bank_account' => $this->bank_account,
                        'notes' => $this->notes,
                    ]);
                }
            });

            $message = $this->isEdit ? 'Employee updated successfully!' : 'Employee created successfully!';
            $this->success($message);
            
            return redirect()->route('hr.employees.show', $this->employee);

        } catch (\Exception $e) {
            $this->error('An error occurred while saving the employee: ' . $e->getMessage());
        }
    }

    public function with()
    {
        $workspaceId = session('workspace_id');
        
        $departments = Employee::where('workspace_id', $workspaceId)
            ->distinct()
            ->pluck('department')
            ->filter()
            ->sort()
            ->values();

        $positions = Employee::where('workspace_id', $workspaceId)
            ->distinct()
            ->pluck('position')
            ->filter()
            ->sort()
            ->values();

        return [
            'departments' => $departments,
            'positions' => $positions
        ];
    }
}; ?>

<div>
    <x-header 
        :title="$isEdit ? 'Edit Employee' : 'Add New Employee'" 
        separator 
        progress-indicator
    >
        <x-slot:middle class="!justify-end">
            <x-button label="Back to Employees" icon="fas.arrow-left" link="/hr/employees" class="btn-ghost" />
        </x-slot:middle>
    </x-header>

    <form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Personal Information -->
            <div class="lg:col-span-2">
                <x-card title="Personal Information" class="mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input 
                            label="Full Name" 
                            wire:model="name" 
                            required 
                            icon="fas.user"
                        />
                        
                        <x-input 
                            label="Email Address" 
                            wire:model="email" 
                            type="email" 
                            required 
                            icon="fas.envelope"
                        />
                        
                        <x-input 
                            label="Phone Number" 
                            wire:model="phone" 
                            icon="fas.phone"
                        />
                        
                        <x-input 
                            label="Birth Date" 
                            wire:model="birth_date" 
                            type="date" 
                            icon="fas.birthday-cake"
                        />
                        
                        <x-input 
                            label="National ID" 
                            wire:model="national_id" 
                            icon="fas.id-card"
                        />
                        
                        <x-input 
                            label="Bank Account" 
                            wire:model="bank_account" 
                            icon="fas.university"
                        />
                    </div>
                    
                    <x-textarea 
                        label="Address" 
                        wire:model="address" 
                        rows="3" 
                        class="mt-4"
                    />
                </x-card>

                <!-- Employment Information -->
                <x-card title="Employment Information" class="mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input 
                            label="Employee ID" 
                            wire:model="employee_id" 
                            required 
                            icon="fas.id-badge"
                        />
                        
                        <x-input 
                            label="Hire Date" 
                            wire:model="hire_date" 
                            type="date" 
                            required 
                            icon="fas.calendar"
                        />
                        
                        <div>
                            <x-input 
                                label="Department" 
                                wire:model="department" 
                                required 
                                icon="fas.building"
                                list="departments-list"
                            />
                            <datalist id="departments-list">
                                @foreach($departments as $dept)
                                    <option value="{{ $dept }}">
                                @endforeach
                            </datalist>
                        </div>
                        
                        <div>
                            <x-input 
                                label="Position" 
                                wire:model="position" 
                                required 
                                icon="fas.briefcase"
                                list="positions-list"
                            />
                            <datalist id="positions-list">
                                @foreach($positions as $pos)
                                    <option value="{{ $pos }}">
                                @endforeach
                            </datalist>
                        </div>
                    </div>
                </x-card>

                <!-- Emergency Contact -->
                <x-card title="Emergency Contact" class="mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input 
                            label="Contact Name" 
                            wire:model="emergency_contact_name" 
                            icon="fas.user-friends"
                        />
                        
                        <x-input 
                            label="Contact Phone" 
                            wire:model="emergency_contact_phone" 
                            icon="fas.phone"
                        />
                    </div>
                </x-card>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Account Information -->
                <x-card title="Account Information">
                    @if(!$isEdit)
                        <x-input 
                            label="Password" 
                            wire:model="password" 
                            type="password" 
                            required 
                            icon="fas.lock"
                        />
                        
                        <x-input 
                            label="Confirm Password" 
                            wire:model="password_confirmation" 
                            type="password" 
                            required 
                            icon="fas.lock" 
                            class="mt-4"
                        />
                    @else
                        <x-input 
                            label="New Password (optional)" 
                            wire:model="password" 
                            type="password" 
                            icon="fas.lock"
                            hint="Leave blank to keep current password"
                        />
                        
                        <x-input 
                            label="Confirm New Password" 
                            wire:model="password_confirmation" 
                            type="password" 
                            icon="fas.lock" 
                            class="mt-4"
                        />
                    @endif
                </x-card>

                <!-- Additional Notes -->
                <x-card title="Additional Notes">
                    <x-textarea 
                        label="Notes" 
                        wire:model="notes" 
                        rows="5" 
                        placeholder="Any additional information about the employee..."
                    />
                </x-card>

                <!-- Actions -->
                <x-card>
                    <div class="space-y-3">
                        <x-button 
                            label="{{ $isEdit ? 'Update Employee' : 'Create Employee' }}" 
                            type="submit" 
                            icon="fas.save" 
                            class="btn-primary w-full"
                        />
                        
                        <x-button 
                            label="Cancel" 
                            link="/hr/employees" 
                            class="btn-ghost w-full"
                        />
                    </div>
                </x-card>
            </div>
        </div>
    </form>
</div>
