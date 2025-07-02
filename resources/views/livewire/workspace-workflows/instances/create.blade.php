<?php

use App\Models\User;
use App\Models\WorkspaceWorkflow;
use App\Models\WorkspaceWorkflowInstance;
use App\Models\WorkspaceWorkflowStepInstance;
use Mary\Traits\Toast;

new class extends Livewire\Volt\Component {
    use Toast;

    public WorkspaceWorkflow $workflow;
    public $name = '';
    public $description = '';
    public $assigned_users = [];
    public $custom_fields = [];

    protected function rules()
    {
        return [
            'name' => 'required|min:3|max:255',
            'description' => 'nullable|max:1000',
            'assigned_users' => 'array',
            'assigned_users.*' => 'exists:users,id',
            'custom_fields' => 'array'
        ];
    }

    public function mount(WorkspaceWorkflow $workflow)
    {
        // Check if the workflow belongs to the current workspace
        if ($workflow->workspace_id !== session('workspace_id')) {
            $this->error('You do not have access to this workflow.');
            return redirect()->route('workflows.index');
        }

        $this->workflow = $workflow;

        // Set default name
        $this->name = $workflow->name . ' #' . (WorkspaceWorkflowInstance::where('workspace_workflow_id', $workflow->id)->count() + 1);

        // Initialize assigned_users with default assignments from workflow steps
        $steps = $workflow->steps()->orderBy('order')->get();
        foreach ($steps as $step) {
            if ($step->assigned_to) {
                $this->assigned_users[$step->id] = $step->assigned_to;
            }
        }
    }

    public function getWorkspaceMembersProperty()
    {
        return User::whereHas('workspaceMembers', function ($query) {
            $query->where('workspace_id', session('workspace_id'));
        })->get();
    }

    public function getWorkflowStepsProperty()
    {
        return $this->workflow->steps()->orderBy('order')->get();
    }

    public function create()
    {
        $this->validate();

        // Create the workflow instance
        $instance = WorkspaceWorkflowInstance::create([
            'workspace_workflow_id' => $this->workflow->id,
            'workspace_id' => session('workspace_id'),
            'name' => $this->name,
            'description' => $this->description,
            'status' => 'active',
            'initiated_by' => auth()->id(),
            'custom_fields' => $this->custom_fields,
            'title'=> $this->name
        ]);

        // Create step instances for all steps in the workflow
        $steps = $this->workflow->steps()->orderBy('order')->get();
        $firstStep = true;

        foreach ($steps as $step) {
            $assignedTo = $this->assigned_users[$step->id] ?? $step->assigned_to;

            $stepInstance = WorkspaceWorkflowStepInstance::create([
                'workspace_workflow_instance_id' => $instance->id,
                'workspace_workflow_step_id' => $step->id,
                'status' => $firstStep ? 'active' : 'pending',
                'assigned_to' => $assignedTo,
                'started_at' => $firstStep ? now() : null,
            ]);

            $firstStep = false;
        }

        $this->success('Workflow instance created successfully.');

        return redirect()->route('workflows.instances.process', ['workflow' => $this->workflow, 'instance' => $instance]);
    }
}

?>

<div>
    <div class="mb-6">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h1 class="text-2xl font-bold">Start New Workflow</h1>
                <p class="text-gray-600 dark:text-gray-400">
                    {{ $workflow->name }}
                </p>
            </div>

            <x-button
                color="gray"
                icon="fas.arrow-left"
                link="{{ route('workflows.instances.index', $workflow) }}"
            >
                Cancel
            </x-button>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <form wire:submit="create">
                <div class="space-y-6">
                    <div>
                        <x-input
                            label="Instance Name"
                            wire:model="name"
                            placeholder="Enter a name for this workflow instance"
                            required
                        />
                    </div>

                    <div>
                        <x-textarea
                            label="Description"
                            wire:model="description"
                            placeholder="Describe the purpose of this workflow instance"
                            rows="3"
                        />
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                        <h3 class="text-lg font-medium mb-4">Workflow Steps</h3>

                        <div class="space-y-4">
                            @foreach ($this->workflowSteps as $index => $step)
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                    <div class="flex items-start">
                                        <div class="bg-blue-100 dark:bg-blue-900 rounded-full w-8 h-8 flex items-center justify-center text-blue-800 dark:text-blue-200 font-bold mr-3">
                                            {{ $index + 1 }}
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex justify-between">
                                                <h4 class="font-medium">{{ $step->name }}</h4>
                                                <span class="text-sm text-gray-500">{{ ucfirst($step->step_type) }}</span>
                                            </div>
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                {{ $step->description ?: 'No description' }}
                                            </p>

                                            <div class="mt-3">
                                                <x-select
                                                    label="Assign To"
                                                    wire:model="assigned_users.{{ $step->id }}"
                                                    placeholder="Select a user"
                                                    :options="$this->workspaceMembers->select('name', 'id')"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @if ($workflow->settings['custom_fields'] ?? false)
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <h3 class="text-lg font-medium mb-4">Custom Fields</h3>

                            <div class="space-y-4">
                                @foreach ($workflow->settings['custom_fields'] as $field)
                                    @if ($field['type'] === 'text')
                                        <div>
                                            <x-input
                                                label="{{ $field['name'] }}"
                                                wire:model="custom_fields.{{ $field['id'] }}"
                                                placeholder="Enter {{ strtolower($field['name']) }}"
                                                required="{{ $field['required'] ?? false }}"
                                            />
                                        </div>
                                    @elseif ($field['type'] === 'textarea')
                                        <div>
                                            <x-textarea
                                                label="{{ $field['name'] }}"
                                                wire:model="custom_fields.{{ $field['id'] }}"
                                                placeholder="Enter {{ strtolower($field['name']) }}"
                                                required="{{ $field['required'] ?? false }}"
                                                rows="3"
                                            />
                                        </div>
                                    @elseif ($field['type'] === 'select' && isset($field['options']))
                                        <div>
                                            <x-select
                                                label="{{ $field['name'] }}"
                                                wire:model="custom_fields.{{ $field['id'] }}"
                                                :options="collect($field['options'])->mapWithKeys(function($option) {
                                                    return [$option => $option];
                                                })"
                                                required="{{ $field['required'] ?? false }}"
                                            />
                                        </div>
                                    @elseif ($field['type'] === 'date')
                                        <div>
                                            <x-input
                                                type="date"
                                                label="{{ $field['name'] }}"
                                                wire:model="custom_fields.{{ $field['id'] }}"
                                                required="{{ $field['required'] ?? false }}"
                                            />
                                        </div>
                                    @elseif ($field['type'] === 'checkbox')
                                        <div>
                                            <x-checkbox
                                                label="{{ $field['name'] }}"
                                                wire:model="custom_fields.{{ $field['id'] }}"
                                            />
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="flex justify-end space-x-3 pt-4">
                        <x-button
                            color="gray"
                            link="{{ route('workflows.instances.index', $workflow) }}"
                        >
                            Cancel
                        </x-button>

                        <x-button
                            type="submit"
                            icon="fas.play"
                        >
                            Start Workflow
                        </x-button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
