<?php

new class extends Livewire\Volt\Component {
    public \App\Models\Project $project;
    public $search = '';
    public $statusFilter = '';
    public $priorityFilter = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
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
            ->paginate(10);

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

        <x-button link="{{ route('tasks.create', ['project' => $project]) }}" label="Create Task" icon="o-plus"
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
                        <x-button link="{{ route('tasks.create', ['project' => $project]) }}" label="Create Task" icon="o-plus"
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
                                    @if($task->status)
                                        <div class="badge" style="background-color: {{ $task->status->color }}">
                                            {{ $task->status->name }}
                                        </div>
                                    @else
                                        <span class="text-gray-500">-</span>
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
                                        <x-button link="{{ route('tasks.show', ['project' => $project, 'task' => $task]) }}" icon="o-eye"
                                                  class="btn-sm btn-ghost" tooltip="View"/>
                                        <x-button link="{{ route('tasks.edit', ['project' => $project, 'task' => $task]) }}"
                                                  icon="o-pencil" class="btn-sm btn-ghost" tooltip="Edit"/>
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
