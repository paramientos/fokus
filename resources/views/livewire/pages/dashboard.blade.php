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

<div class="min-h-screen">
    <x-slot:title>Dashboard</x-slot:title>

    <!-- Dashboard Header with Stats -->
    <div class="bg-gradient-to-r from-primary/10 to-primary/5 border-b border-base-300">
        <div class="max-w-7xl mx-auto p-6">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-primary mb-1">Dashboard</h1>
                    <p class="text-base-content/70">Welcome to your project management hub</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <x-button
                        link="/workspaces"
                        icon="fas.building"
                        class="btn-outline btn-primary hover:shadow-md transition-all duration-300"
                    >
                        My Workspaces
                    </x-button>
                    <x-button
                        link="/projects/create"
                        icon="fas.plus"
                        class="btn-primary hover:shadow-lg transition-all duration-300"
                    >
                        Create Project
                    </x-button>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
                <div class="bg-base-100 rounded-xl shadow-md border border-base-300 p-4 flex items-center gap-4 hover:shadow-lg transition-all duration-300">
                    <div class="p-3 rounded-full bg-primary/10 text-primary">
                        <i class="fas fa-project-diagram text-xl"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold">{{ \App\Models\Project::count() }}</div>
                        <div class="text-xs text-base-content/70">Total Projects</div>
                    </div>
                </div>

                <div class="bg-base-100 rounded-xl shadow-md border border-base-300 p-4 flex items-center gap-4 hover:shadow-lg transition-all duration-300">
                    <div class="p-3 rounded-full bg-success/10 text-success">
                        <i class="fas fa-tasks text-xl"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold">{{ \App\Models\Task::count() }}</div>
                        <div class="text-xs text-base-content/70">Total Tasks</div>
                    </div>
                </div>

                <div class="bg-base-100 rounded-xl shadow-md border border-base-300 p-4 flex items-center gap-4 hover:shadow-lg transition-all duration-300">
                    <div class="p-3 rounded-full bg-info/10 text-info">
                        <i class="fas fa-flag text-xl"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold">{{ \App\Models\Sprint::where('is_active', true)->count() }}</div>
                        <div class="text-xs text-base-content/70">Active Sprints</div>
                    </div>
                </div>

                <div class="bg-base-100 rounded-xl shadow-md border border-base-300 p-4 flex items-center gap-4 hover:shadow-lg transition-all duration-300">
                    <div class="p-3 rounded-full bg-warning/10 text-warning">
                        <i class="fas fa-users text-xl"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold">{{ \App\Models\Workspace::count() }}</div>
                        <div class="text-xs text-base-content/70">Workspaces</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto p-6">
        <!-- Main Dashboard Layout -->
        <div class="grid grid-cols-12 gap-6">
            <!-- Left Column - Workspaces and Activity -->
            <div class="col-span-12 lg:col-span-4 space-y-6">
                <!-- Workspaces Section -->
                <div class="bg-base-100 rounded-xl shadow-md border border-base-300 overflow-hidden">
                    <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-building text-primary"></i>
                            <h2 class="font-semibold">My Workspaces</h2>
                        </div>
                        <x-button
                            link="/workspaces"
                            icon="fas.arrow-right"
                            class="btn-xs btn-ghost hover:bg-base-200 transition-all duration-200"
                            tooltip="View All"
                        />
                    </div>

                    <div class="p-4">
                        <livewire:components.all-info-component />

                        @forelse($workspaces->take(3) as $workspace)
                            <div class="mb-3 bg-base-200/50 rounded-lg p-3 hover:bg-base-200 transition-all duration-200">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="bg-primary/10 text-primary rounded-lg w-8 h-8 flex items-center justify-center">
                                            <span class="font-medium">{{ substr($workspace->name, 0, 1) }}</span>
                                        </div>
                                        <div>
                                            <div class="font-medium">{{ $workspace->name }}</div>
                                            <div class="flex items-center gap-1">
                                                <span class="text-xs text-base-content/60">{{ $workspace->projects->count() }} projects</span>
                                                <span class="text-xs text-base-content/60">·</span>
                                                <span class="text-xs text-base-content/60">{{ $workspace->members->count() }} members</span>
                                            </div>
                                        </div>
                                    </div>
                                    <x-button
                                        link="/workspaces/{{ $workspace->id }}"
                                        icon="fas.arrow-right"
                                        class="btn-xs btn-ghost hover:bg-base-200 transition-all duration-200"
                                        tooltip="Open Workspace"
                                    />
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-6">
                                <div class="p-4 rounded-full bg-base-200 mx-auto mb-3">
                                    <i class="fas fa-building text-2xl text-base-content/50"></i>
                                </div>
                                <h3 class="font-bold mb-2">No workspaces yet</h3>
                                <p class="text-xs text-base-content/70 mb-3">Create your first workspace</p>
                                <x-button
                                    link="/workspaces"
                                    icon="fas.plus"
                                    class="btn-sm btn-primary hover:shadow-md transition-all duration-300"
                                >
                                    Create Workspace
                                </x-button>
                            </div>
                        @endforelse

                        @if($workspaces->count() > 0)
                            <div class="mt-3 pt-3 border-t border-base-200 text-center">
                                <x-button
                                    link="/workspaces"
                                    class="btn-sm btn-outline w-full hover:shadow-md transition-all duration-300"
                                >
                                    View All Workspaces
                                </x-button>
                            </div>
                        @endif
                    </div>
                </div>

                @if($latestProject)
                <!-- Activity Timeline -->
                <div class="bg-base-100 rounded-xl shadow-md border border-base-300 overflow-hidden">
                    <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-history text-primary"></i>
                            <h2 class="font-semibold">Recent Activity</h2>
                        </div>
                        <x-button
                            link="/projects/{{ $latestProject->id }}/activities"
                            icon="fas.clock-rotate-left"
                            class="btn-xs btn-ghost hover:bg-base-200 transition-all duration-200"
                            tooltip="View Full Timeline"
                        />
                    </div>

                    <div class="p-4">
                        @if($tasks->isEmpty())
                            <div class="text-center py-4">
                                <div class="p-3 rounded-full bg-base-200 mx-auto mb-2">
                                    <i class="fas fa-history text-xl text-base-content/50"></i>
                                </div>
                                <p class="text-base-content/50 italic text-sm">No recent activity</p>
                            </div>
                        @else
                            <div class="space-y-3">
                                @foreach($tasks->take(4) as $task)
                                    <div class="flex items-start gap-3 p-2 rounded-lg hover:bg-base-200/50 transition-all duration-200">
                                        <div class="bg-primary/10 text-primary rounded-full w-8 h-8 flex-shrink-0 flex items-center justify-center">
                                            <i class="fas fa-tasks text-sm"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="font-medium truncate">{{ get_task_with_id($task) }}</div>
                                            <div class="flex items-center gap-1">
                                                <span class="text-xs bg-primary/10 text-primary px-1 rounded">{{ $task->project->key }}</span>
                                                <span class="text-xs text-base-content/60">{{ $task->created_at->diffForHumans() }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>

            <!-- Right Column - Projects and Tasks -->
            <div class="col-span-12 lg:col-span-8 space-y-6">
                <!-- Projects and Tasks Tabs -->
                <div class="bg-base-100 rounded-xl shadow-md border border-base-300 overflow-hidden">
                    <div class="tabs tabs-boxed bg-base-200/50 p-1 m-2 rounded-lg">
                        <a class="tab tab-active gap-2">
                            <i class="fas fa-project-diagram text-sm"></i>
                            <span>Projects</span>
                        </a>
                        <a class="tab gap-2">
                            <i class="fas fa-tasks text-sm"></i>
                            <span>Tasks</span>
                        </a>
                        <a class="tab gap-2">
                            <i class="fas fa-lightbulb text-sm"></i>
                            <span>Recommendations</span>
                        </a>
                    </div>

                    <div class="p-4">
                        <!-- Projects Tab Content -->
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="text-lg font-semibold">Recent Projects</h2>
                                <div class="flex gap-2">
                                    <x-button
                                        link="/projects/create"
                                        icon="fas.plus"
                                        class="btn-sm btn-primary hover:shadow-md transition-all duration-300"
                                    >
                                        New Project
                                    </x-button>
                                    <x-button
                                        link="/projects"
                                        icon="fas.arrow-right"
                                        class="btn-sm btn-ghost hover:bg-base-200 transition-all duration-200"
                                        tooltip="View All Projects"
                                    />
                                </div>
                            </div>

                            @if($projects->isEmpty())
                                <div class="flex flex-col items-center justify-center py-8 text-center">
                                    <div class="p-5 rounded-full bg-base-200 mb-3">
                                        <i class="fas fa-folder-open text-2xl text-base-content/50"></i>
                                    </div>
                                    <h3 class="text-lg font-bold mb-2">No projects yet</h3>
                                    <p class="text-base-content/70 max-w-md mb-4">Create your first project to start organizing your tasks</p>
                                    <x-button
                                        link="/projects/create"
                                        icon="fas.plus"
                                        class="btn-primary hover:shadow-lg transition-all duration-300"
                                    >
                                        Create Project
                                    </x-button>
                                </div>
                            @else
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    @foreach($projects as $project)
                                        <div class="bg-base-200/30 rounded-lg p-4 hover:bg-base-200/50 hover:shadow-md transition-all duration-200 border border-base-300">
                                            <div class="flex items-center justify-between mb-2">
                                                <div class="flex items-center gap-3">
                                                    <div class="bg-primary/10 text-primary rounded-lg w-8 h-8 flex items-center justify-center">
                                                        <span class="font-medium">{{ $project->key }}</span>
                                                    </div>
                                                    <a href="/projects/{{ $project->id }}" class="font-medium text-primary hover:underline transition-colors duration-200">
                                                        {{ $project->name }}
                                                    </a>
                                                </div>
                                                <div class="badge badge-sm badge-{{ $project->is_active ? 'success' : 'error' }}">
                                                    {{ $project->is_active ? 'Active' : 'Inactive' }}
                                                </div>
                                            </div>

                                            @if($project->description)
                                                <p class="text-sm text-base-content/70 mb-3 line-clamp-2">{{ Str::limit($project->description, 100) }}</p>
                                            @endif

                                            <div class="flex justify-between items-center mt-3 pt-2 border-t border-base-300">
                                                <div class="flex items-center gap-3">
                                                    <span class="text-xs bg-primary/5 px-2 py-1 rounded">
                                                        {{ $project->tasks->count() }} {{ Str::plural('task', $project->tasks->count()) }}
                                                    </span>
                                                    <span class="text-xs bg-info/5 px-2 py-1 rounded">
                                                        {{ $project->sprints->where('is_active', true)->count() }} active {{ Str::plural('sprint', $project->sprints->where('is_active', true)->count()) }}
                                                    </span>
                                                </div>
                                                <div class="flex gap-1">
                                                    <x-button
                                                        link="/projects/{{ $project->id }}"
                                                        icon="fas.eye"
                                                        class="btn-xs btn-ghost hover:bg-base-300 transition-all duration-200"
                                                        tooltip="View Project"
                                                    />
                                                    <x-button
                                                        link="/projects/{{ $project->id }}/board"
                                                        icon="fas.columns"
                                                        class="btn-xs btn-ghost hover:bg-base-300 transition-all duration-200"
                                                        tooltip="Board View"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Recent Tasks with Status Distribution -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Tasks List -->
                    <div class="md:col-span-2 bg-base-100 rounded-xl shadow-md border border-base-300 overflow-hidden">
                        <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-tasks text-primary"></i>
                                <h2 class="font-semibold">Recent Tasks</h2>
                            </div>
                        </div>

                        <div class="p-4">
                            @if($tasks->isEmpty())
                                <div class="flex flex-col items-center justify-center py-6 text-center">
                                    <div class="p-4 rounded-full bg-base-200 mb-3">
                                        <i class="fas fa-clipboard-list text-2xl text-base-content/50"></i>
                                    </div>
                                    <p class="text-base-content/50 italic">No tasks yet</p>
                                </div>
                            @else
                                <div class="space-y-3">
                                    @foreach($tasks->take(6) as $task)
                                        <div class="flex items-center justify-between p-3 bg-base-200/30 rounded-lg hover:bg-base-200/50 transition-all duration-200 border border-base-300">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <div class="bg-primary/10 text-primary rounded-lg w-8 h-8 flex-shrink-0 flex items-center justify-center">
                                                    <span class="text-xs font-medium">{{ $task->project->key }}</span>
                                                </div>
                                                <div class="min-w-0">
                                                    <a href="/projects/{{ $task->project_id }}/tasks/{{ $task->id }}" class="font-medium text-primary hover:underline transition-colors duration-200 truncate block">
                                                        {{ get_task_with_id($task) }}
                                                    </a>
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-xs text-base-content/60">{{ $task->created_at->diffForHumans() }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <div class="badge" style="background-color: {{ $task->status->color }}; color: white;">
                                                    {{ $task->status->name }}
                                                </div>
                                                <x-button
                                                    link="/projects/{{ $task->project_id }}/tasks/{{ $task->id }}"
                                                    icon="fas.eye"
                                                    class="btn-xs btn-ghost hover:bg-base-300 transition-all duration-200"
                                                    tooltip="View Task"
                                                />
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Recommended Tasks -->
                    <div class="bg-base-100 rounded-xl shadow-md border border-base-300 overflow-hidden">
                        <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center gap-2">
                            <i class="fas fa-lightbulb text-primary"></i>
                            <h2 class="font-semibold">Recommendations</h2>
                        </div>
                        <div class="p-4">
                            <livewire:tasks.recommendations :project="$latestProject"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
