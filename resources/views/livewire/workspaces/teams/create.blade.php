<?php
    use App\Models\Team;
    use App\Models\Workspace;
    use Mary\Traits\Toast;
    new class extends Livewire\Component {
        use Toast;
        public $workspace;
        public $name = '';
        public $description = '';
        public function mount($workspaceId) {
            $this->workspace = Workspace::findOrFail($workspaceId);
        }
        public function save() {
            $this->validate([
                'name' => 'required|string|max:64',
                'description' => 'nullable|string|max:255',
            ]);
            Team::create([
                'workspace_id' => $this->workspace->id,
                'name' => $this->name,
                'description' => $this->description,
                'created_by' => auth()->id(),
            ]);
            $this->success('Team created successfully!');
            return redirect()->route('workspaces.teams.index', $this->workspace->id);
        }
        public function render() {
            return view('livewire.workspaces.teams.create');
        }
    }
?>
<div class="mx-auto max-w-lg py-10">
    <x-card>
        <form wire:submit.prevent="save">
            <h1 class="text-xl font-bold mb-4"><i class="fas fa-users mr-2"></i>New Team</h1>
            <x-input label="Team Name" wire:model.defer="name" required class="mb-4" />
            <x-input label="Description" wire:model.defer="description" class="mb-4" />
            <div class="flex justify-end">
                <x-button type="submit" color="primary"><i class="fas fa-plus mr-1"></i>Create</x-button>
            </div>
        </form>
    </x-card>
</div>
