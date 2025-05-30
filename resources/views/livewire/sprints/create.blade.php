<?php

new class extends Livewire\Volt\Component {
    public $project;
    public $name = '';
    public $goal = '';
    public $start_date = null;
    public $end_date = null;
    public $is_active = false;

    protected $rules = [
        'name' => 'required|min:3|max:255',
        'goal' => 'nullable|max:1000',
        'start_date' => 'nullable|date',
        'end_date' => 'nullable|date|after_or_equal:start_date',
        'is_active' => 'boolean',
    ];

    public function mount($project)
    {
        $this->project = \App\Models\Project::findOrFail($project);

        // Set default dates to current week
        $this->start_date = now()->startOfWeek()->format('Y-m-d');
        $this->end_date = now()->endOfWeek()->format('Y-m-d');
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    public function save()
    {
        $this->validate();

        $sprint = \App\Models\Sprint::create([
            'name' => $this->name,
            'goal' => $this->goal,
            'project_id' => $this->project->id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'is_active' => $this->is_active,
            'is_completed' => false,
        ]);

        session()->flash('message', 'Sprint created successfully!');

        return $this->redirect('/projects/' . $this->project->id . '/sprints/' . $sprint->id, navigate: true);
    }
}

?>

<div>
    <x-slot:title>Create Sprint - {{ $project->name }}</x-slot:title>

    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-primary">Create Sprint</h1>
            <x-button link="/projects/{{ $project->id }}/sprints" label="Back to Sprints" icon="o-arrow-left" class="btn-ghost" />
        </div>

        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <form wire:submit="save">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-control md:col-span-2">
                            <x-input label="Sprint Name" wire:model="name" placeholder="Enter sprint name" required />
                            @error('name') <span class="text-error text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-control md:col-span-2">
                            <x-textarea label="Sprint Goal" wire:model="goal" placeholder="Enter sprint goal" rows="4" />
                            @error('goal') <span class="text-error text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-control">
                            <x-input
                                type="date"
                                label="Start Date"
                                wire:model="start_date"
                            />
                            @error('start_date') <span class="text-error text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-control">
                            <x-input
                                type="date"
                                label="End Date"
                                wire:model="end_date"
                            />
                            @error('end_date') <span class="text-error text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-control">
                            <label class="cursor-pointer label justify-start gap-4">
                                <span class="label-text">Set as active sprint</span>
                                <x-checkbox wire:model="is_active" />
                            </label>
                            <p class="text-sm text-gray-500">Only one sprint can be active at a time</p>
                            @error('is_active') <span class="text-error text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end">
                        <x-button type="submit" label="Create Sprint" icon="o-check" class="btn-primary" />
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
