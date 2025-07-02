<?php

use App\Models\WorkspaceWorkflow;
use App\Models\WorkspaceWorkflowInstance;
use Mary\Traits\Toast;

new class extends Livewire\Volt\Component {
    use Toast;

    public WorkspaceWorkflow $workflow;
    public WorkspaceWorkflowInstance $instance;

    public function mount(WorkspaceWorkflow $workflow, WorkspaceWorkflowInstance $instance)
    {
        // Check if the workflow belongs to the current workspace
        if ($workflow->workspace_id !== session('workspace_id')) {
            $this->error('You do not have access to this workflow.');
            return redirect()->route('workflows.index');
        }

        // Check if the instance belongs to this workflow
        if ($instance->workspace_workflow_id !== $workflow->id) {
            $this->error('This instance does not belong to the selected workflow.');
            return redirect()->route('workflows.instances.index', $workflow);
        }

        $this->workflow = $workflow;
        $this->instance = $instance;
    }

    public function cancelInstance()
    {
        $this->instance->update([
            'status' => 'cancelled',
            'completed_at' => now()
        ]);

        // Update all pending step instances to cancelled
        $this->instance->stepInstances()
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);

        // Update all active step instances to cancelled
        $this->instance->stepInstances()
            ->where('status', 'active')
            ->update(['status' => 'cancelled']);

        $this->success('Workflow instance cancelled successfully.');
    }
}

?>

