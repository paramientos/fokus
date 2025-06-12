<?php

use App\Models\Workspace;
use Mary\Traits\Toast;

new class extends Livewire\Volt\Component {
    use Toast;

    public $workspaces = [];
    public string $newWorkspaceName = '';
    public string $newWorkspaceDescription = '';
    public bool $showCreateWorkspaceModal = false;

    public function mount(): void
    {
        // Reset workspace session if requested
        if (request()->has('reset_workspace')) {
            session()->forget('workspace_id');
        }

        $this->loadWorkspaces();
    }

    public function loadWorkspaces()
    {
        $this->workspaces = Workspace::where('owner_id', auth()->id())
            ->orWhereHas('members', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->with(['owner', 'members'])
            ->get();
    }

    public function displayCreateWorkspaceModal(): void
    {
        $this->showCreateWorkspaceModal = true;
    }

    public function createWorkspace(): void
    {
        $this->validate([
            'newWorkspaceName' => 'required|min:3|max:50',
            'newWorkspaceDescription' => 'nullable|max:255',
        ]);

        $workspace = Workspace::create([
            'name' => $this->newWorkspaceName,
            'description' => $this->newWorkspaceDescription,
            'owner_id' => auth()->id(),
            'created_by' => auth()->id(),
        ]);


        $this->newWorkspaceName = '';
        $this->newWorkspaceDescription = '';

        $this->loadWorkspaces();

        $this->showCreateWorkspaceModal = false;

        $this->success('Workspace başarıyla oluşturuldu.');
    }

    public function selectWorkspace($workspaceId)
    {
        session(['workspace_id' => $workspaceId]);
        return redirect()->route('dashboard');
    }
}

?>

<div>
    <x-slot:title>My Workspaces</x-slot:title>

    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-primary">My Workspaces</h1>

            <x-button @click="$wire.showCreateWorkspaceModal = true" icon="fas.plus" class="btn-primary">
                Create Workspace
            </x-button>
        </div>

        <!-- Workspace Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($workspaces as $workspace)
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h2 class="card-title">{{ $workspace->name }}</h2>
                        <p class="text-gray-500">{{ $workspace->description }}</p>

                        <div class="flex items-center gap-2 mt-2">
                            <div class="avatar placeholder">
                                <div class="bg-neutral text-neutral-content rounded-full w-6">
                                    <span class="text-xs">{{ substr($workspace->owner->name, 0, 1) }}</span>
                                </div>
                            </div>
                            <span class="text-sm">{{ $workspace->owner->name }}</span>
                        </div>

                        <div class="flex items-center gap-2 mt-4">
                            <span class="text-sm">{{ $workspace->members->count() }} members</span>

                            <div class="avatar-group -space-x-2">
                                @foreach($workspace->members->take(3) as $member)
                                    <div class="avatar placeholder">
                                        <div class="bg-neutral text-neutral-content rounded-full w-6">
                                            <span class="text-xs">{{ substr($member->name, 0, 1) }}</span>
                                        </div>
                                    </div>
                                @endforeach

                                @if($workspace->members->count() > 3)
                                    <div class="avatar placeholder">
                                        <div class="bg-neutral text-neutral-content rounded-full w-6">
                                            <span class="text-xs">+{{ $workspace->members->count() - 3 }}</span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="card-actions justify-end mt-4">
                            <x-button wire:click="selectWorkspace({{ $workspace->id }})" icon="fas.check"
                                      class="btn-sm btn-primary">
                                Select
                            </x-button>
                            <x-button link="/workspaces/{{ $workspace->id }}" icon="fas.arrow-right"
                                      class="btn-sm btn-outline">
                                View Details
                            </x-button>

                            @if($workspace->owner_id === auth()->id())
                                <x-button link="/workspaces/{{ $workspace->id }}/members" icon="fas.users"
                                          class="btn-sm">
                                    Manage Members
                                </x-button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach

            @if($workspaces->isEmpty())
                <div
                    class="col-span-full flex flex-col items-center justify-center py-12 bg-base-100 rounded-lg shadow-sm">
                    <x-icon name="fas.building" class="w-16 h-16 text-gray-400"/>
                    <p class="mt-4 text-lg font-medium">No workspaces found</p>
                    <p class="text-gray-500">Create a new workspace or ask to be invited to one</p>
                    <x-button @click="$wire.showCreateWorkspaceModal = true" icon="fas.plus"
                              class="btn-primary mt-4">
                        Create Workspace
                    </x-button>
                </div>
            @endif
        </div>
    </div>

    <!-- Create Workspace Modal -->
    <x-modal wire:model="showCreateWorkspaceModal" name="create-workspace-modal">
        <x-card title="Create New Workspace">
            <form wire:submit="createWorkspace">
                <x-input
                    wire:model="newWorkspaceName"
                    label="Workspace Name"
                    placeholder="Enter workspace name"
                    error="{{ $errors->first('newWorkspaceName') }}"
                />

                <x-textarea
                    wire:model="newWorkspaceDescription"
                    label="Description (optional)"
                    placeholder="Enter workspace description"
                    class="mt-4"
                    error="{{ $errors->first('newWorkspaceDescription') }}"
                />

                <div class="flex justify-end gap-2 mt-6">
                    <x-button @click="$dispatch('close-modal', 'create-workspace-modal')" class="btn-ghost">
                        Cancel
                    </x-button>
                    <x-button type="submit" icon="fas.plus" class="btn-primary">
                        Create Workspace
                    </x-button>
                </div>
            </form>
        </x-card>
    </x-modal>
</div>
