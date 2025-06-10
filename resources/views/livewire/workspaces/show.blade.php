<?php

use App\Models\Workspace;
use App\Models\Project;
use Mary\Traits\Toast;

new class extends Livewire\Volt\Component {
    use Toast;
    
    public Workspace $workspace;
    public $projects = [];
    public $newProjectName = '';
    public $newProjectDescription = '';
    public $userRole = null;
    
    public function mount($id)
    {
        $this->workspace = Workspace::with(['members', 'owner', 'projects'])->findOrFail($id);
        
        // Kullanıcının bu workspace'deki rolünü belirle
        $member = $this->workspace->members()->where('user_id', auth()->id())->first();
        if ($member) {
            $this->userRole = $member->pivot->role;
        } elseif ($this->workspace->owner_id === auth()->id()) {
            $this->userRole = 'owner';
        }
        
        // Kullanıcı workspace'e erişim yetkisine sahip değilse hata ver
        if (!$this->userRole && $this->workspace->owner_id !== auth()->id()) {
            abort(403, 'You do not have permission to access this workspace.');
        }
        
        $this->loadProjects();
    }
    
    public function loadProjects()
    {
        $this->projects = $this->workspace->projects()->with(['owner'])->get();
    }
    
    public function createProject()
    {
        $this->validate([
            'newProjectName' => 'required|min:3|max:50',
            'newProjectDescription' => 'nullable|max:255',
        ]);
        
        $project = Project::create([
            'name' => $this->newProjectName,
            'description' => $this->newProjectDescription,
            'workspace_id' => $this->workspace->id,
            'user_id' => auth()->id(),
        ]);
        
        // Projeyi oluşturan kişiyi otomatik olarak admin rolüyle ekle
        $project->teamMembers()->attach(auth()->id(), [
            'role' => 'admin'
        ]);
        
        // Workspace'deki tüm üyeleri projeye ekle
        foreach ($this->workspace->members as $member) {
            if ($member->id !== auth()->id()) {
                $project->teamMembers()->attach($member->id, [
                    'role' => $member->pivot->role
                ]);
            }
        }
        
        $this->newProjectName = '';
        $this->newProjectDescription = '';
        
        $this->loadProjects();
        
        $this->success('Project successfully created.');
    }
}

?>

<div>
    <x-slot:title>{{ $workspace->name }}</x-slot:title>
    
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <div class="flex items-center gap-2">
                    <a href="/workspaces" class="text-primary hover:underline">
                        <i class="fas fa-arrow-left mr-1"></i> Workspaces
                    </a>
                    <span class="text-gray-500">/</span>
                    <h1 class="text-2xl font-bold text-primary">{{ $workspace->name }}</h1>
                </div>
                <p class="text-gray-500 mt-1">{{ $workspace->description }}</p>
            </div>
            
            <div class="flex gap-2">
                @if($userRole === 'owner' || $userRole === 'admin')
                    <x-button link="/workspaces/{{ $workspace->id }}/members" icon="fas.users" class="btn-outline">
                        Manage Members
                    </x-button>
                    
                    <x-button @click="$dispatch('open-modal', 'create-project-modal')" icon="fas.plus" class="btn-primary">
                        Create Project
                    </x-button>
                @endif
            </div>
        </div>
        
        <!-- Workspace Info Card -->
        <div class="card bg-base-100 shadow-sm mb-6">
            <div class="card-body">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="card-title">Workspace Information</h2>
                        <p class="text-gray-500">Created by {{ $workspace->owner->name }}</p>
                    </div>
                    
                    <div class="flex items-center gap-4">
                        <div>
                            <div class="text-sm text-gray-500">Your Role</div>
                            <x-badge color="{{ $userRole === 'owner' ? 'primary' : ($userRole === 'admin' ? 'secondary' : 'neutral') }}">
                                {{ ucfirst($userRole) }}
                            </x-badge>
                        </div>
                        
                        <div>
                            <div class="text-sm text-gray-500">Members</div>
                            <div class="flex items-center">
                                <span class="font-medium">{{ $workspace->members->count() }}</span>
                                <x-button link="/workspaces/{{ $workspace->id }}/members" class="btn-ghost btn-xs ml-1">
                                    <i class="fas fa-external-link-alt"></i>
                                </x-button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Projects List -->
        <h2 class="text-xl font-semibold mb-4">Projects</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($projects as $project)
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h2 class="card-title">{{ $project->name }}</h2>
                        <p class="text-gray-500">{{ $project->description }}</p>
                        
                        <div class="flex items-center gap-2 mt-2">
                            <div class="avatar placeholder">
                                <div class="bg-neutral text-neutral-content rounded-full w-6">
                                    <span class="text-xs">{{ substr($project->owner->name, 0, 1) }}</span>
                                </div>
                            </div>
                            <span class="text-sm">{{ $project->owner->name }}</span>
                        </div>
                        
                        <div class="card-actions justify-end mt-4">
                            <x-button link="/projects/{{ $project->id }}" icon="fas.arrow-right" class="btn-sm btn-primary">
                                Open Project
                            </x-button>
                        </div>
                    </div>
                </div>
            @endforeach
            
            @if($projects->isEmpty())
                <div class="col-span-full flex flex-col items-center justify-center py-12 bg-base-100 rounded-lg shadow-sm">
                    <x-icon name="fas.folder" class="w-16 h-16 text-gray-400"/>
                    <p class="mt-4 text-lg font-medium">No projects found</p>
                    <p class="text-gray-500">Create a new project to get started</p>
                    
                    @if($userRole === 'owner' || $userRole === 'admin')
                        <x-button @click="$dispatch('open-modal', 'create-project-modal')" icon="fas.plus" class="btn-primary mt-4">
                            Create Project
                        </x-button>
                    @endif
                </div>
            @endif
        </div>
    </div>
    
    <!-- Create Project Modal -->
    <x-modal name="create-project-modal">
        <x-card title="Create New Project">
            <form wire:submit="createProject">
                <x-input 
                    wire:model="newProjectName" 
                    label="Project Name" 
                    placeholder="Enter project name"
                    error="{{ $errors->first('newProjectName') }}"
                />
                
                <x-textarea 
                    wire:model="newProjectDescription" 
                    label="Description (optional)" 
                    placeholder="Enter project description"
                    class="mt-4"
                    error="{{ $errors->first('newProjectDescription') }}"
                />
                
                <div class="flex justify-end gap-2 mt-6">
                    <x-button @click="$dispatch('close-modal', 'create-project-modal')" class="btn-ghost">
                        Cancel
                    </x-button>
                    <x-button type="submit" icon="fas.plus" class="btn-primary">
                        Create Project
                    </x-button>
                </div>
            </form>
        </x-card>
    </x-modal>
</div>
