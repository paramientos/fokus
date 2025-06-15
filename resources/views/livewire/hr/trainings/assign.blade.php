<?php

use Livewire\Volt\Component;
use App\Models\Training;
use App\Models\Employee;
use App\Models\EmployeeTraining;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public $selectedTraining = '';
    public $selectedEmployees = [];
    public $notes = '';
    public $dueDate = '';
    public $isRequired = false;

    public function mount()
    {
        $this->dueDate = now()->addDays(30)->format('Y-m-d');
    }

    public function assignTraining()
    {
        $this->validate([
            'selectedTraining' => 'required|exists:trainings,id',
            'selectedEmployees' => 'required|array|min:1',
            'selectedEmployees.*' => 'exists:employees,id',
            'dueDate' => 'required|date|after:today',
            'notes' => 'nullable|string|max:1000'
        ]);

        $workspaceId = session('workspace_id');
        $training = Training::where('id', $this->selectedTraining)
            ->where('workspace_id', $workspaceId)
            ->first();

        if (!$training) {
            $this->error('Training not found');
            return;
        }

        $assignedCount = 0;
        $alreadyAssignedCount = 0;

        foreach ($this->selectedEmployees as $employeeId) {
            // Check if already assigned
            $existing = EmployeeTraining::where('employee_id', $employeeId)
                ->where('training_id', $this->selectedTraining)
                ->first();

            if ($existing) {
                $alreadyAssignedCount++;
                continue;
            }

            // Create assignment
            EmployeeTraining::create([
                'employee_id' => $employeeId,
                'training_id' => $this->selectedTraining,
                'assigned_at' => now(),
                'due_date' => $this->dueDate,
                'status' => 'assigned',
                'is_required' => $this->isRequired,
                'notes' => $this->notes
            ]);

            $assignedCount++;
        }

        if ($assignedCount > 0) {
            $this->success("Training assigned to {$assignedCount} employee(s) successfully");
        }

        if ($alreadyAssignedCount > 0) {
            $this->warning("{$alreadyAssignedCount} employee(s) were already assigned to this training");
        }

        // Reset form
        $this->reset(['selectedTraining', 'selectedEmployees', 'notes', 'isRequired']);
        $this->dueDate = now()->addDays(30)->format('Y-m-d');
    }

    public function with()
    {
        $workspaceId = session('workspace_id');

        return [
            'trainings' => Training::where('workspace_id', $workspaceId)
                ->where('end_date', '>=', now())
                ->orderBy('start_date')
                ->get()
                ->map(fn($training) => [
                    'id' => $training->id,
                    'name' => $training->title . ' (' . $training->start_date->format('M d') . ')'
                ]),

            'employees' => Employee::whereHas('user')
                ->where('workspace_id', $workspaceId)
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn($employee) => [
                    'id' => $employee->id,
                    'name' => $employee->user->name . ' - ' . $employee->position
                ])
        ];
    }
};

?>

