<?php

use App\Models\Project;
use App\Models\Status;
use App\Models\StatusTransition;

new class extends Livewire\Volt\Component {
    public $name = '';
    public $key = '';
    public $description = '';
    public $avatar = '';

    protected array $rules = [
        'name' => 'required|min:3|max:255',
        'key' => 'required|min:2|max:10|unique:projects,key|alpha_num',
        'description' => 'nullable|max:1000',
        'avatar' => 'nullable|url|max:255',
    ];

    public function updated($propertyName): void
    {
        $this->validateOnly($propertyName);
    }

    public function generateKey(): void
    {
        $this->key = generate_project_key($this->name);
    }

    /**
     * Create status transitions for the project
     *
     * @param string $projectId
     * @param array $statuses
     * @return void
     */
    private function createStatusTransitions(string $projectId, array $statuses): void
    {
        $transitions = [
            ['from' => 'to-do', 'to' => 'in-progress'],
            ['from' => 'in-progress', 'to' => 'to-do'],
            ['from' => 'in-progress', 'to' => 'ready-for-test'],
            ['from' => 'ready-for-test', 'to' => 'in-progress'],
            ['from' => 'ready-for-test', 'to' => 'ready-for-uat'],
            ['from' => 'ready-for-uat', 'to' => 'ready-for-test'],
            ['from' => 'ready-for-uat', 'to' => 'uat'],
            ['from' => 'ready-for-uat', 'to' => 'in-progress'],
            ['from' => 'uat', 'to' => 'ready-for-uat'],
            ['from' => 'uat', 'to' => 'ready-for-test'],
            ['from' => 'uat', 'to' => 'done'],
            ['from' => 'uat', 'to' => 'in-progress'],
            ['from' => 'done', 'to' => 'uat'],
        ];

        foreach ($transitions as $transition) {
            if (isset($statuses[$transition['from']]) && isset($statuses[$transition['to']])) {
                StatusTransition::create([
                    'project_id' => $projectId,
                    'from_status_id' => $statuses[$transition['from']]->id,
                    'to_status_id' => $statuses[$transition['to']]->id,
                ]);
            }
        }
    }

    public function save()
    {
        $this->validate();

        $project = Project::create([
            'name' => $this->name,
            'key' => strtoupper($this->key),
            'description' => $this->description,
            'avatar' => $this->avatar,
            'user_id' => auth()->id() ?? 1, // Fallback to ID 1 for demo purposes
            'is_active' => true,
        ]);

        $statuses = [
            ['name' => 'To Do', 'slug' => 'to-do', 'color' => '#3B82F6', 'order' => 0, 'is_completed' => false],
            ['name' => 'In Progress', 'slug' => 'in-progress', 'color' => '#3B82F6', 'order' => 1, 'is_completed' => false],
            ['name' => 'Ready For Test', 'slug' => 'ready-for-test', 'color' => '#174896', 'order' => 2, 'is_completed' => false],
            ['name' => 'Ready For UAT', 'slug' => 'ready-for-uat', 'color' => '#3B82F6', 'order' => 3, 'is_completed' => false],
            ['name' => 'UAT', 'slug' => 'uat', 'color' => '#3B82F6', 'order' => 4, 'is_completed' => false],
            ['name' => 'Done', 'slug' => 'done', 'color' => '#3B82F6', 'order' => 5, 'is_completed' => false],
        ];

        // Create status records
        $createdStatuses = [];
        foreach ($statuses as $status) {
            $createdStatus = Status::create([
                'name' => $status['name'],
                'slug' => $status['slug'],
                'color' => $status['color'],
                'order' => $status['order'],
                'project_id' => $project->id,
                'is_completed' => $status['is_completed'],
            ]);
            $createdStatuses[$status['slug']] = $createdStatus;
        }

        // Create status transitions
        $this->createStatusTransitions($project->id, $createdStatuses);

        session()->flash('message', 'Project created successfully!');

        return $this->redirect('/projects/' . $project->id, navigate: true);
    }
}

?>

<div>
    <x-slot:title>Create Project</x-slot:title>

    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-primary">Create Project</h1>
            <x-button link="/projects" label="Back to Projects" icon="o-arrow-left" class="btn-ghost"/>
        </div>

        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <form wire:submit="save">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-control">
                            <x-input label="Project Name" wire:model="name" placeholder="Enter project name" required/>
                            @error('name') <span class="text-error text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-control">
                            <div class="flex items-end gap-2">
                                <div class="flex-1">
                                    <x-input label="Project Key" wire:model="key" placeholder="e.g., PRJ" required/>
                                    @error('key') <span class="text-error text-sm">{{ $message }}</span> @enderror
                                </div>
                                <x-button type="button" wire:click="generateKey" label="Generate" class="btn-sm"/>
                            </div>
                            <span class="text-sm text-gray-500 mt-1">This will be used as a prefix for all tasks (e.g., PRJ-123)</span>
                        </div>

                        <div class="form-control md:col-span-2">
                            <x-textarea label="Description" wire:model="description"
                                        placeholder="Enter project description" rows="4"/>
                            @error('description') <span class="text-error text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-control">
                            <x-input label="Avatar URL (optional)" wire:model="avatar"
                                     placeholder="https://example.com/avatar.png"/>
                            @error('avatar') <span class="text-error text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-control flex items-end">
                            @if($avatar)
                                <div class="avatar">
                                    <div class="w-16 rounded-full">
                                        <img src="{{ $avatar }}" alt="Project Avatar"/>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end">
                        <x-button type="submit" label="Create Project" icon="o-check" class="btn-primary"/>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
