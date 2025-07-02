<?php

use App\Models\WorkspaceWorkflow;
use Mary\Traits\Toast;

new class extends Livewire\Volt\Component {
    use Toast;

    public $name = '';
    public $description = '';
    public $status = 'draft';
    public $is_active = true;
    public $settings = [
        'allow_parallel_steps' => false,
        'require_approvals' => false,
        'notify_on_step_completion' => true,
        'auto_assign_steps' => false
    ];

    protected $rules = [
        'name' => 'required|min:3|max:255',
        'description' => 'nullable|max:1000',
        'status' => 'required|in:draft,active,archived',
        'is_active' => 'boolean',
        'settings' => 'array'
    ];

    public function save()
    {
        $this->validate();

        $workflow = WorkspaceWorkflow::create([
            'name' => $this->name,
            'description' => $this->description,
            'workspace_id' => session('workspace_id'),
            'created_by' => auth()->id(),
            'status' => $this->status,
            'is_active' => $this->is_active,
            'settings' => $this->settings
        ]);

        $this->success('Workflow created successfully.');

        return redirect()->route('workflows.steps.create', $workflow);
    }
}

?>

<div>
    <div class="mb-6">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-bold">Create New Workflow</h1>
            <a href="{{ route('workflows.index') }}" class="inline-flex">
                <x-button color="gray" icon="fas.arrow-left">Back to Workflows</x-button>
            </a>
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
                                    ['id' => 'draft', 'name' => 'Draft'],
                                    ['id' => 'active', 'name' => 'Active'],
                                    ['id' => 'archived', 'name' => 'Archived']
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
                            href="{{ route('workflows.index') }}"
                        >
                            Cancel
                        </x-button>

                        <x-button
                            type="submit"
                            icon="fas.save"
                        >
                            Create Workflow
                        </x-button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
