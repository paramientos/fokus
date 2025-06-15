<?php

use Livewire\Volt\Component;
use App\Models\PerformanceReview;
use App\Models\Employee;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public PerformanceReview $review;
    public $employee_id = '';
    public $reviewer_id = '';
    public $review_date = '';
    public $overall_rating = 3;
    public $status = 'draft';
    public $comments = '';
    public $goals = [];
    public $strengths = [];
    public $improvement_areas = [];

    public function mount(PerformanceReview $review)
    {
        // Check workspace access
        if ($review->workspace_id !== session('workspace_id')) {
            abort(403);
        }

        $this->review = $review;
        $this->employee_id = $review->employee_id;
        $this->reviewer_id = $review->reviewer_id;
        $this->review_date = $review->review_date->format('Y-m-d');
        $this->overall_rating = $review->overall_rating;
        $this->status = $review->status;
        $this->comments = $review->comments;
        $this->goals = $review->goals ? json_decode($review->goals, true) : [];
        $this->strengths = $review->strengths ? json_decode($review->strengths, true) : [];
        $this->improvement_areas = $review->improvement_areas ? json_decode($review->improvement_areas, true) : [];
    }

    public function addGoal()
    {
        $this->goals[] = [
            'title' => '',
            'description' => '',
            'achieved' => false
        ];
    }

    public function removeGoal($index)
    {
        unset($this->goals[$index]);
        $this->goals = array_values($this->goals);
    }

    public function addStrength()
    {
        $this->strengths[] = [
            'title' => '',
            'description' => ''
        ];
    }

    public function removeStrength($index)
    {
        unset($this->strengths[$index]);
        $this->strengths = array_values($this->strengths);
    }

    public function addImprovementArea()
    {
        $this->improvement_areas[] = [
            'title' => '',
            'description' => '',
            'action_plan' => ''
        ];
    }

    public function removeImprovementArea($index)
    {
        unset($this->improvement_areas[$index]);
        $this->improvement_areas = array_values($this->improvement_areas);
    }

    public function save()
    {
        $this->validate([
            'employee_id' => 'required|exists:employees,id',
            'reviewer_id' => 'required|exists:employees,id',
            'review_date' => 'required|date',
            'overall_rating' => 'required|numeric|min:1|max:5',
            'status' => 'required|in:draft,in_progress,completed,cancelled',
            'comments' => 'nullable|string|max:2000',
        ]);

        $workspaceId = session('workspace_id');

        // Check if employees belong to workspace
        $employee = Employee::where('id', $this->employee_id)->where('workspace_id', $workspaceId)->first();
        $reviewer = Employee::where('id', $this->reviewer_id)->where('workspace_id', $workspaceId)->first();

        if (!$employee || !$reviewer) {
            $this->error('Invalid employee or reviewer selection.');
            return;
        }

        // Filter arrays
        $filteredGoals = array_filter($this->goals, fn($goal) => !empty($goal['title']));
        $filteredStrengths = array_filter($this->strengths, fn($strength) => !empty($strength['title']));
        $filteredImprovements = array_filter($this->improvement_areas, fn($area) => !empty($area['title']));

        $this->review->update([
            'employee_id' => $this->employee_id,
            'reviewer_id' => $this->reviewer_id,
            'review_date' => $this->review_date,
            'overall_rating' => $this->overall_rating,
            'status' => $this->status,
            'comments' => $this->comments,
            'goals' => !empty($filteredGoals) ? json_encode(array_values($filteredGoals)) : null,
            'strengths' => !empty($filteredStrengths) ? json_encode(array_values($filteredStrengths)) : null,
            'improvement_areas' => !empty($filteredImprovements) ? json_encode(array_values($filteredImprovements)) : null,
        ]);

        $this->success('Performance review updated successfully!');
        return redirect()->route('hr.performance.show', $this->review);
    }

    public function cancel()
    {
        return redirect()->route('hr.performance.show', $this->review);
    }

    public function with()
    {
        $workspaceId = session('workspace_id');

        $employees = Employee::where('workspace_id', $workspaceId)
            ->with('user')
            ->get()
            ->map(fn($emp) => ['id' => $emp->id, 'name' => $emp->user->name]);

        return [
            'employees' => $employees,
        ];
    }
};
?>

