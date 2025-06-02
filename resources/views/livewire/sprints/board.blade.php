<?php

use App\Models\Status;

new class extends Livewire\Volt\Component {
    public $project;
    public $sprint;
    public $statuses = [];
    public $tasksByStatus = [];

    public function mount($project, $sprint)
    {
        $this->project = \App\Models\Project::findOrFail($project);
        $this->sprint = \App\Models\Sprint::findOrFail($sprint);
        $this->loadData();
    }

    public function loadData()
    {
        // Proje durumlarını yükle
        $this->statuses = Status::where('project_id', $this->project->id)
            ->orderBy('order')
            ->get();

        // Eğer durum yoksa, varsayılan durumları oluştur
        if ($this->statuses->isEmpty()) {
            $defaultStatuses = [
                ['name' => 'To Do', 'slug' => 'todo', 'color' => '#3498db', 'order' => 1],
                ['name' => 'In Progress', 'slug' => 'in-progress', 'color' => '#f39c12', 'order' => 2],
                ['name' => 'Review', 'slug' => 'review', 'color' => '#9b59b6', 'order' => 3],
                ['name' => 'Done', 'slug' => 'done', 'color' => '#2ecc71', 'order' => 4, 'is_completed' => true],
            ];

            foreach ($defaultStatuses as $status) {
                Status::create([
                    'name' => $status['name'],
                    'slug' => $status['slug'],
                    'color' => $status['color'],
                    'order' => $status['order'],
                    'project_id' => $this->project->id,
                    'is_completed' => $status['is_completed'] ?? false
                ]);
            }

            $this->statuses = Status::where('project_id', $this->project->id)
                ->orderBy('order')
                ->get();
        }

        // Sprint görevlerini durumlara göre grupla
        $tasks = \App\Models\Task::with(['user', 'status'])
            ->where('sprint_id', $this->sprint->id)
            ->get();

        // Durumlara göre görevleri grupla
        $this->tasksByStatus = [];
        foreach ($this->statuses as $status) {
            $this->tasksByStatus[$status->id] = $tasks->filter(function ($task) use ($status) {
                return $task->status_id === $status->id;
            })->values()->toArray();
        }

        // Durumu olmayan görevleri ilk duruma ata
        $tasksWithoutStatus = $tasks->filter(function ($task) {
            return $task->status_id === null;
        });

        if ($tasksWithoutStatus->isNotEmpty() && $this->statuses->isNotEmpty()) {
            $firstStatus = $this->statuses->first();
            foreach ($tasksWithoutStatus as $task) {
                $task->update(['status_id' => $firstStatus->id]);
                $this->tasksByStatus[$firstStatus->id][] = $task->toArray();
            }
        }
    }

    public function updateTaskStatus($taskId, $statusId)
    {
        $task = \App\Models\Task::find($taskId);

        if ($task && $task->sprint_id === $this->sprint->id) {
            $task->update(['status_id' => $statusId]);
            $this->loadData();
        }
    }
}

?>

<div>
    <x-slot:title>Sprint Board - {{ $sprint->name }}</x-slot:title>

    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <div class="flex items-center gap-2">
                <x-button link="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}" icon="o-arrow-left"
                          class="btn-ghost btn-sm"/>
                <h1 class="text-2xl font-bold text-primary">Sprint Board: {{ $sprint->name }}</h1>
                <div
                    class="badge {{ $sprint->is_completed ? 'badge-info' : ($sprint->is_active ? 'badge-success' : 'badge-warning') }}">
                    {{ $sprint->is_completed ? 'Completed' : ($sprint->is_active ? 'Active' : 'Planned') }}
                </div>
            </div>

            <div class="flex gap-2">
                <x-button link="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}/report" label="View Report"
                          icon="o-chart-bar" class="btn-outline"/>
                <x-button link="/projects/{{ $project->id }}/tasks/create" label="Add Task" icon="o-plus"
                          class="btn-primary"/>
            </div>
        </div>

        <!-- Sprint Board -->
        <div
            x-data="{
                draggingTask: null,
                handleDragStart(event, taskId) {
                    this.draggingTask = taskId;
                    event.dataTransfer.effectAllowed = 'move';
                },
                handleDragOver(event) {
                    event.preventDefault();
                    event.dataTransfer.dropEffect = 'move';
                },
                handleDrop(event, statusId) {
                    event.preventDefault();
                    if (this.draggingTask) {
                        $wire.updateTaskStatus(this.draggingTask, statusId);
                        this.draggingTask = null;
                    }
                }
            }"
            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"
        >
            @foreach($statuses as $status)
                <div
                    class="card bg-base-100 shadow-xl"
                    x-on:dragover="handleDragOver($event)"
                    x-on:drop="handleDrop($event, {{ $status->id }})"
                >
                    <div class="card-body p-4">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="card-title" style="color: {{ $status->color }}">
                                {{ $status->name }}
                                <span class="badge badge-sm">{{ count($tasksByStatus[$status->id] ?? []) }}</span>
                            </h2>
                        </div>

                        <div class="overflow-y-auto max-h-[calc(100vh-250px)] space-y-2">
                            @foreach($tasksByStatus[$status->id] ?? [] as $task)
                                <div
                                    class="card bg-base-200 shadow-sm cursor-move"
                                    draggable="true"
                                    x-on:dragstart="handleDragStart($event, {{ $task['id'] }})"
                                >
                                    <div class="card-body p-3">
                                        <div class="flex justify-between items-start">
                                            <h3 class="font-medium text-sm">
                                                <a href="/projects/{{ $project->id }}/tasks/{{ $task['id'] }}"
                                                   class="link link-hover">
                                                    {{ $project->key }}-{{ $task['id'] }}
                                                </a>
                                            </h3>

                                            @if($task['priority'])
                                                <div class="badge {{
                                                    $task['priority'] === 'high' ? 'badge-error' :
                                                    ($task['priority'] === 'medium' ? 'badge-warning' : 'badge-info')
                                                }} badge-sm">
                                                    {{ ucfirst($task['priority']) }}
                                                </div>
                                            @endif
                                        </div>

                                        <p class="text-sm">{{ $task['title'] }}</p>

                                        <div class="flex justify-between items-center mt-2">
                                            @if(isset($task['user']) && $task['user'])
                                                <div class="flex items-center gap-1">
                                                    <div class="avatar placeholder">
                                                        <div
                                                            class="bg-neutral text-neutral-content rounded-full w-5 h-5">
                                                            <span
                                                                class="text-xs">{{ substr($task['user']['name'], 0, 1) }}</span>
                                                        </div>
                                                    </div>
                                                    <span class="text-xs">{{ $task['user']['name'] }}</span>
                                                </div>
                                            @else
                                                <span class="text-xs text-gray-500">Unassigned</span>
                                            @endif

                                            @if($task['story_points'])
                                                <div class="badge badge-sm">{{ $task['story_points'] }} pts</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            @if(empty($tasksByStatus[$status->id]))
                                <div class="text-center py-4 text-gray-500 text-sm">
                                    <p>No tasks</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
