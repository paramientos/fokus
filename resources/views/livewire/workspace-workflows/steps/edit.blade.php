<?php

use App\Models\User;
use App\Models\WorkspaceWorkflow;
use App\Models\WorkspaceWorkflowStep;
use Mary\Traits\Toast;

new class extends Livewire\Volt\Component {
    use Toast;

    public WorkspaceWorkflow $workflow;
    public WorkspaceWorkflowStep $step;
    public $name;
    public $description;
    public $step_type;
    public $assigned_to;
    public $step_config = [];

    protected $rules = [
        'name' => 'required|min:3|max:255',
        'description' => 'nullable|max:1000',
        'step_type' => 'required|in:task,approval,form,notification,automation',
        'assigned_to' => 'nullable|exists:users,id',
        'step_config' => 'array'
    ];

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

        $step = $this->step;

        $this->name = $step->name;
        $this->description = $step->description;
        $this->step_type = $step->step_type;
        $this->assigned_to = $step->assigned_to;
        $this->step_config = $step->step_config;
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

        $this->step->update([
            'name' => $this->name,
            'description' => $this->description,
            'step_type' => $this->step_type,
            'step_config' => $this->step_config,
            'assigned_to' => $this->assigned_to
        ]);

        $this->success('Workflow step updated successfully.');

        // Dispatch event for parent component to refresh
        $this->dispatch('step-updated');

        return redirect()->route('workflows.steps.index', $this->workflow);
    }

    public function addFormField()
    {
        if (!isset($this->step_config['form_fields'])) {
            $this->step_config['form_fields'] = [];
        }

        $fieldId = 'field_' . uniqid();

        $this->step_config['form_fields'][] = [
            'id' => $fieldId,
            'name' => 'New Field',
            'type' => 'text',
            'required' => false,
            'options' => []
        ];
    }

    public function removeFormField($index)
    {
        unset($this->step_config['form_fields'][$index]);
        $this->step_config['form_fields'] = array_values($this->step_config['form_fields']);
    }

    public function updateFormFieldName($index, $value)
    {
        $this->step_config['form_fields'][$index]['name'] = $value;
    }

    public function updateFormFieldType($index, $value)
    {
        $this->step_config['form_fields'][$index]['type'] = $value;
    }

    public function toggleFormFieldRequired($index)
    {
        $this->step_config['form_fields'][$index]['required'] = !$this->step_config['form_fields'][$index]['required'];
    }
}

?>

<div>
    <div class="mb-6">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h1 class="text-2xl font-bold">Edit Workflow Step</h1>
                <p class="text-gray-600 dark:text-gray-400">
                    {{ $workflow->name }} &raquo; {{ $step->name }}
                </p>
            </div>

            <x-button
                color="gray"
                icon="fas.arrow-left"
                link="{{ route('workflows.steps.index', $workflow) }}"
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
                                <div class="flex justify-between items-center mb-2">
                                    <h4 class="font-medium">Form Fields</h4>
                                    <x-button
                                        size="sm"
                                        icon="fas.plus"
                                        wire:click="addFormField"
                                    >
                                        Add Field
                                    </x-button>
                                </div>

                                @if (empty($step_config['form_fields']))
                                    <div
                                        class="text-center py-4 text-gray-500 border border-dashed border-gray-300 dark:border-gray-600 rounded-lg">
                                        <p>No form fields added yet</p>
                                        <x-button
                                            size="sm"
                                            icon="fas.plus"
                                            wire:click="addFormField"
                                            class="mt-2"
                                        >
                                            Add Field
                                        </x-button>
                                    </div>
                                @else
                                    <div class="space-y-3">
                                        @foreach ($step_config['form_fields'] as $index => $field)
                                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                                <div class="flex justify-between items-start mb-3">
                                                    <div class="flex-1 mr-4">
                                                        <x-input
                                                            label="Field Name"
                                                            value="{{ $field['name'] }}"
                                                            wire:change="updateFormFieldName({{ $index }}, $event.target.value)"
                                                        />
                                                    </div>
                                                    <x-button
                                                        icon="fas.trash"
                                                        wire:click="removeFormField({{ $index }})"
                                                        size="xs"
                                                        color="red"
                                                    />
                                                </div>

                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                    <div>
                                                        <x-select
                                                            label="Field Type"
                                                            value="{{ $field['type'] }}"
                                                            wire:change="updateFormFieldType({{ $index }}, $event.target.value)"
                                                            :options="[
                                                                ['id' => 'text', 'name' => 'Text'],
                                                                ['id' => 'textarea', 'name' => 'Text Area'],
                                                                ['id' => 'number', 'name' => 'Number'],
                                                                ['id' => 'select', 'name' => 'Select'],
                                                                ['id' => 'checkbox', 'name' => 'Checkbox'],
                                                                ['id' => 'date', 'name' => 'Date'],
                                                                ['id' => 'file', 'name' => 'File Upload']
                                                            ]"
                                                        />
                                                    </div>

                                                    <div class="flex items-center">
                                                        <x-checkbox
                                                            label="Required Field"
                                                            checked="{{ $field['required'] }}"
                                                            wire:click="toggleFormFieldRequired({{ $index }})"
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
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
                            Update Step
                        </x-button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
