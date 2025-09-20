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

<div class="bg-gradient-to-br from-base-100 to-base-200 min-h-screen">
    <x-slot:title>Create Sprint - {{ $project->name }}</x-slot:title>

    <div class="max-w-5xl mx-auto p-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div class="flex items-center gap-3">
                <x-button 
                    link="/projects/{{ $project->id }}/sprints" 
                    icon="fas.arrow-left" 
                    class="btn-ghost btn-sm hover:bg-base-200 transition-all duration-200"
                    tooltip="Back to Sprints"
                />
                <h1 class="text-2xl font-bold text-primary">Create Sprint</h1>
            </div>
            <div class="text-sm text-base-content/70">
                Project: <span class="font-medium text-primary">{{ $project->name }}</span>
            </div>
        </div>

        <div class="card bg-base-100 shadow-xl border border-base-300 overflow-hidden">
            <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center gap-3">
                <span class="p-2 rounded-full bg-primary/10 text-primary">
                    <i class="fas fa-flag text-lg"></i>
                </span>
                <h2 class="text-xl font-semibold">Sprint Details</h2>
            </div>
            
            <div class="card-body p-6">
                <form wire:submit="save" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                        <div class="form-control md:col-span-2">
                            <x-input 
                                label="Sprint Name" 
                                wire:model="name" 
                                placeholder="Enter a descriptive name for this sprint" 
                                icon="fas.flag"
                                class="focus:border-primary/50 transition-all duration-300"
                                required 
                            />
                            @error('name') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-control md:col-span-2">
                            <x-textarea 
                                label="Sprint Goal" 
                                wire:model="goal" 
                                placeholder="What is the main objective of this sprint?" 
                                icon="fas.bullseye"
                                class="focus:border-primary/50 transition-all duration-300"
                                rows="4" 
                            />
                            @error('goal') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                            <p class="text-xs text-base-content/70 mt-1">A clear sprint goal helps the team stay focused on what needs to be accomplished</p>
                        </div>

                        <div class="form-control">
                            <x-input
                                type="date"
                                label="Start Date"
                                wire:model="start_date"
                                icon="fas.calendar-day"
                                class="focus:border-primary/50 transition-all duration-300"
                            />
                            @error('start_date') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-control">
                            <x-input
                                type="date"
                                label="End Date"
                                wire:model="end_date"
                                icon="fas.calendar-check"
                                class="focus:border-primary/50 transition-all duration-300"
                            />
                            @error('end_date') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-control bg-base-200/30 p-4 rounded-lg border border-base-300 md:col-span-2">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-full bg-success/10 text-success">
                                    <i class="fas fa-play-circle"></i>
                                </div>
                                <div class="flex-1">
                                    <label class="cursor-pointer flex items-center justify-between">
                                        <div>
                                            <span class="font-medium">Set as active sprint</span>
                                            <p class="text-sm text-base-content/70">Only one sprint can be active at a time</p>
                                        </div>
                                        <x-checkbox wire:model="is_active" class="checkbox-success" />
                                    </label>
                                </div>
                            </div>
                            @error('is_active') <span class="text-error text-sm mt-2">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="pt-4 border-t border-base-200 flex justify-end gap-3">
                        <x-button 
                            link="/projects/{{ $project->id }}/sprints" 
                            label="Cancel" 
                            class="btn-ghost hover:bg-base-200 transition-all duration-200"
                        />
                        <x-button 
                            type="submit" 
                            label="Create Sprint" 
                            icon="fas.check" 
                            class="btn-primary hover:shadow-lg transition-all duration-300" 
                        />
                    </div>
                </form>
            </div>
        </div>
        
        <div class="mt-6 text-center text-sm text-base-content/70">
            <p>After creating the sprint, you'll be able to add tasks and track progress</p>
        </div>
    </div>
</div>
