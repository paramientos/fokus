<?php

new class extends Livewire\Volt\Component {
    public \App\Models\Project $project;
    public \App\Models\Sprint $sprint;
    public $chartData = [];

    public function mount($project, $sprint)
    {
        $this->sprint = \App\Models\Sprint::with(['tasks.status', 'tasks.user'])->findOrFail($this->sprint->id);
        $this->prepareChartData();
    }

    public function prepareChartData()
    {
        // Sprint başlangıç ve bitiş tarihleri
        $startDate = $this->sprint->start_date ?? $this->sprint->created_at;
        $endDate = $this->sprint->end_date ?? $startDate->copy()->addDays(14);

        // Görevlerin durumlara göre dağılımı
        $tasksByStatus = $this->sprint->tasks->groupBy(function ($task) {
            return $task->status ? $task->status->name : 'No Status';
        });

        $this->chartData = [
            'statusDistribution' => [
                'labels' => $tasksByStatus->keys()->toArray(),
                'data' => $tasksByStatus->map->count()->values()->toArray(),
            ],
            'sprintDates' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
                'days' => $startDate->diffInDays($endDate) + 1,
                'remaining' => now()->diffInDays($endDate, false),
            ],
            'completion' => [
                'total' => $this->sprint->tasks->count(),
                'completed' => $this->sprint->tasks->filter(function ($task) {
                    return $task->status && $task->status->slug === 'done';
                })->count(),
            ],
            'assignees' => $this->sprint->tasks->groupBy(function ($task) {
                return $task->user ? $task->user->name : 'Unassigned';
            })->map(function ($tasks, $assignee) {
                return [
                    'name' => $assignee,
                    'total' => $tasks->count(),
                    'completed' => $tasks->filter(function ($task) {
                        return $task->status && $task->status->slug === 'done';
                    })->count(),
                ];
            })->values()->toArray(),
        ];
    }
}

?>