<div>
    <x-header title="Edit Performance Review" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-button label="Cancel" icon="fas.times" wire:click="cancel" class="btn-ghost" />
        </x-slot:middle>
    </x-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Form -->
        <div class="lg:col-span-2">
            <div class="space-y-6">
                <x-card title="Review Details">
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <x-select
                                label="Employee"
                                wire:model="employee_id"
                                :options="$employees"
                                placeholder="Select employee"
                                required
                            />

                            <x-select
                                label="Reviewer"
                                wire:model="reviewer_id"
                                :options="$employees"
                                placeholder="Select reviewer"
                                required
                            />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <x-datetime
                                label="Review Date"
                                wire:model="review_date"
                                type="date"
                                required
                            />

                            <x-select
                                label="Status"
                                wire:model="status"
                                :options="[
                                    ['id' => 'draft', 'name' => 'Draft'],
                                    ['id' => 'in_progress', 'name' => 'In Progress'],
                                    ['id' => 'completed', 'name' => 'Completed'],
                                    ['id' => 'cancelled', 'name' => 'Cancelled']
                                ]"
                                required
                            />

                            <x-input
                                label="Overall Rating"
                                wire:model="overall_rating"
                                type="number"
                                min="1"
                                max="5"
                                step="0.1"
                                required
                            />
                        </div>
                    </div>
                </x-card>

                <x-card title="Goals & Objectives">
                    <div class="space-y-4">
                        @foreach($goals as $index => $goal)
                            <div class="p-4 border rounded-lg">
                                <div class="flex justify-between items-start mb-3">
                                    <h5 class="font-medium">Goal {{ $index + 1 }}</h5>
                                    <x-button
                                        icon="fas.trash"
                                        wire:click="removeGoal({{ $index }})"
                                        class="btn-error btn-sm"
                                    />
                                </div>
                                <div class="space-y-3">
                                    <x-input
                                        label="Title"
                                        wire:model="goals.{{ $index }}.title"
                                        placeholder="Enter goal title..."
                                    />
                                    <x-textarea
                                        label="Description"
                                        wire:model="goals.{{ $index }}.description"
                                        placeholder="Describe the goal..."
                                        rows="2"
                                    />
                                    <x-checkbox
                                        label="Achieved"
                                        wire:model="goals.{{ $index }}.achieved"
                                    />
                                </div>
                            </div>
                        @endforeach

                        <x-button
                            label="Add Goal"
                            icon="fas.plus"
                            wire:click="addGoal"
                            class="btn-outline"
                        />
                    </div>
                </x-card>

                <x-card title="Strengths">
                    <div class="space-y-4">
                        @foreach($strengths as $index => $strength)
                            <div class="p-4 border rounded-lg">
                                <div class="flex justify-between items-start mb-3">
                                    <h5 class="font-medium">Strength {{ $index + 1 }}</h5>
                                    <x-button
                                        icon="fas.trash"
                                        wire:click="removeStrength({{ $index }})"
                                        class="btn-error btn-sm"
                                    />
                                </div>
                                <div class="space-y-3">
                                    <x-input
                                        label="Title"
                                        wire:model="strengths.{{ $index }}.title"
                                        placeholder="Enter strength title..."
                                    />
                                    <x-textarea
                                        label="Description"
                                        wire:model="strengths.{{ $index }}.description"
                                        placeholder="Describe the strength..."
                                        rows="2"
                                    />
                                </div>
                            </div>
                        @endforeach

                        <x-button
                            label="Add Strength"
                            icon="fas.plus"
                            wire:click="addStrength"
                            class="btn-outline"
                        />
                    </div>
                </x-card>

                <x-card title="Areas for Improvement">
                    <div class="space-y-4">
                        @foreach($improvement_areas as $index => $area)
                            <div class="p-4 border rounded-lg">
                                <div class="flex justify-between items-start mb-3">
                                    <h5 class="font-medium">Improvement Area {{ $index + 1 }}</h5>
                                    <x-button
                                        icon="fas.trash"
                                        wire:click="removeImprovementArea({{ $index }})"
                                        class="btn-error btn-sm"
                                    />
                                </div>
                                <div class="space-y-3">
                                    <x-input
                                        label="Title"
                                        wire:model="improvement_areas.{{ $index }}.title"
                                        placeholder="Enter improvement area..."
                                    />
                                    <x-textarea
                                        label="Description"
                                        wire:model="improvement_areas.{{ $index }}.description"
                                        placeholder="Describe the area..."
                                        rows="2"
                                    />
                                    <x-textarea
                                        label="Action Plan"
                                        wire:model="improvement_areas.{{ $index }}.action_plan"
                                        placeholder="What actions will be taken..."
                                        rows="2"
                                    />
                                </div>
                            </div>
                        @endforeach

                        <x-button
                            label="Add Improvement Area"
                            icon="fas.plus"
                            wire:click="addImprovementArea"
                            class="btn-outline"
                        />
                    </div>
                </x-card>

                <x-card title="Additional Comments">
                    <x-textarea
                        label="Comments"
                        wire:model="comments"
                        placeholder="Any additional comments about the performance review..."
                        rows="4"
                    />
                </x-card>
            </div>
        </div>

        <!-- Summary Sidebar -->
        <div class="lg:col-span-1">
            <x-card title="Review Summary" class="sticky top-6">
                <div class="space-y-4">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Employee:</span>
                        <span class="font-medium">
                            {{ $employees->firstWhere('id', $employee_id)['name'] ?? 'Not selected' }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600">Reviewer:</span>
                        <span class="font-medium">
                            {{ $employees->firstWhere('id', $reviewer_id)['name'] ?? 'Not selected' }}
                        </span>
                    </div>

                    @if($review_date)
                    <div class="flex justify-between">
                        <span class="text-gray-600">Review Date:</span>
                        <span class="font-medium">{{ \Carbon\Carbon::parse($review_date)->format('M d, Y') }}</span>
                    </div>
                    @endif

                    <div class="flex justify-between">
                        <span class="text-gray-600">Rating:</span>
                        <div class="flex items-center gap-2">
                            <span class="font-medium">{{ $overall_rating }}/5</span>
                            <div class="flex">
                                @for($i = 1; $i <= 5; $i++)
                                    <x-icon
                                        name="fas.star"
                                        class="w-4 h-4 {{ $i <= $overall_rating ? 'text-yellow-400' : 'text-gray-300' }}"
                                    />
                                @endfor
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600">Status:</span>
                        <span class="font-medium capitalize">{{ str_replace('_', ' ', $status) }}</span>
                    </div>

                    <div class="pt-3 border-t">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Goals:</span>
                            <span class="font-medium">{{ count($goals) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Strengths:</span>
                            <span class="font-medium">{{ count($strengths) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Improvements:</span>
                            <span class="font-medium">{{ count($improvement_areas) }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-6 space-y-3">
                    <x-button
                        label="Update Review"
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
