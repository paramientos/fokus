<?php

new class extends Livewire\Volt\Component {
    public \App\Models\Project $project;
    public \App\Models\Sprint $sprint;

    public $availableTasks = [];
    /** @var \App\Models\Task[] $selectedTasks */
    public $selectedTasks = [];
    public $tasks = [];

    public array $tasksByStatus = [];
    public $showCloneModal = false;
    public $cloneOptions = [
        'include_tasks' => true,
        'adjust_dates' => true,
    ];

    public function mount()
    {
        $this->sprint = $this->sprint->with(['tasks.status', 'tasks.user'])->firstOrFail();
        $this->loadAvailableTasks();

        // Görevleri durumlara göre grupla
        $this->tasks = $this->sprint->tasks;

        $this->tasksByStatus = $this->tasks->groupBy(function ($task) {
            return $task->status ? $task->status->name : 'No Status';
        })
            ->toArray();
    }

    public function loadAvailableTasks()
    {
        $this->availableTasks = \App\Models\Task::where('project_id', $this->project->id)
            ->whereNull('sprint_id')
            ->get()
            ->toArray();
    }

    public function addTasksToSprint()
    {
        if (empty($this->selectedTasks)) {
            return;
        }

        \App\Models\Task::whereIn('id', $this->selectedTasks)
            ->update(['sprint_id' => $this->sprint->id]);

        $this->selectedTasks = [];
        $this->sprint = \App\Models\Sprint::with(['tasks.status', 'tasks.user'])->findOrFail($this->sprint->id);
        $this->loadAvailableTasks();

        // Görevleri durumlara göre grupla
        $this->tasks = $this->sprint->tasks;

        $this->tasksByStatus = $this->tasks->groupBy(function ($task) {
            return $task->status ? $task->status->name : 'No Status';
        });
    }

    public function removeFromSprint($taskId)
    {
        \App\Models\Task::where('id', $taskId)
            ->update(['sprint_id' => null]);

        $this->sprint = \App\Models\Sprint::with(['tasks.status', 'tasks.user'])->findOrFail($this->sprint->id);
        $this->loadAvailableTasks();

        // Görevleri durumlara göre grupla
        $this->tasks = $this->sprint->tasks;

        $this->tasksByStatus = $this->tasks->groupBy(function ($task) {
            return $task->status ? $task->status->name : 'No Status';
        });
    }

    public function completeSprint()
    {
        $this->sprint->update([
            'is_active' => false,
            'is_completed' => true,
            'end_date' => $this->sprint->end_date ?? now(),
        ]);

        $this->sprint = \App\Models\Sprint::with(['tasks.status', 'tasks.user'])->findOrFail($this->sprint->id);

        session()->flash('message', 'Sprint completed successfully!');
    }

    public function startSprint()
    {
        // First, deactivate any active sprints
        \App\Models\Sprint::where('project_id', $this->project->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        // Then activate this sprint
        $this->sprint->update([
            'is_active' => true,
            'is_completed' => false,
            'start_date' => $this->sprint->start_date ?? now(),
        ]);

        $this->sprint = \App\Models\Sprint::with(['tasks.status', 'tasks.user'])->findOrFail($this->sprint->id);

        session()->flash('message', 'Sprint started successfully!');
    }

    public function cancelSprint()
    {
        $this->sprint->update([
            'is_active' => false,
            'is_completed' => false,
        ]);

        $this->sprint = \App\Models\Sprint::with(['tasks.status', 'tasks.user'])->findOrFail($this->sprint->id);

        session()->flash('message', 'Sprint cancelled successfully!');
    }

    public function toggleCloneModal()
    {
        $this->showCloneModal = !$this->showCloneModal;
    }

    public function with(): array
    {
        $totalTasks = $this->sprint->tasks->count();

        $completedTasks = $this->sprint->tasks->filter(function ($task) {
            return $task->status && $task->status->slug === 'done';
        })->count();

        $progressPercentage = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

        return [
            'totalTasks' => $totalTasks,
            'completedTasks' => $completedTasks,
            'progressPercentage' => $progressPercentage,
            'tasksByStatus' => $this->tasksByStatus,
        ];
    }
}

?>

<div>
    <x-slot:title>{{ $sprint->name }} - {{ $project->name }}</x-slot:title>

    <div class="p-6">
        <!-- Bildirim Mesajı -->
        @if (session()->has('message'))
            <div class="alert alert-success mb-6">
                <x-icon name="o-check-circle" class="w-6 h-6"/>
                <span>{{ session('message') }}</span>
            </div>
        @endif

        <!-- Sprint Başlığı ve Bilgileri -->
        <div class="flex justify-between items-start mb-6">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <x-button link="/projects/{{ $project->id }}/sprints" icon="o-arrow-left" class="btn-ghost btn-sm"/>
                    <h1 class="text-2xl font-bold text-primary">{{ $sprint->name }}</h1>
                    <div
                        class="badge {{ $sprint->is_completed ? 'badge-info' : ($sprint->is_active ? 'badge-success' : 'badge-warning') }}">
                        {{ $sprint->is_completed ? 'Completed' : ($sprint->is_active ? 'Active' : 'Planned') }}
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-4 text-sm text-gray-500">
                    <div class="flex items-center gap-1">
                        <x-icon name="o-calendar" class="w-4 h-4"/>
                        <span>
                            {{ $sprint->start_date ? $sprint->start_date->format('M d, Y') : 'No start date' }} -
                            {{ $sprint->end_date ? $sprint->end_date->format('M d, Y') : 'No end date' }}
                        </span>
                    </div>

                    <div class="flex items-center gap-1">
                        <x-icon name="o-clipboard-document-list" class="w-4 h-4"/>
                        <span>{{ $tasks->count() }} tasks</span>
                    </div>
                </div>
            </div>

            <div class="flex gap-2">
                @if(!$sprint->is_active && !$sprint->is_completed)
                    <x-button wire:click="startSprint" label="Start Sprint" icon="o-play" class="btn-success"/>
                @endif

                @if($sprint->is_active)
                    <x-button wire:click="completeSprint" label="Complete Sprint" icon="o-check" class="btn-info"/>
                    <x-button wire:click="cancelSprint" label="Cancel Sprint" icon="o-x-mark" class="btn-error"/>
                @endif

                <x-button link="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}/board" label="Task Board"
                          icon="o-view-columns" class="btn-outline"/>
                <x-button link="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}/burndown" label="Burndown Chart"
                          icon="fas.chart-line" class="btn-outline"/>
                <x-button link="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}/report" label="View Report"
                          icon="fas.chart-bar" class="btn-outline"/>
                <x-button link="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}/retrospective"
                          label="Retrospective" icon="fas.user" class="btn-outline"/>
                <x-button link="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}/edit" label="Edit"
                          icon="o-pencil" class="btn-outline"/>
                <x-button wire:click="toggleCloneModal" label="Clone" icon="o-document-duplicate" class="btn-outline"/>
            </div>
        </div>

        <!-- Sprint Hedefi -->
        @if($sprint->goal)
            <div class="card bg-base-100 shadow-xl mb-6">
                <div class="card-body">
                    <h2 class="card-title">
                        <x-icon name="o-flag" class="w-5 h-5"/>
                        Sprint Goal
                    </h2>
                    <p>{{ $sprint->goal }}</p>
                </div>
            </div>
        @endif

        <!-- Sprint İlerleme Durumu -->
        <div class="card bg-base-100 shadow-xl mb-6">
            <div class="card-body">
                <h2 class="card-title">Sprint Progress</h2>

                @php
                    $completedTasks = $tasks->filter(function ($task) {
                        return $task->status && $task->status->slug === 'done';
                    })->count();

                    $progress = $tasks->count() > 0 ? ($completedTasks / $tasks->count()) * 100 : 0;
                @endphp

                <div class="mt-4">
                    <div class="flex justify-between mb-1">
                        <span
                            class="text-sm font-medium">{{ $completedTasks }} of {{ $tasks->count() }} tasks completed</span>
                        <span class="text-sm font-medium">{{ round($progress) }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="bg-primary h-2.5 rounded-full" style="width: {{ $progress }}%"></div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Görevler -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @foreach($tasksByStatus as $status => $statusTasks)
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h2 class="card-title">{{ $status }}</h2>

                        <div class="overflow-y-auto max-h-[400px]">
                            <table class="table table-zebra w-full">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Assignee</th>
                                    <th>Priority</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($statusTasks as $task)
                                    <tr>
                                        <td>{{ $project->key }}-{{ $task['id'] }}</td>
                                        <td>
                                            <a href="/projects/{{ $project->id }}/tasks/{{ $task['id'] }}"
                                               class="link link-hover">
                                                {{ $task['title'] }}
                                            </a>
                                        </td>
                                        <td>
                                            @if($task['user'])
                                                <div class="flex items-center gap-2">
                                                    <div class="avatar placeholder">
                                                        <div class="bg-neutral text-neutral-content rounded-full w-8">
                                                            <span>{{ substr($task['user']['name'], 0, 1) }}</span>
                                                        </div>
                                                    </div>
                                                    <span>{{ $task['user']['name'] }}</span>
                                                </div>
                                            @else
                                                <span class="text-gray-500">Unassigned</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if(!empty($task['priority']))
                                                <div class="badge {{
                                                        $task['priority'] === \App\Enums\Priority::HIGH->value ? 'badge-error' :
                                                        ($task['priority'] === \App\Enums\Priority::MEDIUM->value ? 'badge-warning' : 'badge-info')
                                                    }}">
                                                    {{ ucfirst(\App\Enums\Priority::from($task['priority'])->label()) }}
                                                </div>
                                            @else
                                                <span class="text-gray-500">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endforeach

            @if(!empty($tasksByStatus))
                <div class="card bg-base-100 shadow-xl lg:col-span-2">
                    <div class="card-body">
                        <div class="py-8 text-center">
                            <x-icon name="o-clipboard-document" class="w-16 h-16 mx-auto text-gray-400"/>
                            <h3 class="mt-4 text-lg font-medium text-gray-900">No tasks in this sprint</h3>
                            <p class="mt-1 text-sm text-gray-500">Get started by adding tasks to this sprint.</p>
                            <div class="mt-6">
                                <x-button link="/projects/{{ $project->id }}/tasks/create" label="Create Task"
                                          icon="o-plus" class="btn-primary"/>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Görev Ekleme Butonu -->
        <div class="flex justify-end mt-6">
            <x-button
                x-data=""
                x-on:click="$dispatch('open-modal', 'add-tasks-modal')"
                label="Add Tasks"
                icon="o-plus"
                class="btn-primary btn-sm"
            />
        </div>

        <x-modal wire:model="showAddTasksModal" name="add-tasks-modal" title="Add Tasks to Sprint">
            <div class="p-4">
                @if(empty($availableTasks))
                    <div class="py-4 text-center text-gray-500">
                        <p>No available tasks to add to this sprint</p>
                    </div>
                @else
                    <div class="mb-4">
                        <p class="mb-2">Select tasks to add to this sprint:</p>
                        <div class="overflow-y-auto max-h-96">
                            @foreach($availableTasks as $task)
                                <div class="form-control">
                                    <label class="label cursor-pointer justify-start gap-2">
                                        <x-checkbox wire:model="selectedTasks" value="{{ $task['id'] }}"/>
                                        <span
                                            class="label-text">{{ $project->key }}-{{ $task['id'] }}: {{ $task['title'] }}</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex justify-end gap-2">
                        <x-button
                            x-on:click="$dispatch('close-modal', 'add-tasks-modal')"
                            label="Cancel"
                            class="btn-ghost"
                        />
                        <x-button
                            wire:click="addTasksToSprint"
                            x-on:click="$dispatch('close-modal', 'add-tasks-modal')"
                            label="Add Selected Tasks"
                            icon="o-plus"
                            class="btn-primary"
                        />
                    </div>
                @endif
            </div>
        </x-modal>

        <!-- Sprint Kopyalama Modal -->
        <x-modal wire:model="showCloneModal">
            <x-card title="Clone Sprint">
                <p class="mb-4">Create a copy of this sprint with the following options:</p>

                <div class="space-y-4">
                    <div class="flex items-center">
                        <input
                            type="checkbox"
                            id="include_tasks"
                            wire:model="cloneOptions.include_tasks"
                            class="checkbox checkbox-primary"
                        />
                        <label for="include_tasks" class="ml-2">Include tasks</label>
                    </div>

                    <div class="flex items-center">
                        <input
                            type="checkbox"
                            id="adjust_dates"
                            wire:model="cloneOptions.adjust_dates"
                            class="checkbox checkbox-primary"
                        />
                        <label for="adjust_dates" class="ml-2">Adjust dates (start from tomorrow)</label>
                    </div>
                </div>

                <x-slot:footer>
                    <div class="flex justify-end gap-2">
                        <x-button label="Cancel" wire:click="toggleCloneModal" class="btn-ghost"/>

                        <form
                            action="{{ route('sprints.clone', ['project' => $project->id, 'sprint' => $sprint->id]) }}"
                            method="POST">
                            @csrf
                            <input type="hidden" name="include_tasks" :value="cloneOptions.include_tasks ? 1 : 0"
                                   x-bind:value="$wire.cloneOptions.include_tasks ? 1 : 0">
                            <input type="hidden" name="adjust_dates" :value="cloneOptions.adjust_dates ? 1 : 0"
                                   x-bind:value="$wire.cloneOptions.adjust_dates ? 1 : 0">
                            <x-button type="submit" label="Clone Sprint" class="btn-primary"/>
                        </form>
                    </div>
                </x-slot:footer>
            </x-card>
        </x-modal>
    </div>
</div>
