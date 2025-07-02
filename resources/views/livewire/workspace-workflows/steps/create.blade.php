<?php

use App\Models\User;
use App\Models\WorkspaceWorkflow;
use App\Models\WorkspaceWorkflowStep;
use Mary\Traits\Toast;

new class extends Livewire\Volt\Component {
    use Toast;

    public WorkspaceWorkflow $workflow;
    public $name = '';
    public $description = '';
    public $step_type = 'task';
    public $assigned_to = null;
    public $step_config = [
        'requires_approval' => false,
        'time_limit' => null,
        'form_fields' => [],
        'notifications' => []
    ];

    protected $rules = [
        'name' => 'required|min:3|max:255',
        'description' => 'nullable|max:1000',
        'step_type' => 'required|in:task,approval,form,notification,automation',
        'assigned_to' => 'nullable|exists:users,id',
        'step_config' => 'array'
    ];

    public function mount(WorkspaceWorkflow $workflow)
    {
        // Check if the workflow belongs to the current workspace
        if ($workflow->workspace_id !== session('workspace_id')) {
            $this->error('You do not have access to this workflow.');
            return redirect()->route('workflows.index');
        }

        $this->workflow = $workflow;
    }

    public function getWorkspaceMembersProperty()
    {
        return User::whereHas('workspaceMembers', function ($query) {
            $query->where('workspace_id', session('workspace_id'));
        })->get();
    }

    public function save()
    {
        $this->validate();

        // Get the next order number
        $nextOrder = $this->workflow->steps()->max('order') + 1;

        WorkspaceWorkflowStep::create([
            'name' => $this->name,
            'description' => $this->description,
            'workspace_workflow_id' => $this->workflow->id,
            'order' => $nextOrder,
            'step_type' => $this->step_type,
            'step_config' => $this->step_config,
            'assigned_to' => $this->assigned_to,
            'status' => 'active'
        ]);

        $this->success('Workflow step created successfully.');

        return redirect()->route('workflows.steps.index', $this->workflow);
    }

    public function updatedStepType()
    {
        // Reset step_config when step_type changes
        switch ($this->step_type) {
            case 'task':
                $this->step_config = [
                    'requires_approval' => false,
                    'time_limit' => null
                ];
                break;
            case 'approval':
                $this->step_config = [
                    'approvers' => [],
                    'min_approvals' => 1,
                    'rejection_behavior' => 'stop_workflow'
                ];
                break;
            case 'form':
                $this->step_config = [
                    'form_fields' => [],
                    'required_fields' => []
                ];
                break;
            case 'notification':
                $this->step_config = [
                    'notification_type' => 'email',
                    'recipients' => [],
                    'template' => 'default'
                ];
                break;
            case 'automation':
                $this->step_config = [
                    'action_type' => 'status_update',
                    'target_entity' => null,
                    'parameters' => []
                ];
                break;
        }
    }
}

?>

<div>
    <div class="mb-6">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h1 class="text-2xl font-bold">Add Workflow Step</h1>
                <p class="text-gray-600 dark:text-gray-400">
                    {{ $workflow->name }}
                </p>
            </div>

            <x-button
                color="gray"
                icon="fas.arrow-left"
                href="{{ route('workflows.steps.index', $workflow) }}"
            >
                Back to Steps
            </x-button>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <form wire:submit="save">
                <div class="space-y-6">
                    <div>
                        <x-input
                            label="Step Name"
                            wire:model="name"
                            placeholder="Enter step name"
                            required
                        />
                    </div>

                    <div>
                        <x-textarea
                            label="Description"
                            wire:model="description"
                            placeholder="Describe what happens in this step"
                            rows="3"
                        />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-select
                                label="Step Type"
                                wire:model.live="step_type"
                                :options="[
                                    ['id' => 'task', 'name' => 'Task'],
                                    ['id' => 'approval', 'name' => 'Approval'],
                                    ['id' => 'form', 'name' => 'Form'],
                                    ['id' => 'notification', 'name' => 'Notification'],
                                    ['id' => 'automation', 'name' => 'Automation']
                                ]"
                            />
                        </div>

                        <div>
                            <x-select
                                label="Assign To"
                                wire:model="assigned_to"
                                placeholder="Select a user (optional)"
                                :options="$this->workspaceMembers->select('name', 'id')"
                            />
                        </div>
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                        <h3 class="text-lg font-medium mb-4">Step Configuration</h3>

                        @if ($step_type === 'task')
                            <div class="space-y-4">
                                <x-checkbox
                                    label="Requires Approval"
                                    wire:model="step_config.requires_approval"
                                    hint="Task completion must be approved by a supervisor"
                                />

                                <div>
                                    <x-input
                                        type="number"
                                        label="Time Limit (hours)"
                                        wire:model="step_config.time_limit"
                                        placeholder="Leave empty for no time limit"
                                        min="0"
                                    />
                                </div>
                            </div>
                        @elseif ($step_type === 'approval')
                            <div class="space-y-4">
                                <div>
                                    <x-input
                                        type="number"
                                        label="Minimum Approvals Required"
                                        wire:model="step_config.min_approvals"
                                        min="1"
                                        value="1"
                                    />
                                </div>

                                <div>
                                    <x-select
                                        label="Rejection Behavior"
                                        wire:model="step_config.rejection_behavior"
                                        :options="[
                                            ['id' => 'stop_workflow', 'name' => 'Stop Workflow'],
                                            ['id' => 'return_to_previous', 'name' => 'Return to Previous Step'],
                                            ['id' => 'continue_anyway', 'name' => 'Continue Anyway']
                                        ]"
                                    />
                                </div>
                            </div>
                        @elseif ($step_type === 'form')
                            <div class="space-y-4">
                                <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Form configuration will be available in the step editor after creation.
                                    </p>
                                </div>
                            </div>
                        @elseif ($step_type === 'notification')
                            <div class="space-y-4">
                                <div>
                                    <x-select
                                        label="Notification Type"
                                        wire:model="step_config.notification_type"
                                        :options="[
                                            ['id' => 'email', 'name' => 'Email'],
                                            ['id' => 'slack', 'name' => 'Slack'],
                                            ['id' => 'system', 'name' => 'System Notification']
                                        ]"
                                    />
                                </div>

                                <div>
                                    <x-select
                                        label="Template"
                                        wire:model="step_config.template"
                                        :options="[
                                            ['id' => 'default', 'name' => 'Default'],
                                            ['id' => 'alert', 'name' => 'Alert'],
                                            ['id' => 'reminder', 'name' => 'Reminder']
                                        ]"
                                    />
                                </div>
                            </div>
                        @elseif ($step_type === 'automation')
                            <div class="space-y-4">
                                <div>
                                    <x-select
                                        label="Action Type"
                                        wire:model="step_config.action_type"
                                        :options="[
                                            ['id' => 'status_update', 'name' => 'Update Status'],
                                            ['id' => 'assign_user', 'name' => 'Assign User'],
                                            ['id' => 'create_entity', 'name' => 'Create Entity'],
                                            ['id' => 'api_call', 'name' => 'API Call']
                                        ]"
                                    />
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <x-button
                            color="gray"
                            href="{{ route('workflows.steps.index', $workflow) }}"
                        >
                            Cancel
                        </x-button>

                        <x-button
                            type="submit"
                            icon="fas.save"
                        >
                            Create Step
                        </x-button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
