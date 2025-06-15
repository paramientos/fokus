<?php

use App\Models\Employee;
use App\Models\PerformanceReview;
use App\Models\User;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public $employee_id = '';
    public $reviewer_id = '';
    public $review_date = '';
    public $next_review_date = '';
    public array $goals = [''];
    public array $strengths = [''];
    public array $improvement_areas = [''];
    public $overall_rating = '';
    public $feedback = '';
    public $status = 'draft';

    public array $statusOptions = [
        'draft' => 'Draft',
        'in_progress' => 'In Progress',
        'completed' => 'Completed'
    ];

    public function mount()
    {
        $this->review_date = now()->format('Y-m-d');
        $this->next_review_date = now()->addYear()->format('Y-m-d');
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'reviewer_id' => 'required|exists:users,id',
            'review_date' => 'required|date',
            'next_review_date' => 'required|date|after:review_date',
            'goals' => 'required|array|min:1',
            'goals.*' => 'required|string|max:500',
            'strengths' => 'required|array|min:1',
            'strengths.*' => 'required|string|max:500',
            'improvement_areas' => 'required|array|min:1',
            'improvement_areas.*' => 'required|string|max:500',
            'overall_rating' => 'required|numeric|min:1|max:5',
            'feedback' => 'nullable|string',
            'status' => 'required|in:draft,in_progress,completed'
        ];
    }

    public function addGoal()
    {
        $this->goals[] = '';
    }

    public function removeGoal($index)
    {
        if (count($this->goals) > 1) {
            unset($this->goals[$index]);
            $this->goals = array_values($this->goals);
        }
    }

    public function addStrength()
    {
        $this->strengths[] = '';
    }

    public function removeStrength($index)
    {
        if (count($this->strengths) > 1) {
            unset($this->strengths[$index]);
            $this->strengths = array_values($this->strengths);
        }
    }

    public function addImprovementArea()
    {
        $this->improvement_areas[] = '';
    }

    public function removeImprovementArea($index)
    {
        if (count($this->improvement_areas) > 1) {
            unset($this->improvement_areas[$index]);
            $this->improvement_areas = array_values($this->improvement_areas);
        }
    }

    public function save()
    {
        $this->validate();

        try {
            PerformanceReview::create([
                'employee_id' => $this->employee_id,
                'reviewer_id' => $this->reviewer_id,
                'review_date' => $this->review_date,
                'next_review_date' => $this->next_review_date,
                'goals' => array_filter($this->goals),
                'strengths' => array_filter($this->strengths),
                'improvement_areas' => array_filter($this->improvement_areas),
                'overall_rating' => $this->overall_rating,
                'feedback' => $this->feedback,
                'status' => $this->status,
            ]);

            $this->success('Performance review created successfully!');
            return redirect()->route('hr.performance.index');
        } catch (\Exception $e) {
            $this->error('Failed to create performance review: ' . $e->getMessage());
        }
    }

    public function cancel()
    {
        return redirect()->route('hr.performance.index');
    }

    public function with()
    {
        $workspaceId = session('workspace_id');

        $employees = Employee::where('workspace_id', $workspaceId)
            ->with('user')
            ->get()
            ->map(fn($emp) => ['id' => $emp->id, 'name' => $emp->user->name . ' - ' . ($emp->position ?? 'N/A')]);

        $reviewers = User::whereHas('workspaceMembers', function($q) use ($workspaceId) {
                $q->where('workspace_id', $workspaceId);
            })
            ->get()
            ->map(fn($user) => ['id' => $user->id, 'name' => $user->name]);

        return [
            'employees' => $employees,
            'reviewers' => $reviewers
        ];
    }
}
?>

