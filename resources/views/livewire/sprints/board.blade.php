<?php

use App\Models\Status;
use App\Models\StatusTransition;
use App\Models\Task;
use App\Models\Comment;

new class extends Livewire\Volt\Component {
    use \Mary\Traits\Toast;
    
    public $project;
    public $sprint;
    public $statuses = [];
    public $tasksByStatus = [];
    public $selectedTask = null;
    public $selectedTaskDetails = null;
    public $newComment = '';
    public $showCommentForm = false;

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
        $task = Task::find($taskId);
        if (!$task) {
            $this->error('Task bulunamadı!');
            return;
        }

        // Statü geçiş kontrolü
        $fromStatusId = $task->status_id;
        $allowed = StatusTransition::where('project_id', $this->project->id)
            ->where('from_status_id', $fromStatusId)
            ->where('to_status_id', $statusId)
            ->exists();

        // Aynı statüye sürüklemeye izin ver
        if ($fromStatusId == $statusId) {
            $allowed = true;
        }

        if (!$allowed) {
            $this->error('Bu durum geçişine izin verilmiyor!');
            $this->loadData();
            return;
        }

        if ($task && $task->sprint_id === $this->sprint->id) {
            $task->update(['status_id' => $statusId]);
            $this->success('Task durumu güncellendi!');
            $this->loadData();
        }
    }
    
    public function viewTask($taskId)
    {
        $this->selectedTask = $taskId;
        $this->selectedTaskDetails = Task::with(['user', 'reporter', 'status', 'comments.user'])
            ->find($taskId)
            ->toArray();
        $this->showCommentForm = false;
        $this->newComment = '';
    }
    
    public function closeTaskDetails()
    {
        $this->selectedTask = null;
        $this->selectedTaskDetails = null;
        $this->showCommentForm = false;
        $this->newComment = '';
    }
    
    public function showAddComment()
    {
        $this->showCommentForm = true;
    }
    
    public function hideAddComment()
    {
        $this->showCommentForm = false;
        $this->newComment = '';
    }
    
    public function saveComment()
    {
        if (empty(trim($this->newComment))) {
            $this->error('Yorum boş olamaz!');
            return;
        }
        
        $comment = new Comment();
        $comment->task_id = $this->selectedTask;
        $comment->user_id = auth()->id();
        $comment->content = $this->newComment;
        $comment->save();
        
        $this->success('Yorum eklendi!');
        $this->newComment = '';
        $this->showCommentForm = false;
        
        // Yorum eklendikten sonra task detaylarını yeniden yükle
        $this->selectedTaskDetails = Task::with(['user', 'reporter', 'status', 'comments.user'])
            ->find($this->selectedTask)
            ->toArray();
    }
}

?>

