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

<div>
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div class="flex flex-col sm:flex-row gap-4 w-full">
            <x-input placeholder="Search tasks..." wire:model.live.debounce.300ms="search" icon="o-magnifying-glass" class="w-full"/>
        </div>

        <x-button link="/projects/{{ $project->id }}/tasks/create" icon="o-plus" class="btn-primary whitespace-nowrap">
            Add Task
        </x-button>
    </div>

    <!-- Advanced Filters -->
    <div class="card bg-base-100 shadow-sm mb-6">
        <div class="card-body p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-4">
                <!-- Status Filter -->
                <div>
                    <x-select 
                        placeholder="Status" 
                        wire:model.live="statusFilter"
                        :options="$availableStatuses"
                        option-value="id"
                        option-label="name"
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
                    />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-4 mt-4">
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
                    />
                </div>

                <!-- Clear Filters -->
                <div class="flex items-end">
                    <x-button 
                        wire:click="clearFilters" 
                        icon="o-x-mark" 
                        class="btn-ghost btn-sm w-full"
                    >
                        Clear Filters
                    </x-button>
                </div>
            </div>
        </div>
    </div>

    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            @if($tasks->isEmpty())
                <div class="py-8 text-center">
                    <x-icon name="o-clipboard-document-list" class="w-16 h-16 mx-auto text-gray-400"/>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">No tasks found</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by creating a new task.</p>
                    <div class="mt-6">
                        <x-button no-wire-navigate link="{{ route('tasks.create', ['project' => $project]) }}"
                                  label="Create Task" icon="o-plus"
                                  class="btn-primary"/>
                    </div>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="table table-zebra w-full">
                        <thead>
                        <tr>
                            <th></th>
                            <th class="cursor-pointer" wire:click="sortBy('id')">
                                ID
                                @if($sortField === 'id')
                                    <x-icon name="{{ $sortDirection === 'asc' ? 'o-chevron-up' : 'o-chevron-down' }}"
                                            class="w-4 h-4 inline"/>
                                @endif
                            </th>
                            <th class="cursor-pointer" wire:click="sortBy('title')">
                                Title
                                @if($sortField === 'title')
                                    <x-icon name="{{ $sortDirection === 'asc' ? 'o-chevron-up' : 'o-chevron-down' }}"
                                            class="w-4 h-4 inline"/>
                                @endif
                            </th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Assignee</th>
                            <th>Sprint</th>
                            <th class="cursor-pointer" wire:click="sortBy('created_at')">
                                Created
                                @if($sortField === 'created_at')
                                    <x-icon name="{{ $sortDirection === 'asc' ? 'o-chevron-up' : 'o-chevron-down' }}"
                                            class="w-4 h-4 inline"/>
                                @endif
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
                            <tr data-id="{{ $task->id }}">
                                <td class="drag-handle cursor-move select-none text-gray-400"><x-icon name="o-bars-3" class="w-4 h-4"/></td>
                                <td>{{ $project->key }}-{{ $task->id }}</td>
                                <td>
                                    <a href="{{ route('tasks.show', ['project' => $project, 'task' => $task]) }}"
                                       class="link link-hover font-medium">
                                        {{ $task->title }}
                                    </a>
                                </td>
                                <td>
                                    @if($editingTaskId === $task->id)
                                        <x-select
                                                wire:model="editingTaskStatus"
                                                :options="$availableStatuses"
                                                class="w-full"
                                        />
                                    @else
                                        @if($task->status)
                                            <div class="badge cursor-pointer"
                                                 style="background-color: {{ $task->status->color }}"
                                                 wire:click="editTask({{ $task->id }})">
                                                {{ $task->status->name }}
                                            </div>
                                        @else
                                            <span class="text-gray-500">-</span>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    @if($task->priority)
                                        <div class="badge {{
                                                $task->priority->value === 'high' ? 'badge-error' :
                                                ($task->priority->value === 'medium' ? 'badge-warning' : 'badge-info')
                                            }}">
                                            {{ ucfirst($task->priority->label()) }}
                                        </div>
                                    @else
                                        <span class="text-gray-500">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($editingTaskId === $task->id)
                                        <x-select
                                                wire:model="editingTaskUser"
                                                :options="collect($users)->pluck('name', 'id')->toArray()"
                                                empty-message="Unassigned"
                                                class="w-full"
                                        />
                                    @else
                                        <div class="cursor-pointer" wire:click="editTask({{ $task->id }})">
                                            @if($task->user)
                                                <div class="flex items-center gap-2">
                                                    <div class="avatar placeholder">
                                                        <div class="bg-neutral text-neutral-content rounded-full w-6">
                                                            <span>{{ substr($task->user->name, 0, 1) }}</span>
                                                        </div>
                                                    </div>
                                                    <span>{{ $task->user->name }}</span>
                                                </div>
                                            @else
                                                <span class="text-gray-500">Unassigned</span>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if($task->sprint)
                                        <div class="badge badge-outline">
                                            {{ $task->sprint->name }}
                                        </div>
                                    @else
                                        <span class="text-gray-500">-</span>
                                    @endif
                                </td>
                                <td>{{ $task->created_at->format('M d, Y') }}</td>
                                <td>
                                    <div class="flex gap-2">
                                        @if($editingTaskId === $task->id)
                                            <x-button wire:click="saveChanges" icon="o-check" class="btn-sm btn-success"
                                                      tooltip="Save"/>
                                            <x-button wire:click="cancelEdit" icon="o-x-mark" class="btn-sm btn-ghost"
                                                      tooltip="Cancel"/>
                                        @else
                                            <x-button
                                                    link="{{ route('tasks.show', ['project' => $project, 'task' => $task]) }}"
                                                    icon="o-eye"
                                                    class="btn-sm btn-ghost" tooltip="View"/>
                                            <x-button no-wire-navigate
                                                      link="{{ route('tasks.edit', ['project' => $project, 'task' => $task]) }}"
                                                      icon="o-pencil" class="btn-sm btn-ghost" tooltip="Edit"/>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $tasks->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