<div>
    <x-slot:title>Sprint Report - {{ $sprint->name }}</x-slot:title>

    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <div class="flex items-center gap-2">
                <x-button link="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}" icon="o-arrow-left"
                          class="btn-ghost btn-sm"/>
                <h1 class="text-2xl font-bold text-primary">Sprint Report: {{ $sprint->name }}</h1>
                <div class="badge {{ $sprint->is_completed ? 'badge-info' : ($sprint->is_active ? 'badge-success' : 'badge-warning') }}">
                    {{ $sprint->is_completed ? 'Completed' : ($sprint->is_active ? 'Active' : 'Planned') }}
                </div>
            </div>

            <div class="flex gap-2">
                <div class="dropdown dropdown-end">
                    <label tabindex="0" class="btn btn-outline">
                        <x-icon name="o-arrow-down-tray" class="w-5 h-5"/>
                        Export
                    </label>
                    <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-52">
                        <li>
                            <a href="{{ route('sprints.export.csv', ['project' => $project->id, 'sprint' => $sprint->id]) }}">Export
                                as CSV</a></li>
                        <li>
                            <a href="{{ route('sprints.export.json', ['project' => $project->id, 'sprint' => $sprint->id]) }}">Export
                                as JSON</a></li>
                    </ul>
                </div>
                <x-button
                        onclick="window.print()"
                        label="Print Report"
                        icon="o-printer"
                        class="btn-outline"
                />
            </div>
        </div>

        <!-- Sprint Overview -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">Sprint Progress</h2>
                    <div class="flex flex-col items-center justify-center py-4">
                        <div class="radial-progress text-primary"
                             style="--value:{{ $chartData['completion']['total'] > 0 ? ($chartData['completion']['completed'] / $chartData['completion']['total']) * 100 : 0 }}; --size:8rem;">
                            {{ $chartData['completion']['total'] > 0 ? round(($chartData['completion']['completed'] / $chartData['completion']['total']) * 100) : 0 }}
                            %
                        </div>
                        <p class="mt-4">{{ $chartData['completion']['completed'] }}
                            of {{ $chartData['completion']['total'] }} tasks completed</p>
                    </div>
                </div>
            </div>

            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">Sprint Timeline</h2>
                    <div class="py-4">
                        <div class="flex justify-between mb-2">
                            <span>{{ \Carbon\Carbon::parse($chartData['sprintDates']['start'])->format('M d, Y') }}</span>
                            <span>{{ \Carbon\Carbon::parse($chartData['sprintDates']['end'])->format('M d, Y') }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            @php
                                $progressPercentage = $chartData['sprintDates']['days'] > 0
                                    ? (($chartData['sprintDates']['days'] - $chartData['sprintDates']['remaining']) / $chartData['sprintDates']['days']) * 100
                                    : 0;
                                $progressPercentage = max(0, min(100, $progressPercentage));
                            @endphp
                            <div class="bg-primary h-2.5 rounded-full" style="width: {{ $progressPercentage }}%"></div>
                        </div>
                        <div class="mt-4">
                            @if($chartData['sprintDates']['remaining'] > 0)
                                <p>{{ $chartData['sprintDates']['remaining'] }} days remaining</p>
                            @else
                                <p>Sprint {{ $sprint->is_completed ? 'completed' : 'ended' }}</p>
                            @endif
                            <p>Total duration: {{ $chartData['sprintDates']['days'] }} days</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">Task Distribution</h2>
                    <div class="py-4">
                        @if(empty($chartData['statusDistribution']['labels']))
                            <p class="text-center text-gray-500">No tasks in this sprint</p>
                        @else
                            <div class="stats shadow">
                                @foreach($chartData['statusDistribution']['labels'] as $index => $status)
                                    <div class="stat">
                                        <div class="stat-title">{{ $status }}</div>
                                        <div class="stat-value">{{ $chartData['statusDistribution']['data'][$index] }}</div>
                                        <div class="stat-desc">
                                            {{ $chartData['completion']['total'] > 0
                                                ? round(($chartData['statusDistribution']['data'][$index] / $chartData['completion']['total']) * 100)
                                                : 0 }}% of total
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Assignee Performance -->
        <div class="card bg-base-100 shadow-xl mb-6">
            <div class="card-body">
                <h2 class="card-title">Assignee Performance</h2>

                @if(empty($chartData['assignees']))
                    <div class="py-4 text-center text-gray-500">
                        <p>No tasks assigned in this sprint</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="table table-zebra w-full">
                            <thead>
                            <tr>
                                <th>Assignee</th>
                                <th>Total Tasks</th>
                                <th>Completed</th>
                                <th>Progress</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($chartData['assignees'] as $assignee)
                                <tr>
                                    <td>{{ $assignee['name'] }}</td>
                                    <td>{{ $assignee['total'] }}</td>
                                    <td>{{ $assignee['completed'] }}</td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                                <div class="bg-primary h-2.5 rounded-full"
                                                     style="width: {{ $assignee['total'] > 0 ? ($assignee['completed'] / $assignee['total']) * 100 : 0 }}%"></div>
                                            </div>
                                            <span>{{ $assignee['total'] > 0 ? round(($assignee['completed'] / $assignee['total']) * 100) : 0 }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <!-- Task List -->
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h2 class="card-title">Sprint Tasks</h2>

                @if($sprint->tasks->isEmpty())
                    <div class="py-4 text-center text-gray-500">
                        <p>No tasks in this sprint</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="table table-zebra w-full">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Assignee</th>
                                <th>Story Points</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($sprint->tasks as $task)
                                <tr>
                                    <td>{{ $project->key }}-{{ $task->id }}</td>
                                    <td>
                                        <a href="/projects/{{ $project->id }}/tasks/{{ $task->id }}"
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
                                    <td>{{ $task->story_points ?? '-' }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
