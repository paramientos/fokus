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

<div class="bg-gradient-to-br from-base-100 to-base-200 min-h-screen p-6">
    <div class="max-w-7xl mx-auto">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <span class="text-xs font-mono bg-primary/10 text-primary px-2 py-1 rounded">
                        {{ $project->key }}
                    </span>
                    <h1 class="text-2xl font-bold text-primary">Kanban Board</h1>
                </div>
                <p class="text-base-content/70">Drag and drop tasks to change their status</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <x-button 
                    link="{{ route('projects.show', $project) }}" 
                    icon="fas.arrow-left" 
                    class="btn-outline btn-sm hover:bg-base-200 transition-all duration-200"
                >
                    Back to Project
                </x-button>
                <x-button 
                    link="{{ route('tasks.create', ['project' => $project]) }}" 
                    icon="fas.plus" 
                    class="btn-primary btn-sm hover:shadow-md transition-all duration-300"
                >
                    New Task
                </x-button>
            </div>
        </div>

        <div class="bg-base-100 rounded-xl shadow-xl border border-base-300 p-5 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="searchQuery" class="block text-sm font-medium mb-2">Search Tasks</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-base-content/50"></i>
                        </div>
                        <x-input 
                            id="searchQuery" 
                            wire:model.live.debounce.300ms="searchQuery"
                            class="pl-10 w-full focus:border-primary/50 transition-all duration-300"
                            placeholder="Search by title or description..."
                        />
                    </div>
                </div>

                <div>
                    <label for="filterAssignee" class="block text-sm font-medium mb-2">Filter by Assignee</label>
                    <x-select 
                        id="filterAssignee" 
                        wire:model.live="filterAssignee" 
                        class="w-full focus:border-primary/50 transition-all duration-300"
                        icon="fas.user"
                    >
                        <option value="">All Assignees</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </x-select>
                </div>

                <div>
                    <label for="filterPriority" class="block text-sm font-medium mb-2">Filter by Priority</label>
                    <x-select 
                        id="filterPriority" 
                        wire:model.live="filterPriority" 
                        class="w-full focus:border-primary/50 transition-all duration-300"
                        icon="fas.arrow-up-wide-short"
                    >
                        <option value="">All Priorities</option>
                        @foreach($priorities as $priority)
                            <option value="{{ $priority['id'] }}">{{ $priority['name'] }}</option>
                        @endforeach
                    </x-select>
                </div>
            </div>
        </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 overflow-x-auto pb-6">
        @foreach($statuses as $status)
            <div class="bg-base-100 rounded-xl shadow-lg border border-base-300 overflow-hidden min-w-[300px]">
                <div class="bg-base-200/50 p-3 border-b border-base-300 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full"
                              style="background-color: {{ $status->color ?? '#64748b' }}"></span>
                        <h3 class="font-semibold">{{ $status->name }}</h3>
                    </div>
                    <span class="badge badge-sm bg-primary/10 text-primary border-0">
                        {{ $tasks->get($status->id)?->count() ?? 0 }}
                    </span>
                </div>

                <div
                    class="space-y-3 p-3 min-h-[300px] bg-base-200/20"
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
                                class="bg-base-100 p-4 rounded-lg shadow-sm border-l-4 cursor-move hover:shadow-md transition-all duration-200 group"
                                style="border-left-color: {{ $this->getPriorityColor($task->priority) }}"
                            >
                                <div class="flex justify-between items-start mb-3 gap-2">
                                    <a href="{{ route('tasks.show', ['project' => $project, 'task' => $task]) }}"
                                       class="font-medium text-primary/90 hover:text-primary transition-colors duration-200 group-hover:underline">
                                        <div class="flex items-center gap-1">
                                            <span class="text-xs font-mono bg-primary/10 text-primary px-1.5 py-0.5 rounded">
                                                {{ $project->key }}-{{ $task->task_id ?? $task->id }}
                                            </span>
                                        </div>
                                        <div class="mt-1">{{ $task->title }}</div>
                                    </a>
                                    <div>
                                        <span class="badge {{ 
                                            $task->priority?->value === 'high' ? 'badge-error' : 
                                            ($task->priority?->value === 'medium' ? 'badge-warning' : 'badge-info') 
                                        }} badge-sm">
                                            @if($task->priority?->value === 'high')
                                                <i class="fas fa-arrow-up text-xs mr-1"></i>
                                            @elseif($task->priority?->value === 'medium')
                                                <i class="fas fa-equals text-xs mr-1"></i>
                                            @else
                                                <i class="fas fa-arrow-down text-xs mr-1"></i>
                                            @endif
                                            {{ $this->getPriorityLabel($task->priority) }}
                                        </span>
                                    </div>
                                </div>

                                @if($task->description)
                                    <div class="text-sm text-base-content/70 mb-3 line-clamp-2 bg-base-200/30 p-2 rounded">
                                        {{ $task->description }}
                                    </div>
                                @endif

                                @if($task->tags->isNotEmpty())
                                    <div class="flex flex-wrap gap-1 mb-3">
                                        @foreach($task->tags as $tag)
                                            <span class="badge badge-sm"
                                                  style="background-color: {{ $tag->color }}; color: {{ $this->getContrastColor($tag->color) }};">
                                                <i class="fas fa-tag text-xs mr-1"></i>
                                                {{ $tag->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="flex justify-between items-center border-t border-base-300/50 pt-2">
                                    <div class="flex items-center">
                                        @if($task->assignee)
                                            <div class="bg-primary/10 text-primary rounded-full w-6 h-6 flex items-center justify-center">
                                                @if($task->assignee->avatar_url)
                                                    <img src="{{ $task->assignee->avatar_url }}" alt="{{ $task->assignee->name }}" class="rounded-full">
                                                @else
                                                    <span class="text-xs font-medium">{{ substr($task->assignee->name, 0, 1) }}</span>
                                                @endif
                                            </div>
                                            <span class="text-xs ml-1 text-base-content/70">{{ $task->assignee->name }}</span>
                                        @else
                                            <span class="text-xs text-base-content/50 flex items-center gap-1">
                                                <i class="fas fa-user-slash"></i>
                                                <span>Unassigned</span>
                                            </span>
                                        @endif
                                    </div>

                                    <div class="flex items-center text-xs">
                                        @if($task->due_date)
                                            <span class="flex items-center gap-1 {{ $task->due_date < now() ? 'text-error' : 'text-base-content/70' }}">
                                                <i class="fas fa-calendar-day"></i>
                                                <span>{{ $task->due_date->format('M d') }}</span>
                                            </span>
                                        @else
                                            <span class="flex items-center gap-1 text-base-content/50">
                                                <i class="fas fa-calendar-xmark"></i>
                                                <span>No due date</span>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="flex flex-col items-center justify-center h-full py-12 text-base-content/40">
                            <i class="fas fa-inbox text-3xl mb-2"></i>
                            <p>No tasks in this status</p>
                            <p class="text-xs mt-1">Drag tasks here</p>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        // Sürükle-bırak işlevselliği için Dragula'yı yapılandır
        const drake = dragula([...document.querySelectorAll('[wire\\:drop-target]')], {
            moves: function (el) {
                return el.hasAttribute('wire:drag');
            },
            accepts: function (el, target) {
                return true; // Herhangi bir kolona bırakılabilir
            },
            direction: 'vertical',
            mirrorContainer: document.body,
            ignoreInputTextSelection: true
        });

        // Sürükleme başladığında görsel geri bildirim ekle
        drake.on('drag', function(el) {
            el.classList.add('opacity-75', 'scale-105', 'shadow-lg', 'border-primary');
        });

        // Sürükleme bittiğinde görsel geri bildirimi kaldır
        drake.on('dragend', function(el) {
            el.classList.remove('opacity-75', 'scale-105', 'shadow-lg', 'border-primary');
        });

        // Hedef üzerine geldiğinde görsel geri bildirim ekle
        drake.on('over', function(el, container) {
            container.classList.add('bg-primary/5', 'border-primary/50');
        });

        // Hedeften ayrıldığında görsel geri bildirimi kaldır
        drake.on('out', function(el, container) {
            container.classList.remove('bg-primary/5', 'border-primary/50');
        });

        // Bırakıldığında Livewire'a bildir
        drake.on('drop', function (el, target, source) {
            const statusId = target.getAttribute('wire:drop-target.status-id');
            const taskId = el.getAttribute('wire:drag.task-id');

            if (statusId && taskId) {
                // Görsel geri bildirim ekle
                el.classList.add('animate-pulse', 'border-success');
                
                // Livewire olayını tetikle
                Livewire.dispatch('drop-task', {statusId, taskId});
                
                // Kısa bir süre sonra animasyonu kaldır
                setTimeout(() => {
                    el.classList.remove('animate-pulse', 'border-success');
                }, 1000);
            }
        });
    });
</script>
