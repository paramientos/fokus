<?php

use App\Models\WorkspaceWorkflow;
use Mary\Traits\Toast;

new class extends Livewire\Volt\Component {
    use Toast;

    public WorkspaceWorkflow $workflow;
    public $name;
    public $description;
    public $status;
    public $is_active;
    public $settings = [];

    protected $rules = [
        'name' => 'required|min:3|max:255',
        'description' => 'nullable|max:1000',
        'status' => 'required|in:draft,active,archived',
        'is_active' => 'boolean',
        'settings' => 'array'
    ];

    public function mount(WorkspaceWorkflow $workflow)
    {
        // Check if the workflow belongs to the current workspace
        if ($workflow->workspace_id !== session('workspace_id')) {
            $this->error('You do not have access to this workflow.');
            return redirect()->route('workflows.index');
        }

        $this->workflow = $workflow;
        $this->name = $workflow->name;
        $this->description = $workflow->description;
        $this->status = $workflow->status;
        $this->is_active = $workflow->is_active;
        $this->settings = $workflow->settings;
    }

    public function save()
    {
        $this->validate();

        $this->workflow->update([
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'is_active' => $this->is_active,
            'settings' => $this->settings
        ]);

        $this->success('Workflow updated successfully.');

        return redirect()->route('workflows.show', $this->workflow);
    }
}

?>

<div>
    <div class="mb-6">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-bold">Edit Workflow</h1>
            <div class="flex space-x-2">
                <x-button
                    color="gray"
                    icon="fas.arrow-left"
                    link="{{ route('workflows.show', $workflow) }}"
                >
                    Cancel
                </x-button>

                <x-button
                    color="blue"
                    icon="fas.list"
                    link="{{ route('workflows.steps.index', $workflow) }}"
                >
                    Manage Steps
                </x-button>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <form wire:submit="save">
                <div class="space-y-6">
                    <div>
                        <x-input
                            label="Workflow Name"
                            wire:model="name"
                            placeholder="Enter workflow name"
                            required
                        />
                    </div>

                    <div>
                        <x-textarea
                            label="Description"
                            wire:model="description"
                            placeholder="Describe the purpose of this workflow"
                            rows="4"
                        />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-select
                                label="Status"
                                wire:model="status"
                                :options="[
                                    'draft' => 'Draft',
                                    'active' => 'Active',
                                    'archived' => 'Archived'
                                ]"
                            />
                        </div>

                        <div>
                            <x-checkbox
                                label="Active"
                                wire:model="is_active"
                                hint="Inactive workflows cannot be started"
                            />
                        </div>
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                        <h3 class="text-lg font-medium mb-4">Workflow Settings</h3>

                        <div class="space-y-4">
                            <x-checkbox
                                label="Allow Parallel Steps"
                                wire:model="settings.allow_parallel_steps"
                                hint="Multiple steps can be active at the same time"
                            />

                            <x-checkbox
                                label="Require Approvals"
                                wire:model="settings.require_approvals"
                                hint="Steps require explicit approval to proceed"
                            />

                            <x-checkbox
                                label="Notify on Step Completion"
                                wire:model="settings.notify_on_step_completion"
                                hint="Send notifications when steps are completed"
                            />

                            <x-checkbox
                                label="Auto-assign Steps"
                                wire:model="settings.auto_assign_steps"
                                hint="Automatically assign steps based on roles"
                            />
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <x-button
                            color="gray"
                            link="{{ route('workflows.show', $workflow) }}"
                        >
                            Cancel
                        </x-button>

                        <x-button
                            type="submit"
                            icon="fas.save"
                        >
                            Update Workflow
                        </x-button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
