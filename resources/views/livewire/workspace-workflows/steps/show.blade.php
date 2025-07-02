<?php

use App\Models\WorkspaceWorkflow;
use App\Models\WorkspaceWorkflowStep;
use Mary\Traits\Toast;

new class extends Livewire\Volt\Component {
    use Toast;

    public WorkspaceWorkflow $workflow;
    public WorkspaceWorkflowStep $step;

    public function mount()
    {
        // Check if the workflow belongs to the current workspace
        if ($this->workflow->workspace_id !== session('workspace_id')) {
            $this->error('You do not have access to this workflow.');
            return redirect()->route('workflows.index');
        }

        // Check if the step belongs to the workflow
        if ($this->step->workspace_workflow_id !== $this->workflow->id) {
            $this->error('This step does not belong to the selected workflow.');
            return redirect()->route('workflows.steps.index', $this->workflow);
        }

    }

    public function getStepTypeDisplayProperty()
    {
        $types = [
            'task' => 'Task',
            'approval' => 'Approval',
            'form' => 'Form',
            'notification' => 'Notification',
            'automation' => 'Automation'
        ];

        return $types[$this->step->step_type] ?? ucfirst($this->step->step_type);
    }

    public function getStepConfigDisplayProperty()
    {
        $config = [];

        switch ($this->step->step_type) {
            case 'task':
                if (isset($this->step->step_config['requires_approval'])) {
                    $config['Requires Approval'] = $this->step->step_config['requires_approval'] ? 'Yes' : 'No';
                }

                if (isset($this->step->step_config['time_limit'])) {
                    $config['Time Limit'] = $this->step->step_config['time_limit'] ? $this->step->step_config['time_limit'] . ' hours' : 'No limit';
                }
                break;

            case 'approval':
                if (isset($this->step->step_config['min_approvals'])) {
                    $config['Minimum Approvals'] = $this->step->step_config['min_approvals'];
                }

                if (isset($this->step->step_config['rejection_behavior'])) {
                    $behaviors = [
                        'stop_workflow' => 'Stop Workflow',
                        'return_to_previous' => 'Return to Previous Step',
                        'continue_anyway' => 'Continue Anyway'
                    ];

                    $config['Rejection Behavior'] = $behaviors[$this->step->step_config['rejection_behavior']] ?? ucfirst(str_replace('_', ' ', $this->step->step_config['rejection_behavior']));
                }
                break;

            case 'form':
                if (isset($this->step->step_config['form_fields'])) {
                    $config['Form Fields'] = count($this->step->step_config['form_fields']);
                }
                break;

            case 'notification':
                if (isset($this->step->step_config['notification_type'])) {
                    $config['Notification Type'] = ucfirst($this->step->step_config['notification_type']);
                }

                if (isset($this->step->step_config['template'])) {
                    $config['Template'] = ucfirst($this->step->step_config['template']);
                }
                break;

            case 'automation':
                if (isset($this->step->step_config['action_type'])) {
                    $config['Action Type'] = ucfirst(str_replace('_', ' ', $this->step->step_config['action_type']));
                }
                break;
        }

        return $config;
    }

    public function getFormFieldsProperty()
    {
        if ($this->step->step_type !== 'form' || !isset($this->step->step_config['form_fields'])) {
            return [];
        }

        return $this->step->step_config['form_fields'];
    }
}

?>