<div>
    <x-header title="Assign Training to Employees" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-button
                label="Back to Trainings"
                icon="fas.arrow-left"
                link="/hr/trainings"
                class="btn-ghost"
            />
        </x-slot:middle>
    </x-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Assignment Form -->
        <div class="lg:col-span-2">
            <x-card title="Training Assignment" class="h-fit">
                <x-form wire:submit="assignTraining">
                    <!-- Training Selection -->
                    <x-select
                        label="Select Training"
                        wire:model.live="selectedTraining"
                        :options="$trainings"
                        placeholder="Choose a training program"
                        icon="fas.graduation-cap"
                        hint="Only upcoming trainings are shown"
                    />

                    <!-- Employee Selection -->
                    <div class="mt-6">
                        <x-choices-offline
                            label="Select Employees"
                            wire:model="selectedEmployees"
                            :options="$employees"
                            placeholder="Choose employees to assign"
                            icon="fas.users"
                            searchable
                            multiple
                            hint="You can select multiple employees"
                        />
                    </div>

                    <!-- Due Date -->
                    <x-input
                        label="Due Date"
                        wire:model="dueDate"
                        type="date"
                        icon="fas.calendar"
                        hint="When should the training be completed"
                    />

                    <!-- Required Training -->
                    <x-checkbox
                        label="Required Training"
                        wire:model="isRequired"
                        hint="Mark as mandatory training"
                    />

                    <!-- Notes -->
                    <x-textarea
                        label="Assignment Notes"
                        wire:model="notes"
                        placeholder="Add any special instructions or notes..."
                        rows="3"
                        hint="Optional notes for the assigned employees"
                    />

                    <x-slot:actions>
                        <x-button
                            label="Assign Training"
                            type="submit"
                            icon="fas.user-plus"
                            class="btn-primary"
                            spinner="assignTraining"
                        />
                    </x-slot:actions>
                </x-form>
            </x-card>
        </div>

        <!-- Assignment Summary -->
        <div class="space-y-6">
            <!-- Quick Stats -->
            <x-card title="Assignment Overview">
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Available Trainings</span>
                        <span class="font-bold text-blue-600">{{ $trainings->count() }}</span>
                    </div>

                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Total Employees</span>
                        <span class="font-bold text-green-600">{{ $employees->count() }}</span>
                    </div>

                    @if($selectedTraining && $selectedEmployees)
                    <div class="pt-4 border-t border-gray-200">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Selected Employees</span>
                            <span class="font-bold text-purple-600">{{ count($selectedEmployees) }}</span>
                        </div>
                    </div>
                    @endif
                </div>
            </x-card>

            <!-- Assignment Tips -->
            <x-card title="Assignment Tips">
                <div class="space-y-3 text-sm text-gray-600">
                    <div class="flex items-start space-x-2">
                        <x-icon name="fas.lightbulb" class="w-4 h-4 text-yellow-500 mt-0.5" />
                        <span>Select multiple employees to assign training in bulk</span>
                    </div>

                    <div class="flex items-start space-x-2">
                        <x-icon name="fas.calendar-check" class="w-4 h-4 text-blue-500 mt-0.5" />
                        <span>Set realistic due dates based on training duration</span>
                    </div>

                    <div class="flex items-start space-x-2">
                        <x-icon name="fas.exclamation-triangle" class="w-4 h-4 text-red-500 mt-0.5" />
                        <span>Mark as required for mandatory compliance training</span>
                    </div>

                    <div class="flex items-start space-x-2">
                        <x-icon name="fas.sticky-note" class="w-4 h-4 text-green-500 mt-0.5" />
                        <span>Add notes for special instructions or context</span>
                    </div>
                </div>
            </x-card>

            <!-- Recent Assignments -->
            @if($selectedTraining)
            <x-card title="Training Details">
                @php
                    $training = $trainings->firstWhere('id', $selectedTraining);
                @endphp

                @if($training)
                <div class="space-y-3 text-sm">
                    <div>
                        <span class="font-medium text-gray-900">Training:</span>
                        <span class="text-gray-600">{{ explode(' (', $training['name'])[0] }}</span>
                    </div>

                    <div>
                        <span class="font-medium text-gray-900">Start Date:</span>
                        <span class="text-gray-600">{{ explode('(', explode(')', $training['name'])[0])[1] ?? 'N/A' }}</span>
                    </div>

                    @if($selectedEmployees)
                    <div class="pt-3 border-t border-gray-200">
                        <span class="font-medium text-gray-900">Selected Employees:</span>
                        <div class="mt-2 space-y-1">
                            @foreach($selectedEmployees as $empId)
                                @php
                                    $emp = $employees->firstWhere('id', $empId);
                                @endphp
                                @if($emp)
                                <div class="text-xs text-gray-600">• {{ explode(' - ', $emp['name'])[0] }}</div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
                @endif
            </x-card>
            @endif
        </div>
    </div>
</div>
