<?php

new class extends Livewire\Volt\Component {
    use \Mary\Traits\Toast;

    public \App\Models\Project $project;
    public $search = '';
    public $statusFilter = '';
    public $priorityFilter = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $users = [];
    public $editingTaskId = null;
    public $editingTaskStatus = null;
    public $editingTaskUser = null;
    public $availableStatuses = [];

    public function mount()
    {
        // Projedeki kullanıcıları yükle
        $this->users = $this->project->members()->select('users.id', 'users.name')->get()->toArray();

        // Projedeki statüsleri yükle
        $this->availableStatuses = $this->project->statuses;
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
            $this->err 'Task not found!');
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
        $this - $this->success('Task status updated successfully!');
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

    public function with(): array
    {
        $query = $this->project->tasks()
            ->with(['status', 'user', 'sprint']);

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
            <x-input placeholder="Search tasks..." wire:model.live="search" icon="o-magnifying-glass" class="w-full"/>

            <div class="flex gap-2">
                <x-select
                        placeholder="Status"
                        wire:model.live="statusFilter"
                        :options="$statuses"
                        empty-message="All Statuses"
                        class="w-32"
                />

                <x-select
                        placeholder="Priority"
                        wire:model.live="priorityFilter"
                        :options="['low' => 'Low', 'medium' => 'Medium', 'high' => 'High']"
                        empty-message="All Priorities"
                        class="w-32"
                />
            </div>
        </div>

        <x-button no-wire-navigate link="{{ route('tasks.create', ['project' => $project]) }}" label="Create Task"
                  icon="o-plus"
                  class="btn-primary"/>
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
                        <tbody>
                        @foreach($tasks as $task)
                            <tr>
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
