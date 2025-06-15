<?php

use Livewire\Volt\Component;
use App\Models\OkrGoal;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public OkrGoal $goal;
    public $newProgress = '';

    public function mount(OkrGoal $goal)
    {
        // Check workspace access
        if ($goal->workspace_id !== session('workspace_id')) {
            abort(403);
        }

        $this->goal = $goal->load(['employee.user', 'parent', 'children']);
        $this->newProgress = $goal->progress_percentage;
    }

    public function updateProgress()
    {
        $this->validate([
            'newProgress' => 'required|integer|min:0|max:100'
        ]);

        $this->goal->update([
            'progress_percentage' => $this->newProgress,
            'current_value' => $this->goal->target_value ?
                ($this->goal->target_value * $this->newProgress / 100) :
                $this->newProgress
        ]);

        // Auto-update status based on progress
        if ($this->newProgress == 100) {
            $this->goal->update(['status' => 'completed']);
        } elseif ($this->newProgress > 0 && $this->goal->status == 'not_started') {
            $this->goal->update(['status' => 'in_progress']);
        }

        $this->goal->refresh();
        $this->success('Progress updated successfully!');
    }

    public function updateStatus($status)
    {
        $this->goal->update(['status' => $status]);
        $this->goal->refresh();
        $this->success('Status updated successfully!');
    }

    public function deleteGoal()
    {
        $this->goal->delete();
        $this->success('Goal deleted successfully!');
        return redirect()->route('hr.okr.index');
    }

    public function getStatusColor($status)
    {
        return match($status) {
            'not_started' => 'bg-gray-100 text-gray-800',
            'in_progress' => 'bg-blue-100 text-blue-800',
            'on_track' => 'bg-green-100 text-green-800',
            'at_risk' => 'bg-yellow-100 text-yellow-800',
            'completed' => 'bg-purple-100 text-purple-800',
            'cancelled' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    public function getProgressColor($progress)
    {
        if ($progress >= 80) return 'bg-green-500';
        if ($progress >= 60) return 'bg-blue-500';
        if ($progress >= 40) return 'bg-yellow-500';
        return 'bg-red-500';
    }
};
?>

<div>
    <x-header title="OKR Goal Details" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-button
                label="Edit"
                icon="fas.edit"
                link="/hr/okr/{{ $goal->id }}/edit"
                class="btn-primary"
            />
            <x-button
                label="Back"
                icon="fas.arrow-left"
                link="/hr/okr"
                class="btn-ghost"
            />
        </x-slot:middle>
    </x-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Goal Overview -->
            <x-card title="Goal Overview">
                <div class="space-y-4">
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900">{{ $goal->title }}</h3>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $this->getStatusColor($goal->status) }} mt-2">
                            {{ ucwords(str_replace('_', ' ', $goal->status)) }}
                        </span>
                    </div>

                    @if($goal->description)
                    <div>
                        <h4 class="font-medium text-gray-900 mb-2">Description</h4>
                        <p class="text-gray-600">{{ $goal->description }}</p>
                    </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">Employee</h4>
                            <p class="text-gray-600">{{ $goal->employee->user->name }}</p>
                        </div>

                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">Type</h4>
                            <p class="text-gray-600 capitalize">{{ str_replace('_', ' ', $goal->type) }}</p>
                        </div>

                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">Start Date</h4>
                            <p class="text-gray-600">{{ $goal->start_date->format('M d, Y') }}</p>
                        </div>

                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">End Date</h4>
                            <p class="text-gray-600">{{ $goal->end_date->format('M d, Y') }}</p>
                        </div>
                    </div>
                </div>
            </x-card>

            <!-- Progress Tracking -->
            <x-card title="Progress Tracking">
                <div class="space-y-4">
                    <!-- Progress Bar -->
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-medium text-gray-700">Overall Progress</span>
                            <span class="text-sm font-medium text-gray-900">{{ $goal->progress_percentage }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="h-3 rounded-full {{ $this->getProgressColor($goal->progress_percentage) }}"
                                 style="width: {{ $goal->progress_percentage }}%"></div>
                        </div>
                    </div>

                    <!-- Target vs Current -->
                    @if($goal->target_value)
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <div class="text-2xl font-bold text-gray-900">{{ $goal->current_value ?? 0 }}</div>
                            <div class="text-sm text-gray-600">Current {{ $goal->unit }}</div>
                        </div>
                        <div class="text-center p-4 bg-blue-50 rounded-lg">
                            <div class="text-2xl font-bold text-blue-600">{{ $goal->target_value }}</div>
                            <div class="text-sm text-gray-600">Target {{ $goal->unit }}</div>
                        </div>
                        <div class="text-center p-4 bg-green-50 rounded-lg">
                            <div class="text-2xl font-bold text-green-600">
                                {{ $goal->target_value - ($goal->current_value ?? 0) }}
                            </div>
                            <div class="text-sm text-gray-600">Remaining {{ $goal->unit }}</div>
                        </div>
                    </div>
                    @endif

                    <!-- Update Progress -->
                    <div class="border-t pt-4">
                        <h4 class="font-medium text-gray-900 mb-3">Update Progress</h4>
                        <div class="flex gap-3">
                            <div class="flex-1">
                                <x-input
                                    wire:model="newProgress"
                                    type="number"
                                    min="0"
                                    max="100"
                                    placeholder="Enter progress percentage"
                                />
                            </div>
                            <x-button
                                label="Update"
                                icon="fas.save"
                                wire:click="updateProgress"
                                class="btn-primary"
                                spinner="updateProgress"
                            />
                        </div>
                    </div>
                </div>
            </x-card>

            <!-- Milestones -->
            @if($goal->milestones)
            <x-card title="Milestones">
                <div class="space-y-3">
                    @foreach(json_decode($goal->milestones, true) as $milestone)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-4 h-4 rounded-full {{ $milestone['completed'] ? 'bg-green-500' : 'bg-gray-300' }}"></div>
                            <span class="font-medium {{ $milestone['completed'] ? 'line-through text-gray-500' : 'text-gray-900' }}">
                                {{ $milestone['title'] }}
                            </span>
                        </div>
                        <span class="text-sm text-gray-600">
                            {{ \Carbon\Carbon::parse($milestone['target_date'])->format('M d, Y') }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </x-card>
            @endif

            <!-- Parent/Children Goals -->
            @if($goal->parent || $goal->children->count() > 0)
            <x-card title="Related Goals">
                @if($goal->parent)
                <div class="mb-4">
                    <h4 class="font-medium text-gray-900 mb-2">Parent Goal</h4>
                    <div class="p-3 bg-blue-50 rounded-lg">
                        <a href="/hr/okr/{{ $goal->parent->id }}" class="text-blue-600 hover:text-blue-800 font-medium">
                            {{ $goal->parent->title }}
                        </a>
                        <div class="text-sm text-gray-600 mt-1">
                            Progress: {{ $goal->parent->progress_percentage }}%
                        </div>
                    </div>
                </div>
                @endif

                @if($goal->children->count() > 0)
                <div>
                    <h4 class="font-medium text-gray-900 mb-2">Key Results</h4>
                    <div class="space-y-2">
                        @foreach($goal->children as $child)
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <a href="/hr/okr/{{ $child->id }}" class="text-gray-900 hover:text-gray-700 font-medium">
                                {{ $child->title }}
                            </a>
                            <div class="flex items-center justify-between mt-2">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $this->getStatusColor($child->status) }}">
                                    {{ ucwords(str_replace('_', ' ', $child->status)) }}
                                </span>
                                <span class="text-sm text-gray-600">{{ $child->progress_percentage }}%</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </x-card>
            @endif

            <!-- Notes -->
            @if($goal->notes)
            <x-card title="Notes">
                <p class="text-gray-600">{{ $goal->notes }}</p>
            </x-card>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Quick Actions -->
            <x-card title="Quick Actions">
                <div class="space-y-3">
                    <h4 class="font-medium text-gray-900 mb-3">Update Status</h4>

                    <x-button
                        label="Mark In Progress"
                        icon="fas.play"
                        wire:click="updateStatus('in_progress')"
                        class="btn-outline w-full"
                        :class="$goal->status === 'in_progress' ? 'btn-primary' : ''"
                    />

                    <x-button
                        label="Mark On Track"
                        icon="fas.check"
                        wire:click="updateStatus('on_track')"
                        class="btn-outline w-full"
                        :class="$goal->status === 'on_track' ? 'btn-success' : ''"
                    />

                    <x-button
                        label="Mark At Risk"
                        icon="fas.exclamation-triangle"
                        wire:click="updateStatus('at_risk')"
                        class="btn-outline w-full"
                        :class="$goal->status === 'at_risk' ? 'btn-warning' : ''"
                    />

                    <x-button
                        label="Mark Completed"
                        icon="fas.check-circle"
                        wire:click="updateStatus('completed')"
                        class="btn-outline w-full"
                        :class="$goal->status === 'completed' ? 'btn-success' : ''"
                    />
                </div>

                <div class="border-t pt-4 mt-4">
                    <x-button
                        label="Delete Goal"
                        icon="fas.trash"
                        wire:click="deleteGoal"
                        wire:confirm="Are you sure you want to delete this goal?"
                        class="btn-error w-full"
                        spinner="deleteGoal"
                    />
                </div>
            </x-card>

            <!-- Goal Info -->
            <x-card title="Goal Information">
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Created:</span>
                        <span class="font-medium">{{ $goal->created_at->format('M d, Y') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600">Updated:</span>
                        <span class="font-medium">{{ $goal->updated_at->format('M d, Y') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600">Duration:</span>
                        <span class="font-medium">{{ $goal->start_date->diffInDays($goal->end_date) }} days</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600">Days Remaining:</span>
                        <span class="font-medium">
                            {{ $goal->end_date->isPast() ? 'Overdue' : $goal->end_date->diffInDays(now()) . ' days' }}
                        </span>
                    </div>
                </div>
            </x-card>
        </div>
    </div>
</div>
