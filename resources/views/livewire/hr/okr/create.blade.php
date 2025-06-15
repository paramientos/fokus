<?php

use Livewire\Volt\Component;
use App\Models\OkrGoal;
use App\Models\Employee;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public $employee_id = '';
    public $title = '';
    public $description = '';
    public $type = 'objective';
    public $parent_id = '';
    public $target_value = '';
    public $current_value = 0;
    public $unit = '';
    public $start_date = '';
    public $end_date = '';
    public $status = 'not_started';
    public $progress_percentage = 0;
    public $notes = '';
    public $milestones = [];

    public function mount()
    {
        $this->start_date = now()->format('Y-m-d');
        $this->end_date = now()->addMonths(3)->format('Y-m-d');
    }

    public function updatedStartDate()
    {
        if ($this->start_date && $this->end_date && $this->start_date > $this->end_date) {
            $this->end_date = $this->start_date;
        }
    }

    public function updatedEndDate()
    {
        if ($this->start_date && $this->end_date && $this->start_date > $this->end_date) {
            $this->start_date = $this->end_date;
        }
    }

    public function addMilestone()
    {
        $this->milestones[] = [
            'title' => '',
            'target_date' => '',
            'completed' => false
        ];
    }

    public function removeMilestone($index)
    {
        unset($this->milestones[$index]);
        $this->milestones = array_values($this->milestones);
    }

    public function save()
    {
        $this->validate([
            'employee_id' => 'required|exists:employees,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:objective,key_result',
            'parent_id' => 'nullable|exists:okr_goals,id',
            'target_value' => 'nullable|numeric|min:0',
            'current_value' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|in:not_started,in_progress,on_track,at_risk,completed,cancelled',
            'progress_percentage' => 'required|integer|min:0|max:100',
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

        // Filter milestones
        $filteredMilestones = array_filter($this->milestones, function($milestone) {
            return !empty($milestone['title']) && !empty($milestone['target_date']);
        });

        OkrGoal::create([
            'employee_id' => $this->employee_id,
            'workspace_id' => $workspaceId,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'parent_id' => $this->parent_id ?: null,
            'target_value' => $this->target_value ?: null,
            'current_value' => $this->current_value ?: 0,
            'unit' => $this->unit ?: null,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'status' => $this->status,
            'progress_percentage' => $this->progress_percentage,
            'notes' => $this->notes,
            'milestones' => !empty($filteredMilestones) ? json_encode(array_values($filteredMilestones)) : null,
        ]);

        $this->success('OKR goal created successfully!');
        return redirect()->route('hr.okr.index');
    }

    public function cancel()
    {
        return redirect()->route('hr.okr.index');
    }

    public function with()
    {
        $workspaceId = session('workspace_id');

        $employees = Employee::where('workspace_id', $workspaceId)
            ->with('user')
            ->get()
            ->map(fn($emp) => ['id' => $emp->id, 'name' => $emp->user->name]);

        $parentGoals = OkrGoal::where('workspace_id', $workspaceId)
            ->where('type', 'objective')
            ->get()
            ->map(fn($goal) => ['id' => $goal->id, 'name' => $goal->title]);

        return [
            'employees' => $employees,
            'parentGoals' => $parentGoals,
        ];
    }
};
?>

<div>
    <x-header title="Create OKR Goal" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-button label="Cancel" icon="fas.times" wire:click="cancel" class="btn-ghost" />
        </x-slot:middle>
    </x-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Form -->
        <div class="lg:col-span-2">
            <div class="space-y-6">
                <x-card title="Goal Details">
                    <div class="space-y-4">
                        <x-select
                            label="Employee"
                            wire:model="employee_id"
                            :options="$employees"
                            placeholder="Select employee"
                            required
                        />

                        <x-input
                            label="Title"
                            wire:model="title"
                            placeholder="Enter goal title..."
                            required
                        />

                        <x-textarea
                            label="Description"
                            wire:model="description"
                            placeholder="Describe the goal in detail..."
                            rows="4"
                        />

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <x-select
                                label="Type"
                                wire:model="type"
                                :options="[
                                    ['id' => 'objective', 'name' => 'Objective'],
                                    ['id' => 'key_result', 'name' => 'Key Result']
                                ]"
                                required
                            />

                            <x-select
                                label="Parent Goal (Optional)"
                                wire:model="parent_id"
                                :options="$parentGoals"
                                placeholder="Select parent goal"
                            />
                        </div>
                    </div>
                </x-card>

                <x-card title="Measurement & Timeline">
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <x-input
                                label="Target Value"
                                wire:model="target_value"
                                type="number"
                                step="0.01"
                                placeholder="100"
                            />

                            <x-input
                                label="Current Value"
                                wire:model="current_value"
                                type="number"
                                step="0.01"
                                placeholder="0"
                            />

                            <x-input
                                label="Unit"
                                wire:model="unit"
                                placeholder="%, users, revenue"
                            />
                        </div>

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

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <x-select
                                label="Status"
                                wire:model="status"
                                :options="[
                                    ['id' => 'not_started', 'name' => 'Not Started'],
                                    ['id' => 'in_progress', 'name' => 'In Progress'],
                                    ['id' => 'on_track', 'name' => 'On Track'],
                                    ['id' => 'at_risk', 'name' => 'At Risk'],
                                    ['id' => 'completed', 'name' => 'Completed'],
                                    ['id' => 'cancelled', 'name' => 'Cancelled']
                                ]"
                                required
                            />

                            <x-input
                                label="Progress (%)"
                                wire:model="progress_percentage"
                                type="number"
                                min="0"
                                max="100"
                                required
                            />
                        </div>
                    </div>
                </x-card>

                <x-card title="Milestones">
                    <div class="space-y-4">
                        @foreach($milestones as $index => $milestone)
                            <div class="flex gap-4 items-end">
                                <div class="flex-1">
                                    <x-input
                                        label="Milestone Title"
                                        wire:model="milestones.{{ $index }}.title"
                                        placeholder="Enter milestone..."
                                    />
                                </div>
                                <div class="flex-1">
                                    <x-datetime
                                        label="Target Date"
                                        wire:model="milestones.{{ $index }}.target_date"
                                        type="date"
                                    />
                                </div>
                                <x-button
                                    icon="fas.trash"
                                    wire:click="removeMilestone({{ $index }})"
                                    class="btn-error btn-sm"
                                />
                            </div>
                        @endforeach

                        <x-button
                            label="Add Milestone"
                            icon="fas.plus"
                            wire:click="addMilestone"
                            class="btn-outline"
                        />
                    </div>
                </x-card>

                <x-card title="Additional Notes">
                    <x-textarea
                        label="Notes"
                        wire:model="notes"
                        placeholder="Any additional notes or context..."
                        rows="4"
                    />
                </x-card>
            </div>
        </div>

        <!-- Summary Sidebar -->
        <div class="lg:col-span-1">
            <x-card title="Goal Summary" class="sticky top-6">
                <div class="space-y-4">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Employee:</span>
                        <span class="font-medium">
                            {{ $employees->firstWhere('id', $employee_id)['name'] ?? 'Not selected' }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600">Type:</span>
                        <span class="font-medium capitalize">{{ $type }}</span>
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

                    @if($target_value)
                    <div class="flex justify-between">
                        <span class="text-gray-600">Target:</span>
                        <span class="font-medium">{{ $target_value }} {{ $unit }}</span>
                    </div>
                    @endif

                    <div class="flex justify-between">
                        <span class="text-gray-600">Progress:</span>
                        <span class="font-medium">{{ $progress_percentage }}%</span>
                    </div>

                    @if($title)
                    <div class="pt-3 border-t">
                        <span class="text-gray-600 text-sm">Title:</span>
                        <p class="text-sm mt-1">{{ $title }}</p>
                    </div>
                    @endif
                </div>

                <div class="mt-6 space-y-3">
                    <x-button
                        label="Create Goal"
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
