<?php

use App\Models\WorkspaceWorkflow;
use App\Models\WorkspaceWorkflowStep;
use Mary\Traits\Toast;
use Livewire\Attributes\On;

new class extends Livewire\Volt\Component {
    use Toast;

    public WorkspaceWorkflow $workflow;
    public $steps = [];
    public $reordering = false;

    public function mount(WorkspaceWorkflow $workflow)
    {
        // Check if the workflow belongs to the current workspace
        if ($workflow->workspace_id !== session('workspace_id')) {
            $this->error('You do not have access to this workflow.');
            return redirect()->route('workflows.index');
        }

        $this->workflow = $workflow;
        $this->loadSteps();
    }

    public function loadSteps()
    {
        $this->steps = $this->workflow->steps()->orderBy('order')->get()->toArray();
    }

    #[On('step-updated')]
    public function handleStepUpdated()
    {
        $this->loadSteps();
        $this->success('Step updated successfully.');
    }

    #[On('step-deleted')]
    public function handleStepDeleted()
    {
        $this->loadSteps();
        $this->success('Step deleted successfully.');
    }

    public function toggleReordering()
    {
        $this->reordering = !$this->reordering;
    }

    public function updateStepOrder($orderedIds)
    {
        $order = 1;
        foreach ($orderedIds as $id) {
            WorkspaceWorkflowStep::where('id', $id)->update(['order' => $order]);
            $order++;
        }

        $this->loadSteps();
        $this->reordering = false;
        $this->success('Step order updated successfully.');
    }

    public function deleteStep($stepId)
    {
        $step = WorkspaceWorkflowStep::findOrFail($stepId);

        // Check if step belongs to this workflow
        if ($step->workspace_workflow_id !== $this->workflow->id) {
            $this->error('You do not have permission to delete this step.');
            return;
        }

        $stepName = $step->name;
        $step->delete();

        // Reorder remaining steps
        $remainingSteps = $this->workflow->steps()->orderBy('order')->get();
        $order = 1;
        foreach ($remainingSteps as $remainingStep) {
            $remainingStep->update(['order' => $order]);
            $order++;
        }

        $this->loadSteps();
        $this->success("Step '{$stepName}' deleted successfully.");
    }
}

?>

<div>
    <div class="mb-6">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h1 class="text-2xl font-bold">Workflow Steps</h1>
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

                @if (count($steps) > 1 && !$reordering)
                    <x-button
                        color="gray"
                        icon="fas.sort"
                        wire:click="toggleReordering"
                    >
                        Reorder Steps
                    </x-button>
                @endif

                @if ($reordering)
                    <x-button
                        color="gray"
                        icon="fas.times"
                        wire:click="toggleReordering"
                    >
                        Cancel Reordering
                    </x-button>
                @endif

                <x-button
                    icon="fas.plus"
                    link="{{ route('workflows.steps.create', $workflow) }}"
                >
                    Add Step
                </x-button>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            @if (empty($steps))
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-sitemap text-4xl mb-3"></i>
                    <p class="text-lg font-medium">No steps defined</p>
                    <p class="text-sm mt-1">Add steps to make this workflow functional</p>
                    <a href="{{ route('workflows.steps.create', $workflow) }}" class="mt-3 inline-block">
                        <x-button size="sm" icon="fas.plus">Add First Step</x-button>
                    </a>
                </div>
            @else
                @if ($reordering)
                    <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900 rounded-lg text-blue-800 dark:text-blue-200">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle mt-1 mr-2"></i>
                            <div>
                                <p class="font-medium">Reordering Mode</p>
                                <p class="text-sm">Drag and drop steps to reorder them, then click Save Order.</p>
                            </div>
                        </div>
                    </div>

                    <div
                        x-data="{
                            steps: @entangle('steps'),
                            draggingIndex: null,

                            init() {
                                this.$nextTick(() => {
                                    const container = this.$refs.sortableContainer;
                                    const sortable = new Sortable(container, {
                                        animation: 150,
                                        ghostClass: 'bg-gray-100 dark:bg-gray-700',
                                        onEnd: (evt) => {
                                            // Update the steps array based on the new order
                                            const itemEl = evt.item;
                                            const newIndex = evt.newIndex;
                                            const oldIndex = evt.oldIndex;

                                            // Move the item in the array
                                            const item = this.steps.splice(oldIndex, 1)[0];
                                            this.steps.splice(newIndex, 0, item);
                                        }
                                    });
                                });
                            }
                        }"
                    >
                        <div class="space-y-2" x-ref="sortableContainer">
                            <template x-for="(step, index) in steps" :key="step.id">
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 flex items-center bg-white dark:bg-gray-800 cursor-move">
                                    <div class="bg-blue-100 dark:bg-blue-900 rounded-full w-8 h-8 flex items-center justify-center text-blue-800 dark:text-blue-200 font-bold mr-3">
                                        <span x-text="index + 1"></span>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="font-medium" x-text="step.name"></h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400" x-text="step.description || 'No description'"></p>
                                    </div>
                                    <div class="ml-4 text-gray-400">
                                        <i class="fas fa-grip-vertical"></i>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <x-button
                                icon="fas.save"
                                wire:click="updateStepOrder(steps.map(s => s.id))"
                            >
                                Save Order
                            </x-button>
                        </div>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach ($steps as $index => $step)
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 flex items-start">
                                <div class="bg-blue-100 dark:bg-blue-900 rounded-full w-8 h-8 flex items-center justify-center text-blue-800 dark:text-blue-200 font-bold mr-3">
                                    {{ $index + 1 }}
                                </div>
                                <div class="flex-1">
                                    <div class="flex justify-between">
                                        <h3 class="font-medium">{{ $step['name'] }}</h3>
                                        <span class="text-sm text-gray-500">{{ ucfirst($step['step_type']) }}</span>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        {{ $step['description'] ?: 'No description' }}
                                    </p>

                                    @if ($step['assigned_to'])
                                        <div class="mt-2 flex items-center text-sm">
                                            <i class="fas fa-user mr-1 text-gray-500"></i>
                                            <span>Assigned to: {{ $step['assignee']['name'] ?? 'Unknown User' }}</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="ml-4 flex space-x-1">
                                    <x-button
                                        icon="fas.eye"
                                        link="{{ route('workflows.steps.show', ['workflow' => $workflow, 'step' => $step['id']]) }}"
                                        size="xs"
                                    />
                                    <x-button
                                        icon="fas.pen"
                                        link="{{ route('workflows.steps.edit', ['workflow' => $workflow, 'step' => $step['id']]) }}"
                                        size="xs"
                                    />
                                    <x-button
                                        icon="fas.trash"
                                        wire:click="deleteStep({{ $step['id'] }})"
                                        wire:confirm="Are you sure you want to delete this step? This may break existing workflow instances."
                                        size="xs"
                                        color="red"
                                    />
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
