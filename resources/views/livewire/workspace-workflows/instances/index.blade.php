<?php

use App\Models\WorkspaceWorkflow;
use App\Models\WorkspaceWorkflowInstance;
use Mary\Traits\Toast;
use Livewire\WithPagination;

new class extends Livewire\Volt\Component {
    use Toast, WithPagination;

    public WorkspaceWorkflow $workflow;
    public $search = '';
    public $status = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';

    public function mount(WorkspaceWorkflow $workflow)
    {
        // Check if the workflow belongs to the current workspace
        if ($workflow->workspace_id !== session('workspace_id')) {
            $this->error('You do not have access to this workflow.');
            return redirect()->route('workflows.index');
        }

        $this->workflow = $workflow;
    }

    public function getInstancesProperty()
    {
        return WorkspaceWorkflowInstance::query()
            ->where('workspace_workflow_id', $this->workflow->id)
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function deleteInstance($instanceId)
    {
        $instance = WorkspaceWorkflowInstance::findOrFail($instanceId);

        // Check if instance belongs to this workflow
        if ($instance->workspace_workflow_id !== $this->workflow->id) {
            $this->error('You do not have permission to delete this instance.');
            return;
        }

        // Delete all related step instances first
        $instance->stepInstances()->delete();

        // Then delete the instance
        $instance->delete();

        $this->success('Workflow instance deleted successfully.');
    }
}

?>

<div>
    <div class="mb-6">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h1 class="text-2xl font-bold">Workflow Instances</h1>
                <p class="text-gray-600 dark:text-gray-400">
                    {{ $workflow->name }}
                </p>
            </div>

            <div class="flex space-x-2">
                <x-button
                    color="gray"
                    icon="fas.arrow-left"
                    link="{{ route('workflows.show', $workflow) }}"
                >
                    Back to Workflow
                </x-button>

                <x-button
                    icon="fas.plus"
                    link="{{ route('workflows.instances.create', $workflow) }}"
                >
                    Start New Instance
                </x-button>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="flex-1">
                        <x-input
                            icon="fas.search"
                            placeholder="Search instances..."
                            wire:model.live.debounce.300ms="search"
                        />
                    </div>

                    <div class="w-full md:w-48">
                        <x-select
                            wire:model.live="status"
                            :options="[
                                '' => 'All Statuses',
                                'active' => 'Active',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled'
                            ]"
                        />
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer" wire:click="sortBy('name')">
                                <div class="flex items-center">
                                    Name
                                    @if ($sortField === 'name')
                                        <span class="ml-1">
                                            @if ($sortDirection === 'asc')
                                                <i class="fas fa-sort-up"></i>
                                            @else
                                                <i class="fas fa-sort-down"></i>
                                            @endif
                                        </span>
                                    @endif
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer" wire:click="sortBy('status')">
                                <div class="flex items-center">
                                    Status
                                    @if ($sortField === 'status')
                                        <span class="ml-1">
                                            @if ($sortDirection === 'asc')
                                                <i class="fas fa-sort-up"></i>
                                            @else
                                                <i class="fas fa-sort-down"></i>
                                            @endif
                                        </span>
                                    @endif
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Current Step
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer" wire:click="sortBy('created_at')">
                                <div class="flex items-center">
                                    Started
                                    @if ($sortField === 'created_at')
                                        <span class="ml-1">
                                            @if ($sortDirection === 'asc')
                                                <i class="fas fa-sort-up"></i>
                                            @else
                                                <i class="fas fa-sort-down"></i>
                                            @endif
                                        </span>
                                    @endif
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Initiated By
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($this->instances as $instance)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-medium text-gray-900 dark:text-gray-100">
                                        <a href="{{ route('workflows.instances.show', ['workflow' => $workflow, 'instance' => $instance]) }}" class="hover:text-blue-600 dark:hover:text-blue-400">
                                            {{ $instance->name }}
                                        </a>
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        ID: {{ $instance->id }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($instance->status === 'active')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                            Active
                                        </span>
                                    @elseif ($instance->status === 'completed')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                            Completed
                                        </span>
                                    @elseif ($instance->status === 'cancelled')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                                            Cancelled
                                        </span>
                                    @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                            {{ ucfirst($instance->status) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $currentStep = $instance->currentStepInstance();
                                    @endphp

                                    @if ($currentStep)
                                        <div class="text-sm font-medium">
                                            {{ $currentStep->step->name }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            @if ($currentStep->assignee)
                                                Assigned to: {{ $currentStep->assignee->name }}
                                            @else
                                                Unassigned
                                            @endif
                                        </div>
                                    @elseif ($instance->status === 'completed')
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            All steps completed
                                        </div>
                                    @else
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            No active step
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $instance->created_at->format('M d, Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm">
                                        @if ($instance->initiator)
                                            {{ $instance->initiator->name }}
                                        @else
                                            <span class="text-gray-500 dark:text-gray-400">System</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-2">
                                        <x-button
                                            icon="fas.eye"
                                            link="{{ route('workflows.instances.show', ['workflow' => $workflow, 'instance' => $instance]) }}"
                                            size="xs"
                                        />

                                        @if ($instance->status === 'in_progress')
                                            <x-button
                                                icon="fas.play"
                                                link="{{ route('workflows.instances.process', ['workflow' => $workflow, 'instance' => $instance]) }}"
                                                size="xs"
                                                color="green"
                                            />
                                        @endif

                                        <x-button
                                            icon="fas.trash"
                                            wire:click="deleteInstance({{ $instance->id }})"
                                            wire:confirm="Are you sure you want to delete this workflow instance? This action cannot be undone."
                                            size="xs"
                                            color="red"
                                        />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                    <div class="py-8">
                                        <i class="fas fa-clipboard-list text-4xl mb-3"></i>
                                        <p class="text-lg font-medium">No workflow instances found</p>
                                        <p class="text-sm mt-1">Start a new workflow instance to begin tracking progress</p>
                                        <a href="{{ route('workflows.instances.create', $workflow) }}" class="mt-3 inline-block">
                                            <x-button size="sm" icon="fas.plus">Start New Instance</x-button>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $this->instances->links() }}
            </div>
        </div>
    </div>
</div>
