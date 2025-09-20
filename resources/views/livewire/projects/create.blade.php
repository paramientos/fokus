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

<div class="bg-gradient-to-br from-base-100 to-base-200 min-h-screen">
    <x-slot:title>Create Project</x-slot:title>

    <div class="p-6 max-w-5xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-primary mb-2">Create Project</h1>
                <p class="text-base-content/70">Set up a new project workspace with all necessary configurations</p>
            </div>
            <x-button link="/projects" label="Back to Projects" icon="o-arrow-left" class="btn-outline btn-primary hover:shadow-lg transition-all duration-300"/>
        </div>

        <div class="card bg-base-100 shadow-2xl border border-base-300 overflow-hidden">
            <div class="card-body p-0">
                <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center gap-3">
                    <span class="p-2 rounded-full bg-primary/10 text-primary">
                        <i class="fas fa-project-diagram text-xl"></i>
                    </span>
                    <h2 class="text-xl font-semibold">Project Details</h2>
                </div>

                <div class="p-6">
                    <form wire:submit="save" class="space-y-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-6">
                                <div>
                                    <x-input
                                        label="Project Name"
                                        wire:model="name"
                                        placeholder="Enter project name"
                                        icon="fas.signature"
                                        class="transition-all duration-300 focus:ring-2 focus:ring-primary/30"
                                        required
                                    />
                                    @error('name') <span class="text-error text-sm mt-1 block">{{ $message }}</span> @enderror
                                    <p class="text-xs text-base-content/60 mt-1">Choose a descriptive name for your project</p>
                                </div>

                                <div>
                                    <div class="flex items-end gap-2">
                                        <div class="flex-1">
                                            <x-input
                                                label="Project Key"
                                                wire:model="key"
                                                placeholder="e.g., PRJ"
                                                icon="fas.key"
                                                class="transition-all duration-300 focus:ring-2 focus:ring-primary/30"
                                                required
                                            />
                                            @error('key') <span class="text-error text-sm mt-1 block">{{ $message }}</span> @enderror
                                        </div>
                                        <x-button
                                            type="button"
                                            wire:click="generateKey"
                                            label="Generate"
                                            icon="fas.magic"
                                            class="btn-secondary btn-sm hover:shadow-md transition-all duration-300"
                                        />
                                    </div>
                                    <p class="text-xs text-base-content/60 mt-1">This will be used as a prefix for all tasks (e.g., PRJ-123)</p>
                                </div>

                                <div>
                                    <x-input
                                        label="Avatar URL (optional)"
                                        wire:model="avatar"
                                        placeholder="https://example.com/avatar.png"
                                        icon="fas.image"
                                        class="transition-all duration-300 focus:ring-2 focus:ring-primary/30"
                                    />
                                    @error('avatar') <span class="text-error text-sm mt-1 block">{{ $message }}</span> @enderror
                                    <p class="text-xs text-base-content/60 mt-1">Add a visual identity to your project</p>
                                </div>
                            </div>

                            <div class="space-y-6">
                                <div>
                                    <x-textarea
                                        label="Description"
                                        wire:model="description"
                                        placeholder="Enter project description"
                                        rows="6"
                                        class="transition-all duration-300 focus:ring-2 focus:ring-primary/30"
                                    />
                                    @error('description') <span class="text-error text-sm mt-1 block">{{ $message }}</span> @enderror
                                    <p class="text-xs text-base-content/60 mt-1">Provide details about the project's purpose and goals</p>
                                </div>

                                @if($avatar)
                                    <div class="flex flex-col items-center justify-center p-4 bg-base-200/50 rounded-lg border border-base-300">
                                        <p class="text-sm font-medium mb-2">Project Avatar Preview</p>
                                        <div class="avatar">
                                            <div class="w-24 h-24 rounded-xl ring ring-primary ring-offset-2 ring-offset-base-100 overflow-hidden">
                                                <img src="{{ $avatar }}" alt="Project Avatar" class="object-cover"/>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="pt-4 border-t border-base-300 flex justify-between items-center">
                            <div class="text-sm text-base-content/70">
                                <i class="fas fa-info-circle mr-1"></i> Project will be created with default statuses and workflows
                            </div>
                            <x-button
                                type="submit"
                                label="Create Project"
                                icon="fas.rocket"
                                class="btn-primary hover:shadow-lg transition-all duration-300"
                            />
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
