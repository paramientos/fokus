<?php

new class extends Livewire\Volt\Component {
    public $project;
    public $sprint = null;
    public $sprints = [];
    public $statuses = [];
    public $tasks = [];

    public function mount($project, $sprint = null)
    {
        $this->project = \App\Models\Project::findOrFail($project);

        if ($sprint) {
            $this->sprint = \App\Models\Sprint::where('project_id', $this->project->id)
                ->findOrFail($sprint);
        }

        $this->loadStatuses();
        $this->loadTasks();
    }

    public function loadStatuses()
    {
        $this->statuses = $this->project->statuses()->orderBy('order')->get();
    }

    public function loadTasks()
    {
        $query = $this->project->tasks()->with(['user', 'reporter', 'status']);

        if ($this->sprint) {
            $query->where('sprint_id', $this->sprint->id);
        }

        $this->tasks = $query->get()->groupBy('status_id');
    }

    public function updateTaskStatus($taskId, $statusId)
    {
        $task = \App\Models\Task::findOrFail($taskId);
        $task->update(['status_id' => $statusId]);

        // Aktivite kaydı oluştur
        \App\Models\Activity::create([
            'user_id' => auth()->id() ?? 1,
            'project_id' => $this->project->id,
            'task_id' => $task->id,
            'action' => 'status_changed',
            'description' => 'Task status changed to ' . \App\Models\Status::find($statusId)->name
        ]);

        $this->loadTasks();
    }

    public function changeSprint($sprintId)
    {
        if ($sprintId) {
            $this->sprint = \App\Models\Sprint::where('project_id', $this->project->id)
                ->findOrFail($sprintId);
        } else {
            $this->sprint = null;
        }

        $this->loadTasks();
    }
}
?>

<div x-data="{
    draggingTask: null,
    dragOverStatus: null,
    onDragStart(taskId) {
        this.draggingTask = taskId;
    },
    onDragOver(statusId) {
        event.preventDefault();
        this.dragOverStatus = statusId;
    },
    onDrop(statusId) {
        if (this.draggingTask) {
            $wire.updateTaskStatus(this.draggingTask, statusId);
            this.draggingTask = null;
            this.dragOverStatus = null;
        }
    }
}">
    <x-slot:title>Kanban Board - {{ $project->name }}</x-slot:title>

    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-primary">Kanban Board</h1>

            <div class="flex gap-4">
                <x-select
                    label="Sprint"
                    wire:model.live="sprint"
                    wire:change="changeSprint($event.target.value)"
                    placeholder="All Tasks"
                >
                    <option value="">All Tasks</option>
                    @foreach($sprints as $sprintOption)
                        <option value="{{ $sprintOption->id }}">{{ $sprintOption->name }}</option>
                    @endforeach
                </x-select>

                <x-button link="/projects/{{ $project->id }}/tasks/create" label="Add Task" icon="fas.plus" class="btn-primary" />
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-{{ count($statuses) }} gap-4 overflow-x-auto">
            @foreach($statuses as $status)
                <div
                    class="card bg-base-100 shadow-xl min-w-[300px]"
                    @dragover="onDragOver({{ $status->id }})"
                    @drop="onDrop({{ $status->id }})"
                    :class="{ 'border-2 border-primary': dragOverStatus === {{ $status->id }} }"
                >
                    <div class="card-body">
                        <div class="flex justify-between items-center">
                            <h2 class="card-title">
                                <div class="badge" style="background-color: {{ $status->color }}">
                                    {{ $status->name }}
                                </div>
                            </h2>
                            <span class="badge badge-ghost">{{ isset($tasks[$status->id]) ? count($tasks[$status->id]) : 0 }}</span>
                        </div>

                        <div class="mt-4 space-y-3 min-h-[200px]">
                            @if(isset($tasks[$status->id]))
                                @foreach($tasks[$status->id] as $task)
                                    <div
                                        class="card bg-base-200 shadow cursor-move"
                                        draggable="true"
                                        @dragstart="onDragStart({{ $task->id }})"
                                    >
                                        <div class="card-body p-4">
                                            <div class="flex justify-between items-start">
                                                <a href="/projects/{{ $project->id }}/tasks/{{ $task->id }}" class="card-title text-sm hover:text-primary">
                                                    {{ $project->key }}-{{ $task->id }}
                                                </a>
                                                <div class="badge badge-sm" style="background-color: {{ $task->priority->color() }}">
                                                    {{ $task->priority->label() }}
                                                </div>
                                            </div>

                                            <p class="text-sm mt-1">{{ $task->title }}</p>

                                            <div class="flex justify-between items-center mt-2">
                                                <div class="flex items-center gap-2">
                                                    @if($task->user)
                                                        <div class="avatar">
                                                            <div class="w-6 h-6 rounded-full">
                                                                <img src="{{ $task->user->avatar_url }}" alt="{{ $task->user->name }}" />
                                                            </div>
                                                        </div>
                                                    @else
                                                        <div class="avatar placeholder">
                                                            <div class="bg-neutral text-neutral-content rounded-full w-6 h-6">
                                                                <span class="text-xs">?</span>
                                                            </div>
                                                        </div>
                                                    @endif

                                                    @if($task->story_points)
                                                        <div class="badge badge-sm badge-outline">{{ $task->story_points }} pts</div>
                                                    @endif
                                                </div>

                                                <div class="flex items-center">
                                                    <x-icon name="fas.{{ $task->task_type->icon() }}" class="w-4 h-4 text-gray-500" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
