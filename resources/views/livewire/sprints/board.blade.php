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
                <div class="modal-box max-w-4xl bg-base-100 shadow-xl border border-base-300 p-0 overflow-hidden">
                    <!-- Task Header -->
                    <div class="bg-primary/5 p-4 border-b border-base-300">
                        <div class="flex justify-between items-start">
                            <div class="flex items-center gap-3">
                                <div class="bg-primary/10 text-primary rounded-lg p-2">
                                    @if($selectedTaskDetails['task_type'] === 'bug')
                                        <i class="fas fa-bug text-error text-lg"></i>
                                    @elseif($selectedTaskDetails['task_type'] === 'feature')
                                        <i class="fas fa-star text-primary text-lg"></i>
                                    @elseif($selectedTaskDetails['task_type'] === 'improvement')
                                        <i class="fas fa-arrow-up text-success text-lg"></i>
                                    @else
                                        <i class="fas fa-tasks text-primary text-lg"></i>
                                    @endif
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-mono bg-primary/10 text-primary px-2 py-0.5 rounded">
                                            {{ $project->key }}-{{ $selectedTaskDetails['id'] }}
                                        </span>
                                        @if($selectedTaskDetails['priority'])
                                            <div class="badge {{
                                                $selectedTaskDetails['priority'] === 'high' ? 'badge-error' :
                                                ($selectedTaskDetails['priority'] === 'medium' ? 'badge-warning' : 'badge-info')
                                            }}">
                                                @if($selectedTaskDetails['priority'] === 'high')
                                                    <i class="fas fa-arrow-up mr-1"></i>
                                                @elseif($selectedTaskDetails['priority'] === 'medium')
                                                    <i class="fas fa-equals mr-1"></i>
                                                @else
                                                    <i class="fas fa-arrow-down mr-1"></i>
                                                @endif
                                                {{ ucfirst($selectedTaskDetails['priority']) }}
                                            </div>
                                        @endif
                                    </div>
                                    <h3 class="text-lg font-bold mt-1">{{ $selectedTaskDetails['title'] }}</h3>
                                </div>
                            </div>
                            <x-button 
                                wire:click="closeTaskDetails" 
                                icon="fas.xmark" 
                                class="btn-sm btn-ghost hover:bg-base-200 transition-all duration-200"
                                tooltip="Close"
                            />
                        </div>
                    </div>

                    <div class="p-5">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Left Column - Description and Comments -->
                            <div class="md:col-span-2 space-y-6">
                                <!-- Description -->
                                <div class="bg-base-200/30 p-4 rounded-lg border border-base-300">
                                    <h4 class="font-semibold flex items-center gap-2 mb-3">
                                        <i class="fas fa-align-left text-primary"></i>
                                        Description
                                    </h4>
                                    <div class="prose max-w-none">
                                        {!! $selectedTaskDetails['description'] ?? '<p class="text-base-content/50 italic">No description provided.</p>' !!}
                                    </div>
                                </div>

                                <!-- Comments -->
                                <div>
                                    <h4 class="font-semibold flex items-center gap-2 mb-3">
                                        <i class="fas fa-comments text-primary"></i>
                                        Comments
                                    </h4>
                                    
                                    @if(!empty($selectedTaskDetails['comments']))
                                        <div class="space-y-4">
                                            @foreach($selectedTaskDetails['comments'] as $comment)
                                                <div class="bg-base-200/50 p-4 rounded-lg border border-base-300 hover:bg-base-200/70 transition-colors duration-200">
                                                    <div class="flex justify-between items-start">
                                                        <div class="flex items-center gap-3">
                                                            <div class="bg-primary/10 text-primary rounded-lg w-8 h-8 flex items-center justify-center">
                                                                <span class="font-medium">{{ substr($comment['user']['name'] ?? 'U', 0, 1) }}</span>
                                                            </div>
                                                            <div>
                                                                <p class="font-medium">{{ $comment['user']['name'] ?? 'Unknown' }}</p>
                                                                <p class="text-xs text-base-content/60">{{ \Carbon\Carbon::parse($comment['created_at'])->format('M d, Y H:i') }}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="mt-3 prose max-w-none text-base-content/90">
                                                        {!! $comment['content'] !!}
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-center py-6 text-base-content/50 bg-base-200/30 rounded-lg border border-base-300">
                                            <i class="fas fa-comment-slash text-2xl mb-2"></i>
                                            <p>No comments yet</p>
                                        </div>
                                    @endif

                                    @if($showCommentForm)
                                        <div class="mt-4 bg-base-200/30 p-4 rounded-lg border border-base-300">
                                            <h5 class="font-medium mb-2">Add Comment</h5>
                                            <textarea 
                                                wire:model="newComment" 
                                                class="textarea textarea-bordered w-full focus:border-primary/50 transition-all duration-300" 
                                                placeholder="Write your comment here..."
                                                rows="3"
                                            ></textarea>
                                            <div class="flex justify-end gap-2 mt-3">
                                                <x-button 
                                                    wire:click="hideAddComment" 
                                                    icon="fas.xmark" 
                                                    class="btn-sm btn-ghost hover:bg-base-200 transition-all duration-200"
                                                >
                                                    Cancel
                                                </x-button>
                                                <x-button 
                                                    wire:click="saveComment" 
                                                    icon="fas.paper-plane" 
                                                    class="btn-sm btn-primary hover:shadow-md transition-all duration-300"
                                                >
                                                    Add Comment
                                                </x-button>
                                            </div>
                                        </div>
                                    @else
                                        <div class="mt-4">
                                            <x-button 
                                                wire:click="showAddComment" 
                                                icon="fas.comment" 
                                                class="btn-sm btn-outline hover:shadow-md transition-all duration-300"
                                            >
                                                Add Comment
                                            </x-button>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Right Column - Task Details -->
                            <div>
                                <div class="bg-base-200/30 p-4 rounded-lg border border-base-300">
                                    <h4 class="font-semibold flex items-center gap-2 mb-4">
                                        <i class="fas fa-info-circle text-primary"></i>
                                        Task Details
                                    </h4>
                                    
                                    <div class="space-y-4">
                                        <!-- Status -->
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-base-content/70">Status</span>
                                            <span class="badge" style="background-color: {{ $selectedTaskDetails['status']['color'] ?? '#6c757d' }}; color: white;">
                                                {{ $selectedTaskDetails['status']['name'] ?? 'None' }}
                                            </span>
                                        </div>
                                        
                                        <!-- Assignee -->
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-base-content/70">Assignee</span>
                                            @if(isset($selectedTaskDetails['user']) && $selectedTaskDetails['user'])
                                                <div class="flex items-center gap-2">
                                                    <div class="bg-primary/10 text-primary rounded-lg w-6 h-6 flex items-center justify-center">
                                                        <span class="text-xs font-medium">{{ substr($selectedTaskDetails['user']['name'], 0, 1) }}</span>
                                                    </div>
                                                    <span class="font-medium">{{ $selectedTaskDetails['user']['name'] }}</span>
                                                </div>
                                            @else
                                                <span class="text-base-content/50 flex items-center gap-1">
                                                    <i class="fas fa-user-slash"></i> Unassigned
                                                </span>
                                            @endif
                                        </div>
                                        
                                        <!-- Reporter -->
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-base-content/70">Reporter</span>
                                            @if(isset($selectedTaskDetails['reporter']) && $selectedTaskDetails['reporter'])
                                                <span class="font-medium">{{ $selectedTaskDetails['reporter']['name'] }}</span>
                                            @else
                                                <span class="text-base-content/50">Unknown</span>
                                            @endif
                                        </div>
                                        
                                        <!-- Story Points -->
                                        @if(isset($selectedTaskDetails['story_points']) && $selectedTaskDetails['story_points'])
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm text-base-content/70">Story Points</span>
                                                <span class="text-xs bg-info/10 text-info px-2 py-0.5 rounded-full font-medium">
                                                    {{ $selectedTaskDetails['story_points'] }} pts
                                                </span>
                                            </div>
                                        @endif
                                        
                                        <!-- Time Tracking -->
                                        @if(isset($selectedTaskDetails['estimated_hours']) || isset($selectedTaskDetails['spent_hours']))
                                            <div class="border-t border-base-300 pt-3 mt-3">
                                                <h5 class="font-medium mb-2 text-sm">Time Tracking</h5>
                                                
                                                @if(isset($selectedTaskDetails['estimated_hours']) && $selectedTaskDetails['estimated_hours'])
                                                    <div class="flex items-center justify-between mb-1">
                                                        <span class="text-xs text-base-content/70">Estimated</span>
                                                        <span class="font-medium flex items-center gap-1">
                                                            <i class="fas fa-clock text-xs"></i>
                                                            {{ $selectedTaskDetails['estimated_hours'] }}h
                                                        </span>
                                                    </div>
                                                @endif
                                                
                                                @if(isset($selectedTaskDetails['spent_hours']) && $selectedTaskDetails['spent_hours'])
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-xs text-base-content/70">Spent</span>
                                                        <span class="font-medium flex items-center gap-1">
                                                            <i class="fas fa-stopwatch text-xs"></i>
                                                            {{ $selectedTaskDetails['spent_hours'] }}h
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                        
                                        <!-- Dates -->
                                        <div class="border-t border-base-300 pt-3 mt-3">
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="text-xs text-base-content/70">Created</span>
                                                <span class="text-xs">{{ \Carbon\Carbon::parse($selectedTaskDetails['created_at'])->format('M d, Y') }}</span>
                                            </div>
                                            
                                            <div class="flex items-center justify-between">
                                                <span class="text-xs text-base-content/70">Updated</span>
                                                <span class="text-xs">{{ \Carbon\Carbon::parse($selectedTaskDetails['updated_at'])->format('M d, Y') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-6 pt-4 border-t border-base-300">
                                        <x-button 
                                            link="/projects/{{ $project->id }}/tasks/{{ $selectedTaskDetails['id'] }}"
                                            label="View Full Details" 
                                            icon="fas.external-link-alt" 
                                            class="btn-sm btn-outline w-full hover:shadow-md transition-all duration-300"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
