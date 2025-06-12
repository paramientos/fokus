<?php

new class extends Livewire\Volt\Component {
    public function with(): array
    {
        $workspaces = \App\Models\Workspace::where('owner_id', auth()->id())
            ->orWhereHas('members', function($query) {
                $query->where('user_id', auth()->id());
            })
            ->with(['owner', 'members'])
            ->get();

        $projects = \App\Models\Project::where('is_active', true)
            ->latest()
            ->take(5)
            ->get();

        $tasks = \App\Models\Task::whereHas('project', function ($query) {
            $query->where('is_active', true);
        })
            ->latest()
            ->take(10)
            ->get();

        $latestProject = \App\Models\Project::latest()->first();

        return [
            'workspaces' => $workspaces,
            'projects' => $projects,
            'tasks' => $tasks,
            'latestProject' => $latestProject,
        ];
    }
}

?>

<div>
    <x-slot:title>Dashboard</x-slot:title>
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-primary">Dashboard</h1>
            <div class="flex gap-2">
                <x-button link="/workspaces" icon="fas.building" class="btn-outline">
                    My Workspaces
                </x-button>
                <x-button link="/projects/create" icon="fas.plus" class="btn-primary">
                    Create Project
                </x-button>
            </div>
        </div>

        <!-- Workspaces Section -->
        <div class="mb-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">My Workspaces</h2>
                <x-button link="/workspaces" icon="fas.arrow-right" class="btn-sm btn-ghost">
                    View All
                </x-button>
            </div>

            <livewire:components.all-info-component />

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @forelse($workspaces->take(3) as $workspace)
                    <div class="card bg-base-100 shadow-lg">
                        <div class="card-body">
                            <h3 class="card-title">{{ $workspace->name }}</h3>
                            <p class="text-gray-500 text-sm">{{ Str::limit($workspace->description, 60) }}</p>

                            <div class="flex items-center gap-2 mt-2">
                                <div class="avatar placeholder">
                                    <div class="bg-neutral text-neutral-content rounded-full w-6">
                                        <span class="text-xs">{{ substr($workspace->owner->name, 0, 1) }}</span>
                                    </div>
                                </div>
                                <span class="text-sm">{{ $workspace->owner->name }}</span>
                                @if($workspace->owner_id === auth()->id())
                                    <x-badge color="primary" size="sm">Owner</x-badge>
                                @else
                                    @php
                                        $member = $workspace->members->firstWhere('id', auth()->id());
                                        $role = $member ? $member->pivot->role : 'member';
                                    @endphp
                                    <x-badge color="{{ $role === 'admin' ? 'secondary' : 'neutral' }}" size="sm">
                                        {{ ucfirst($role) }}
                                    </x-badge>
                                @endif
                            </div>

                            <div class="card-actions justify-end mt-4">
                                <x-button link="/workspaces/{{ $workspace->id }}" icon="fas.arrow-right" class="btn-sm btn-primary">
                                    Open
                                </x-button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-3 card bg-base-100 shadow-lg">
                        <div class="card-body text-center">
                            <x-icon name="fas.building" class="w-12 h-12 mx-auto text-gray-400"/>
                            <h3 class="text-lg font-medium mt-2">No workspaces found</h3>
                            <p class="text-gray-500">Create your first workspace to organize your projects</p>
                            <div class="card-actions justify-center mt-4">
                                <x-button link="/workspaces" icon="fas.plus" class="btn-primary">
                                    Create Workspace
                                </x-button>
                            </div>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Projects -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title flex justify-between">
                        <span>Recent Projects</span>
                        <x-button link="/projects" icon="fas.arrow-right" class="btn-sm btn-ghost"/>
                    </h2>

                    @if($projects->isEmpty())
                        <div class="py-4 text-center text-gray-500">
                            <p>No projects found</p>
                            <x-button link="/projects/create" icon="fas.plus"
                                      class="btn-primary mt-2">Create your first project</x-button>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="table table-zebra w-full">
                                <thead>
                                <tr>
                                    <th>Key</th>
                                    <th>Name</th>
                                    <th>Tasks</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($projects as $project)
                                    <tr>
                                        <td>{{ $project->key }}</td>
                                        <td>{{ $project->name }}</td>
                                        <td>{{ $project->tasks->count() }}</td>
                                        <td>
                                            <x-button link="/projects/{{ $project->id }}" icon="fas.eye"
                                                      class="btn-sm btn-ghost" tooltip="View Project"/>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Recent Tasks -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">Recent Tasks</h2>

                    @if($tasks->isEmpty())
                        <div class="py-4 text-center text-gray-500">
                            <p>No tasks found</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="table table-zebra w-full">
                                <thead>
                                <tr>
                                    <th>Project</th>
                                    <th>Title</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($tasks as $task)
                                    <tr>
                                        <td>{{ $task->project->key }}</td>
                                        <td>{{ $task->title }}</td>
                                        <td>
                                            <div class="badge" style="background-color: {{ $task->status->color }}">
                                                {{ $task->status->name }}
                                            </div>
                                        </td>
                                        <td>
                                            <x-button link="/projects/{{ $task->project_id }}/tasks/{{ $task->id }}"
                                                      icon="fas.eye" class="btn-sm btn-ghost" tooltip="View Task"/>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Önerilen Görevler Bileşeni -->
            <div class="col-span-1 md:col-span-2">
                <livewire:tasks.recommendations :project="$latestProject"/>
            </div>

            <!-- Project Statistics -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">Project Statistics</h2>
                    <div class="stats stats-vertical lg:stats-horizontal shadow">
                        <div class="stat">
                            <div class="stat-title">Total Projects</div>
                            <div class="stat-value">{{ \App\Models\Project::count() }}</div>
                        </div>

                        <div class="stat">
                            <div class="stat-title">Total Tasks</div>
                            <div class="stat-value">{{ \App\Models\Task::count() }}</div>
                        </div>

                        <div class="stat">
                            <div class="stat-title">Active Sprints</div>
                            <div class="stat-value">{{ \App\Models\Sprint::where('is_active', true)->count() }}</div>
                        </div>
                    </div>
                </div>
            </div>

            @if ($latestProject)
                <!-- Activity Timeline -->
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h2 class="card-title">Recent Activity</h2>
                        <x-button link="/projects/{{ $latestProject->id }}/activities"
                                  icon="fas.clock-rotate-left" class="btn-outline mb-4">Activity Timeline</x-button>
                        <div class="py-4">
                            <ul class="timeline timeline-vertical">
                                @foreach($tasks->take(5) as $index => $task)
                                    <li>
                                        <div class="timeline-start">{{ $task->created_at->diffForHumans() }}</div>
                                        <div class="timeline-middle">
                                            <x-icon name="fas.circle" class="text-primary"/>
                                        </div>
                                        <div class="timeline-end timeline-box">
                                            <p class="font-bold">{{ $task->title }}</p>
                                            <p class="text-sm">Created in {{ $task->project->name }}</p>
                                        </div>
                                        @if($index < count($tasks->take(5)) - 1)
                                            <hr/>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