<div>
    <div class="mb-6">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h1 class="text-2xl font-bold">Workflow Step Details</h1>
                <p class="text-gray-600 dark:text-gray-400">
                    {{ $workflow->name }} &raquo; {{ $step->name }}
                </p>
            </div>

            <div class="flex space-x-2">
                <x-button
                    color="gray"
                    icon="fas.arrow-left"
                    link="{{ route('workflows.steps.index', $workflow) }}"
                >
                    Back to Steps
                </x-button>

                <x-button
                    icon="fas.pen"
                    link="{{ route('workflows.steps.edit', ['workflow' => $workflow, 'step' => $step]) }}"
                >
                    Edit Step
                </x-button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="space-y-6">
                        <div>
                            <h2 class="text-xl font-semibold mb-4">Step Information</h2>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Name</div>
                                    <div class="font-medium">{{ $step->name }}</div>
                                </div>

                                <div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Type</div>
                                    <div class="font-medium">{{ $this->stepTypeDisplay }}</div>
                                </div>

                                <div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Order</div>
                                    <div class="font-medium">{{ $step->order }}</div>
                                </div>

                                <div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Status</div>
                                    <div class="font-medium">{{ ucfirst($step->status) }}</div>
                                </div>

                                <div class="md:col-span-2">
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Description</div>
                                    <div class="font-medium">{{ $step->description ?: 'No description provided' }}</div>
                                </div>

                                <div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Assigned To</div>
                                    <div class="font-medium">
                                        @if ($step->assignee)
                                            {{ $step->assignee->name }}
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500">Not assigned</span>
                                        @endif
                                    </div>
                                </div>

                                <div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Created</div>
                                    <div class="font-medium">{{ $step->created_at->format('M d, Y H:i') }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <h2 class="text-xl font-semibold mb-4">Step Configuration</h2>

                            @if (empty($this->stepConfigDisplay))
                                <div class="text-gray-500 dark:text-gray-400">No configuration settings</div>
                            @else
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    @foreach ($this->stepConfigDisplay as $key => $value)
                                        <div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $key }}</div>
                                            <div class="font-medium">{{ $value }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @if ($step->step_type === 'form' && !empty($this->formFields))
                                <div class="mt-6">
                                    <h3 class="text-lg font-medium mb-3">Form Fields</h3>

                                    <div class="space-y-3">
                                        @foreach ($this->formFields as $field)
                                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                                <div class="flex justify-between">
                                                    <div class="font-medium">{{ $field['name'] }}</div>
                                                    <div class="text-sm text-gray-500">
                                                        {{ ucfirst($field['type']) }}
                                                        @if ($field['required'])
                                                            <span class="text-red-500 ml-1">*</span>
                                                        @endif
                                                    </div>
                                                </div>

                                                @if ($field['type'] === 'select' && !empty($field['options']))
                                                    <div class="mt-2">
                                                        <div class="text-sm text-gray-500">Options:</div>
                                                        <div class="flex flex-wrap gap-2 mt-1">
                                                            @foreach ($field['options'] as $option)
                                                                <span
                                                                    class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-sm">
                                                                    {{ $option }}
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold mb-4">Step Position</h2>

                    <div class="space-y-4">
                        @php
                            $steps = $workflow->steps()->orderBy('order')->get();
                            $currentIndex = $steps->search(function($item) use ($step) {
                                return $item->id === $step->id;
                            });
                            $previousStep = $currentIndex > 0 ? $steps[$currentIndex - 1] : null;
                            $nextStep = $currentIndex < $steps->count() - 1 ? $steps[$currentIndex + 1] : null;
                        @endphp

                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Previous Step</div>
                            <div class="font-medium">
                                @if ($previousStep)
                                    <a href="{{ route('workflows.steps.show', ['workflow' => $workflow, 'step' => $previousStep]) }}"
                                       class="text-blue-600 dark:text-blue-400 hover:underline">
                                        {{ $previousStep->name }}
                                    </a>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">None (First Step)</span>
                                @endif
                            </div>
                        </div>

                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Current Step</div>
                            <div class="font-medium text-blue-600 dark:text-blue-400">
                                {{ $step->name }}
                            </div>
                        </div>

                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Next Step</div>
                            <div class="font-medium">
                                @if ($nextStep)
                                    <a href="{{ route('workflows.steps.show', ['workflow' => $workflow, 'step' => $nextStep]) }}"
                                       class="text-blue-600 dark:text-blue-400 hover:underline">
                                        {{ $nextStep->name }}
                                    </a>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">None (Last Step)</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-semibold mb-4">Step Statistics</h2>

                        <div class="space-y-4">
                            <div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Active Instances</div>
                                <div
                                    class="font-medium">{{ $step->instances()->where('status', 'active')->count() }}</div>
                            </div>

                            <div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Completed Instances</div>
                                <div
                                    class="font-medium">{{ $step->instances()->where('status', 'completed')->count() }}</div>
                            </div>

                            <div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Rejected Instances</div>
                                <div
                                    class="font-medium">{{ $step->instances()->where('status', 'rejected')->count() }}</div>
                            </div>

                            <div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Average Completion Time</div>
                                <div class="font-medium">
                                    @php
                                        $completedInstances = $step->instances()
                                            ->where('status', 'completed')
                                            ->whereNotNull('completed_at')
                                            ->whereNotNull('started_at')
                                            ->get();

                                        $totalHours = 0;
                                        $count = count($completedInstances);

                                        foreach ($completedInstances as $instance) {
                                            $totalHours += $instance->started_at->diffInHours($instance->completed_at);
                                        }

                                        $avgHours = $count > 0 ? round($totalHours / $count, 1) : 0;
                                    @endphp

                                    @if ($count > 0)
                                        {{ $avgHours }} hours
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">No data</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
