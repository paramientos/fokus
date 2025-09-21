<?php

new class extends Livewire\Volt\Component {
    /** @var \App\Models\Project */
    public $project;
    /** @var \App\Models\Task */
    public $task;
    public $title = '';
    public $description = '';
    public $status_id = null;
    public $sprint_id = null;
    public $user_id = null;
    public $reporter_id = null;
    public $task_type = 'task';
    public $priority = 'medium';
    public $story_points = null;
    public $due_date = null;

    protected $rules = [
        'title' => 'required|min:3|max:255',
        'description' => 'nullable|max:10000',
        'status_id' => 'required|exists:statuses,id',
        'sprint_id' => 'nullable|exists:sprints,id',
        'user_id' => 'nullable|exists:users,id',
        'reporter_id' => 'nullable|exists:users,id',
        'task_type' => 'required',
        'priority' => 'required',
        'story_points' => 'nullable|integer|min:1|max:100',
        'due_date' => 'nullable|date',
    ];

    public function mount($project, $task): void
    {
        $this->project = \App\Models\Project::findOrFail($project);
        $this->task = \App\Models\Task::findOrFail($task);

        // Mevcut görev verilerini yükle
        $this->title = $this->task->title;
        $this->description = $this->task->description;
        $this->status_id = $this->task->status_id;
        $this->sprint_id = $this->task->sprint_id;
        $this->user_id = $this->task->user_id;
        $this->reporter_id = $this->task->reporter_id;
        $this->task_type = $this->task->task_type;
        $this->priority = $this->task->priority;
        $this->story_points = $this->task->story_points;
        $this->due_date = $this->task->due_date ? $this->task->due_date->format('Y-m-d') : null;
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    public function save()
    {
        $this->validate();

        $this->task->update([
            'title' => $this->title,
            'description' => $this->description,
            'status_id' => $this->status_id,
            'sprint_id' => $this->sprint_id,
            'user_id' => $this->user_id,
            'reporter_id' => $this->reporter_id,
            'task_type' => $this->task_type,
            'priority' => $this->priority,
            'story_points' => $this->story_points,
            'due_date' => $this->due_date,
        ]);

        session()->flash('message', 'Task updated successfully!');
        return redirect("/projects/{$this->project->id}/tasks/{$this->task->id}");
    }

    public function with(): array
    {
        $statuses = $this->project->statuses()->orderBy('order')->get();
        $sprints = $this->project->sprints()->orderBy('created_at', 'desc')->get();
        $users = \App\Models\User::orderBy('name')->get();
        $taskTypes = \App\Enums\TaskType::listForMaryUI();
        $priorities = \App\Enums\Priority::listForMaryUI();

        return [
            'statuses' => $statuses,
            'sprints' => $sprints,
            'users' => $users,
            'taskTypes' => $taskTypes,
            'priorities' => $priorities,
        ];
    }
}
?>

<div class="bg-gradient-to-br from-base-100 to-base-200 min-h-screen">
    <x-slot:title>Edit Task - {{ $project->name }}</x-slot:title>

    <div class="max-w-7xl mx-auto p-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div class="flex items-center gap-3">
                <x-button 
                    link="/projects/{{ $project->id }}/tasks/{{ $task->id }}" 
                    icon="fas.arrow-left" 
                    class="btn-ghost btn-sm hover:bg-base-200 transition-all duration-200"
                    tooltip="Back to Task"
                />
                <div>
                    <h1 class="text-2xl font-bold text-primary">Edit Task</h1>
                    <p class="text-sm text-base-content/70">{{ $project->key }}-{{ $task->id }}: {{ $task->title }}</p>
                </div>
            </div>
        </div>

        <div class="bg-base-100 rounded-xl shadow-xl border border-base-300 overflow-hidden">
            <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center gap-3">
                <span class="p-2 rounded-full bg-primary/10 text-primary">
                    <i class="fas fa-pen-to-square text-lg"></i>
                </span>
                <div>
                    <h2 class="text-lg font-semibold">Task Details</h2>
                    <p class="text-sm text-base-content/70">Update the information below to modify this task</p>
                </div>
            </div>
            
            <div class="p-6">
                <form wire:submit="save" class="space-y-8">
                    <!-- Basic Information Section -->
                    <div class="space-y-6">
                        <h3 class="text-base font-semibold text-primary border-b border-base-300 pb-2">Basic Information</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-control md:col-span-2">
                                <x-input 
                                    label="Title" 
                                    wire:model="title" 
                                    placeholder="Enter task title" 
                                    icon="fas.heading"
                                    class="focus:border-primary/50 transition-all duration-300"
                                    required
                                />
                                @error('title') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium mb-1">Description</label>
                                <x-markdown-editor 
                                    id="task-description" 
                                    wire:model="description"
                                    value="{{ $description }}"
                                />
                            </div>
                        </div>
                    </div>
                    
                    <!-- Classification Section -->
                    <div class="space-y-6">
                        <h3 class="text-base font-semibold text-primary border-b border-base-300 pb-2">Classification</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-control">
                                <x-choices-offline
                                    single
                                    searchable
                                    label="Status"
                                    wire:model="status_id"
                                    :options="$statuses"
                                    placeholder="Select status"
                                    icon="fas.check-circle"
                                    required
                                />
                                @error('status_id') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-control">
                                <x-choices-offline
                                    single
                                    searchable
                                    label="Task Type"
                                    wire:model="task_type"
                                    :options="$taskTypes"
                                    placeholder="Select task type"
                                    icon="fas.list-check"
                                    required
                                />
                                @error('task_type') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-control">
                                <x-choices-offline
                                    single
                                    searchable
                                    label="Priority"
                                    wire:model="priority"
                                    :options="$priorities"
                                    placeholder="Select priority"
                                    icon="fas.arrow-up-wide-short"
                                    required
                                />
                                @error('priority') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                            </div>
                            
                            <div class="form-control">
                                <x-choices-offline
                                    single
                                    searchable
                                    label="Sprint"
                                    wire:model="sprint_id"
                                    :options="$sprints"
                                    placeholder="Select sprint (optional)"
                                    empty-message="No Sprint"
                                    icon="fas.flag"
                                />
                                @error('sprint_id') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                    
                    <!-- People Section -->
                    <div class="space-y-6">
                        <h3 class="text-base font-semibold text-primary border-b border-base-300 pb-2">People</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-control">
                                <x-choices-offline
                                    single
                                    searchable
                                    label="Assignee"
                                    wire:model="user_id"
                                    :options="$users"
                                    placeholder="Select assignee (optional)"
                                    empty-message="Unassigned"
                                    icon="fas.user"
                                />
                                @error('user_id') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-control">
                                <x-choices-offline
                                    single
                                    searchable
                                    label="Reporter"
                                    wire:model="reporter_id"
                                    :options="$users"
                                    placeholder="Select reporter"
                                    icon="fas.user-edit"
                                    required
                                />
                                @error('reporter_id') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                    
                    <!-- Planning Section -->
                    <div class="space-y-6">
                        <h3 class="text-base font-semibold text-primary border-b border-base-300 pb-2">Planning</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-control">
                                <x-input
                                    type="number"
                                    label="Story Points"
                                    wire:model="story_points"
                                    placeholder="Enter story points (optional)"
                                    min="1"
                                    max="100"
                                    icon="fas.chart-simple"
                                    class="focus:border-primary/50 transition-all duration-300"
                                />
                                @error('story_points') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-control">
                                <x-input
                                    type="date"
                                    label="Due Date"
                                    wire:model="due_date"
                                    placeholder="Select due date (optional)"
                                    icon="fas.calendar-days"
                                    class="focus:border-primary/50 transition-all duration-300"
                                />
                                @error('due_date') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="pt-6 border-t border-base-300 flex justify-end gap-3">
                        <x-button 
                            link="/projects/{{ $project->id }}/tasks/{{ $task->id }}" 
                            class="btn-ghost hover:bg-base-200 transition-all duration-200"
                        >
                            Cancel
                        </x-button>
                        <x-button 
                            type="submit" 
                            icon="fas.check" 
                            class="btn-primary hover:shadow-md transition-all duration-300"
                        >
                            Update Task
                        </x-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
