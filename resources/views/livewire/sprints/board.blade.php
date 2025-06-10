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
                                    wire:click="viewTask({{ $task['id'] }})"
                                >
                                    <div class="card-body p-3">
                                        <div class="flex justify-between items-start">
                                            <h3 class="font-medium text-sm">
                                                {{ $project->key }}-{{ $task['id'] }}
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
                                <div class="flex flex-col items-center justify-center py-6 text-gray-400">
                                    <x-icon name="o-inbox" class="w-8 h-8"/>
                                    <p class="text-sm mt-2">No tasks</p>
                                </div>
                            @endif
                        </div>
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
