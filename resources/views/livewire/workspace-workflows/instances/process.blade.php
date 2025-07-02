<?php

use App\Models\WorkspaceWorkflow;
use App\Models\WorkspaceWorkflowInstance;
use Mary\Traits\Toast;

new class extends Livewire\Volt\Component {
    use Toast;

    public WorkspaceWorkflow $workflow;
    public WorkspaceWorkflowInstance $instance;
    /** @var ?\App\Models\WorkspaceWorkflowStepInstance $activeStepInstance */
    public $activeStepInstance = null;
    public $formData = [];
    public $comments = '';
    public $showCompleteModal = false;
    public $showRejectModal = false;
    public $rejectReason = '';
    public $showAdminApproveModal = false;
    public $adminApproveComments = '';

    public function mount()
    {
        // Check if the workflow belongs to the current workspace
        if ($this->workflow->workspace_id !== get_workspace_id()) {
            $this->error('You do not have access to this workflow.');
            return redirect()->route('workflows.index');
        }

        // Check if the instance belongs to this workflow
        if ($this->instance->workspace_workflow_id !== $this->workflow->id) {
            $this->error('This instance does not belong to the selected workflow.');
            return redirect()->route('workflows.instances.index', $this->workflow);
        }

        // Check if the instance is active
        if ($this->instance->status !== 'active') {
            $this->error('This workflow instance is not active.');
            return redirect()->route('workflows.instances.show', ['workflow' => $this->workflow, 'instance' => $this->instance]);
        }

        $this->loadActiveStep();
    }

    public function loadActiveStep()
    {
        $this->activeStepInstance = $this->instance->currentStepInstance();

        if ($this->activeStepInstance && $this->activeStepInstance->step->step_type === 'form') {
            $this->formData = $this->activeStepInstance->form_data ?? [];
        }
    }

    public function assignToMe()
    {
        if (!$this->activeStepInstance) {
            $this->error('No active step found.');
            return;
        }

        $this->activeStepInstance->update([
            'assigned_to' => auth()->id()
        ]);

        $this->success('Step assigned to you.');
        $this->loadActiveStep();
    }

    public function openCompleteModal()
    {
        if (!$this->activeStepInstance) {
            $this->error('No active step found.');
            return;
        }

        // Check if the user is assigned to this step
        if ($this->activeStepInstance->assigned_to && $this->activeStepInstance->assigned_to !== auth()->id()) {
            $this->error('You are not assigned to this step.');
            return;
        }

        $this->showCompleteModal = true;
    }

    public function openRejectModal()
    {
        if (!$this->activeStepInstance) {
            $this->error('No active step found.');
            return;
        }

        // Check if the user is assigned to this step
        if ($this->activeStepInstance->assigned_to && $this->activeStepInstance->assigned_to !== auth()->id()) {
            $this->error('You are not assigned to this step.');
            return;
        }

        $this->showRejectModal = true;
    }

    public function openAdminApproveModal()
    {
        $this->showAdminApproveModal = true;
    }

    public function completeStep()
    {
        if (!$this->activeStepInstance) {
            $this->error('No active step found.');
            return;
        }

        // For form steps, save the form data
        if ($this->activeStepInstance->step->step_type === 'form') {
            $this->activeStepInstance->update([
                'form_data' => $this->formData
            ]);
        }

        // Complete the step
        $this->activeStepInstance->complete(auth()->id(), $this->comments);

        // Advance to the next step
        $advanced = $this->instance->advanceToNextStep();

        if ($advanced) {
            $this->success('Step completed. Moving to the next step.');
        } else {
            $this->success('Workflow completed successfully!');
            return redirect()->route('workflows.instances.show', ['workflow' => $this->workflow, 'instance' => $this->instance]);
        }

        $this->comments = '';
        $this->showCompleteModal = false;

        // Reload the active step
        $this->loadActiveStep();
    }

    public function rejectStep()
    {
        if (!$this->activeStepInstance) {
            $this->error('No active step found.');
            return;
        }

        // Reject the step
        $this->activeStepInstance->reject(auth()->id(), $this->rejectReason);

        // Handle rejection based on workflow settings
        $stepConfig = $this->activeStepInstance->step->step_config ?? [];
        $rejectionBehavior = $stepConfig['rejection_behavior'] ?? 'stop_workflow';

        if ($rejectionBehavior === 'stop_workflow') {
            // Cancel the workflow
            $this->instance->update([
                'status' => 'cancelled',
                'completed_at' => now()
            ]);

            // Cancel all pending steps
            $this->instance->stepInstances()
                ->where('status', 'pending')
                ->update(['status' => 'cancelled']);

            $this->success('Step rejected. Workflow has been cancelled.');
            return redirect()->route('workflows.instances.show', ['workflow' => $this->workflow, 'instance' => $this->instance]);
        } else {
            // Continue to the next step
            $advanced = $this->instance->advanceToNextStep();

            if ($advanced) {
                $this->success('Step rejected. Moving to the next step.');
            } else {
                $this->success('Workflow completed with rejection.');
                return redirect()->route('workflows.instances.show', ['workflow' => $this->workflow, 'instance' => $this->instance]);
            }
        }

        $this->rejectReason = '';
        $this->showRejectModal = false;

        // Reload the active step
        $this->loadActiveStep();
    }

    public function approveWorkflowByAdmin()
    {
        // Sadece admin ve waiting_admin_approval durumunda çalışsın
        if (!auth()->user()->is_admin) {
            $this->error('Only admins can approve this workflow.');
            return;
        }
        if ($this->instance->status !== 'waiting_admin_approval') {
            $this->error('Workflow is not waiting for admin approval.');
            return;
        }
        $result = $this->instance->approveByAdmin(auth()->id(), $this->adminApproveComments);
        if ($result) {
            $this->success('Workflow approved and completed by admin.');
            return redirect()->route('workflows.instances.show', ['workflow' => $this->workflow, 'instance' => $this->instance]);
        } else {
            $this->error('Approval failed.');
        }
        $this->showAdminApproveModal = false;
        $this->adminApproveComments = '';
    }
}
?>

