<?php

new class extends Livewire\Volt\Component {
    public $project;
    public $name = '';
    public $key = '';
    public $description = '';
    public $avatar = '';
    public $is_active = true;

    protected $rules = [
        'name' => 'required|min:3|max:255',
        'key' => 'required|min:2|max:10|alpha_num',
        'description' => 'nullable|max:1000',
        'avatar' => 'nullable|url|max:255',
        'is_active' => 'boolean',
    ];

    public function mount($project)
    {
        $this->project = \App\Models\Project::findOrFail($project);
        $this->name = $this->project->name;
        $this->key = $this->project->key;
        $this->description = $this->project->description;
        $this->avatar = $this->project->avatar;
        $this->is_active = $this->project->is_active;

        // Özel doğrulama kuralı - key benzersiz olmalı ancak mevcut projenin anahtarı hariç
        $this->rules['key'] = 'required|min:2|max:10|alpha_num|unique:projects,key,' . $this->project->id;
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    public function generateKey()
    {
        if (empty($this->name)) {
            return;
        }

        $words = explode(' ', $this->name);
        if (count($words) > 1) {
            $this->key = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        } else {
            $this->key = strtoupper(substr($this->name, 0, 2));
        }

        // Anahtar benzersiz olmalı
        $count = 1;
        $originalKey = $this->key;
        while (\App\Models\Project::where('key', $this->key)->where('id', '!=', $this->project->id)->exists()) {
            $this->key = $originalKey . $count;
            $count++;
        }
    }

    public function save()
    {
        $this->validate();

        $this->project->update([
            'name' => $this->name,
            'key' => strtoupper($this->key),
            'description' => $this->description,
            'avatar' => $this->avatar,
            'is_active' => $this->is_active,
        ]);

        session()->flash('message', 'Proje başarıyla güncellendi!');

        return $this->redirect('/projects/' . $this->project->id, navigate: true);
    }
}

?>

<div>
    <x-slot:title>{{ $project->name }} - Düzenle</x-slot:title>

    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-primary">Edit Project</h1>
            <x-button link="/projects/{{ $project->id }}" label="Back to Project" icon="o-arrow-left" class="btn-ghost"/>
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

                        <div class="form-control">
                            <x-checkbox label="Active Project" wire:model="is_active" />
                            <span class="text-sm text-gray-500 mt-1">Inactive projects will be hidden from the main dashboard</span>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end">
                        <x-button type="submit" label="Update Project" icon="o-check" class="btn-primary" />
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
