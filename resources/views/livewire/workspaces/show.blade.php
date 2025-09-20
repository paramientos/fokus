<?php

use App\Models\Project;
use App\Models\Team;
use App\Models\Workspace;
use Mary\Traits\Toast;

new class extends Livewire\Volt\Component {
    use Toast;

    public Workspace $workspace;
    public $projects = [];
    public string $newProjectName = '';
    public string $newProjectKey = '';
    public string $newProjectDescription = '';
    public ?string $userRole = null;
    public bool $showCreateProjectModal = false;
    public bool $showCreateTeamModal = false;
    public string $newTeamName = '';
    public string $newTeamDescription = '';

    // Delete modals
    public bool $showDeleteWorkspaceModal = false;
    public bool $showDeleteProfileModal = false;
    public bool $confirmDeleteWorkspace = false;

    // Math question
    public string $mathQuestion = '';
    public ?int $mathAnswer = null;
    public ?int $mathAnswerProfile = null;
    public int $correctAnswer = 0;
    public bool $confirmDeleteProfile = false;

    protected $rules = [
        'mathAnswer' => 'required|numeric',
        'mathAnswerProfile' => 'required|numeric',
        'newTeamName' => 'required',
        'newTeamDescription' => 'required',
    ];

    protected $listeners = ['refreshWorkspace' => '$refresh'];

    public function generateMathQuestion(): void
    {
        $num1 = rand(5, 15);
        $num2 = rand(1, 9);
        $this->correctAnswer = $num1 + $num2;
        $this->mathQuestion = "$num1 + $num2";
        $this->mathAnswer = null;
        $this->mathAnswerProfile = null;
    }

    public function updatedMathAnswer()
    {
        $this->validateOnly('mathAnswer');
    }

    public function updatedMathAnswerProfile()
    {
        $this->validateOnly('mathAnswerProfile');
    }

    public function showWorkspaceDeleteModal()
    {
        $this->generateMathQuestion();
        $this->showDeleteWorkspaceModal = true;
    }

    public function showProfileDeleteModal()
    {
        $this->generateMathQuestion();
        $this->confirmDeleteProfile = false;
        $this->showDeleteProfileModal = true;
    }

    public function mount($id): void
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

    public function generateProjectKey(): void
    {
        $this->newProjectKey = generate_project_key($this->newProjectName);
    }

    public function loadProjects()
    {
        $this->projects = $this->workspace->projects()->with(['owner'])->get();
    }

    public function displayCreateProjectModal(): void
    {
        $this->showCreateProjectModal = true;

        $this->reset([
            'newProjectName',
            'newProjectDescription'
        ]);
    }

    public function createProject(): void
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
            'key' => generate_project_key($this->newProjectName),
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

    public function createTeam(): void
    {
        $this->validate([
            'newTeamName' => 'required',
        ]);

        Team::create([
            'name' => $this->newTeamName,
            'description' => $this->newTeamDescription,
            'workspace_id' => $this->workspace->id,
            'created_by' => auth()->id(),
        ]);

        $this->newTeamName = '';
        $this->newTeamDescription = '';
        $this->showCreateTeamModal = false;

        $this->success('Team successfully created.');
    }
}

?>

