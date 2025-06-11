<?php

use App\Models\StatusTransition;
use App\Models\Task;
use Carbon\Carbon;

new class extends Livewire\Volt\Component {
    use \Mary\Traits\Toast;

    public $project;
    public $statuses = [];
    public $tasks = [];
    public $selectedTask = null;
    public $selectedTaskDetails = null;
    public $selectedTaskActivities = [];
    public $modalStatusId = null;
    public $modalUserId = null;
    public $users = [];

    public function mount($project)
    {
        $this->project = \App\Models\Project::findOrFail($project);
        $this->users = $this->project->teamMembers()->select('users.id','users.name')->get();
        $this->loadBoard();
    }

    public function loadBoard()
    {
        $this->statuses = $this->project->statuses()->orderBy('order')->get();

        $this->tasks = [];
        foreach ($this->statuses as $status) {
            $this->tasks[$status->id] = $status->tasks()->with(['user', 'reporter'])->get()->toArray();
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
            $this->loadBoard();
            return;
        }

        $task->update(['status_id' => $statusId]);
        $this->success('Task durumu güncellendi!');
        $this->loadBoard();
    }

    public function viewTask($taskId)
    {
        $this->selectedTask = $taskId;
        $this->selectedTaskDetails = Task::with(['user', 'reporter', 'status', 'comments.user'])
            ->find($taskId)
            ->toArray();

        $this->modalStatusId = $this->selectedTaskDetails['status_id'] ?? null;
        $this->modalUserId = $this->selectedTaskDetails['user_id'] ?? null;

        // Load latest activities (history)
        $this->selectedTaskActivities = \App\Models\Activity::with('user')
            ->where('task_id', $taskId)
            ->latest()
            ->take(20)
            ->get()
            ->toArray();
    }

    public function closeTaskDetails()
    {
        $this->selectedTask = null;
        $this->selectedTaskDetails = null;
        $this->selectedTaskActivities = [];
        $this->modalStatusId = null;
        $this->modalUserId = null;
    }

    public function saveModalChanges()
    {
        if(!$this->selectedTask) return;
        $task = Task::find($this->selectedTask);
        if(!$task) { $this->error('Task bulunamadı!'); return; }

        // Status change
        if($this->modalStatusId && $this->modalStatusId != $task->status_id){
            $allowed = StatusTransition::where('project_id',$this->project->id)
                ->where('from_status_id',$task->status_id)
                ->where('to_status_id',$this->modalStatusId)
                ->exists();
            if(!$allowed){
                $this->error('Bu durum geçişine izin verilmiyor!');
            } else {
                $task->status_id = $this->modalStatusId;
            }
        }

        // Assignee change
        if($this->modalUserId != $task->user_id){
            $task->user_id = $this->modalUserId ?: null;
        }

        $task->save();
        $this->success('Task güncellendi');
        $this->loadBoard();
        $this->viewTask($task->id); // refresh modal data
    }
}

?>

