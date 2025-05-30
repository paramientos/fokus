<?php

new class extends Livewire\Volt\Component {
    public function with(): array
    {
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
            <x-button link="/projects/create" label="Create Project" icon="o-plus" class="btn-primary"/>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Projects -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title flex justify-between">
                        <span>Recent Projects</span>
                        <x-button link="/projects" label="View All" icon="o-arrow-right" class="btn-sm btn-ghost"/>
                    </h2>

                    @if($projects->isEmpty())
                        <div class="py-4 text-center text-gray-500">
                            <p>No projects found</p>
                            <x-button link="/projects/create" label="Create your first project" icon="o-plus"
                                      class="btn-primary mt-2"/>
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
                                            <x-button link="/projects/{{ $project->id }}" icon="o-eye"
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
                                                      icon="o-eye" class="btn-sm btn-ghost" tooltip="View Task"/>
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
                <livewire:tasks.recommendations :project="$latestProject" />
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

            <!-- Activity Timeline -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">Recent Activity</h2>
                    <x-button link="/projects/{{ $latestProject->id }}/activities" label="Activity Timeline" icon="fas.clock-rotate-left" class="btn-outline mb-4" />
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
        </div>
    </div>
</div>
