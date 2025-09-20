<?php

use App\Models\Project;
use App\Models\Status;

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
            ['name' => 'To Do', 'slug' => 'to-do', 'color' => '#3498db', 'order' => 1],
            ['name' => 'In Progress', 'slug' => 'in-progress', 'color' => '#f39c12', 'order' => 2],
            ['name' => 'Review', 'slug' => 'review', 'color' => '#9b59b6', 'order' => 3],
            ['name' => 'Done', 'slug' => 'done', 'color' => '#2ecc71', 'order' => 4],
        ];

        foreach ($statuses as $status) {
            Status::create([
                'name' => $status['name'],
                'slug' => $status['slug'],
                'color' => $status['color'],
                'order' => $status['order'],
                'project_id' => $project->id,
            ]);
        }

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
