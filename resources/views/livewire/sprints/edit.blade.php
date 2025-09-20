<?php

new class extends Livewire\Volt\Component {
    public $project;
    public $sprint;
    public $name;
    public $goal;
    public $start_date;
    public $end_date;
    public $is_active;
    public $is_completed;

    protected $rules = [
        'name' => 'required|min:3|max:255',
        'goal' => 'nullable|max:1000',
        'start_date' => 'nullable|date',
        'end_date' => 'nullable|date|after_or_equal:start_date',
        'is_active' => 'boolean',
        'is_completed' => 'boolean',
    ];

    public function mount($project, $sprint)
    {
        $this->project = \App\Models\Project::findOrFail($project);
        $this->sprint = \App\Models\Sprint::findOrFail($sprint);

        $this->name = $this->sprint->name;
        $this->goal = $this->sprint->goal;
        $this->start_date = $this->sprint->start_date ? $this->sprint->start_date->format('Y-m-d') : null;
        $this->end_date = $this->sprint->end_date ? $this->sprint->end_date->format('Y-m-d') : null;
        $this->is_active = $this->sprint->is_active;
        $this->is_completed = $this->sprint->is_completed;
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);

        // If sprint is marked as completed, it cannot be active
        if ($propertyName === 'is_completed' && $this->is_completed) {
            $this->is_active = false;
        }

        // If sprint is marked as active, it cannot be completed
        if ($propertyName === 'is_active' && $this->is_active) {
            $this->is_completed = false;
        }
    }

    public function save()
    {
        $this->validate();

        // If marking this sprint as active, deactivate all other sprints first
        if ($this->is_active && !$this->sprint->is_active) {
            \App\Models\Sprint::where('project_id', $this->project->id)
                ->where('id', '!=', $this->sprint->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        $this->sprint->update([
            'name' => $this->name,
            'goal' => $this->goal,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'is_active' => $this->is_active,
            'is_completed' => $this->is_completed,
        ]);

        session()->flash('message', 'Sprint updated successfully!');

        return $this->redirect('/projects/' . $this->project->id . '/sprints/' . $this->sprint->id, navigate: true);
    }

    public function deleteSprint()
    {
        // Update tasks to remove sprint association
        \App\Models\Task::where('sprint_id', $this->sprint->id)->update(['sprint_id' => null]);

        // Delete the sprint
        $this->sprint->delete();

        session()->flash('message', 'Sprint deleted successfully!');

        return $this->redirect('/projects/' . $this->project->id . '/sprints', navigate: true);
    }
}

?>

<div class="bg-gradient-to-br from-base-100 to-base-200 min-h-screen">
    <x-slot:title>Edit Sprint - {{ $project->name }}</x-slot:title>

    <div class="max-w-5xl mx-auto p-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div class="flex items-center gap-3">
                <x-button 
                    link="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}" 
                    icon="fas.arrow-left" 
                    class="btn-ghost btn-sm hover:bg-base-200 transition-all duration-200"
                    tooltip="Back to Sprint"
                />
                <h1 class="text-2xl font-bold text-primary">Edit Sprint</h1>
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

                        <div class="form-control bg-success/5 p-4 rounded-lg border border-success/20">
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

                        <div class="form-control bg-info/5 p-4 rounded-lg border border-info/20">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-full bg-info/10 text-info">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="flex-1">
                                    <label class="cursor-pointer flex items-center justify-between">
                                        <div>
                                            <span class="font-medium">Mark as completed</span>
                                            <p class="text-sm text-base-content/70">Completed sprints cannot be active</p>
                                        </div>
                                        <x-checkbox wire:model="is_completed" class="checkbox-info" />
                                    </label>
                                </div>
                            </div>
                            @error('is_completed') <span class="text-error text-sm mt-2">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="pt-4 border-t border-base-200 flex justify-end gap-3">
                        <x-button 
                            link="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}" 
                            label="Cancel" 
                            class="btn-ghost hover:bg-base-200 transition-all duration-200"
                        />
                        <x-button 
                            type="submit" 
                            label="Update Sprint" 
                            icon="fas.check" 
                            class="btn-primary hover:shadow-lg transition-all duration-300" 
                        />
                    </div>
                </form>
            </div>
        </div>

        <div class="mt-6">
            <div class="card bg-base-100 shadow-xl border border-base-300 overflow-hidden">
                <div class="bg-error/5 p-4 border-b border-base-300 flex items-center gap-3">
                    <span class="p-2 rounded-full bg-error/10 text-error">
                        <i class="fas fa-exclamation-triangle text-lg"></i>
                    </span>
                    <h2 class="text-xl font-semibold text-error">Danger Zone</h2>
                </div>
                
                <div class="card-body p-6">
                    <div class="bg-error/5 p-4 rounded-lg border border-error/20">
                        <h3 class="font-medium text-error mb-2">Delete this sprint</h3>
                        <p class="text-base-content/70 mb-4">Permanently delete this sprint. All tasks in this sprint will be unassigned from it.</p>

                        <div class="flex justify-end">
                            <x-button
                                x-data=""
                                x-on:click="$dispatch('open-modal', 'delete-sprint-modal')"
                                label="Delete Sprint"
                                icon="fas.trash"
                                class="btn-error hover:shadow-md transition-all duration-300"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Sprint Modal -->
        <x-modal wire:model="showDeleteModal" name="delete-sprint-modal">
            <x-card title="Delete Sprint">
                <div class="flex items-center gap-4 mb-4 p-4 bg-error/5 rounded-lg border border-error/20">
                    <div class="p-3 rounded-full bg-error/10 text-error">
                        <i class="fas fa-exclamation-triangle text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-error">Permanent Action</h3>
                        <p class="text-base-content/70">This action cannot be undone.</p>
                    </div>
                </div>
                
                <p class="mb-4">Are you sure you want to delete the sprint <span class="font-bold">"{{ $name }}"</span>?</p>
                <p class="text-base-content/70">All tasks in this sprint will be unassigned, but they will not be deleted.</p>
                
                <x-slot:footer>
                    <div class="flex justify-end gap-2">
                        <x-button
                            x-on:click="$dispatch('close-modal', 'delete-sprint-modal')"
                            label="Cancel"
                            class="btn-ghost hover:bg-base-200 transition-all duration-200"
                        />
                        <x-button
                            wire:click="deleteSprint"
                            x-on:click="$dispatch('close-modal', 'delete-sprint-modal')"
                            label="Delete Sprint"
                            icon="fas.trash"
                            class="btn-error hover:shadow-md transition-all duration-300"
                        />
                    </div>
                </x-slot:footer>
            </x-card>
        </x-modal>
    </div>
</div>