<div>
    <div class="mb-6">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h1 class="text-2xl font-bold">{{ $instance->name }}</h1>
                <p class="text-gray-600 dark:text-gray-400">
                    {{ $workflow->name }}
                </p>
            </div>

            <div class="flex space-x-2">
                <x-button
                    color="gray"
                    icon="fas.arrow-left"
                    link="{{ route('workflows.instances.index', $workflow) }}"
                >
                    Back to Instances
                </x-button>

                @if ($instance->status === 'active')
                    <x-button
                        icon="fas.play"
                        link="{{ route('workflows.instances.process', ['workflow' => $workflow, 'instance' => $instance]) }}"
                        color="green"
                    >
                        Process Workflow
                    </x-button>

                    <x-button
                        icon="fas.times"
                        wire:click="cancelInstance"
                        wire:confirm="Are you sure you want to cancel this workflow instance? This action cannot be undone."
                        color="red"
                    >
                        Cancel Workflow
                    </x-button>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                    <h2 class="text-xl font-semibold mb-4">Instance Information</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Status</div>
                            <div class="font-medium">
                                @if ($instance->status === 'active')
                                    <span class="text-green-600 dark:text-green-400">Active</span>
                                @elseif ($instance->status === 'completed')
                                    <span class="text-blue-600 dark:text-blue-400">Completed</span>
                                @elseif ($instance->status === 'cancelled')
                                    <span class="text-red-600 dark:text-red-400">Cancelled</span>
                                @else
                                    <span>{{ ucfirst($instance->status) }}</span>
                                @endif
                            </div>
                        </div>

                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Started</div>
                            <div class="font-medium">{{ $instance->created_at->format('M d, Y H:i') }}</div>
                        </div>

                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Initiated By</div>
                            <div class="font-medium">
                                @if ($instance->initiator)
                                    {{ $instance->initiator->name }}
                                @else
                                    <span class="text-gray-500 dark:text-gray-400">System</span>
                                @endif
                            </div>
                        </div>

                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Completed</div>
                            <div class="font-medium">
                                @if ($instance->completed_at)
                                    {{ $instance->completed_at->format('M d, Y H:i') }}
                                @else
                                    <span class="text-gray-500 dark:text-gray-400">Not completed</span>
                                @endif
                            </div>
                        </div>

                        <div class="md:col-span-2">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Description</div>
                            <div class="font-medium">{{ $instance->description ?: 'No description provided' }}</div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold mb-4">Workflow Steps</h2>

                    <div class="space-y-4">
                        @foreach ($instance->stepInstances()->with('step')->orderBy('id')->get() as $stepInstance)
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <div class="flex items-start">
                                    <div class="mr-3">
                                        @if ($stepInstance->status === 'completed')
                                            <div class="bg-green-100 dark:bg-green-900 rounded-full w-8 h-8 flex items-center justify-center text-green-800 dark:text-green-200">
                                                <i class="fas fa-check"></i>
                                            </div>
                                        @elseif ($stepInstance->status === 'active')
                                            <div class="bg-blue-100 dark:bg-blue-900 rounded-full w-8 h-8 flex items-center justify-center text-blue-800 dark:text-blue-200">
                                                <i class="fas fa-play"></i>
                                            </div>
                                        @elseif ($stepInstance->status === 'rejected')
                                            <div class="bg-red-100 dark:bg-red-900 rounded-full w-8 h-8 flex items-center justify-center text-red-800 dark:text-red-200">
                                                <i class="fas fa-times"></i>
                                            </div>
                                        @elseif ($stepInstance->status === 'cancelled')
                                            <div class="bg-gray-100 dark:bg-gray-700 rounded-full w-8 h-8 flex items-center justify-center text-gray-800 dark:text-gray-200">
                                                <i class="fas fa-ban"></i>
                                            </div>
                                        @else
                                            <div class="bg-gray-100 dark:bg-gray-700 rounded-full w-8 h-8 flex items-center justify-center text-gray-800 dark:text-gray-200">
                                                <i class="fas fa-clock"></i>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex-1">
                                        <div class="flex justify-between">
                                            <h4 class="font-medium">{{ $stepInstance->step->name }}</h4>
                                            <span class="text-sm">
                                                @if ($stepInstance->status === 'completed')
                                                    <span class="text-green-600 dark:text-green-400">Completed</span>
                                                @elseif ($stepInstance->status === 'active')
                                                    <span class="text-blue-600 dark:text-blue-400">Active</span>
                                                @elseif ($stepInstance->status === 'rejected')
                                                    <span class="text-red-600 dark:text-red-400">Rejected</span>
                                                @elseif ($stepInstance->status === 'cancelled')
                                                    <span class="text-gray-500 dark:text-gray-400">Cancelled</span>
                                                @else
                                                    <span class="text-gray-500 dark:text-gray-400">Pending</span>
                                                @endif
                                            </span>
                                        </div>

                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            {{ $stepInstance->step->description ?: 'No description' }}
                                        </p>

                                        <div class="mt-2 grid grid-cols-2 gap-2 text-sm">
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400">Assigned to:</span>
                                                <span class="ml-1">
                                                    @if ($stepInstance->assignee)
                                                        {{ $stepInstance->assignee->name }}
                                                    @else
                                                        <span class="text-gray-500 dark:text-gray-400">Unassigned</span>
                                                    @endif
                                                </span>
                                            </div>

                                            @if ($stepInstance->started_at)
                                                <div>
                                                    <span class="text-gray-500 dark:text-gray-400">Started:</span>
                                                    <span class="ml-1">{{ $stepInstance->started_at->format('M d, Y H:i') }}</span>
                                                </div>
                                            @endif

                                            @if ($stepInstance->completed_at)
                                                <div>
                                                    <span class="text-gray-500 dark:text-gray-400">Completed:</span>
                                                    <span class="ml-1">{{ $stepInstance->completed_at->format('M d, Y H:i') }}</span>
                                                </div>
                                            @endif

                                            @if ($stepInstance->completed_by)
                                                <div>
                                                    <span class="text-gray-500 dark:text-gray-400">Completed by:</span>
                                                    <span class="ml-1">{{ $stepInstance->completedByUser->name }}</span>
                                                </div>
                                            @endif
                                        </div>

                                        @if ($stepInstance->comments)
                                            <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-700 rounded text-sm">
                                                <div class="font-medium mb-1">Comments:</div>
                                                <div>{{ $stepInstance->comments }}</div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                    <h2 class="text-xl font-semibold mb-4">Progress</h2>

                    @php
                        $totalSteps = $instance->stepInstances->count();
                        $completedSteps = $instance->stepInstances->where('status', 'completed')->count();
                        $progress = $totalSteps > 0 ? ($completedSteps / $totalSteps) * 100 : 0;
                    @endphp

                    <div class="mb-2 flex justify-between text-sm">
                        <span>{{ $completedSteps }} of {{ $totalSteps }} steps completed</span>
                        <span>{{ round($progress) }}%</span>
                    </div>

                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                        <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ $progress }}%"></div>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-4 text-center">
                        <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="text-2xl font-bold">{{ $instance->stepInstances->where('status', 'active')->count() }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Active Steps</div>
                        </div>

                        <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="text-2xl font-bold">{{ $instance->stepInstances->where('status', 'pending')->count() }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Pending Steps</div>
                        </div>
                    </div>
                </div>

                @if (!empty($instance->custom_fields))
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h2 class="text-xl font-semibold mb-4">Custom Fields</h2>

                        <div class="space-y-3">
                            @foreach ($instance->custom_fields as $key => $value)
                                <div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ ucfirst(str_replace('_', ' ', $key)) }}</div>
                                    <div class="font-medium">
                                        @if (is_bool($value))
                                            {{ $value ? 'Yes' : 'No' }}
                                        @elseif (is_array($value))
                                            {{ implode(', ', $value) }}
                                        @else
                                            {{ $value }}
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
