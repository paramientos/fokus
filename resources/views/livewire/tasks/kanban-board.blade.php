<?php

use App\Enums\Priority;
use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;

new class extends Livewire\Volt\Component {
    public Project $project;
    public $statuses;
    public $tasks;
    public $draggedTaskId;
    public $searchQuery = '';
    public $filterAssignee = '';
    public $filterPriority = '';
    public $users = [];

    public function mount(): void
    {
        $this->loadBoard();

        $this->users = User::all();
    }

    public function loadBoard()
    {
        $this->statuses = Status::all();

        $query = Task::where('project_id', $this->project->id)
            ->with(['assignee', 'status', 'tags']);

        if (!empty($this->searchQuery)) {
            $query->where(function ($q) {
                $q->where('title', 'like', "%{$this->searchQuery}%")
                    ->orWhere('description', 'like', "%{$this->searchQuery}%");
            });
        }

        if (!empty($this->filterAssignee)) {
            $query->where('assignee_id', $this->filterAssignee);
        }

        if (!empty($this->filterPriority)) {
            $query->where('priority_id', $this->filterPriority);
        }

        $allTasks = $query->get();

        // Group tasks by status
        $this->tasks = $allTasks->groupBy('status_id')->collect();
    }

    public function startDrag($taskId)
    {
        $this->draggedTaskId = $taskId;
    }

    public function dropTask($statusId)
    {
        if ($this->draggedTaskId) {
            $task = Task::find($this->draggedTaskId);

            if ($task && $task->status_id != $statusId) {
                $oldStatusId = $task->status_id;
                $task->status_id = $statusId;
                $task->save();

                // Refresh the board
                $this->loadBoard();

                $this->dispatch('task-moved', [
                    'taskId' => $task->id,
                    'oldStatusId' => $oldStatusId,
                    'newStatusId' => $statusId
                ]);
            }

            $this->draggedTaskId = null;
        }
    }

    public function updatedSearchQuery()
    {
        $this->loadBoard();
    }

    public function updatedFilterAssignee()
    {
        $this->loadBoard();
    }

    public function updatedFilterPriority()
    {
        $this->loadBoard();
    }

    public function with(): array
    {
        return [
            'project' => $this->project,
            'statuses' => $this->statuses,
            'tasks' => $this->tasks,
            'users' => $this->project->users,
            'priorities' => Priority::listForMaryUI()
        ];
    }

    public function getContrastColor($hexColor)
    {
        // Hex rengi RGB'ye dönüştür
        $r = hexdec(substr($hexColor, 1, 2));
        $g = hexdec(substr($hexColor, 3, 2));
        $b = hexdec(substr($hexColor, 5, 2));

        // Parlaklık hesapla (YIQ formülü)
        $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

        // Parlaklığa göre siyah veya beyaz döndür
        return ($yiq >= 128) ? '#000000' : '#ffffff';
    }

    public function getPriorityColor($priority)
    {
        if (!$priority) {
            return '#64748b';
        }

        if (is_numeric($priority)) {
            $priority = Priority::tryFrom((int)$priority);
        }

        return $priority?->color() ?? '#64748b';
    }

    public function getPriorityLabel($priority)
    {
        if (!$priority) {
            return 'No Priority';
        }

        if (is_numeric($priority)) {
            $priority = Priority::tryFrom((int)$priority);
        }

        return $priority?->label() ?? 'No Priority';
    }
}
?>

