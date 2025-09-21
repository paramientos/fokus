<?php

new class extends Livewire\Volt\Component {
    use \Mary\Traits\Toast;

    public \App\Models\Project $project;
    public $search = '';
    public $statusFilter = '';
    public $priorityFilter = '';
    public $assigneeFilter = '';
    public $reporterFilter = '';
    public $sprintFilter = '';
    public $taskTypeFilter = '';
    public $dueDateFilter = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $users = [];
    public $editingTaskId = null;
    public $editingTaskStatus = null;
    public $editingTaskUser = null;
    public $availableStatuses = [];
    
    // Filter options
    public $sprints = [];
    public $priorities = [];
    public $taskTypes = [];

    public function mount()
    {
        // Projedeki kullanıcıları yükle
        $this->users = $this->project->teamMembers()->select('users.id', 'users.name')->get()->toArray();

        // Projedeki statüsleri yükle
        $this->availableStatuses = $this->project->statuses;
        
        $this->loadFilterOptions();
    }

    public function loadFilterOptions()
    {
        // Load sprints
        $this->sprints = $this->project->sprints()->select('id', 'name')->get()->toArray();
        
        // Load priorities
        $this->priorities = collect(\App\Enums\Priority::cases())->map(function ($priority) {
            return ['value' => $priority->value, 'label' => $priority->name];
        })->toArray();
        
        // Load task types
        $this->taskTypes = collect(\App\Enums\TaskType::cases())->map(function ($type) {
            return ['value' => $type->value, 'label' => $type->name];
        })->toArray();
    }

    public function getTasksProperty()
    {
        $query = $this->project->tasks()->with(['status', 'user', 'reporter', 'sprint']);

        // Apply search filter
        if ($this->search) {
            $query->where(function($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        // Apply status filter
        if ($this->statusFilter) {
            $query->where('status_id', $this->statusFilter);
        }

        // Apply priority filter
        if ($this->priorityFilter) {
            $query->where('priority', $this->priorityFilter);
        }

        // Apply assignee filter
        if ($this->assigneeFilter) {
            $query->where('user_id', $this->assigneeFilter);
        }

        // Apply reporter filter
        if ($this->reporterFilter) {
            $query->where('reporter_id', $this->reporterFilter);
        }

        // Apply sprint filter
        if ($this->sprintFilter) {
            $query->where('sprint_id', $this->sprintFilter);
        }

        // Apply task type filter
        if ($this->taskTypeFilter) {
            $query->where('task_type', $this->taskTypeFilter);
        }

        // Apply due date filter
        if ($this->dueDateFilter) {
            switch ($this->dueDateFilter) {
                case 'overdue':
                    $query->where('due_date', '<', now());
                    break;
                case 'today':
                    $query->whereDate('due_date', today());
                    break;
                case 'this_week':
                    $query->whereBetween('due_date', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'next_week':
                    $query->whereBetween('due_date', [now()->addWeek()->startOfWeek(), now()->addWeek()->endOfWeek()]);
                    break;
                case 'no_due_date':
                    $query->whereNull('due_date');
                    break;
            }
        }

        return $query->orderBy($this->sortField, $this->sortDirection)->paginate(20);
    }

    public function clearFilters()
    {
        $this->reset(['search', 'statusFilter', 'priorityFilter', 'assigneeFilter', 'reporterFilter', 'sprintFilter', 'taskTypeFilter', 'dueDateFilter']);
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

    public function editTask($taskId)
    {
        $task = \App\Models\Task::find($taskId);
        if ($task) {
            $this->editingTaskId = $taskId;
            $this->editingTaskStatus = $task->status_id;
            $this->editingTaskUser = $task->user_id;
        }
    }

    public function cancelEdit()
    {
        $this->reset(['editingTaskId', 'editingTaskStatus', 'editingTaskUser']);
    }

    public function updateTaskStatus($taskId, $statusId)
    {
        $task = \App\Models\Task::find($taskId);
        if (!$task) {
            $this->error('Task not found!');
            return;
        }

        // Aynı statüye güncelleme yapılıyorsa işlem yapmaya gerek yok
        if ($task->status_id == $statusId) {
            return;
        }

        // Statü geçiş kontrolü
        $fromStatusId = $task->status_id;
        $allowed = \App\Models\StatusTransition::where('project_id', $this->project->id)
            ->where('from_status_id', $fromStatusId)
            ->where('to_status_id', $statusId)
            ->exists();

        if (!$allowed) {
            $this->error('This status transition is not allowed!');
            return;
        }

        $task->update(['status_id' => $statusId]);
        $this->success('Task status updated successfully!');
        $this->cancelEdit();
    }

    public function updateTaskAssignee($taskId, $userId)
    {
        $task = \App\Models\Task::find($taskId);
        if (!$task) {
            $this->error('Task not found!');
            return;
        }

        // Kullanıcı null olabilir (unassigned)
        $task->update(['user_id' => $userId ?: null]);
        $this->success('Task assignee updated successfully!');
        $this->cancelEdit();
    }

    public function saveChanges()
    {
        if ($this->editingTaskId) {
            $task = \App\Models\Task::find($this->editingTaskId);
            if ($task) {
                // Status değişikliği
                if ($task->status_id != $this->editingTaskStatus) {
                    $this->updateTaskStatus($this->editingTaskId, $this->editingTaskStatus);
                }

                // Atama değişikliği
                if ($task->user_id != $this->editingTaskUser) {
                    $this->updateTaskAssignee($this->editingTaskId, $this->editingTaskUser);
                }
            }
        }

        $this->cancelEdit();
    }

    public function reorder(array $ids)
    {
        foreach ($ids as $index => $id) {
            \App\Models\Task::where('id', $id)->update(['order' => $index]);
        }
        $this->success('Task order updated');
    }

    public function with(): array
    {
        $query = $this->project->tasks()
            ->with(['status', 'user', 'sprint'])
            ->ordered();

        // Apply search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        // Apply status filter
        if ($this->statusFilter) {
            $query->where('status_id', $this->statusFilter);
        }

        // Apply priority filter
        if ($this->priorityFilter) {
            $query->where('priority', $this->priorityFilter);
        }

        // Apply sorting
        $tasks = $query->orderBy($this->sortField, $this->sortDirection)
            ->paginate(50);

        $statuses = $this->project->statuses;

        return [
            'tasks' => $tasks,
            'statuses' => $statuses,
        ];
    }
}

?>

<div class="bg-gradient-to-br from-base-100 to-base-200 min-h-screen">
    <div class="max-w-7xl mx-auto p-6">
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
            <div class="flex flex-col sm:flex-row gap-4 w-full">
                <x-input 
                    placeholder="Search tasks..." 
                    wire:model.live.debounce.300ms="search" 
                    icon="fas.magnifying-glass" 
                    class="w-full shadow-sm focus:border-primary/50 transition-all duration-300"
                />
            </div>

            <x-button 
                link="/projects/{{ $project->id }}/tasks/create" 
                icon="fas.plus" 
                class="btn-primary whitespace-nowrap hover:shadow-md transition-all duration-300"
            >
                Add Task
            </x-button>
        </div>

        <!-- Advanced Filters -->
        <div class="bg-base-100 rounded-xl shadow-md border border-base-300 overflow-hidden mb-6">
            <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center gap-3">
                <span class="p-2 rounded-full bg-primary/10 text-primary">
                    <i class="fas fa-filter text-lg"></i>
                </span>
                <div>
                    <h2 class="text-lg font-semibold">Filters</h2>
                    <p class="text-sm text-base-content/70">Refine your task list</p>
                </div>
                
                <div class="ml-auto">
                    <x-button 
                        wire:click="clearFilters" 
                        icon="fas.xmark" 
                        class="btn-ghost btn-sm hover:bg-base-200 transition-all duration-200"
                        tooltip="Clear all filters"
                    >
                        Clear Filters
                    </x-button>
                </div>
            </div>
            
            <div class="p-5">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
                    <!-- Status Filter -->
                    <div>
                        <x-select 
                            placeholder="Status" 
                            wire:model.live="statusFilter"
                            :options="$availableStatuses"
                            option-value="id"
                            option-label="name"
                            icon="fas.check-circle"
                            class="w-full focus:border-primary/50 transition-all duration-300"
                        />
                    </div>

                    <!-- Assignee Filter -->
                    <div>
                        <x-select 
                            placeholder="Assignee" 
                            wire:model.live="assigneeFilter"
                            :options="$users"
                            option-value="id"
                            option-label="name"
                            icon="fas.user"
                            class="w-full focus:border-primary/50 transition-all duration-300"
                        />
                    </div>

                    <!-- Reporter Filter -->
                    <div>
                        <x-select 
                            placeholder="Reporter" 
                            wire:model.live="reporterFilter"
                            :options="$users"
                            option-value="id"
                            option-label="name"
                            icon="fas.user-edit"
                            class="w-full focus:border-primary/50 transition-all duration-300"
                        />
                    </div>

                    <!-- Sprint Filter -->
                    <div>
                        <x-select 
                            placeholder="Sprint" 
                            wire:model.live="sprintFilter"
                            :options="$sprints"
                            option-value="id"
                            option-label="name"
                            icon="fas.flag"
                            class="w-full focus:border-primary/50 transition-all duration-300"
                        />
                    </div>

                    <!-- Priority Filter -->
                    <div>
                        <x-select 
                            placeholder="Priority" 
                            wire:model.live="priorityFilter"
                            :options="$priorities"
                            option-value="value"
                            option-label="label"
                            icon="fas.arrow-up-wide-short"
                            class="w-full focus:border-primary/50 transition-all duration-300"
                        />
                    </div>

                    <!-- Task Type Filter -->
                    <div>
                        <x-select 
                            placeholder="Task Type" 
                            wire:model.live="taskTypeFilter"
                            :options="$taskTypes"
                            option-value="value"
                            option-label="label"
                            icon="fas.list-check"
                            class="w-full focus:border-primary/50 transition-all duration-300"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mt-4">
                    <!-- Due Date Filter -->
                    <div>
                        <x-select 
                            placeholder="Due Date" 
                            wire:model.live="dueDateFilter"
                            :options="[
                                ['value' => 'overdue', 'label' => 'Overdue'],
                                ['value' => 'today', 'label' => 'Due Today'],
                                ['value' => 'this_week', 'label' => 'This Week'],
                                ['value' => 'next_week', 'label' => 'Next Week'],
                                ['value' => 'no_due_date', 'label' => 'No Due Date']
                            ]"
                            option-value="value"
                            option-label="label"
                            icon="fas.calendar-days"
                            class="w-full focus:border-primary/50 transition-all duration-300"
                        />
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-base-100 rounded-xl shadow-xl border border-base-300 overflow-hidden">
            <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center gap-3">
                <span class="p-2 rounded-full bg-primary/10 text-primary">
                    <i class="fas fa-list-check text-lg"></i>
                </span>
                <div>
                    <h2 class="text-lg font-semibold">Task List</h2>
                    <p class="text-sm text-base-content/70">{{ $tasks->total() }} tasks found</p>
                </div>
            </div>
            
            <div class="p-0">
                @if($tasks->isEmpty())
                    <div class="flex flex-col items-center justify-center py-16 px-4">
                        <div class="p-6 rounded-full bg-base-200/50 mb-4">
                            <i class="fas fa-clipboard-list text-5xl text-base-content/30"></i>
                        </div>
                        <h3 class="text-xl font-medium text-base-content/80 mb-2">No tasks found</h3>
                        <p class="text-base-content/60 text-center max-w-md mb-8">No tasks match your current filters or there are no tasks in this project yet.</p>
                        <x-button 
                            no-wire-navigate 
                            link="{{ route('tasks.create', ['project' => $project]) }}"
                            label="Create Task" 
                            icon="fas.plus"
                            class="btn-primary hover:shadow-md transition-all duration-300"
                        />
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="table w-full">
                            <thead class="bg-base-200/50">
                                <tr>
                                    <th class="w-8"></th>
                                    <th class="cursor-pointer hover:bg-base-200/80 transition-colors duration-200" wire:click="sortBy('id')">
                                        <div class="flex items-center gap-1">
                                            <span>ID</span>
                                            @if($sortField === 'id')
                                                <i class="fas fa-{{ $sortDirection === 'asc' ? 'arrow-up' : 'arrow-down' }} text-xs text-primary"></i>
                                            @endif
                                        </div>
                                    </th>
                                    <th class="cursor-pointer hover:bg-base-200/80 transition-colors duration-200" wire:click="sortBy('title')">
                                        <div class="flex items-center gap-1">
                                            <span>Title</span>
                                            @if($sortField === 'title')
                                                <i class="fas fa-{{ $sortDirection === 'asc' ? 'arrow-up' : 'arrow-down' }} text-xs text-primary"></i>
                                            @endif
                                        </div>
                                    </th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Assignee</th>
                                    <th>Sprint</th>
                                    <th class="cursor-pointer hover:bg-base-200/80 transition-colors duration-200" wire:click="sortBy('created_at')">
                                        <div class="flex items-center gap-1">
                                            <span>Created</span>
                                            @if($sortField === 'created_at')
                                                <i class="fas fa-{{ $sortDirection === 'asc' ? 'arrow-up' : 'arrow-down' }} text-xs text-primary"></i>
                                            @endif
                                        </div>
                                    </th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody x-data
                                   x-init="
                                        Sortable.create($el, {
                                            animation: 150,
                                            handle: '.drag-handle',
                                            onEnd: function (evt) {
                                                const ids = Array.from(evt.to.children).map(row => row.dataset.id);
                                                $wire.reorder(ids);
                                            }
                                        });
                                   "
                            >
                            @foreach($tasks as $task)
                                <tr data-id="{{ $task->id }}" class="hover:bg-base-200/30 transition-colors duration-150">
                                    <td class="drag-handle cursor-move select-none text-base-content/30 hover:text-base-content/70 transition-colors duration-200">
                                        <i class="fas fa-grip-vertical"></i>
                                    </td>
                                    <td>
                                        <span class="text-xs font-mono bg-primary/10 text-primary px-2 py-1 rounded">
                                            {{ $project->key }}-{{ $task->task_id ?? $task->id }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('tasks.show', ['project' => $project, 'task' => $task]) }}"
                                           class="font-medium text-primary hover:underline transition-colors duration-200">
                                            {{ $task->title }}
                                        </a>
                                    </td>
                                    <td>
                                        @if($editingTaskId === $task->id)
                                            <x-select
                                                wire:model="editingTaskStatus"
                                                :options="$availableStatuses"
                                                option-value="id"
                                                option-label="name"
                                                class="w-full focus:border-primary/50 transition-all duration-300"
                                            />
                                        @else
                                            @if($task->status)
                                                <div class="badge cursor-pointer hover:shadow-sm transition-all duration-200"
                                                     style="background-color: {{ $task->status->color }}; color: white;"
                                                     wire:click="editTask({{ $task->id }})">
                                                    {{ $task->status->name }}
                                                </div>
                                            @else
                                                <span class="text-base-content/50">-</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td>
                                        @if($task->priority)
                                            <div class="badge {{
                                                    $task->priority->value === 'high' ? 'badge-error' :
                                                    ($task->priority->value === 'medium' ? 'badge-warning' : 'badge-info')
                                                }}">
                                                @if($task->priority->value === 'high')
                                                    <i class="fas fa-arrow-up mr-1"></i>
                                                @elseif($task->priority->value === 'medium')
                                                    <i class="fas fa-equals mr-1"></i>
                                                @else
                                                    <i class="fas fa-arrow-down mr-1"></i>
                                                @endif
                                                {{ ucfirst($task->priority->label()) }}
                                            </div>
                                        @else
                                            <span class="text-base-content/50">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($editingTaskId === $task->id)
                                            <x-select
                                                wire:model="editingTaskUser"
                                                :options="collect($users)->pluck('name', 'id')->toArray()"
                                                empty-message="Unassigned"
                                                class="w-full focus:border-primary/50 transition-all duration-300"
                                                icon="fas.user"
                                            />
                                        @else
                                            <div class="cursor-pointer hover:bg-base-200/50 rounded-lg px-2 py-1 transition-all duration-200" wire:click="editTask({{ $task->id }})">
                                                @if($task->user)
                                                    <div class="flex items-center gap-2">
                                                        <div class="bg-primary/10 text-primary rounded-lg w-6 h-6 flex items-center justify-center">
                                                            <span class="text-xs font-medium">{{ substr($task->user->name, 0, 1) }}</span>
                                                        </div>
                                                        <span>{{ $task->user->name }}</span>
                                                    </div>
                                                @else
                                                    <span class="text-base-content/50 flex items-center gap-1">
                                                        <i class="fas fa-user-slash"></i> Unassigned
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($task->sprint)
                                            <div class="badge badge-outline border-primary/30 text-primary/80 hover:border-primary hover:text-primary transition-all duration-200">
                                                <i class="fas fa-flag mr-1 text-xs"></i> {{ $task->sprint->name }}
                                            </div>
                                        @else
                                            <span class="text-base-content/50">-</span>
                                        @endif
                                    </td>
                                    <td>{{ $task->created_at->format('M d, Y') }}</td>
                                    <td>
                                        <div class="flex gap-2">
                                            @if($editingTaskId === $task->id)
                                                <x-button 
                                                    wire:click="saveChanges" 
                                                    icon="fas.check" 
                                                    class="btn-sm btn-success hover:shadow-sm transition-all duration-200"
                                                    tooltip="Save changes"
                                                />
                                                <x-button 
                                                    wire:click="cancelEdit" 
                                                    icon="fas.xmark" 
                                                    class="btn-sm btn-ghost hover:bg-base-200 transition-all duration-200"
                                                    tooltip="Cancel"
                                                />
                                            @else
                                                <x-button
                                                    link="{{ route('tasks.show', ['project' => $project, 'task' => $task]) }}"
                                                    icon="fas.eye"
                                                    class="btn-sm btn-ghost hover:bg-base-200 transition-all duration-200" 
                                                    tooltip="View task details"
                                                />
                                                <x-button 
                                                    no-wire-navigate
                                                    link="{{ route('tasks.edit', ['project' => $project, 'task' => $task]) }}"
                                                    icon="fas.pen" 
                                                    class="btn-sm btn-ghost hover:bg-base-200 transition-all duration-200" 
                                                    tooltip="Edit task"
                                                />
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="p-4 border-t border-base-300">
                        {{ $tasks->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
