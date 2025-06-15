<?php

use App\Models\Training;
use App\Models\Employee;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public $title = '';
    public $description = '';
    public $provider = '';
    public $type = 'online';
    public $start_date = '';
    public $end_date = '';
    public $max_participants = '';
    public $location = '';
    public $cost = 0;
    public $is_mandatory = false;
    public $prerequisites = [];
    public $selected_employees = [];

    public array $trainingTypes = [
        'online' => 'Online',
        'classroom' => 'Classroom',
        'workshop' => 'Workshop',
        'conference' => 'Conference'
    ];

    public function mount()
    {
        $this->start_date = now()->addDays(7)->format('Y-m-d');
        $this->end_date = now()->addDays(8)->format('Y-m-d');
    }

    public function updatedStartDate()
    {
        if ($this->start_date && $this->end_date && $this->start_date > $this->end_date) {
            $this->end_date = $this->start_date;
        }
    }

    public function addPrerequisite()
    {
        $this->prerequisites[] = '';
    }

    public function removePrerequisite($index)
    {
        unset($this->prerequisites[$index]);
        $this->prerequisites = array_values($this->prerequisites);
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'provider' => 'nullable|string|max:255',
            'type' => 'required|in:online,classroom,workshop,conference',
            'start_date' => 'required|date|after:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'max_participants' => 'nullable|integer|min:1',
            'location' => 'nullable|string|max:255',
            'cost' => 'nullable|numeric|min:0',
            'is_mandatory' => 'boolean',
            'prerequisites' => 'nullable|array',
            'prerequisites.*' => 'nullable|string|max:255',
            'selected_employees' => 'nullable|array',
            'selected_employees.*' => 'exists:employees,id'
        ];
    }

    public function save()
    {
        $this->validate();

        $workspaceId = session('workspace_id');

        $training = Training::create([
            'workspace_id' => $workspaceId,
            'title' => $this->title,
            'description' => $this->description,
            'provider' => $this->provider,
            'type' => $this->type,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'max_participants' => $this->max_participants,
            'location' => $this->location,
            'cost' => $this->cost ?: null,
            'is_mandatory' => $this->is_mandatory,
            'prerequisites' => !empty(array_filter($this->prerequisites)) ? json_encode(array_filter($this->prerequisites)) : null,
        ]);

        // Attach selected employees
        if (!empty($this->selected_employees)) {
            $training->employees()->attach($this->selected_employees, [
                'enrolled_at' => now(),
                'status' => 'enrolled'
            ]);
        }

        $this->success('Training created successfully!');
        return redirect()->route('hr.trainings.index');
    }

    public function cancel()
    {
        return redirect()->route('hr.trainings.index');
    }

    public function with()
    {
        $workspaceId = session('workspace_id');

        $employees = Employee::where('workspace_id', $workspaceId)
            ->with('user')
            ->get()
            ->map(fn($emp) => [
                'id' => $emp->id, 
                'name' => $emp->user->name . ' - ' . ($emp->position ?? 'N/A')
            ]);

        return [
            'employees' => $employees,
            'trainingTypes' => collect($this->trainingTypes)->map(fn($name, $value) => ['id' => $value, 'name' => $name]),
        ];
    }
}
?>

<div>
    <x-header title="Create Training" separator>
        <x-slot:middle class="!justify-end">
            <x-button label="Cancel" link="{{ route('hr.trainings.index') }}" />
        </x-slot:middle>
    </x-header>

    <x-form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Left Column -->
            <div class="space-y-6">
                <x-card title="Training Information">
                    <div class="space-y-4">
                        <x-input
                            label="Training Title"
                            wire:model="title"
                            placeholder="Enter training title"
                            required
                        />

                        <x-textarea
                            label="Description"
                            wire:model="description"
                            placeholder="Describe the training content and objectives..."
                            rows="4"
                            required
                        />

                        <x-input 
                            label="Provider/Trainer" 
                            wire:model="provider" 
                            placeholder="Training provider or trainer name"
                        />

                        <x-select 
                            label="Training Type" 
                            wire:model="type"
                            :options="collect($trainingTypes)->map(fn($name, $value) => ['id' => $value, 'name' => $name])"
                            required
                        />
                    </div>
                </x-card>

                <x-card title="Schedule & Logistics">
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <x-input
                                label="Start Date"
                                wire:model.live="start_date"
                                type="date"
                                required
                            />

                            <x-input
                                label="End Date"
                                wire:model="end_date"
                                type="date"
                                required
                            />
                        </div>

                        <x-input
                            label="Max Participants"
                            wire:model="max_participants"
                            type="number"
                            placeholder="e.g., 20"
                        />

                        <x-input
                            label="Location"
                            wire:model="location"
                            placeholder="Training venue or online platform"
                        />

                        <x-input
                            label="Cost"
                            wire:model="cost"
                            type="number"
                            step="0.01"
                            placeholder="0.00"
                            prefix="$"
                        />
                    </div>
                </x-card>
            </div>

            <!-- Right Column -->
            <div class="space-y-6">
                <x-card title="Participants">
                    <div class="space-y-4">
                        <x-choices
                            label="Select Employees"
                            wire:model="selected_employees"
                            :options="$employees"
                            searchable
                            multiple
                            placeholder="Choose employees to enroll..."
                        />

                        @if(!empty($selected_employees))
                            <div class="bg-blue-50 p-3 rounded-lg">
                                <div class="text-sm text-blue-800">
                                    <strong>{{ count($selected_employees) }}</strong> employee(s) selected
                                </div>
                            </div>
                        @endif
                    </div>
                </x-card>

                <x-card title="Additional Details">
                    <div class="space-y-4">
                        <x-checkbox 
                            label="Mandatory Training" 
                            wire:model="is_mandatory"
                            hint="Check if this training is mandatory for selected employees"
                        />
                    </div>
                </x-card>

                <x-card title="Prerequisites">
                    <div class="space-y-4">
                        @foreach($prerequisites as $index => $prerequisite)
                            <div class="flex items-center justify-between">
                                <x-input 
                                    label="Prerequisite {{ $index + 1 }}" 
                                    wire:model="prerequisites.{{ $index }}" 
                                    placeholder="Enter prerequisite"
                                />
                                <x-button 
                                    label="Remove" 
                                    wire:click="removePrerequisite({{ $index }})"
                                    class="btn-danger"
                                />
                            </div>
                        @endforeach
                        <x-button 
                            label="Add Prerequisite" 
                            wire:click="addPrerequisite"
                            class="btn-primary"
                        />
                    </div>
                </x-card>

                <x-card title="Training Summary" class="bg-gray-50">
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-600">Type:</span>
                            <span class="text-sm text-gray-900">{{ $trainingTypes[$type] ?? 'Not selected' }}</span>
                        </div>

                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-600">Duration:</span>
                            <span class="text-sm text-gray-900">
                                @if($start_date && $end_date)
                                    {{ \Carbon\Carbon::parse($start_date)->format('M d') }} - {{ \Carbon\Carbon::parse($end_date)->format('M d, Y') }}
                                @else
                                    Not set
                                @endif
                            </span>
                        </div>

                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-600">Participants:</span>
                            <span class="text-sm text-gray-900">{{ count($selected_employees) }} selected</span>
                        </div>

                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-600">Cost:</span>
                            <span class="text-sm font-semibold text-green-600">${{ number_format($cost, 2) }}</span>
                        </div>
                    </div>
                </x-card>
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Cancel" wire:click="cancel" />
            <x-button label="Create Training" class="btn-primary" type="submit" spinner="save" />
        </x-slot:actions>
    </x-form>
</div>
