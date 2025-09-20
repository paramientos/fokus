<?php

use App\Models\Workspace;
use Livewire\Features\SupportRedirects\Redirector;
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
        /*if (request()->has('reset_workspace')) {
            session()->forget('workspace_id');
        }*/

        $this->loadWorkspaces();
    }

    public function loadWorkspaces(): void
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

    public function selectWorkspace(string $workspaceId): Redirector
    {
        session(['workspace_id' => $workspaceId]);

        return redirect()->route('dashboard');
    }
}

?>

<div class="bg-gradient-to-br from-base-100 to-base-200 min-h-screen">
    <x-slot:title>My Workspaces</x-slot:title>

    <div class="p-6 max-w-7xl mx-auto">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold text-primary mb-1">My Workspaces</h1>
                <p class="text-base-content/70">Manage your teams and projects in dedicated workspaces</p>
            </div>

            <x-button 
                @click="$wire.showCreateWorkspaceModal = true" 
                icon="fas.plus" 
                class="btn-primary hover:shadow-lg transition-all duration-300"
            >
                Create Workspace
            </x-button>
        </div>

        <livewire:components.all-info-component/>

        <!-- Workspace Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($workspaces as $workspace)
                <div class="card bg-base-100 shadow-xl hover:shadow-2xl transition-all duration-300 border border-base-300 overflow-hidden">
                    <div class="bg-primary/5 p-4 border-b border-base-300">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-3">
                                <div class="avatar placeholder">
                                    <div class="bg-primary text-primary-content rounded-lg w-10 h-10 flex items-center justify-center">
                                        <span class="text-lg font-bold">{{ substr($workspace->name, 0, 1) }}</span>
                                    </div>
                                </div>
                                <h2 class="text-xl font-bold">{{ $workspace->name }}</h2>
                            </div>
                            
                            @if($workspace->owner_id === auth()->id())
                                <span class="badge badge-primary badge-sm">Owner</span>
                            @else
                                <span class="badge badge-outline badge-sm">Member</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="card-body p-5">
                        @if($workspace->description)
                            <p class="text-base-content/80 mb-4">{{ $workspace->description }}</p>
                        @else
                            <p class="text-base-content/50 italic mb-4">No description provided</p>
                        @endif

                        <div class="flex items-center gap-2 mb-4 bg-base-200/50 p-3 rounded-lg">
                            <div class="avatar placeholder">
                                <div class="bg-primary/20 text-primary rounded-full w-8 h-8 flex items-center justify-center">
                                    <i class="fas fa-user-tie text-sm"></i>
                                </div>
                            </div>
                            <div>
                                <span class="text-xs text-base-content/70">Workspace Owner</span>
                                <p class="text-sm font-medium">{{ $workspace->owner->name }}</p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-users text-base-content/60"></i>
                                <span class="text-sm font-medium">{{ $workspace->members->count() }} members</span>
                            </div>

                            <div class="avatar-group -space-x-3">
                                @foreach($workspace->members->take(3) as $member)
                                    <div class="avatar placeholder">
                                        <div class="bg-primary/10 text-primary rounded-full w-8 h-8 ring ring-base-100">
                                            <span class="text-xs font-medium">{{ substr($member->name, 0, 1) }}</span>
                                        </div>
                                    </div>
                                @endforeach

                                @if($workspace->members->count() > 3)
                                    <div class="avatar placeholder">
                                        <div class="bg-base-300 text-base-content rounded-full w-8 h-8 ring ring-base-100">
                                            <span class="text-xs font-medium">+{{ $workspace->members->count() - 3 }}</span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="card-actions justify-end mt-6 pt-4 border-t border-base-200">
                            <x-button 
                                wire:click="selectWorkspace('{{ $workspace->id }}')" 
                                icon="fas.check"
                                class="btn-primary btn-sm hover:shadow-md transition-all duration-300"
                            >
                                Select
                            </x-button>
                            
                            <x-button 
                                link="/workspaces/{{ $workspace->id }}" 
                                icon="fas.arrow-right"
                                class="btn-outline btn-sm hover:bg-base-200 transition-all duration-300"
                            >
                                View Details
                            </x-button>

                            @if($workspace->owner_id === auth()->id())
                                <x-button 
                                    link="/workspaces/{{ $workspace->id }}/members" 
                                    icon="fas.users"
                                    class="btn-ghost btn-sm hover:bg-base-200 transition-all duration-300"
                                >
                                    Manage
                                </x-button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach

            @if($workspaces->isEmpty())
                <div class="col-span-full flex flex-col items-center justify-center py-16 bg-base-100 rounded-xl shadow-sm border border-base-300">
                    <div class="p-6 rounded-full bg-primary/10 mb-4">
                        <i class="fas fa-building text-4xl text-primary"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-2">No workspaces found</h3>
                    <p class="text-base-content/70 text-center max-w-md mb-6">Create a new workspace to organize your teams and projects, or ask to be invited to an existing one</p>
                    <x-button 
                        @click="$wire.showCreateWorkspaceModal = true" 
                        icon="fas.plus"
                        class="btn-primary hover:shadow-lg transition-all duration-300"
                    >
                        Create Your First Workspace
                    </x-button>
                </div>
            @endif
        </div>
    </div>

    <!-- Create Workspace Modal -->
    <x-modal wire:model="showCreateWorkspaceModal" name="create-workspace-modal">
        <x-card title="Create New Workspace" class="max-w-lg">
            <div class="flex items-center gap-3 mb-6 p-4 bg-primary/5 rounded-lg border border-primary/10">
                <div class="p-3 rounded-full bg-primary/10 text-primary">
                    <i class="fas fa-building text-xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-lg">New Workspace</h3>
                    <p class="text-sm text-base-content/70">Create a dedicated space for your team and projects</p>
                </div>
            </div>
            
            <form wire:submit="createWorkspace">
                <x-input
                    wire:model="newWorkspaceName"
                    label="Workspace Name"
                    placeholder="Enter workspace name"
                    icon="fas.building"
                    class="transition-all duration-300 focus:ring-2 focus:ring-primary/30"
                    error="{{ $errors->first('newWorkspaceName') }}"
                />
                <p class="text-xs text-base-content/60 mt-1 mb-4">Choose a clear, descriptive name for your workspace</p>

                <x-textarea
                    wire:model="newWorkspaceDescription"
                    label="Description (optional)"
                    placeholder="Enter workspace description"
                    icon="fas.align-left"
                    class="transition-all duration-300 focus:ring-2 focus:ring-primary/30"
                    error="{{ $errors->first('newWorkspaceDescription') }}"
                    rows="4"
                />
                <p class="text-xs text-base-content/60 mt-1">Provide details about the purpose of this workspace</p>

                <div class="flex flex-col sm:flex-row justify-end gap-3 mt-8 pt-4 border-t border-base-200">
                    <x-button 
                        @click="$dispatch('close-modal', 'create-workspace-modal')" 
                        class="btn-ghost hover:bg-base-200 transition-all duration-300"
                    >
                        Cancel
                    </x-button>
                    <x-button 
                        type="submit" 
                        icon="fas.plus" 
                        class="btn-primary hover:shadow-lg transition-all duration-300"
                    >
                        Create Workspace
                    </x-button>
                </div>
            </form>
        </x-card>
    </x-modal>
</div>
