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

<div>
    <x-slot:title>Edit Sprint - {{ $project->name }}</x-slot:title>

    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-primary">Edit Sprint</h1>
            <x-button link="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}" label="Back to Sprint" icon="o-arrow-left" class="btn-ghost" />
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

                        <div class="form-control">
                            <label class="cursor-pointer label justify-start gap-4">
                                <span class="label-text">Mark as completed</span>
                                <x-checkbox wire:model="is_completed" />
                            </label>
                            <p class="text-sm text-gray-500">Completed sprints cannot be active</p>
                            @error('is_completed') <span class="text-error text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end">
                        <x-button type="submit" label="Update Sprint" icon="o-check" class="btn-primary" />
                    </div>
                </form>
            </div>
        </div>

        <div class="mt-6">
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title text-error">Danger Zone</h2>
                    <p class="text-gray-500">Permanently delete this sprint and all of its data.</p>

                    <div class="mt-4">
                        <x-button
                            x-data=""
                            x-on:click="$dispatch('open-modal', 'delete-sprint-modal')"
                            label="Delete Sprint"
                            icon="o-trash"
                            class="btn-error"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Sprint Modal -->
        <x-modal wire:model="showDeleteModal" name="delete-sprint-modal" title="Delete Sprint">
            <div class="p-4">
                <h3 class="text-lg font-bold">Are you sure you want to delete this sprint?</h3>
                <p class="py-4">This action cannot be undone. All tasks in this sprint will be unassigned from it.</p>

                <div class="flex justify-end gap-2">
                    <x-button
                        x-on:click="$dispatch('close-modal', 'delete-sprint-modal')"
                        label="Cancel"
                        class="btn-ghost"
                    />
                    <x-button
                        wire:click="deleteSprint"
                        x-on:click="$dispatch('close-modal', 'delete-sprint-modal')"
                        label="Delete"
                        icon="o-trash"
                        class="btn-error"
                    />
                </div>
            </div>
        </x-modal>
    </div>
</div>