<div>
    <x-slot:title>{{ $project->name }} - Board</x-slot:title>

    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <div class="flex items-center gap-2">
                <x-button link="/projects/{{ $project->id }}" icon="o-arrow-left" class="btn-ghost btn-sm"/>
                <h1 class="text-2xl font-bold text-primary">{{ $project->name }} Board</h1>
            </div>

            <div class="flex gap-2">
                <x-button no-wire-navigate link="/projects/{{ $project->id }}/tasks/create" label="Create Task" icon="o-plus"
                          class="btn-primary"/>
            </div>
        </div>

        <!-- Kanban Board -->
        <div class="grid grid-cols-1 overflow-x-auto">
            <div class="flex gap-4 min-w-max pb-4">
                @foreach($statuses as $status)
                    <div class="w-80 flex flex-col">
                        <div class="bg-base-200 rounded-t-lg p-3 flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full" style="background-color: {{ $status->color }}"></div>
                                <h3 class="font-bold">{{ $status->name }}</h3>
                            </div>
                            <div class="badge badge-neutral">{{ count($tasks[$status->id] ?? []) }}</div>
                        </div>

                        <div
                            class="bg-base-100 rounded-b-lg p-2 flex-1 min-h-[70vh] overflow-y-auto"
                            x-data="{
                                onDrop(event) {
                                    const taskId = event.dataTransfer.getData('taskId');
                                    @this.updateTaskStatus(taskId, {{ $status->id }});
                                }
                            }"
                            x-on:dragover.prevent
                            x-on:drop="onDrop"
                        >
                            @if(empty($tasks[$status->id]))
                                <div class="flex flex-col items-center justify-center h-32 text-gray-400">
                                    <x-icon name="o-inbox" class="w-8 h-8"/>
                                    <p class="text-sm mt-2">No tasks</p>
                                </div>
                            @else
                                <div class="space-y-2">
                                    @foreach($tasks[$status->id] as $task)
                                        <div
                                            class="card bg-base-200 shadow-sm hover:shadow-md transition-shadow cursor-pointer"
                                            draggable="true"
                                            x-data="{
                                                onDragStart(event) {
                                                    event.dataTransfer.setData('taskId', '{{ $task['id'] }}');
                                                }
                                            }"
                                            x-on:dragstart="onDragStart"
                                            wire:click="viewTask({{ $task['id'] }})"
                                        >
                                            <div class="card-body p-3">
                                                <div class="flex justify-between items-start">
                                                    <h4 class="font-medium text-sm">{{ $project->key }}
                                                        -{{ $task['id'] }}</h4>
                                                    @if($task['priority'])
                                                        <div class="badge badge-sm {{
                                                            $task['priority'] === 'high' ? 'badge-error' :
                                                            ($task['priority'] === 'medium' ? 'badge-warning' : 'badge-info')
                                                        }}">
                                                            {{ ucfirst($task['priority']) }}
                                                        </div>
                                                    @endif
                                                </div>
                                                <p class="text-sm">{{ $task['title'] }}</p>

                                                <div class="flex justify-between items-center mt-2">
                                                    <div class="flex items-center gap-1">
                                                        @if($task['story_points'])
                                                            <div
                                                                class="badge badge-sm badge-outline">{{ $task['story_points'] }}
                                                                p
                                                            </div>
                                                        @endif

                                                        @if($task['task_type'])
                                                            <div class="badge badge-sm">
                                                                {{ ucfirst($task['task_type']) }}
                                                            </div>
                                                        @endif
                                                    </div>

                                                    @if(!empty($task['user']))
                                                        <div class="avatar placeholder">
                                                            <div
                                                                class="bg-neutral text-neutral-content rounded-full w-6">
                                                                <span
                                                                    class="text-xs">{{ substr($task['user']['name'] ?? 'U', 0, 1) }}</span>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
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

                                @if($selectedTaskDetails['story_points'])
                                    <div class="badge badge-outline">{{ $selectedTaskDetails['story_points'] }}points
                                    </div>
                                @endif
                            </div>
                        </div>
                        <button wire:click="closeTaskDetails" class="btn btn-sm btn-circle">✕</button>
                    </div>

                    <div class="divider"></div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="md:col-span-2">
                            <div class="mb-6">
                                <h4 class="font-bold mb-2">Description</h4>
                                <div class="prose max-w-none">
                                    @if($selectedTaskDetails['description'])
                                        <p>{{ $selectedTaskDetails['description'] }}</p>
                                    @else
                                        <p class="text-gray-500 italic">No description provided</p>
                                    @endif
                                </div>
                            </div>

                            <div>
                                <h4 class="font-bold mb-2">Comments</h4>
                                @if(empty($selectedTaskDetails['comments']))
                                    <p class="text-gray-500 italic">No comments yet</p>
                                @else
                                    <div class="space-y-4">
                                        @foreach($selectedTaskDetails['comments'] as $comment)
                                            <div class="bg-base-200 p-3 rounded-lg">
                                                <div class="flex justify-between items-center mb-2">
                                                    <div class="flex items-center gap-2">
                                                        <div class="avatar placeholder">
                                                            <div
                                                                class="bg-neutral text-neutral-content rounded-full w-8">
                                                                <span>{{ substr($comment['user']['name'] ?? 'U', 0, 1) }}</span>
                                                            </div>
                                                        </div>
                                                        <span
                                                            class="font-medium">{{ $comment['user']['name'] ?? 'Unknown User' }}</span>
                                                    </div>
                                                    <span
                                                        class="text-sm text-gray-500">{{ Carbon::parse($comment['created_at'])->diffForHumans() }}</span>
                                                </div>
                                                <p>{{ $comment['content'] }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="mt-4">
                                    <x-textarea placeholder="Add a comment..." rows="2"/>
                                    <div class="mt-2 flex justify-end">
                                        <x-button label="Add Comment" class="btn-primary btn-sm"/>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6">
                                <h4 class="font-bold mb-2">History</h4>
                                @if(empty($selectedTaskActivities))
                                    <p class="text-gray-500 italic">No history</p>
                                @else
                                    <div class="space-y-2 max-h-60 overflow-y-auto pr-2">
                                        @foreach($selectedTaskActivities as $activity)
                                            <div class="flex items-start gap-2">
                                                <x-icon name="{{ $activity['icon'] ?? 'fas.circle-info' }}" class="w-4 h-4 text-{{ $activity['color'] ?? 'neutral' }}"/>
                                                <p class="text-sm">
                                                    <span class="font-medium">{{ $activity['user']['name'] ?? 'Unknown' }}</span>
                                                    {{ $activity['description'] ?? $activity['action'] }}
                                                    <span class="text-gray-500">- {{ Carbon::parse($activity['created_at'])->diffForHumans() }}</span>
                                                </p>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div>
                            <h4 class="font-bold mb-2">Details</h4>
                            <div class="space-y-4">
                                <div>
                                    <p class="text-sm text-gray-500">Status</p>
                                    <x-select wire:model.live="modalStatusId" :options="$statuses" class="w-full mt-1"/>
                                </div>

                                <div>
                                    <p class="text-sm text-gray-500">Assignee</p>
                                    <x-select wire:model.live="modalUserId" :options="$users->pluck('name','id')->toArray()" empty-message="Unassigned" class="w-full mt-1"/>
                                </div>

                                <div>
                                    <p class="text-sm text-gray-500">Reporter</p>
                                    @if(!empty($selectedTaskDetails['reporter']))
                                        <div class="flex items-center gap-2 mt-1">
                                            <div class="avatar placeholder">
                                                <div class="bg-neutral text-neutral-content rounded-full w-6">
                                                    <span>{{ substr($selectedTaskDetails['reporter']['name'] ?? 'U', 0, 1) }}</span>
                                                </div>
                                            </div>
                                            <span>{{ $selectedTaskDetails['reporter']['name'] }}</span>
                                        </div>
                                    @else
                                        <p class="italic text-gray-500 mt-1">Unknown</p>
                                    @endif
                                </div>

                                @if($selectedTaskDetails['sprint_id'])
                                    <div>
                                        <p class="text-sm text-gray-500">Sprint</p>
                                        <div class="badge badge-outline mt-1">
                                            {{ \App\Models\Sprint::find($selectedTaskDetails['sprint_id'])->name ?? 'Unknown Sprint' }}
                                        </div>
                                    </div>
                                @endif

                                @if($selectedTaskDetails['due_date'])
                                    <div>
                                        <p class="text-sm text-gray-500">Due Date</p>
                                        <p class="mt-1">{{ Carbon::parse($selectedTaskDetails['due_date'])->format('M d, Y') }}</p>
                                    </div>
                                @endif

                                <div>
                                    <p class="text-sm text-gray-500">Created</p>
                                    <p class="mt-1">{{ Carbon::parse($selectedTaskDetails['created_at'])->format('M d, Y H:i') }}</p>
                                </div>

                                <div>
                                    <p class="text-sm text-gray-500">Updated</p>
                                    <p class="mt-1">{{ Carbon::parse($selectedTaskDetails['updated_at'])->format('M d, Y H:i') }}</p>
                                </div>
                            </div>

                            <div class="divider"></div>

                            <div class="flex flex-col gap-2">
                                <x-button
                                    link="/projects/{{ $project->id }}/tasks/{{ $selectedTaskDetails['id'] }}/edit"
                                    label="Edit Task" icon="o-pencil" class="btn-outline w-full"/>
                                <x-button wire:click="saveModalChanges" label="Save Changes" icon="o-check" class="btn-primary w-full"/>
                                <x-button no-wire-navigate
                                    link="{{ route('tasks.show', ['project' => $project, 'task' => $selectedTaskDetails['id']]) }}"
                                    label="Open Task Page" icon="o-arrow-top-right-on-square" class="btn-outline w-full"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
