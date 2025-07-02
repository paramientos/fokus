<?php

use App\Models\WorkspaceWorkflow;
use App\Models\WorkspaceWorkflowInstance;
use Mary\Traits\Toast;

new class extends Livewire\Volt\Component {
    use Toast;

    public WorkspaceWorkflow $workflow;
    public $showCreateInstanceModal = false;
    public $newInstance = [
        'title' => '',
        'description' => ''
    ];

    protected $rules = [
        'newInstance.title' => 'required|min:3|max:255',
        'newInstance.description' => 'nullable|max:1000'
    ];

    public function mount(WorkspaceWorkflow $workflow)
    {
        $this->workflow = $workflow;

        // Check if the workflow belongs to the current workspace
        if ($workflow->workspace_id !== session('workspace_id')) {
            $this->error('You do not have access to this workflow.');
            return redirect()->route('workflows.index');
        }
    }

    public function getInstancesProperty()
    {
        return WorkspaceWorkflowInstance::where('workspace_workflow_id', $this->workflow->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
    }

    public function getStepsProperty()
    {
        return $this->workflow->steps()->orderBy('order')->get();
    }

    public function createInstance()
    {
        $this->validate();

        // Check if workflow has steps
        if ($this->workflow->steps()->count() === 0) {
            $this->error('Cannot start workflow without steps. Please add steps first.');
            return;
        }

        // Create new workflow instance
        $instance = WorkspaceWorkflowInstance::create([
            'workspace_workflow_id' => $this->workflow->id,
            'workspace_id' => session('workspace_id'),
            'initiated_by' => auth()->id(),
            'title' => $this->newInstance['title'],
            'description' => $this->newInstance['description'],
            'status' => 'in_progress',
            'current_step' => 1,
            'started_at' => now()
        ]);

        // Create step instances for each step in the workflow
        foreach ($this->workflow->steps as $step) {
            $instance->stepInstances()->create([
                'workspace_workflow_step_id' => $step->id,
                'assigned_to' => $step->assigned_to,
                'status' => $step->order === 1 ? 'in_progress' : 'pending',
                'started_at' => $step->order === 1 ? now() : null
            ]);
        }

        $this->success('Workflow instance created successfully.');
        $this->showCreateInstanceModal = false;
        $this->newInstance = [
            'title' => '',
            'description' => ''
        ];

        return redirect()->route('workflows.instances.show', [
            'workflow' => $this->workflow,
            'instance' => $instance
        ]);
    }
}

?>

<div>
    <div class="mb-6">
        <div class="flex justify-between items-center mb-4">
            <div class="flex items-center">
                <h1 class="text-2xl font-bold">{{ $workflow->name }}</h1>
                <div class="ml-3">
                    @if ($workflow->status === 'draft')
                        <x-badge color="gray">Draft</x-badge>
                    @elseif ($workflow->status === 'active')
                        <x-badge color="green">Active</x-badge>
                    @elseif ($workflow->status === 'archived')
                        <x-badge color="yellow">Archived</x-badge>
                    @endif

                    @if (!$workflow->is_active)
                        <x-badge color="red" class="ml-1">Inactive</x-badge>
                    @endif
                </div>
            </div>

            <div class="flex space-x-2">
                <x-button
                    color="gray"
                    icon="fas.arrow-left"
                    link="{{ route('workflows.index') }}"
                >
                    Back
                </x-button>

                <x-button
                    icon="fas.pen"
                    link="{{ route('workflows.edit', $workflow) }}"
                >
                    Edit
                </x-button>

                <x-button
                    color="green"
                    icon="fas.play"
                    wire:click="$set('showCreateInstanceModal', true)"
                    :disabled="!$workflow->is_active || $workflow->steps->isEmpty()"
                >
                    Start Workflow
                </x-button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Workflow Details -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                    <h2 class="text-lg font-medium mb-4">Workflow Details</h2>

                    <div class="prose dark:prose-invert max-w-none">
                        <p>{{ $workflow->description ?: 'No description provided.' }}</p>
                    </div>

                    <div class="mt-6 grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="font-medium">Created by:</span>
                            <span>{{ $workflow->creator->name }}</span>
                        </div>
                        <div>
                            <span class="font-medium">Created on:</span>
                            <span>{{ $workflow->created_at->format('M d, Y H:i') }}</span>
                        </div>
                        <div>
                            <span class="font-medium">Last updated:</span>
                            <span>{{ $workflow->updated_at->format('M d, Y H:i') }}</span>
                        </div>
                        <div>
                            <span class="font-medium">Steps:</span>
                            <span>{{ $workflow->steps->count() }}</span>
                        </div>
                    </div>
                </div>

                <!-- Workflow Steps -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-medium">Workflow Steps</h2>
                        <a href="{{ route('workflows.steps.index', $workflow) }}">
                            <x-button size="sm" icon="fas.list">Manage Steps</x-button>
                        </a>
                    </div>

                    @if ($this->steps->isEmpty())
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-sitemap text-4xl mb-3"></i>
                            <p class="text-lg font-medium">No steps defined</p>
                            <p class="text-sm mt-1">Add steps to make this workflow functional</p>
                            <a href="{{ route('workflows.steps.create', $workflow) }}" class="mt-3 inline-block">
                                <x-button size="sm" icon="fas.plus">Add First Step</x-button>
                            </a>
                        </div>
                    @else
                        <div class="space-y-4">
                            @foreach ($this->steps as $step)
                                <div
                                    class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 flex items-start">
                                    <div
                                        class="bg-blue-100 dark:bg-blue-900 rounded-full w-8 h-8 flex items-center justify-center text-blue-800 dark:text-blue-200 font-bold mr-3">
                                        {{ $step->order }}
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex justify-between">
                                            <h3 class="font-medium">{{ $step->name }}</h3>
                                            <span class="text-sm text-gray-500">{{ ucfirst($step->step_type) }}</span>
                                        </div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            {{ $step->description ?: 'No description' }}
                                        </p>
                                        @if ($step->assignee)
                                            <div class="mt-2 flex items-center text-sm">
                                                <i class="fas fa-user mr-1 text-gray-500"></i>
                                                <span>Assigned to: {{ $step->assignee->name }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Sidebar -->
            <div>
                <!-- Workflow Settings -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                    <h2 class="text-lg font-medium mb-4">Workflow Settings</h2>

                    <div class="space-y-3">
                        <div class="flex items-center">
                            <i class="fas fa-{{ $workflow->settings['allow_parallel_steps'] ? 'check text-green-500' : 'times text-gray-400' }} w-5"></i>
                            <span class="ml-2">Allow Parallel Steps</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-{{ $workflow->settings['require_approvals'] ? 'check text-green-500' : 'times text-gray-400' }} w-5"></i>
                            <span class="ml-2">Require Approvals</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-{{ $workflow->settings['notify_on_step_completion'] ? 'check text-green-500' : 'times text-gray-400' }} w-5"></i>
                            <span class="ml-2">Notify on Step Completion</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-{{ $workflow->settings['auto_assign_steps'] ? 'check text-green-500' : 'times text-gray-400' }} w-5"></i>
                            <span class="ml-2">Auto-assign Steps</span>
                        </div>
                    </div>
                </div>

                <!-- Recent Instances -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-medium">Recent Instances</h2>
                        <a href="{{ route('workflows.instances.index',$workflow) }}">
                            <x-button size="xs" color="gray">View All</x-button>
                        </a>
                    </div>

                    @if ($this->instances->isEmpty())
                        <div class="text-center py-4 text-gray-500">
                            <p class="text-sm">No instances yet</p>
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach ($this->instances as $instance)
                                <a href="{{ route('workflows.instances.show', ['workflow' => $workflow, 'instance' => $instance]) }}"
                                   class="block border border-gray-200 dark:border-gray-700 rounded-lg p-3 hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <div class="flex justify-between">
                                        <h3 class="font-medium">{{ $instance->title }}</h3>
                                        <span class="text-xs">
                                            @if ($instance->status === 'in_progress')
                                                <x-badge color="blue" size="xs">In Progress</x-badge>
                                            @elseif ($instance->status === 'completed')
                                                <x-badge color="green" size="xs">Completed</x-badge>
                                            @elseif ($instance->status === 'cancelled')
                                                <x-badge color="red" size="xs">Cancelled</x-badge>
                                            @endif
                                        </span>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        Started {{ $instance->started_at->diffForHumans() }}
                                        by {{ $instance->initiator->name }}
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Create Instance Modal -->
    <x-modal wire:model="showCreateInstanceModal">
        <x-card title="Start New Workflow Instance">
            <p class="mb-4">
                You are about to start a new instance of <strong>{{ $workflow->name }}</strong>.
                Please provide the following information:
            </p>

            <div class="space-y-4">
                <x-input
                    label="Title"
                    wire:model="newInstance.title"
                    placeholder="Enter a title for this workflow instance"
                    required
                />

                <x-textarea
                    label="Description"
                    wire:model="newInstance.description"
                    placeholder="Describe the purpose of this workflow instance"
                    rows="3"
                />
            </div>

            <x-slot:actions>
                <div class="flex justify-end gap-x-4">
                    <x-button flat label="Cancel" x-on:click="close"/>
                    <x-button primary label="Start Workflow" wire:click.prevent="createInstance"/>
                </div>
            </x-slot:actions>
        </x-card>
    </x-modal>
</div>
