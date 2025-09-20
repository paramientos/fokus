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
    isDragging: false,
    onDragStart(taskId, event) {
        this.draggingTask = taskId;
        this.isDragging = true;
        event.dataTransfer.effectAllowed = 'move';
    },
    onDragOver(statusId, event) {
        event.preventDefault();
        this.dragOverStatus = statusId;
        event.dataTransfer.dropEffect = 'move';
    },
    onDragEnd() {
        this.isDragging = false;
        setTimeout(() => {
            this.dragOverStatus = null;
        }, 100);
    },
    onDrop(statusId) {
        if (this.draggingTask) {
            $wire.updateTaskStatus(this.draggingTask, statusId);
            this.draggingTask = null;
            this.isDragging = false;
            this.dragOverStatus = null;
        }
    }
}">
    <x-slot:title>Kanban Board - {{ $project->name }}</x-slot:title>

    <div class="bg-gradient-to-br from-base-100 to-base-200 min-h-screen">
        <div class="p-6 max-w-[1600px] mx-auto">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-primary mb-1">{{ $project->name }} Kanban</h1>
                    <p class="text-base-content/70">Drag and drop tasks to update their status</p>
                </div>

                <div class="flex flex-col sm:flex-row gap-4 w-full md:w-auto">
                    <x-select
                        label="Sprint"
                        wire:model.live="sprint"
                        wire:change="changeSprint($event.target.value)"
                        placeholder="All Tasks"
                        icon="fas fa-flag"
                        class="min-w-[200px] transition-all duration-300 focus:ring-2 focus:ring-primary/30"
                    >
                        <option value="">All Tasks</option>
                        @foreach($sprints as $sprintOption)
                            <option value="{{ $sprintOption->id }}">{{ $sprintOption->name }}</option>
                        @endforeach
                    </x-select>

                    <x-button 
                        link="/projects/{{ $project->id }}/tasks/create" 
                        label="Add Task" 
                        icon="fas fa-plus" 
                        class="btn-primary hover:shadow-lg transition-all duration-300" 
                    />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-{{ min(count($statuses), 4) }} lg:grid-cols-{{ count($statuses) }} gap-6 overflow-x-auto pb-4">
                @foreach($statuses as $status)
                    <div
                        class="card bg-base-100 shadow-xl min-w-[300px] border border-base-300 overflow-hidden transition-all duration-300"
                        @dragover="onDragOver({{ $status->id }}, $event)"
                        @drop="onDrop({{ $status->id }})"
                        @dragleave="dragOverStatus !== {{ $status->id }} && (dragOverStatus = null)"
                        :class="{ 'ring-2 ring-primary ring-offset-2 ring-offset-base-100 shadow-2xl': dragOverStatus === {{ $status->id }} }"
                    >
                        <div class="bg-base-200 p-3 border-b border-base-300">
                            <div class="flex justify-between items-center">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full" style="background-color: {{ $status->color }}"></div>
                                    <h2 class="font-bold text-base-content">
                                        {{ $status->name }}
                                    </h2>
                                </div>
                                <div class="badge bg-base-300 text-base-content border-0">
                                    {{ isset($tasks[$status->id]) ? count($tasks[$status->id]) : 0 }}
                                </div>
                            </div>
                        </div>

                        <div class="p-3 space-y-3 min-h-[300px] max-h-[70vh] overflow-y-auto" 
                             :class="{ 'bg-primary/5': dragOverStatus === {{ $status->id }} }">
                            @if(isset($tasks[$status->id]))
                                @foreach($tasks[$status->id] as $task)
                                    <div
                                        class="card bg-base-100 shadow hover:shadow-md border border-base-200 cursor-move transition-all duration-200 hover:-translate-y-1"
                                        draggable="true"
                                        @dragstart="onDragStart({{ $task->id }}, $event)"
                                        @dragend="onDragEnd"
                                        :class="{ 'opacity-50': draggingTask === {{ $task->id }} }"
                                    >
                                        <div class="card-body p-4">
                                            <div class="flex justify-between items-start">
                                                <a href="/projects/{{ $project->id }}/tasks/{{ $task->id }}" 
                                                   class="text-sm font-medium text-primary hover:text-primary-focus transition-colors duration-200">
                                                    {{ $project->key }}-{{ $task->id }}
                                                </a>
                                                <div class="badge" style="background-color: {{ $task->priority->color() }}; color: white;">
                                                    {{ $task->priority->label() }}
                                                </div>
                                            </div>

                                            <p class="text-sm font-medium mt-1 line-clamp-2">{{ $task->title }}</p>

                                            <div class="flex justify-between items-center mt-3 pt-2 border-t border-base-200">
                                                <div class="flex items-center gap-2">
                                                    @if($task->user)
                                                        <div class="avatar">
                                                            <div class="w-7 h-7 rounded-full ring ring-primary ring-offset-1 ring-offset-base-100">
                                                                <img src="{{ $task->user->avatar_url }}" alt="{{ $task->user->name }}" />
                                                            </div>
                                                        </div>
                                                    @else
                                                        <div class="avatar placeholder">
                                                            <div class="bg-primary/20 text-primary rounded-full w-7 h-7 flex items-center justify-center">
                                                                <i class="fas fa-user-alt text-xs"></i>
                                                            </div>
                                                        </div>
                                                    @endif

                                                    @if($task->story_points)
                                                        <div class="badge badge-sm bg-primary/10 text-primary border-0">
                                                            {{ $task->story_points }} pts
                                                        </div>
                                                    @endif
                                                </div>

                                                <div class="flex items-center">
                                                    <span class="w-6 h-6 flex items-center justify-center rounded-full bg-base-200">
                                                        <i class="fas fa-{{ $task->task_type->icon() }} text-xs text-base-content/70"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="flex flex-col items-center justify-center h-32 text-base-content/50">
                                    <i class="fas fa-inbox text-2xl mb-2"></i>
                                    <p class="text-sm">No tasks in this status</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    
    @script
    <script>
        // Enhance drag and drop experience with SortableJS if available
        document.addEventListener('livewire:initialized', () => {
            if (typeof Sortable !== 'undefined') {
                const statusColumns = document.querySelectorAll('.card > div:last-child');
                statusColumns.forEach(column => {
                    const statusId = column.closest('[data-status-id]')?.dataset.statusId;
                    if (statusId) {
                        new Sortable(column, {
                            group: 'tasks',
                            animation: 150,
                            ghostClass: 'bg-primary/10',
                            onEnd: function(evt) {
                                const taskId = evt.item.dataset.taskId;
                                const newStatusId = evt.to.closest('[data-status-id]')?.dataset.statusId;
                                if (taskId && newStatusId && statusId !== newStatusId) {
                                    @this.updateTaskStatus(taskId, newStatusId);
                                }
                            }
                        });
                    }
                });
            }
        });
    </script>
    @endscript
</div>
