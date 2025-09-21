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

<div class="bg-gradient-to-br from-base-100 to-base-200 min-h-screen">
    <x-slot:title>Sprint Report - {{ $sprint->name }}</x-slot:title>

    <div class="max-w-7xl mx-auto p-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div class="flex items-center gap-3">
                <x-button 
                    link="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}" 
                    icon="fas.arrow-left" 
                    class="btn-ghost btn-sm hover:bg-base-200 transition-all duration-200"
                    tooltip="Back to Sprint"
                />
                <div>
                    <h1 class="text-2xl font-bold text-primary">Sprint Report</h1>
                    <div class="flex items-center gap-2 text-base-content/70">
                        <span class="font-medium">{{ $sprint->name }}</span>
                        <div class="badge {{ $sprint->is_completed ? 'badge-info' : ($sprint->is_active ? 'badge-success' : 'badge-warning') }}">
                            @if($sprint->is_completed)
                                <i class="fas fa-check-circle mr-1"></i> Completed
                            @elseif($sprint->is_active)
                                <i class="fas fa-play-circle mr-1"></i> Active
                            @else
                                <i class="fas fa-clock mr-1"></i> Planned
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <div class="dropdown dropdown-end">
                    <label tabindex="0" class="btn btn-outline btn-sm hover:bg-base-200 transition-all duration-200">
                        <i class="fas fa-download mr-1"></i>
                        Export
                    </label>
                    <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow-lg bg-base-100 rounded-lg w-52 border border-base-300">
                        <li>
                            <a href="{{ route('sprints.export.csv', ['project' => $project->id, 'sprint' => $sprint->id]) }}" class="flex items-center gap-2 hover:bg-base-200 transition-all duration-200">
                                <i class="fas fa-file-csv"></i> Export as CSV
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('sprints.export.json', ['project' => $project->id, 'sprint' => $sprint->id]) }}" class="flex items-center gap-2 hover:bg-base-200 transition-all duration-200">
                                <i class="fas fa-file-code"></i> Export as JSON
                            </a>
                        </li>
                    </ul>
                </div>
                <x-button
                    onclick="window.print()"
                    label="Print Report"
                    icon="fas.print"
                    class="btn-outline btn-sm hover:bg-base-200 transition-all duration-200"
                    tooltip="Print this report"
                />
            </div>
        </div>

        <!-- Sprint Overview -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <!-- Sprint Progress -->
            <div class="bg-base-100 rounded-xl shadow-xl border border-base-300 overflow-hidden">
                <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center gap-3">
                    <span class="p-2 rounded-full bg-primary/10 text-primary">
                        <i class="fas fa-chart-pie text-lg"></i>
                    </span>
                    <h2 class="text-xl font-semibold">Sprint Progress</h2>
                </div>
                
                <div class="p-6">
                    <div class="flex flex-col items-center justify-center py-4">
                        @php
                            $completionPercentage = $chartData['completion']['total'] > 0 ? 
                                round(($chartData['completion']['completed'] / $chartData['completion']['total']) * 100) : 0;
                        @endphp
                        
                        <div class="radial-progress text-primary"
                             style="--value:{{ $completionPercentage }}; --size:10rem; --thickness: 1rem;">
                            <span class="text-2xl font-bold">{{ $completionPercentage }}%</span>
                        </div>
                        
                        <div class="mt-6 text-center">
                            <p class="text-lg font-medium">{{ $chartData['completion']['completed'] }}
                                of {{ $chartData['completion']['total'] }} tasks completed</p>
                                
                            <div class="mt-2 text-sm text-base-content/70">
                                @if($completionPercentage < 30)
                                    <span class="text-warning flex items-center justify-center gap-1">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        Sprint is behind schedule
                                    </span>
                                @elseif($completionPercentage < 70)
                                    <span class="text-info flex items-center justify-center gap-1">
                                        <i class="fas fa-info-circle"></i>
                                        Sprint is progressing
                                    </span>
                                @else
                                    <span class="text-success flex items-center justify-center gap-1">
                                        <i class="fas fa-check-circle"></i>
                                        Sprint is on track
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sprint Timeline -->
            <div class="bg-base-100 rounded-xl shadow-xl border border-base-300 overflow-hidden">
                <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center gap-3">
                    <span class="p-2 rounded-full bg-primary/10 text-primary">
                        <i class="fas fa-calendar-alt text-lg"></i>
                    </span>
                    <h2 class="text-xl font-semibold">Sprint Timeline</h2>
                </div>
                
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <div class="text-center p-3 bg-base-200/50 rounded-lg border border-base-300 flex-1 mr-2">
                            <div class="text-xs text-base-content/70">Start Date</div>
                            <div class="font-medium">{{ \Carbon\Carbon::parse($chartData['sprintDates']['start'])->format('M d, Y') }}</div>
                        </div>
                        <div class="text-center p-3 bg-base-200/50 rounded-lg border border-base-300 flex-1 ml-2">
                            <div class="text-xs text-base-content/70">End Date</div>
                            <div class="font-medium">{{ \Carbon\Carbon::parse($chartData['sprintDates']['end'])->format('M d, Y') }}</div>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <div class="flex justify-between mb-2 text-sm">
                            <span class="text-base-content/70">Timeline Progress</span>
                            @php
                                $progressPercentage = $chartData['sprintDates']['days'] > 0
                                    ? (($chartData['sprintDates']['days'] - $chartData['sprintDates']['remaining']) / $chartData['sprintDates']['days']) * 100
                                    : 0;
                                $progressPercentage = max(0, min(100, round($progressPercentage)));
                            @endphp
                            <span class="font-medium">{{ $progressPercentage }}%</span>
                        </div>
                        <div class="w-full bg-base-200 rounded-full h-3 overflow-hidden">
                            <div class="bg-primary h-3 rounded-full transition-all duration-500" style="width: {{ $progressPercentage }}%"></div>
                        </div>
                        
                        <div class="mt-6 flex items-center justify-between">
                            <div>
                                <div class="text-xs text-base-content/70">Total Duration</div>
                                <div class="font-medium flex items-center gap-1">
                                    <i class="fas fa-calendar-week text-primary"></i>
                                    {{ $chartData['sprintDates']['days'] }} days
                                </div>
                            </div>
                            
                            <div>
                                <div class="text-xs text-base-content/70">Status</div>
                                @if($chartData['sprintDates']['remaining'] > 0)
                                    <div class="font-medium flex items-center gap-1 text-warning">
                                        <i class="fas fa-hourglass-half"></i>
                                        {{ $chartData['sprintDates']['remaining'] }} days remaining
                                    </div>
                                @else
                                    <div class="font-medium flex items-center gap-1 {{ $sprint->is_completed ? 'text-success' : 'text-error' }}">
                                        <i class="fas {{ $sprint->is_completed ? 'fa-flag-checkered' : 'fa-clock' }}"></i>
                                        Sprint {{ $sprint->is_completed ? 'completed' : 'ended' }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Task Distribution -->
            <div class="bg-base-100 rounded-xl shadow-xl border border-base-300 overflow-hidden">
                <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center gap-3">
                    <span class="p-2 rounded-full bg-primary/10 text-primary">
                        <i class="fas fa-tasks text-lg"></i>
                    </span>
                    <h2 class="text-xl font-semibold">Task Distribution</h2>
                </div>
                
                <div class="p-6">
                    @if(empty($chartData['statusDistribution']['labels']))
                        <div class="flex flex-col items-center justify-center py-8 text-base-content/50">
                            <i class="fas fa-clipboard-list text-3xl mb-2"></i>
                            <p>No tasks in this sprint</p>
                        </div>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-{{ min(count($chartData['statusDistribution']['labels']), 3) }} gap-4">
                            @foreach($chartData['statusDistribution']['labels'] as $index => $status)
                                <div class="bg-base-200/50 p-4 rounded-lg border border-base-300 text-center">
                                    <div class="text-2xl font-bold {{ $status === 'Done' ? 'text-success' : ($status === 'In Progress' ? 'text-warning' : 'text-primary') }}">
                                        {{ $chartData['statusDistribution']['data'][$index] }}
                                    </div>
                                    <div class="text-sm font-medium">{{ $status }}</div>
                                    <div class="text-xs text-base-content/70 mt-1">
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

        <!-- Assignee Performance -->
        <div class="bg-base-100 rounded-xl shadow-xl border border-base-300 overflow-hidden mb-6">
            <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center gap-3">
                <span class="p-2 rounded-full bg-primary/10 text-primary">
                    <i class="fas fa-users text-lg"></i>
                </span>
                <h2 class="text-xl font-semibold">Assignee Performance</h2>
            </div>
            
            <div class="p-6">
                @if(empty($chartData['assignees']))
                    <div class="flex flex-col items-center justify-center py-8 text-base-content/50">
                        <i class="fas fa-user-slash text-3xl mb-2"></i>
                        <p>No tasks assigned in this sprint</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="table w-full">
                            <thead class="bg-base-200/50">
                                <tr>
                                    <th>Assignee</th>
                                    <th class="text-center">Total Tasks</th>
                                    <th class="text-center">Completed</th>
                                    <th>Progress</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($chartData['assignees'] as $assignee)
                                    @php
                                        $progressPercentage = $assignee['total'] > 0 ? round(($assignee['completed'] / $assignee['total']) * 100) : 0;
                                    @endphp
                                    <tr class="hover:bg-base-200/30 transition-colors duration-150">
                                        <td>
                                            <div class="flex items-center gap-3">
                                                <div class="bg-primary/10 text-primary rounded-lg w-8 h-8 flex items-center justify-center">
                                                    <span class="font-medium">{{ substr($assignee['name'], 0, 1) }}</span>
                                                </div>
                                                <span class="font-medium">{{ $assignee['name'] }}</span>
                                            </div>
                                        </td>
                                        <td class="text-center">{{ $assignee['total'] }}</td>
                                        <td class="text-center">
                                            <span class="{{ $assignee['completed'] > 0 ? 'text-success font-medium' : 'text-base-content/50' }}">
                                                {{ $assignee['completed'] }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="flex items-center gap-3">
                                                <div class="flex-1">
                                                    <div class="w-full bg-base-200 rounded-full h-2.5 overflow-hidden">
                                                        <div class="h-2.5 rounded-full transition-all duration-500 {{ 
                                                            $progressPercentage >= 70 ? 'bg-success' : 
                                                            ($progressPercentage >= 30 ? 'bg-warning' : 'bg-error') 
                                                        }}" style="width: {{ $progressPercentage }}%"></div>
                                                    </div>
                                                </div>
                                                <span class="font-medium text-sm {{ 
                                                    $progressPercentage >= 70 ? 'text-success' : 
                                                    ($progressPercentage >= 30 ? 'text-warning' : 'text-error') 
                                                }}">{{ $progressPercentage }}%</span>
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
        <div class="bg-base-100 rounded-xl shadow-xl border border-base-300 overflow-hidden">
            <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="p-2 rounded-full bg-primary/10 text-primary">
                        <i class="fas fa-clipboard-list text-lg"></i>
                    </span>
                    <h2 class="text-xl font-semibold">Sprint Tasks</h2>
                </div>
                
                @if(!$sprint->tasks->isEmpty())
                    <span class="badge badge-primary badge-lg">{{ $sprint->tasks->count() }} {{ Str::plural('task', $sprint->tasks->count()) }}</span>
                @endif
            </div>
            
            <div class="p-0">
                @if($sprint->tasks->isEmpty())
                    <div class="flex flex-col items-center justify-center py-12 text-base-content/50">
                        <i class="fas fa-tasks text-3xl mb-2"></i>
                        <p>No tasks in this sprint</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="table w-full">
                            <thead class="bg-base-200/50">
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Assignee</th>
                                    <th class="text-center">Points</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sprint->tasks as $task)
                                    <tr class="hover:bg-base-200/30 transition-colors duration-150">
                                        <td>
                                            <span class="text-xs font-mono bg-primary/10 text-primary px-2 py-1 rounded">
                                                {{ $project->key }}-{{ $task->id }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="/projects/{{ $project->id }}/tasks/{{ $task->id }}"
                                               class="font-medium text-primary hover:underline transition-colors duration-200">
                                                {{ $task->title }}
                                            </a>
                                        </td>
                                        <td>
                                            @if($task->status)
                                                <div class="badge" style="background-color: {{ $task->status->color }}; color: white;">
                                                    {{ $task->status->name }}
                                                </div>
                                            @else
                                                <span class="text-base-content/50">-</span>
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
                                        </td>
                                        <td class="text-center">
                                            @if($task->story_points)
                                                <span class="text-xs bg-info/10 text-info px-2 py-0.5 rounded-full font-medium">
                                                    {{ $task->story_points }} pts
                                                </span>
                                            @else
                                                <span class="text-base-content/50">-</span>
                                            @endif
                                        </td>
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
    </div>
</div>