<div class="bg-gradient-to-br from-base-100 to-base-200 min-h-screen">
    <x-slot:title>Sprint Board - {{ $sprint->name }}</x-slot:title>

    <div class="max-w-full mx-auto p-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div class="flex items-center gap-3">
                <x-button 
                    link="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}" 
                    icon="fas.arrow-left"
                    class="btn-ghost btn-sm hover:bg-base-200 transition-all duration-200"
                    tooltip="Back to Sprint"
                />
                <div>
                    <h1 class="text-2xl font-bold text-primary">Sprint Board</h1>
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
                <x-button 
                    link="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}/burndown" 
                    label="Burndown" 
                    icon="fas.chart-line" 
                    class="btn-outline btn-sm hover:bg-base-200 transition-all duration-200"
                    tooltip="View Burndown Chart"
                />
                <x-button 
                    link="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}/report" 
                    label="Report" 
                    icon="fas.chart-bar" 
                    class="btn-outline btn-sm hover:bg-base-200 transition-all duration-200"
                    tooltip="View Sprint Report"
                />
                <x-button 
                    link="/projects/{{ $project->id }}/tasks/create" 
                    label="Add Task" 
                    icon="fas.plus" 
                    class="btn-primary btn-sm hover:shadow-md transition-all duration-300"
                />
            </div>
        </div>
        
        <div class="bg-base-100 rounded-xl shadow-xl border border-base-300 overflow-hidden mb-6 p-4">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div class="flex items-center gap-3">
                    <div class="p-3 rounded-full bg-primary/10 text-primary">
                        <i class="fas fa-columns text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold">Sprint Progress</h2>
                        <p class="text-sm text-base-content/70">{{ $sprint->start_date ? $sprint->start_date->format('M d') : 'No start date' }} - {{ $sprint->end_date ? $sprint->end_date->format('M d') : 'No end date' }}</p>
                    </div>
                </div>
                
                <div class="flex items-center gap-6">
                    <div class="flex items-center gap-2">
                        <div class="p-2 rounded-full bg-success/10 text-success">
                            <i class="fas fa-check"></i>
                        </div>
                        <div>
                            <div class="text-sm text-base-content/70">Completed</div>
                            @php
                                $completedTasks = collect($tasksByStatus)
                                    ->flatten(1)
                                    ->filter(function($task) use ($statuses) {
                                        $status = $statuses->firstWhere('id', $task['status_id']);
                                        return $status && $status->is_completed;
                                    })
                                    ->count();
                                $totalTasks = collect($tasksByStatus)->flatten(1)->count();
                                $completionPercentage = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
                            @endphp
                            <div class="font-medium">{{ $completedTasks }}/{{ $totalTasks }} tasks ({{ $completionPercentage }}%)</div>
                        </div>
                    </div>
                    
                    <div class="w-48 h-2 bg-base-200 rounded-full overflow-hidden">
                        <div class="bg-success h-full" style="width: {{ $completionPercentage }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sprint Board -->
        <div
            x-data="{
                draggingTask: null,
                draggedOverColumn: null,
                handleDragStart(event, taskId) {
                    this.draggingTask = taskId;
                    event.dataTransfer.effectAllowed = 'move';
                    event.target.classList.add('opacity-50');
                },
                handleDragEnd(event) {
                    event.target.classList.remove('opacity-50');
                    this.draggedOverColumn = null;
                },
                handleDragOver(event, statusId) {
                    event.preventDefault();
                    event.dataTransfer.dropEffect = 'move';
                    this.draggedOverColumn = statusId;
                },
                handleDragLeave() {
                    this.draggedOverColumn = null;
                },
                handleDrop(event, statusId) {
                    event.preventDefault();
                    if (this.draggingTask) {
                        $wire.updateTaskStatus(this.draggingTask, statusId);
                        this.draggingTask = null;
                        this.draggedOverColumn = null;
                    }
                }
            }"
            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"
        >
            @foreach($statuses as $status)
                <div
                    class="bg-base-100 rounded-xl shadow-md border border-base-300 overflow-hidden transition-all duration-300"
                    :class="{ 'border-primary shadow-lg': draggedOverColumn === {{ $status->id }} }"
                    x-on:dragover="handleDragOver($event, {{ $status->id }})"
                    x-on:dragleave="handleDragLeave()"
                    x-on:drop="handleDrop($event, {{ $status->id }})"
                >
                    <div class="p-3 border-b border-base-300" style="background-color: {{ $status->color }}15;">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full" style="background-color: {{ $status->color }}"></div>
                                <h2 class="font-semibold" style="color: {{ $status->color }}">
                                    {{ $status->name }}
                                </h2>
                            </div>
                            <span class="badge badge-sm bg-base-200 text-base-content border-0">
                                {{ count($tasksByStatus[$status->id] ?? []) }}
                            </span>
                        </div>
                    </div>

                    <div class="overflow-y-auto max-h-[calc(100vh-300px)] p-2 space-y-2">
                        @foreach($tasksByStatus[$status->id] ?? [] as $task)
                            <div
                                class="bg-base-200/70 hover:bg-base-200 border border-base-300 rounded-lg shadow-sm cursor-move transition-all duration-200 hover:shadow-md"
                                draggable="true"
                                x-on:dragstart="handleDragStart($event, {{ $task['id'] }})"
                                x-on:dragend="handleDragEnd($event)"
                                wire:click="viewTask({{ $task['id'] }})"
                            >
                                <div class="p-3">
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-xs font-mono bg-primary/10 text-primary px-1.5 py-0.5 rounded">
                                                {{ $project->key }}-{{ $task['id'] }}
                                            </span>
                                            
                                            @if($task['task_type'])
                                                <div class="text-xs">
                                                    @if($task['task_type'] === 'bug')
                                                        <i class="fas fa-bug text-error"></i>
                                                    @elseif($task['task_type'] === 'feature')
                                                        <i class="fas fa-star text-primary"></i>
                                                    @elseif($task['task_type'] === 'improvement')
                                                        <i class="fas fa-arrow-up text-success"></i>
                                                    @else
                                                        <i class="fas fa-tasks text-info"></i>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>

                                        @if($task['priority'])
                                            <div class="badge badge-sm {{
                                                $task['priority'] === 'high' ? 'badge-error' :
                                                ($task['priority'] === 'medium' ? 'badge-warning' : 'badge-info')
                                            }}">
                                                @if($task['priority'] === 'high')
                                                    <i class="fas fa-arrow-up mr-1"></i>
                                                @elseif($task['priority'] === 'medium')
                                                    <i class="fas fa-equals mr-1"></i>
                                                @else
                                                    <i class="fas fa-arrow-down mr-1"></i>
                                                @endif
                                                {{ ucfirst($task['priority']) }}
                                            </div>
                                        @endif
                                    </div>

                                    <p class="font-medium text-sm mb-3">{{ $task['title'] }}</p>

                                    <div class="flex justify-between items-center">
                                        @if(isset($task['user']) && $task['user'])
                                            <div class="flex items-center gap-1.5">
                                                <div class="bg-primary/10 text-primary rounded-lg w-5 h-5 flex items-center justify-center">
                                                    <span class="text-xs font-medium">{{ substr($task['user']['name'], 0, 1) }}</span>
                                                </div>
                                                <span class="text-xs text-base-content/70">{{ $task['user']['name'] }}</span>
                                            </div>
                                        @else
                                            <span class="text-xs text-base-content/50 flex items-center gap-1">
                                                <i class="fas fa-user-slash"></i> Unassigned
                                            </span>
                                        @endif

                                        <div class="flex items-center gap-2">
                                            @if(isset($task['comments_count']) && $task['comments_count'] > 0)
                                                <span class="text-xs text-base-content/70 flex items-center gap-1">
                                                    <i class="fas fa-comment"></i> {{ $task['comments_count'] }}
                                                </span>
                                            @endif
                                            
                                            @if($task['story_points'])
                                                <span class="text-xs bg-info/10 text-info px-1.5 py-0.5 rounded-full">
                                                    {{ $task['story_points'] }} pts
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        @if(empty($tasksByStatus[$status->id]))
                            <div class="flex flex-col items-center justify-center py-8 text-base-content/40">
                                <i class="fas fa-inbox text-2xl mb-2"></i>
                                <p class="text-sm">No tasks</p>
                                <p class="text-xs text-base-content/30 mt-1">Drag tasks here</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        
        <!-- Task Details Modal -->
        @if($selectedTask)
            <div class="modal modal-open">
                <div class="modal-box max-w-3xl">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-lg font-bold">{{ $project->key }}-{{ $selectedTaskDetails['id'] }}
                                : {{ $selectedTaskDetails['title'] }}</h3>
                            <div class="flex gap-2 mt-1">
                                @if($selectedTaskDetails['task_type'])
                                    <div class="badge">{{ ucfirst($selectedTaskDetails['task_type']) }}</div>
                                @endif
                                @if($selectedTaskDetails['priority'])
                                    <div class="badge {{
                                        $selectedTaskDetails['priority'] === 'high' ? 'badge-error' :
                                        ($selectedTaskDetails['priority'] === 'medium' ? 'badge-warning' : 'badge-info')
                                    }}">
                                        {{ ucfirst($selectedTaskDetails['priority']) }}
                                    </div>
                                @endif
                            </div>
                        </div>
                        <x-button wire:click="closeTaskDetails" icon="o-x-mark" class="btn-sm btn-ghost"/>
                    </div>

                    <div class="divider"></div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="md:col-span-2">
                            <div class="mb-6">
                                <h4 class="font-bold mb-2">Description</h4>
                                <div class="prose max-w-none">
                                    {!! $selectedTaskDetails['description'] ?? '<p class="text-gray-500">No description provided.</p>' !!}
                                </div>
                            </div>

                            <div>
                                <h4 class="font-bold mb-2">Comments</h4>
                                @if(!empty($selectedTaskDetails['comments']))
                                    <div class="space-y-4">
                                        @foreach($selectedTaskDetails['comments'] as $comment)
                                            <div class="bg-base-200 p-3 rounded-lg">
                                                <div class="flex justify-between items-start">
                                                    <div class="flex items-center gap-2">
                                                        <div class="avatar placeholder">
                                                            <div
                                                                class="bg-neutral text-neutral-content rounded-full w-8">
                                                                <span
                                                                    class="text-xs">{{ substr($comment['user']['name'] ?? 'U', 0, 1) }}</span>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <p class="font-medium">{{ $comment['user']['name'] ?? 'Unknown' }}</p>
                                                            <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($comment['created_at'])->format('M d, Y H:i') }}</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mt-2 prose max-w-none">
                                                    {!! $comment['content'] !!}
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-gray-500">No comments yet.</p>
                                @endif

                                @if($showCommentForm)
                                    <div class="mt-4">
                                        <textarea wire:model="newComment" class="textarea textarea-bordered w-full" placeholder="Write a comment..."></textarea>
                                        <div class="flex gap-2 mt-2">
                                            <x-button wire:click="saveComment" icon="o-paper-airplane" class="btn-sm btn-primary">Add Comment</x-button>
                                            <x-button wire:click="hideAddComment" icon="o-x-mark" class="btn-sm btn-ghost">Cancel</x-button>
                                        </div>
                                    </div>
                                @else
                                    <div class="mt-4">
                                        <x-button wire:click="showAddComment" icon="o-chat-bubble-left" class="btn-sm">Add Comment</x-button>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div>
                            <h4 class="font-bold mb-2">Details</h4>
                            <div class="space-y-4">
                                <div>
                                    <p class="text-sm text-gray-500">Status</p>
                                    <p class="font-medium">{{ $selectedTaskDetails['status']['name'] ?? 'None' }}</p>
                                </div>

                                <div>
                                    <p class="text-sm text-gray-500">Assignee</p>
                                    <p class="font-medium">{{ $selectedTaskDetails['user']['name'] ?? 'Unassigned' }}</p>
                                </div>

                                <div>
                                    <p class="text-sm text-gray-500">Reporter</p>
                                    <p class="font-medium">{{ $selectedTaskDetails['reporter']['name'] ?? 'Unknown' }}</p>
                                </div>

                                @if(isset($selectedTaskDetails['story_points']) && $selectedTaskDetails['story_points'])
                                    <div>
                                        <p class="text-sm text-gray-500">Story Points</p>
                                        <p class="font-medium">{{ $selectedTaskDetails['story_points'] }}</p>
                                    </div>
                                @endif

                                @if(isset($selectedTaskDetails['estimated_hours']) && $selectedTaskDetails['estimated_hours'])
                                    <div>
                                        <p class="text-sm text-gray-500">Estimated Hours</p>
                                        <p class="font-medium">{{ $selectedTaskDetails['estimated_hours'] }}</p>
                                    </div>
                                @endif

                                @if(isset($selectedTaskDetails['spent_hours']) && $selectedTaskDetails['spent_hours'])
                                    <div>
                                        <p class="text-sm text-gray-500">Spent Hours</p>
                                        <p class="font-medium">{{ $selectedTaskDetails['spent_hours'] }}</p>
                                    </div>
                                @endif

                                <div>
                                    <p class="text-sm text-gray-500">Created</p>
                                    <p class="font-medium">{{ \Carbon\Carbon::parse($selectedTaskDetails['created_at'])->format('M d, Y') }}</p>
                                </div>

                                <div>
                                    <p class="text-sm text-gray-500">Updated</p>
                                    <p class="font-medium">{{ \Carbon\Carbon::parse($selectedTaskDetails['updated_at'])->format('M d, Y') }}</p>
                                </div>
                            </div>

                            <div class="mt-6">
                                <x-button link="/projects/{{ $project->id }}/tasks/{{ $selectedTaskDetails['id'] }}"
                                          label="View Full Details" icon="o-arrow-top-right-on-square" class="btn-sm btn-outline w-full"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
