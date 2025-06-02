<?php

new class extends Livewire\Volt\Component {
    public $project;
    public $selectedTab = 'overview';

    public function mount($project)
    {
        $this->project = \App\Models\Project::with(['tasks', 'sprints', 'statuses'])->findOrFail($project);
    }

    public function setTab($tab)
    {
        $this->selectedTab = $tab;
    }

    public function with(): array
    {
        $tasks = $this->project->tasks()
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $sprints = $this->project->sprints()
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $tasksByStatus = $this->project->tasks()
            ->selectRaw('status_id, count(*) as count')
            ->groupBy('status_id')
            ->get()
            ->mapWithKeys(function ($item) {
                $status = \App\Models\Status::find($item->status_id);
                return [$status->name => $item->count];
            });

        return [
            'tasks' => $tasks,
            'sprints' => $sprints,
            'tasksByStatus' => $tasksByStatus,
        ];
    }
}

?>

<div>
    <x-slot:title>{{ $project->name }}</x-slot:title>

    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <div class="flex items-center gap-4">
                @if($project->avatar)
                    <img src="{{ $project->avatar }}" alt="{{ $project->name }}" class="w-12 h-12 rounded-full">
                @else
                    <div class="avatar placeholder">
                        <div class="bg-neutral text-neutral-content rounded-full w-12">
                            <span class="text-xl">{{ substr($project->name, 0, 1) }}</span>
                        </div>
                    </div>
                @endif
                <div>
                    <h1 class="text-2xl font-bold text-primary">{{ $project->name }}</h1>
                    <p class="text-sm text-gray-500">{{ $project->key }} ·
                        Created {{ $project->created_at->format('M d, Y') }}</p>
                </div>
            </div>

            <div class="flex gap-2">
                <x-button link="/projects/{{ $project->id }}/edit" label="Edit" icon="o-pencil" class="btn-outline"/>
                <x-button link="/projects/{{ $project->id }}/board" label="Board" icon="o-view-columns"
                          class="btn-primary"/>
                <x-button link="{{ route('tasks.gantt-chart', ['project' => $project->id]) }}" icon="fas.chart-gantt" class="btn-primary">
                    Gantt Chart
                </x-button>
            </div>
        </div>

        <!-- Project Navigation -->
        <div class="tabs tabs-boxed mb-6">
            <a wire:click="setTab('overview')" class="tab {{ $selectedTab === 'overview' ? 'tab-active' : '' }}">Overview</a>
            <a wire:click="setTab('tasks')" class="tab {{ $selectedTab === 'tasks' ? 'tab-active' : '' }}">Tasks</a>
            <a wire:click="setTab('sprints')"
               class="tab {{ $selectedTab === 'sprints' ? 'tab-active' : '' }}">Sprints</a>
            <a wire:click="setTab('team')" class="tab {{ $selectedTab === 'team' ? 'tab-active' : '' }}">Team Members</a>
            <a wire:click="setTab('status')" onclick="setTimeout(() => window.dispatchEvent(new Event('init-sortable')), 100);" class="tab {{ $selectedTab === 'status' ? 'tab-active' : '' }}">Status</a>
            <a wire:click="setTab('settings')" class="tab {{ $selectedTab === 'settings' ? 'tab-active' : '' }}">Settings</a>
        </div>

        <!-- Tab Content -->
        <div>
            <!-- Overview Tab -->
            @if($selectedTab === 'overview')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Project Description -->
                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body">
                            <h2 class="card-title">Description</h2>
                            <div class="prose">
                                @if($project->description)
                                    <p>{{ $project->description }}</p>
                                @else
                                    <p class="text-gray-500 italic">No description provided</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Project Stats -->
                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body">
                            <h2 class="card-title">Statistics</h2>
                            <div class="stats stats-vertical shadow">
                                <div class="stat">
                                    <div class="stat-title">Total Tasks</div>
                                    <div class="stat-value">{{ $project->tasks->count() }}</div>
                                </div>

                                <div class="stat">
                                    <div class="stat-title">Active Sprints</div>
                                    <div
                                        class="stat-value">{{ $project->sprints->where('is_active', true)->count() }}</div>
                                </div>

                                <div class="stat">
                                    <div class="stat-title">Completed Tasks</div>
                                    <div class="stat-value">
                                        {{ $project->tasks->whereNotNull('status_id')->where(function($query) {
                                            return $query->whereHas('status', function($q) {
                                                return $q->where('slug', 'done');
                                            });
                                        })->count() }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Tasks -->
                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body">
                            <div class="flex justify-between items-center">
                                <h2 class="card-title">Recent Tasks</h2>
                                <x-button link="/projects/{{ $project->id }}/tasks" label="View All"
                                          icon="o-arrow-right" class="btn-sm btn-ghost"/>
                            </div>

                            @if($tasks->isEmpty())
                                <div class="py-4 text-center text-gray-500">
                                    <p>No tasks found</p>
                                    <x-button link="/projects/{{ $project->id }}/tasks/create" label="Create Task"
                                              icon="o-plus" class="btn-primary mt-2"/>
                                </div>
                            @else
                                <div class="overflow-x-auto">
                                    <table class="table table-zebra w-full">
                                        <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Title</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($tasks as $task)
                                            <tr>
                                                <td>{{ $project->key }}-{{ $task->id }}</td>
                                                <td>
                                                    <a href="/projects/{{ $project->id }}/tasks/{{ $task->id }}"
                                                       class="link link-hover">
                                                        {{ $task->title }}
                                                    </a>
                                                </td>
                                                <td>
                                                    @if($task->status)
                                                        <div class="badge"
                                                             style="background-color: {{ $task->status->color }}">
                                                            {{ $task->status->name }}
                                                        </div>
                                                    @else
                                                        <span class="text-gray-500">-</span>
                                                    @endif
                                                </td>
                                                <td>{{ $task->created_at->format('M d, Y') }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Recent Sprints -->
                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body">
                            <div class="flex justify-between items-center">
                                <h2 class="card-title">Recent Sprints</h2>
                                <x-button link="/projects/{{ $project->id }}/sprints" label="View All"
                                          icon="o-arrow-right" class="btn-sm btn-ghost"/>
                            </div>

                            @if($sprints->isEmpty())
                                <div class="py-4 text-center text-gray-500">
                                    <p>No sprints found</p>
                                    <x-button link="/projects/{{ $project->id }}/sprints/create" label="Create Sprint"
                                              icon="o-plus" class="btn-primary mt-2"/>
                                </div>
                            @else
                                <div class="overflow-x-auto">
                                    <table class="table table-zebra w-full">
                                        <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Status</th>
                                            <th>Duration</th>
                                            <th>Tasks</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($sprints as $sprint)
                                            <tr>
                                                <td>
                                                    <a href="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}"
                                                       class="link link-hover">
                                                        {{ $sprint->name }}
                                                    </a>
                                                </td>
                                                <td>
                                                    <div
                                                        class="badge {{ $sprint->is_active ? 'badge-success' : ($sprint->is_completed ? 'badge-info' : 'badge-warning') }}">
                                                        {{ $sprint->is_completed ? 'Completed' : ($sprint->is_active ? 'Active' : 'Planned') }}
                                                    </div>
                                                </td>
                                                <td>
                                                    @if($sprint->start_date && $sprint->end_date)
                                                        {{ $sprint->start_date->format('M d') }}
                                                        - {{ $sprint->end_date->format('M d') }}
                                                    @else
                                                        <span class="text-gray-500">-</span>
                                                    @endif
                                                </td>
                                                <td>{{ $sprint->tasks->count() }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- Tasks Tab -->
            @if($selectedTab === 'tasks')
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="card-title">All Tasks</h2>
                            <x-button link="/projects/{{ $project->id }}/tasks/create" label="Create Task" icon="o-plus"
                                      class="btn-primary"/>
                        </div>

                        <livewire:tasks.index :project="$project"/>
                    </div>
                </div>
            @endif

            <!-- Sprints Tab -->
            @if($selectedTab === 'sprints')
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="card-title">All Sprints</h2>
                            <x-button link="/projects/{{ $project->id }}/sprints/create" label="Create Sprint"
                                      icon="o-plus" class="btn-primary"/>
                        </div>

                        <livewire:sprints.index :project="$project"/>
                    </div>
                </div>
            @endif

            <!-- Team Members Tab -->
            @if($selectedTab === 'team')
                <livewire:projects.team-members :project="$project" />
            @endif

            <!-- Status Tab -->
            @if($selectedTab === 'status')
                <livewire:projects.status-manager :project="$project" />
            @endif

            <!-- Settings Tab -->
            @if($selectedTab === 'settings')
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h2 class="card-title mb-4">Project Settings</h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Project Details -->
                            <div>
                                <h3 class="text-lg font-medium mb-2">Project Details</h3>
                                <div class="overflow-x-auto">
                                    <table class="table w-full">
                                        <tbody>
                                        <tr>
                                            <td class="font-bold">Name</td>
                                            <td>{{ $project->name }}</td>
                                        </tr>
                                        <tr>
                                            <td class="font-bold">Key</td>
                                            <td>{{ $project->key }}</td>
                                        </tr>
                                        <tr>
                                            <td class="font-bold">Status</td>
                                            <td>
                                                <div
                                                    class="badge {{ $project->is_active ? 'badge-success' : 'badge-error' }}">
                                                    {{ $project->is_active ? 'Active' : 'Inactive' }}
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="font-bold">Created</td>
                                            <td>{{ $project->created_at->format('M d, Y H:i') }}</td>
                                        </tr>
                                        <tr>
                                            <td class="font-bold">Last Updated</td>
                                            <td>{{ $project->updated_at->format('M d, Y H:i') }}</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="mt-4">
                                    <x-button link="/projects/{{ $project->id }}/edit" label="Edit Project"
                                              icon="o-pencil" class="btn-outline"/>
                                </div>
                            </div>

                            <!-- Project Statuses -->
                            <div>
                                <h3 class="text-lg font-medium mb-2">Project Statuses</h3>
                                <div class="overflow-x-auto">
                                    <table class="table w-full">
                                        <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Color</th>
                                            <th>Order</th>
                                            <th>Tasks</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($project->statuses->sortBy('order') as $status)
                                            <tr>
                                                <td>{{ $status->name }}</td>
                                                <td>
                                                    <div class="w-6 h-6 rounded"
                                                         style="background-color: {{ $status->color }}"></div>
                                                </td>
                                                <td>{{ $status->order }}</td>
                                                <td>{{ $status->tasks->count() }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
