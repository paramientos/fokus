<?php

new class extends Livewire\Volt\Component {
    /** @var \App\Models\Project */
    public $project;
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
        'description' => 'nullable|max:1000',
        'status_id' => 'required|exists:statuses,id',
        'sprint_id' => 'nullable|exists:sprints,id',
        'user_id' => 'nullable|exists:users,id',
        'reporter_id' => 'nullable|exists:users,id',
        'task_type' => 'required|string',
        'priority' => 'required|int',
        'story_points' => 'nullable|integer|min:1|max:100',
        'due_date' => 'nullable|date|after_or_equal:today',
    ];

    public function mount($project): void
    {
        $this->project = \App\Models\Project::findOrFail($project);

        // Set default status to the first status (usually "To Do")
        $firstStatus = $this->project->statuses()->orderBy('order')->first();
        if ($firstStatus) {
            $this->status_id = $firstStatus->id;
        }

        // Set default reporter to current user
        $this->reporter_id = auth()->id() ?? 1; // Fallback to ID 1 for demo purposes
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    public function save()
    {
        $this->validate();

        $task = new \App\Models\Task([
            'title' => $this->title,
            'description' => $this->description,
            'project_id' => $this->project->id,
            'status_id' => $this->status_id,
            'sprint_id' => $this->sprint_id,
            'user_id' => $this->user_id,
            'reporter_id' => $this->reporter_id,
            'task_type' => $this->task_type,
            'priority' => $this->priority,
            'story_points' => $this->story_points,
            'due_date' => $this->due_date,
        ]);

        $task->save();

        session()->flash('message', 'Task created successfully!');
        return redirect("/projects/{$this->project->id}/tasks/{$task->id}");
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

<div>
    <x-slot:title>Create Task - {{ $project->name }}</x-slot:title>

    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-primary">Create Task</h1>
            <x-button link="/projects/{{ $project->id }}/tasks" icon="fas.arrow-left"
                      class="btn-ghost">Back to Tasks</x-button>
        </div>

        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <form wire:submit="save">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-control md:col-span-2">
                            <x-input label="Title" wire:model="title" placeholder="Enter task title" required/>
                            @error('title') <span class="text-error text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <x-markdown-editor id="task-description" label="Description" wire:model="description" />
                        </div>

                        <div class="form-control">
                            <x-choices-offline
                                single
                                searchable
                                label="Status"
                                wire:model="status_id"
                                :options="$statuses"
                                placeholder="Select status"
                                required
                            />
                            @error('status_id') <span class="text-error text-sm">{{ $message }}</span> @enderror
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
                            />
                            @error('sprint_id') <span class="text-error text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-control">
                            <x-choices-offline
                                single
                                searchable
                                label="Assignee"
                                wire:model="user_id"
                                :options="$users"
                                placeholder="Select assignee (optional)"
                                empty-message="Unassigned"
                            />
                            @error('user_id') <span class="text-error text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-control">
                            <x-choices-offline
                                single
                                searchable
                                label="Reporter"
                                wire:model="reporter_id"
                                :options="$users"
                                placeholder="Select reporter"
                                required
                            />
                            @error('reporter_id') <span class="text-error text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-control">
                            <x-choices-offline
                                single
                                searchable
                                label="Task Type"
                                wire:model="task_type"
                                :options="$taskTypes"
                                placeholder="Select task type"
                                required
                            />
                            @error('task_type') <span class="text-error text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-control">
                            <x-choices-offline
                                single
                                searchable
                                label="Priority"
                                wire:model="priority"
                                :options="$priorities"
                                placeholder="Select priority"
                                required
                            />
                            @error('priority') <span class="text-error text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-control">
                            <x-input
                                type="number"
                                label="Story Points"
                                wire:model="story_points"
                                placeholder="Enter story points (optional)"
                                min="1"
                                max="100"
                            />
                            @error('story_points') <span class="text-error text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-control">
                            <x-input
                                type="date"
                                label="Due Date"
                                wire:model="due_date"
                                placeholder="Select due date (optional)"
                            />
                            @error('due_date') <span class="text-error text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end">
                        <x-button type="submit" icon="fas.check" class="btn-primary">Create Task</x-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