<div>
    <div class="mb-6">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h1 class="text-2xl font-bold">Process Workflow</h1>
                <p class="text-gray-600 dark:text-gray-400">
                    {{ $instance->name }}
                </p>
            </div>

            <div class="flex space-x-2">
                <x-button
                    color="gray"
                    icon="fas.eye"
                    link="{{ route('workflows.instances.show', ['workflow' => $workflow, 'instance' => $instance]) }}"
                >
                    View Details
                </x-button>

                <x-button
                    color="gray"
                    icon="fas.arrow-left"
                    link="{{ route('workflows.instances.index', $workflow) }}"
                >
                    Back to Instances
                </x-button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                @if (!$activeStepInstance)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="text-center py-8">
                            <i class="fas fa-check-circle text-green-500 text-5xl mb-4"></i>
                            <h2 class="text-2xl font-bold mb-2">All Steps Completed</h2>
                            <p class="text-gray-600 dark:text-gray-400 mb-4">
                                This workflow has been completed successfully.
                            </p>
                            <x-button
                                href="{{ route('workflows.instances.show', ['workflow' => $workflow, 'instance' => $instance]) }}"
                            >
                                View Workflow Details
                            </x-button>
                        </div>
                    </div>
                @else
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-semibold">Current Step: {{ $activeStepInstance->step->name }}</h2>
                            <span
                                class="px-3 py-1 text-xs rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                {{ ucfirst($activeStepInstance->step->step_type) }}
                            </span>
                        </div>

                        <div class="mb-6">
                            <p class="text-gray-600 dark:text-gray-400">
                                {{ $activeStepInstance->step->description ?: 'No description provided.' }}
                            </p>
                        </div>

                        <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Assigned to:</span>
                                <span class="ml-1 font-medium">
                                    @if ($activeStepInstance->assignee)
                                        {{ $activeStepInstance->assignee->name }}
                                    @else
                                        <span class="text-gray-500 dark:text-gray-400">Unassigned</span>
                                    @endif
                                </span>
                            </div>

                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Started:</span>
                                <span class="ml-1 font-medium">
                                    {{ $activeStepInstance->started_at ? $activeStepInstance->started_at->format('M d, Y H:i') : 'Not started' }}
                                </span>
                            </div>

                            @if ($activeStepInstance->step->step_config['time_limit'] ?? false)
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Time limit:</span>
                                    <span class="ml-1 font-medium">
                                        {{ $activeStepInstance->step->step_config['time_limit'] }} hours
                                    </span>
                                </div>

                                @if ($activeStepInstance->started_at)
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Due by:</span>
                                        <span class="ml-1 font-medium">
                                            {{ $activeStepInstance->started_at->addHours($activeStepInstance->step->step_config['time_limit'])->format('M d, Y H:i') }}
                                        </span>
                                    </div>
                                @endif
                            @endif
                        </div>

                        @if ($activeStepInstance->step->step_type === 'form')
                            <div class="border-t border-gray-200 dark:border-gray-700 pt-6 mb-6">
                                <h3 class="text-lg font-medium mb-4">Form Fields</h3>

                                <div class="space-y-4">
                                    @foreach ($activeStepInstance->step->step_config['form_fields'] ?? [] as $field)
                                        @if ($field['type'] === 'text')
                                            <div>
                                                <x-input
                                                    label="{{ $field['name'] }}"
                                                    wire:model="formData.{{ $field['id'] }}"
                                                    placeholder="Enter {{ strtolower($field['name']) }}"
                                                    required="{{ $field['required'] ?? false }}"
                                                />
                                            </div>
                                        @elseif ($field['type'] === 'textarea')
                                            <div>
                                                <x-textarea
                                                    label="{{ $field['name'] }}"
                                                    wire:model="formData.{{ $field['id'] }}"
                                                    placeholder="Enter {{ strtolower($field['name']) }}"
                                                    required="{{ $field['required'] ?? false }}"
                                                    rows="3"
                                                />
                                            </div>
                                        @elseif ($field['type'] === 'select' && isset($field['options']))
                                            <div>
                                                <x-select
                                                    label="{{ $field['name'] }}"
                                                    wire:model="formData.{{ $field['id'] }}"
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
                                                    wire:model="formData.{{ $field['id'] }}"
                                                    required="{{ $field['required'] ?? false }}"
                                                />
                                            </div>
                                        @elseif ($field['type'] === 'checkbox')
                                            <div>
                                                <x-checkbox
                                                    label="{{ $field['name'] }}"
                                                    wire:model="formData.{{ $field['id'] }}"
                                                />
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="flex justify-between">
                            <div>
                                @if ($activeStepInstance->assignee && $activeStepInstance->assignee->id !== auth()->id())
                                    <x-button
                                        color="yellow"
                                        icon="fas.user"
                                        wire:click="assignToMe"
                                        wire:confirm="Are you sure you want to assign this step to yourself?"
                                    >
                                        Assign to Me
                                    </x-button>
                                @endif
                            </div>

                            <div class="flex space-x-3">
                                <x-button
                                    color="red"
                                    icon="fas.times"
                                    wire:click="openRejectModal"
                                >
                                    Reject
                                </x-button>

                                <x-button
                                    color="green"
                                    icon="fas.check"
                                    wire:click="openCompleteModal"
                                >
                                    Complete Step
                                </x-button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                    <h2 class="text-xl font-semibold mb-4">Workflow Progress</h2>

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
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold mb-4">All Steps</h2>

                    <div class="space-y-3">
                        @foreach ($instance->stepInstances()->with('step')->orderBy('id')->get() as $stepInstance)
                            <div
                                class="flex items-center p-3 rounded-lg {{ $stepInstance->id === ($activeStepInstance->id ?? null) ? 'bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800' : 'border border-gray-200 dark:border-gray-700' }}">
                                <div class="mr-3">
                                    @if ($stepInstance->status === 'completed')
                                        <div
                                            class="bg-green-100 dark:bg-green-900 rounded-full w-6 h-6 flex items-center justify-center text-green-800 dark:text-green-200">
                                            <i class="fas fa-check text-xs"></i>
                                        </div>
                                    @elseif ($stepInstance->status === 'active')
                                        <div
                                            class="bg-blue-100 dark:bg-blue-900 rounded-full w-6 h-6 flex items-center justify-center text-blue-800 dark:text-blue-200">
                                            <i class="fas fa-play text-xs"></i>
                                        </div>
                                    @elseif ($stepInstance->status === 'rejected')
                                        <div
                                            class="bg-red-100 dark:bg-red-900 rounded-full w-6 h-6 flex items-center justify-center text-red-800 dark:text-red-200">
                                            <i class="fas fa-times text-xs"></i>
                                        </div>
                                    @elseif ($stepInstance->status === 'cancelled')
                                        <div
                                            class="bg-gray-100 dark:bg-gray-700 rounded-full w-6 h-6 flex items-center justify-center text-gray-800 dark:text-gray-200">
                                            <i class="fas fa-ban text-xs"></i>
                                        </div>
                                    @else
                                        <div
                                            class="bg-gray-100 dark:bg-gray-700 rounded-full w-6 h-6 flex items-center justify-center text-gray-800 dark:text-gray-200">
                                            <i class="fas fa-clock text-xs"></i>
                                        </div>
                                    @endif
                                </div>

                                <div class="flex-1">
                                    <div class="font-medium text-sm">{{ $stepInstance->step->name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        @if ($stepInstance->assignee)
                                            {{ $stepInstance->assignee->name }}
                                        @else
                                            Unassigned
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($instance->status === 'waiting_admin_approval' && auth()->user()->is_admin)
        <div class="bg-yellow-50 dark:bg-yellow-900 text-yellow-900 dark:text-yellow-100 rounded-lg p-4 mb-4 flex items-center justify-between">
            <div>
                <i class="fas fa-user-shield mr-2"></i> This workflow is waiting for admin approval.
            </div>
            <x-button color="success" wire:click="openAdminApproveModal">
                <i class="fas fa-check mr-1"></i> Approve & Complete
            </x-button>
        </div>
    @endif

    <!-- Complete Step Modal -->
    <x-modal wire:model="showCompleteModal">
        <x-card title="Complete Step">
            <p>
                Are you sure you want to complete this step?
                @if ($activeStepInstance && !empty($activeStepInstance->step->step_config['requires_approval']) ?? false)
                    This step requires approval and will be marked as completed.
                @endif
            </p>

            <div class="mt-4">
                <x-textarea
                    label="Comments (optional)"
                    wire:model="comments"
                    placeholder="Add any comments or notes about this step completion"
                    rows="3"
                />
            </div>

            <x-slot:actions>
                <div class="flex justify-end gap-x-4">
                    <x-button flat label="Cancel" x-on:click="close"/>
                    <x-button primary label="Complete Step" wire:click="completeStep"/>
                </div>
            </x-slot:actions>
        </x-card>
    </x-modal>

    <!-- Reject Step Modal -->
    <x-modal wire:model="showRejectModal">
        <x-card title="Reject Step">
            <p>
                Are you sure you want to reject this step?
                @php
                    $stepConfig = $activeStepInstance->step->step_config ?? [];
                    $rejectionBehavior = $stepConfig['rejection_behavior'] ?? 'stop_workflow';
                @endphp

                @if ($rejectionBehavior === 'stop_workflow')
                    This will cancel the entire workflow.
                @elseif ($rejectionBehavior === 'return_to_previous')
                    This will return the workflow to the previous step.
                @elseif ($rejectionBehavior === 'continue_anyway')
                    The workflow will continue to the next step despite this rejection.
                @endif
            </p>

            <div class="mt-4">
                <x-textarea
                    label="Rejection Reason"
                    wire:model="rejectReason"
                    placeholder="Please provide a reason for rejecting this step"
                    rows="3"
                    required
                />
            </div>

            <x-slot:actions>
                <div class="flex justify-end gap-x-4">
                    <x-button flat label="Cancel" x-on:click="close"/>
                    <x-button negative label="Reject Step" wire:click="rejectStep"/>
                </div>
            </x-slot:actions>
        </x-card>
    </x-modal>

    <!-- Admin Approve Modal -->
    <x-modal wire:model="showAdminApproveModal">
        <x-card title="Admin Approval">
            <div class="mb-4">
                <x-textarea label="Comments (optional)" wire:model.defer="adminApproveComments" rows="3" />
            </div>
            <div class="flex justify-end space-x-2">
                <x-button color="gray" wire:click="$set('showAdminApproveModal', false)">Cancel</x-button>
                <x-button color="success" wire:click="approveWorkflowByAdmin">
                    <i class="fas fa-check mr-1"></i> Approve & Complete
                </x-button>
            </div>
        </x-card>
    </x-modal>
</div>