<div>
    <x-header title="Create Performance Review" separator>
        <x-slot:middle class="!justify-end">
            <x-button label="Cancel" link="{{ route('hr.performance.index') }}" />
        </x-slot:middle>
    </x-header>

    <x-form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Left Column -->
            <div class="space-y-6">
                <x-card title="Basic Information">
                    <div class="space-y-4">
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
                            :options="$reviewers"
                            placeholder="Select reviewer"
                            required
                        />

                        <div class="grid grid-cols-2 gap-4">
                            <x-input
                                label="Review Date"
                                wire:model="review_date"
                                type="date"
                                required
                            />

                            <x-input
                                label="Next Review Date"
                                wire:model="next_review_date"
                                type="date"
                                required
                            />
                        </div>

                        <x-select
                            label="Status"
                            wire:model="status"
                            :options="collect($statusOptions)->map(fn($name, $value) => ['id' => $value, 'name' => $name])"
                            required
                        />
                    </div>
                </x-card>

                <x-card title="Goals & Objectives">
                    <div class="space-y-3">
                        @foreach($goals as $index => $goal)
                            <div class="flex items-start space-x-2">
                                <x-textarea
                                    wire:model="goals.{{ $index }}"
                                    placeholder="Enter goal or objective..."
                                    rows="2"
                                    class="flex-1"
                                />
                                <div class="flex flex-col space-y-1 pt-2">
                                    @if($index === count($goals) - 1)
                                        <x-button
                                            icon="fas.plus"
                                            wire:click="addGoal"
                                            class="btn-ghost btn-sm"
                                            tooltip="Add Goal"
                                        />
                                    @endif
                                    @if(count($goals) > 1)
                                        <x-button
                                            icon="fas.trash"
                                            wire:click="removeGoal({{ $index }})"
                                            class="btn-ghost btn-sm text-red-600"
                                            tooltip="Remove Goal"
                                        />
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-card>
            </div>

            <!-- Right Column -->
            <div class="space-y-6">
                <x-card title="Rating & Feedback">
                    <div class="space-y-4">
                        <x-input
                            label="Overall Rating (1-5)"
                            wire:model="overall_rating"
                            type="number"
                            min="1"
                            max="5"
                            step="0.1"
                            placeholder="4.5"
                            required
                        />

                        <x-textarea
                            label="General Feedback"
                            wire:model="feedback"
                            placeholder="Overall feedback and comments..."
                            rows="4"
                        />
                    </div>
                </x-card>

                <x-card title="Strengths">
                    <div class="space-y-3">
                        @foreach($strengths as $index => $strength)
                            <div class="flex items-start space-x-2">
                                <x-textarea
                                    wire:model="strengths.{{ $index }}"
                                    placeholder="Enter strength or positive aspect..."
                                    rows="2"
                                    class="flex-1"
                                />
                                <div class="flex flex-col space-y-1 pt-2">
                                    @if($index === count($strengths) - 1)
                                        <x-button
                                            icon="fas.plus"
                                            wire:click="addStrength"
                                            class="btn-ghost btn-sm"
                                            tooltip="Add Strength"
                                        />
                                    @endif
                                    @if(count($strengths) > 1)
                                        <x-button
                                            icon="fas.trash"
                                            wire:click="removeStrength({{ $index }})"
                                            class="btn-ghost btn-sm text-red-600"
                                            tooltip="Remove Strength"
                                        />
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-card>

                <x-card title="Areas for Improvement">
                    <div class="space-y-3">
                        @foreach($improvement_areas as $index => $area)
                            <div class="flex items-start space-x-2">
                                <x-textarea
                                    wire:model="improvement_areas.{{ $index }}"
                                    placeholder="Enter area for improvement..."
                                    rows="2"
                                    class="flex-1"
                                />
                                <div class="flex flex-col space-y-1 pt-2">
                                    @if($index === count($improvement_areas) - 1)
                                        <x-button
                                            icon="fas.plus"
                                            wire:click="addImprovementArea"
                                            class="btn-ghost btn-sm"
                                            tooltip="Add Improvement Area"
                                        />
                                    @endif
                                    @if(count($improvement_areas) > 1)
                                        <x-button
                                            icon="fas.trash"
                                            wire:click="removeImprovementArea({{ $index }})"
                                            class="btn-ghost btn-sm text-red-600"
                                            tooltip="Remove Improvement Area"
                                        />
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-card>
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Cancel" wire:click="cancel" />
            <x-button label="Create Review" class="btn-primary" type="submit" spinner="save" />
        </x-slot:actions>
    </x-form>
</div>