<div class="bg-gradient-to-br from-base-100 to-base-200 min-h-screen">
    <x-slot:title>{{ $workspace->name }}</x-slot:title>

    <div class="p-6 max-w-7xl mx-auto">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <a href="/workspaces" class="text-base-content/70 hover:text-primary transition-colors duration-200">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="text-3xl font-bold text-primary">{{ $workspace->name }}</h1>
                </div>
                <p class="text-base-content/70">{{ $workspace->description ?: 'No description provided' }}</p>
            </div>

            <div class="flex flex-wrap gap-3">
                @if($userRole === 'owner' || $userRole === 'admin')
                    <x-button 
                        link="/workspaces/{{ $workspace->id }}/members" 
                        icon="fas.users" 
                        class="btn-outline btn-primary hover:shadow-md transition-all duration-300"
                    >
                        Manage Members
                    </x-button>

                    <x-button 
                        @click="$wire.showCreateProjectModal = true" 
                        icon="fas.plus"
                        class="btn-primary hover:shadow-lg transition-all duration-300"
                    >
                        Create Project
                    </x-button>
                @endif
            </div>
        </div>

        <!-- Workspace Danger Zone -->
        @if($userRole === 'owner')
            <div class="card bg-base-100 shadow-xl border-2 border-error/50 mb-8 overflow-hidden">
                <div class="bg-error/10 p-4 border-b border-error/30">
                    <div class="flex items-center gap-3">
                        <span class="p-2 rounded-full bg-error/20 text-error">
                            <i class="fas fa-exclamation-triangle text-xl"></i>
                        </span>
                        <h2 class="text-xl font-bold text-error">Danger Zone</h2>
                    </div>
                </div>
                
                <div class="card-body">
                    <p class="mb-6 text-base-content/80">The following actions are <b>irreversible</b> and should be used with caution. Make sure you understand the consequences before proceeding.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Delete Workspace -->
                        <div class="p-4 border border-error/30 rounded-lg bg-error/5 flex flex-col items-center text-center">
                            <div class="p-3 rounded-full bg-error/10 mb-3">
                                <i class="fas fa-trash text-error text-xl"></i>
                            </div>
                            <h3 class="font-bold mb-2">Delete Workspace</h3>
                            <p class="text-sm text-base-content/70 mb-4">Permanently remove this workspace and all associated data</p>
                            <x-button 
                                wire:click="showWorkspaceDeleteModal" 
                                class="btn-error btn-sm hover:shadow-md transition-all duration-300 mt-auto"
                            >
                                Delete Workspace
                            </x-button>
                        </div>

                        <!-- Export Data -->
                        <div class="p-4 border border-warning/30 rounded-lg bg-warning/5 flex flex-col items-center text-center">
                            <div class="p-3 rounded-full bg-warning/10 mb-3">
                                <i class="fas fa-download text-warning text-xl"></i>
                            </div>
                            <h3 class="font-bold mb-2">Export Data</h3>
                            <p class="text-sm text-base-content/70 mb-4">Download all workspace data in a portable format</p>
                            <form method="POST" action="{{ route('workspaces.export', $workspace->id) }}" class="mt-auto">
                                @csrf
                                <x-button 
                                    type="submit" 
                                    class="btn-warning btn-sm hover:shadow-md transition-all duration-300"
                                >
                                    Export Data
                                </x-button>
                            </form>
                        </div>

                        <!-- Delete Profile -->
                        <div class="p-4 border border-neutral/30 rounded-lg bg-neutral/5 flex flex-col items-center text-center">
                            <div class="p-3 rounded-full bg-neutral/10 mb-3">
                                <i class="fas fa-user-slash text-neutral-content text-xl"></i>
                            </div>
                            <h3 class="font-bold mb-2">Delete Profile</h3>
                            <p class="text-sm text-base-content/70 mb-4">Remove your account and personal data from the system</p>
                            <x-button 
                                wire:click="showProfileDeleteModal" 
                                class="btn-neutral btn-sm hover:shadow-md transition-all duration-300 mt-auto"
                            >
                                Delete Profile
                            </x-button>
                        </div>
                    </div>

                    <!-- Delete Workspace Modal -->
                    <x-modal wire:model="showDeleteWorkspaceModal" persistent class="backdrop-blur">
                        <x-card title="Confirm Workspace Deletion"
                                subtitle="This action cannot be undone. All data will be permanently deleted.">
                            <div class="space-y-4">
                                <p class="text-error">Are you sure you want to delete this workspace? All projects,
                                    tasks, and data will be permanently removed.</p>

                                <div class="bg-base-200 p-4 rounded-lg">
                                    <p class="font-medium mb-2">Security Check: Solve this math problem to continue</p>
                                    <div class="flex items-center gap-4">
                                        <span class="text-xl">{{ $mathQuestion }} = </span>
                                        <x-input wire:model.live="mathAnswer" type="number" class="w-24" placeholder="?"
                                                 autofocus/>
                                    </div>
                                    @error('mathAnswer')
                                    <p class="text-error text-sm mt-1">{{ $message }}</p>
                                    @enderror

                                    <div class="mt-4">
                                        <label class="label cursor-pointer">
                                            <input type="checkbox" class="checkbox checkbox-error"
                                                   wire:model.live="confirmDeleteWorkspace"/>
                                            <span class="label-text ml-2">I understand this action is irreversible and I want to proceed</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="flex justify-end gap-3 mt-6">
                                    <x-button label="Cancel" wire:click="$set('showDeleteWorkspaceModal', false)"/>
                                    <form method="POST" action="{{ route('workspaces.destroy', $workspace->id) }}">
                                        @csrf
                                        @method('DELETE')
                                        <x-button type="submit" label="Delete Workspace" icon="fas.trash"
                                                  class="btn-error"
                                                  :disabled="!$confirmDeleteWorkspace || $mathAnswer !== $correctAnswer"/>
                                    </form>
                                </div>
                            </div>
                        </x-card>
                    </x-modal>

                    <!-- Delete Profile Modal -->
                    <x-modal wire:model="showDeleteProfileModal" persistent class="backdrop-blur">
                        <x-card title="Confirm Profile Deletion"
                                subtitle="This action cannot be undone. Your account and all associated data will be permanently removed.">
                            <div class="space-y-4">
                                <p class="text-error">Are you sure you want to delete your profile? This will remove all
                                    your personal data from Fokus.</p>

                                <div class="bg-base-200 p-4 rounded-lg">
                                    <p class="font-medium mb-2">Security Check: Solve this math problem to continue</p>
                                    <div class="flex items-center gap-4">
                                        <span class="text-xl">{{ $mathQuestion }} = </span>
                                        <x-input wire:model.live="mathAnswerProfile" type="number" class="w-24"
                                                 placeholder="?"/>
                                    </div>
                                    @error('mathAnswerProfile')
                                    <p class="text-error text-sm mt-1">{{ $message }}</p>
                                    @enderror

                                    <div class="mt-4">
                                        <label class="label cursor-pointer">
                                            <input type="checkbox" class="checkbox checkbox-error"
                                                   wire:model.live="confirmDeleteProfile"/>
                                            <span class="label-text ml-2">I understand this action is irreversible and I want to proceed</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="flex justify-end gap-3 mt-6">
                                    <x-button label="Cancel" wire:click="$set('showDeleteProfileModal', false)"/>
                                    <form method="POST" action="{{ route('profile.destroy') }}">
                                        @csrf
                                        @method('DELETE')
                                        <x-button type="submit" label="Delete My Profile" icon="fas.user-slash"
                                                  class="btn-error"
                                                  :disabled="!$confirmDeleteProfile || $mathAnswerProfile !== $correctAnswer"/>
                                    </form>
                                </div>
                            </div>
                        </x-card>
                    </x-modal>
                </div>
            </div>
        @endif

        <!-- Workspace Info Card -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Workspace Details -->
            <div class="card bg-base-100 shadow-xl border border-base-300 overflow-hidden">
                <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center gap-3">
                    <span class="p-2 rounded-full bg-primary/10 text-primary">
                        <i class="fas fa-info-circle text-lg"></i>
                    </span>
                    <h2 class="text-xl font-semibold">Workspace Details</h2>
                </div>
                
                <div class="card-body p-5">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="avatar placeholder">
                            <div class="bg-primary text-primary-content rounded-lg w-12 h-12 flex items-center justify-center">
                                <span class="text-xl font-bold">{{ substr($workspace->name, 0, 1) }}</span>
                            </div>
                        </div>
                        <div>
                            <h3 class="font-bold text-lg">{{ $workspace->name }}</h3>
                            <p class="text-sm text-base-content/70">Created by {{ $workspace->owner->name }}</p>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-3 bg-base-200/50 rounded-lg">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-user-shield text-base-content/70"></i>
                                <span class="text-sm">Your Role</span>
                            </div>
                            <x-badge
                                color="{{ $userRole === 'owner' ? 'primary' : ($userRole === 'admin' ? 'secondary' : 'neutral') }}">
                                {{ ucfirst($userRole) }}
                            </x-badge>
                        </div>
                        
                        <div class="flex items-center justify-between p-3 bg-base-200/50 rounded-lg">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-calendar-alt text-base-content/70"></i>
                                <span class="text-sm">Created</span>
                            </div>
                            <span class="text-sm font-medium">{{ $workspace->created_at->format('M d, Y') }}</span>
                        </div>
                        
                        <div class="flex items-center justify-between p-3 bg-base-200/50 rounded-lg">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-users text-base-content/70"></i>
                                <span class="text-sm">Members</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="font-medium">{{ $workspace->members->count() }}</span>
                                <x-button 
                                    link="/workspaces/{{ $workspace->id }}/members" 
                                    class="btn-ghost btn-xs hover:bg-base-300 transition-all duration-200"
                                >
                                    <i class="fas fa-external-link-alt"></i>
                                </x-button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Storage Usage -->
            @php $storage = $workspace->storageUsage; @endphp
            <div class="card bg-base-100 shadow-xl border border-base-300 overflow-hidden">
                <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center gap-3">
                    <span class="p-2 rounded-full bg-primary/10 text-primary">
                        <i class="fas fa-database text-lg"></i>
                    </span>
                    <h2 class="text-xl font-semibold">Storage Usage</h2>
                </div>
                
                <div class="card-body p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-3xl font-bold text-primary">{{ $storage ? $storage->formatted_used : '0B' }}</div>
                        <div class="text-xl text-base-content/70">/ {{ $storage ? $storage->formatted_limit : '—' }}</div>
                    </div>
                    
                    <div class="relative w-full h-4 rounded-full bg-base-200 overflow-hidden mb-4 shadow-inner">
                        <div
                            class="absolute top-0 left-0 h-4 rounded-full bg-gradient-to-r from-primary to-primary-focus transition-all duration-500"
                            style="width: {{ $storage && $storage->limit_bytes > 0 ? min(100, round($storage->used_bytes / $storage->limit_bytes * 100)) : 0 }}%"></div>
                        <div class="absolute inset-0 flex justify-center items-center text-xs font-bold {{ ($storage && $storage->usage_percent > 50) ? 'text-white' : 'text-primary' }}">
                            {{ $storage ? $storage->usage_percent : 0 }}%
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-3 bg-base-200/50 rounded-lg text-center">
                            <div class="text-xs text-base-content/70 mb-1">Plan</div>
                            <div class="font-semibold text-primary flex items-center justify-center gap-1">
                                <i class="fas fa-layer-group text-xs"></i>
                                {{ $storage ? ucfirst($storage->plan_name) : 'Free' }}
                            </div>
                        </div>
                        
                        <div class="p-3 bg-base-200/50 rounded-lg text-center">
                            <div class="text-xs text-base-content/70 mb-1">Members</div>
                            <div class="font-semibold flex items-center justify-center gap-1">
                                <i class="fas fa-users text-xs"></i>
                                {{ $workspace->members->count() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Stats -->
            <div class="card bg-base-100 shadow-xl border border-base-300 overflow-hidden">
                <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center gap-3">
                    <span class="p-2 rounded-full bg-primary/10 text-primary">
                        <i class="fas fa-chart-bar text-lg"></i>
                    </span>
                    <h2 class="text-xl font-semibold">Quick Stats</h2>
                </div>
                
                <div class="card-body p-5">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-4 bg-primary/5 rounded-lg text-center">
                            <div class="text-3xl font-bold text-primary mb-1">{{ $projects->count() }}</div>
                            <div class="text-sm text-base-content/70">Projects</div>
                        </div>
                        
                        <div class="p-4 bg-secondary/5 rounded-lg text-center">
                            <div class="text-3xl font-bold text-secondary mb-1">{{ Team::where('workspace_id', $workspace->id)->count() }}</div>
                            <div class="text-sm text-base-content/70">Teams</div>
                        </div>
                        
                        <div class="p-4 bg-accent/5 rounded-lg text-center">
                            <div class="text-3xl font-bold text-accent mb-1">{{ $workspace->members->count() }}</div>
                            <div class="text-sm text-base-content/70">Members</div>
                        </div>
                        
                        <div class="p-4 bg-success/5 rounded-lg text-center">
                            <div class="text-3xl font-bold text-success mb-1">{{ $workspace->created_at->diffInDays() }}</div>
                            <div class="text-sm text-base-content/70">Days Active</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Teams List -->
        <div class="mb-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-primary flex items-center gap-2 mb-1">
                        <i class="fas fa-users"></i> Teams
                    </h2>
                    <p class="text-base-content/70">Organize your workspace members into teams</p>
                </div>
                
                @if($userRole === 'owner' || $userRole === 'admin')
                    <div class="flex gap-3">
                        <x-button 
                            :link="route('workspaces.teams.index',$workspace)" 
                            icon="fas.users" 
                            class="btn-outline btn-primary hover:shadow-md transition-all duration-300"
                        >
                            View All Teams
                        </x-button>

                        <x-button 
                            @click="$wire.showCreateTeamModal = true" 
                            icon="fas.plus" 
                            class="btn-primary hover:shadow-lg transition-all duration-300"
                        >
                            New Team
                        </x-button>
                    </div>
                @endif
            </div>
            
            <div class="card bg-base-100 shadow-xl border border-base-300 overflow-hidden">
                <div class="bg-primary/5 p-4 border-b border-base-300">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="p-2 rounded-full bg-primary/10 text-primary">
                                <i class="fas fa-users text-lg"></i>
                            </span>
                            <h2 class="text-xl font-semibold">Workspace Teams</h2>
                        </div>
                        <span class="badge bg-primary/10 text-primary border-0 font-medium">
                            {{ Team::where('workspace_id', $workspace->id)->count() }} teams
                        </span>
                    </div>
                </div>
                
                @php $teams = Team::where('workspace_id', $workspace->id)->get(); @endphp
                
                @if($teams->isEmpty())
                    <div class="flex flex-col items-center justify-center py-16 text-center p-6">
                        <div class="p-6 rounded-full bg-base-200 mb-4">
                            <i class="fas fa-users-slash text-3xl text-base-content/50"></i>
                        </div>
                        <h3 class="text-xl font-bold mb-2">No teams yet</h3>
                        <p class="text-base-content/70 max-w-md mb-6">Create teams to organize your workspace members and collaborate more effectively</p>
                        
                        @if($userRole === 'owner' || $userRole === 'admin')
                            <x-button 
                                @click="$wire.showCreateTeamModal = true" 
                                icon="fas.plus" 
                                class="btn-primary hover:shadow-lg transition-all duration-300"
                            >
                                Create Your First Team
                            </x-button>
                        @endif
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="table w-full">
                            <thead class="bg-base-200/50">
                                <tr>
                                    <th>Team</th>
                                    <th>Description</th>
                                    <th class="text-center">Members</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($teams as $team)
                                    <tr class="hover:bg-base-200/30 transition-colors duration-150">
                                        <td>
                                            <a 
                                                href="/workspaces/{{ $workspace->id }}/teams/{{ $team->id }}" 
                                                class="font-medium text-primary hover:underline flex items-center gap-2"
                                            >
                                                <div class="avatar placeholder">
                                                    <div class="bg-primary/10 text-primary rounded-lg w-8 h-8 flex items-center justify-center">
                                                        <span class="font-medium">{{ substr($team->name, 0, 1) }}</span>
                                                    </div>
                                                </div>
                                                {{ $team->name }}
                                            </a>
                                        </td>
                                        <td class="text-base-content/80">{{ $team->description ?: 'No description provided' }}</td>
                                        <td class="text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                <span class="font-medium">{{ $team->members->count() }}</span>
                                                @if($team->members->count() > 0)
                                                    <div class="avatar-group -space-x-2 ml-2">
                                                        @foreach($team->members->take(3) as $member)
                                                            <div class="avatar placeholder">
                                                                <div class="bg-primary/10 text-primary rounded-full w-6 h-6 ring ring-base-100">
                                                                    <span class="text-xs">{{ substr($member->name, 0, 1) }}</span>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                        
                                                        @if($team->members->count() > 3)
                                                            <div class="avatar placeholder">
                                                                <div class="bg-base-300 text-base-content rounded-full w-6 h-6 ring ring-base-100">
                                                                    <span class="text-xs">+{{ $team->members->count() - 3 }}</span>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <x-button 
                                                link="/workspaces/{{ $workspace->id }}/teams/{{ $team->id }}" 
                                                icon="fas.eye" 
                                                class="btn-sm btn-ghost hover:bg-base-200 transition-all duration-200"
                                                tooltip="View Team"
                                            />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <!-- Create Team Modal -->
        <x-modal wire:model="showCreateTeamModal" name="create-team-modal">
            <x-card title="Create New Team">
                <form wire:submit="createTeam">
                    <x-input
                        wire:model="newTeamName"
                        label="Team Name"
                        required
                        class="mb-4"
                        error="{{ $errors->first('newTeamName') }}"
                    />
                    <x-input
                        wire:model="newTeamDescription"
                        label="Description"
                        class="mb-4"
                        error="{{ $errors->first('newTeamDescription') }}"
                    />
                    <div class="flex justify-end gap-2 mt-6">
                        <x-button @click="$dispatch('close-modal', 'create-team-modal')" class="btn-ghost">
                            Cancel
                        </x-button>
                        <x-button type="submit" icon="fas.plus" class="btn-primary">
                            Create
                        </x-button>
                    </div>
                </form>
            </x-card>
        </x-modal>

        <!-- Projects List -->
        <div class="mb-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-primary flex items-center gap-2 mb-1">
                        <i class="fas fa-project-diagram"></i> Projects
                    </h2>
                    <p class="text-base-content/70">Manage your workspace projects</p>
                </div>
                
                @if($userRole === 'owner' || $userRole === 'admin')
                    <x-button 
                        @click="$wire.showCreateProjectModal = true" 
                        icon="fas.plus" 
                        class="btn-primary hover:shadow-lg transition-all duration-300"
                    >
                        Create Project
                    </x-button>
                @endif
            </div>
            
            @if($projects->isEmpty())
                <div class="card bg-base-100 shadow-xl border border-base-300 overflow-hidden">
                    <div class="bg-primary/5 p-4 border-b border-base-300">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="p-2 rounded-full bg-primary/10 text-primary">
                                    <i class="fas fa-project-diagram text-lg"></i>
                                </span>
                                <h2 class="text-xl font-semibold">Workspace Projects</h2>
                            </div>
                            <span class="badge bg-primary/10 text-primary border-0 font-medium">0 projects</span>
                        </div>
                    </div>
                    
                    <div class="flex flex-col items-center justify-center py-16 text-center p-6">
                        <div class="p-6 rounded-full bg-base-200 mb-4">
                            <i class="fas fa-folder-open text-3xl text-base-content/50"></i>
                        </div>
                        <h3 class="text-xl font-bold mb-2">No projects yet</h3>
                        <p class="text-base-content/70 max-w-md mb-6">Create your first project to start managing tasks and collaborating with your team</p>
                        
                        @if($userRole === 'owner' || $userRole === 'admin')
                            <x-button 
                                @click="$wire.showCreateProjectModal = true" 
                                icon="fas.plus" 
                                class="btn-primary hover:shadow-lg transition-all duration-300"
                            >
                                Create Your First Project
                            </x-button>
                        @endif
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($projects as $project)
                        <div class="card bg-base-100 shadow-xl hover:shadow-2xl transition-all duration-300 border border-base-300 overflow-hidden h-full">
                            <div class="bg-primary/5 p-4 border-b border-base-300">
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center gap-2">
                                        <div class="avatar placeholder">
                                            <div class="bg-primary text-primary-content rounded-lg w-8 h-8 flex items-center justify-center">
                                                <span class="font-bold">{{ $project->key }}</span>
                                            </div>
                                        </div>
                                        <h3 class="font-bold truncate">{{ $project->name }}</h3>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card-body p-5">
                                @if($project->description)
                                    <p class="text-base-content/80 mb-4 line-clamp-2">{{ $project->description }}</p>
                                @else
                                    <p class="text-base-content/50 italic mb-4">No description provided</p>
                                @endif
                                
                                <div class="flex items-center gap-2 p-3 bg-base-200/50 rounded-lg">
                                    <div class="avatar placeholder">
                                        <div class="bg-primary/10 text-primary rounded-full w-6 h-6 flex items-center justify-center">
                                            <span class="text-xs">{{ substr($project->owner->name, 0, 1) }}</span>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="text-xs text-base-content/70">Project Lead</span>
                                        <p class="text-sm font-medium">{{ $project->owner->name }}</p>
                                    </div>
                                </div>
                                
                                <div class="card-actions justify-end mt-4 pt-2 border-t border-base-200">
                                    <x-button 
                                        link="/projects/{{ $project->id }}" 
                                        icon="fas.arrow-right"
                                        class="btn-primary btn-sm hover:shadow-md transition-all duration-300"
                                    >
                                        Open Project
                                    </x-button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Create Project Modal -->
    <x-modal wire:model="showCreateProjectModal" name="create-project-modal">
        <x-card title="Create New Project" class="max-w-lg">
            <div class="flex items-center gap-3 mb-6 p-4 bg-primary/5 rounded-lg border border-primary/10">
                <div class="p-3 rounded-full bg-primary/10 text-primary">
                    <i class="fas fa-project-diagram text-xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-lg">New Project</h3>
                    <p class="text-sm text-base-content/70">Create a project to organize tasks and collaborate with your team</p>
                </div>
            </div>
            
            <form wire:submit="createProject" class="space-y-6">
                <div>
                    <x-input
                        wire:model="newProjectName"
                        label="Project Name"
                        placeholder="Enter project name"
                        icon="fas.signature"
                        class="transition-all duration-300 focus:ring-2 focus:ring-primary/30"
                        error="{{ $errors->first('newProjectName') }}"
                    />
                    <p class="text-xs text-base-content/60 mt-1">Choose a descriptive name for your project</p>
                </div>

                <div>
                    <x-textarea
                        wire:model="newProjectDescription"
                        label="Description (optional)"
                        placeholder="Enter project description"
                        icon="fas.align-left"
                        class="transition-all duration-300 focus:ring-2 focus:ring-primary/30"
                        error="{{ $errors->first('newProjectDescription') }}"
                        rows="4"
                    />
                    <p class="text-xs text-base-content/60 mt-1">Provide details about the project's purpose and goals</p>
                </div>

                <div>
                    <div class="flex items-end gap-2">
                        <div class="flex-1">
                            <x-input
                                wire:model="newProjectKey"
                                label="Project Key"
                                placeholder="e.g., PRJ"
                                icon="fas.key"
                                class="transition-all duration-300 focus:ring-2 focus:ring-primary/30"
                                required
                                error="{{ $errors->first('newProjectKey') }}"
                            />
                        </div>
                        <x-button 
                            x-bind:disabled="!$wire.newProjectName" 
                            type="button" 
                            wire:click="generateProjectKey"
                            icon="fas.magic"
                            label="Generate" 
                            class="btn-secondary btn-sm hover:shadow-md transition-all duration-300"
                        />
                    </div>
                    <p class="text-xs text-base-content/60 mt-1">This will be used as a prefix for all tasks (e.g., PRJ-123)</p>
                </div>
                
                <div class="pt-4 mt-2 border-t border-base-200">
                    <div class="flex items-center gap-2 p-3 bg-info/10 rounded-lg text-info">
                        <i class="fas fa-info-circle"></i>
                        <p class="text-sm">Project will be created with default statuses and workflows</p>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row justify-end gap-3 pt-4 border-t border-base-200">
                    <x-button 
                        @click="$dispatch('close-modal', 'create-project-modal')" 
                        class="btn-ghost hover:bg-base-200 transition-all duration-300"
                    >
                        Cancel
                    </x-button>
                    <x-button 
                        type="submit" 
                        icon="fas.rocket" 
                        class="btn-primary hover:shadow-lg transition-all duration-300"
                    >
                        Create Project
                    </x-button>
                </div>
            </form>
        </x-card>
    </x-modal>
</div>