<div class="p-4">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold">{{ $project->name }} - Kanban Board</h1>
            <p class="text-gray-500">Drag and drop tasks to change their status</p>
        </div>
        <div class="flex space-x-2">
            <a href="{{ route('projects.show', $project) }}" class="btn btn-outline">
                <i class="fas fa-arrow-left mr-2"></i> Back to Project
            </a>
            <a href="{{ route('tasks.create', ['project' => $project]) }}" class="btn btn-primary">
                <i class="fas fa-plus mr-2"></i> New Task
            </a>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label for="searchQuery" class="block text-sm font-medium mb-1">Search</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
                <input type="text" id="searchQuery" wire:model.live.debounce.300ms="searchQuery"
                       class="input pl-10 w-full" placeholder="Search tasks...">
            </div>
        </div>

        <div>
            <label for="filterAssignee" class="block text-sm font-medium mb-1">Assignee</label>
            <select id="filterAssignee" wire:model.live="filterAssignee" class="select w-full">
                <option value="">All Assignees</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="filterPriority" class="block text-sm font-medium mb-1">Priority</label>
            <select id="filterPriority" wire:model.live="filterPriority" class="select w-full">
                <option value="">All Priorities</option>
                @foreach($priorities as $priority)
                    <option value="{{ $priority['id'] }}">{{ $priority['name'] }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 overflow-x-auto">
        @foreach($statuses as $status)
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg shadow p-4 min-w-[300px]">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-lg flex items-center">
                        <span class="w-3 h-3 rounded-full mr-2"
                              style="background-color: {{ $status->color ?? '#64748b' }}"></span>
                        {{ $status->name }}
                    </h3>
                    <span class="badge badge-outline">
                        {{ $tasks->get($status->id)?->count() ?? 0 }}
                    </span>
                </div>

                <div
                    class="space-y-3 min-h-[200px]"
                    wire:drop-target.status-id="{{ $status->id }}"
                    wire:drop="dropTask"
                >
                    @if($tasks->has($status->id))
                        @foreach($tasks[$status->id] as $task)
                            <div
                                wire:key="task-{{ $task->id }}"
                                wire:drag.status-id="{{ $status->id }}"
                                wire:drag.task-id="{{ $task->id }}"
                                wire:dragstart="startDrag({{ $task->id }})"
                                class="bg-white dark:bg-gray-700 p-3 rounded-lg shadow-sm border-l-4 cursor-move hover:shadow-md transition-shadow"
                                style="border-left-color: {{ $this->getPriorityColor($task->priority) }}"
                            >
                                <div class="flex justify-between items-start mb-2">
                                    <a href="{{ route('tasks.show', ['project' => $project, 'task' => $task]) }}"
                                       class="font-medium hover:underline">
                                        {{ $task->title }}
                                    </a>
                                    <span class="badge"
                                          style="background-color: {{ $this->getPriorityColor($task->priority) }}">
                                        {{ $this->getPriorityLabel($task->priority) }}
                                    </span>
                                </div>

                                <div class="text-sm text-gray-500 dark:text-gray-400 mb-3 line-clamp-2">
                                    {{ $task->description }}
                                </div>

                                @if($task->tags->isNotEmpty())
                                    <div class="flex flex-wrap gap-1 mb-2">
                                        @foreach($task->tags as $tag)
                                            <span class="badge badge-sm"
                                                  style="background-color: {{ $tag->color }}; color: {{ $this->getContrastColor($tag->color) }};">
                                            {{ $tag->name }}
                                        </span>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="flex justify-between items-center">
                                    <div class="flex items-center">
                                        @if($task->assignee)
                                            <div class="avatar avatar-xs">
                                                <img src="{{ $task->assignee->avatar_url }}"
                                                     alt="{{ $task->assignee->name }}">
                                            </div>
                                            <span class="text-xs ml-1">{{ $task->assignee->name }}</span>
                                        @else
                                            <span class="text-xs text-gray-400">Unassigned</span>
                                        @endif
                                    </div>

                                    <div class="flex items-center text-xs text-gray-500">
                                        <i class="fas fa-clock mr-1"></i>
                                        @if($task->due_date)
                                            <span class="{{ $task->due_date < now() ? 'text-red-500' : '' }}">
                                                {{ $task->due_date->format('M d') }}
                                            </span>
                                        @else
                                            <span>No due date</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-8 text-gray-400 italic">
                            No tasks in this status
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        const drake = dragula([...document.querySelectorAll('[wire\\:drop-target]')], {
            moves: function (el) {
                return el.hasAttribute('wire:drag');
            },
            accepts: function (el, target) {
                return true; // Allow dropping in any column
            }
        });

        drake.on('drop', function (el, target, source) {
            const statusId = target.getAttribute('wire:drop-target.status-id');
            const taskId = el.getAttribute('wire:drag.task-id');

            if (statusId && taskId) {
                Livewire.dispatch('drop-task', {statusId, taskId});
            }
        });
    });
</script>
