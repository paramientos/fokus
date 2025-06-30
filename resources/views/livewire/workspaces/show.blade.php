<?php

use App\Models\Project;
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

                    <x-button @click="$wire.showCreateProjectModal = true" icon="fas.plus"
                              class="btn-primary">
                        Create Project
                    </x-button>
                @endif
            </div>
        </div>

        <!-- Workspace Danger Zone -->
        @if($userRole === 'owner')
        <div class="card bg-base-200 shadow-lg border border-error mb-8">
            <div class="card-body">
                <h2 class="card-title text-error"><i class="fas fa-exclamation-triangle mr-2"></i> Danger Zone</h2>
                <p class="mb-4 text-error-content">You can permanently delete this workspace, export all workspace data, or delete your own profile. These actions are <b>irreversible</b>.</p>
                <div class="flex flex-col md:flex-row gap-4">
                    <!-- Delete Workspace Button -->
                    <x-button wire:click="showWorkspaceDeleteModal" icon="fas.trash" class="btn-error">
                        Delete Workspace
                    </x-button>

                    <!-- Export Workspace Data Button -->
                    <form method="POST" action="{{ route('workspaces.export', $workspace->id) }}">
                        @csrf
                        <x-button type="submit" icon="fas.download" class="btn-warning">
                            Export Workspace Data
                        </x-button>
                    </form>

                    <!-- Delete Profile Button -->
                    <x-button wire:click="showProfileDeleteModal" icon="fas.user-slash" class="btn-neutral">
                        Delete My Profile
                    </x-button>
                </div>

                <!-- Delete Workspace Modal -->
                <x-modal wire:model="showDeleteWorkspaceModal" persistent class="backdrop-blur">
                    <x-card title="Confirm Workspace Deletion" subtitle="This action cannot be undone. All data will be permanently deleted.">
                        <div class="space-y-4">
                            <p class="text-error">Are you sure you want to delete this workspace? All projects, tasks, and data will be permanently removed.</p>

                            <div class="bg-base-200 p-4 rounded-lg">
                                <p class="font-medium mb-2">Security Check: Solve this math problem to continue</p>
                                <div class="flex items-center gap-4">
                                    <span class="text-xl">{{ $mathQuestion }} = </span>
                                    <x-input wire:model.live="mathAnswer" type="number" class="w-24" placeholder="?" autofocus />
                                </div>
                                @error('mathAnswer')
                                    <p class="text-error text-sm mt-1">{{ $message }}</p>
                                @enderror

                                <div class="mt-4">
                                    <label class="label cursor-pointer">
                                        <input type="checkbox" class="checkbox checkbox-error" wire:model.live="confirmDeleteWorkspace" />
                                        <span class="label-text ml-2">I understand this action is irreversible and I want to proceed</span>
                                    </label>
                                </div>
                            </div>

                            <div class="flex justify-end gap-3 mt-6">
                                <x-button label="Cancel" wire:click="$set('showDeleteWorkspaceModal', false)" />
                                <form method="POST" action="{{ route('workspaces.destroy', $workspace->id) }}">
                                    @csrf
                                    @method('DELETE')
                                    <x-button type="submit" label="Delete Workspace" icon="fas.trash" class="btn-error" :disabled="!$confirmDeleteWorkspace || $mathAnswer !== $correctAnswer" />
                                </form>
                            </div>
                        </div>
                    </x-card>
                </x-modal>

                <!-- Delete Profile Modal -->
                <x-modal wire:model="showDeleteProfileModal" persistent class="backdrop-blur">
                    <x-card title="Confirm Profile Deletion" subtitle="This action cannot be undone. Your account and all associated data will be permanently removed.">
                        <div class="space-y-4">
                            <p class="text-error">Are you sure you want to delete your profile? This will remove all your personal data from Fokus.</p>

                            <div class="bg-base-200 p-4 rounded-lg">
                                <p class="font-medium mb-2">Security Check: Solve this math problem to continue</p>
                                <div class="flex items-center gap-4">
                                    <span class="text-xl">{{ $mathQuestion }} = </span>
                                    <x-input wire:model.live="mathAnswerProfile" type="number" class="w-24" placeholder="?" />
                                </div>
                                @error('mathAnswerProfile')
                                    <p class="text-error text-sm mt-1">{{ $message }}</p>
                                @enderror

                                <div class="mt-4">
                                    <label class="label cursor-pointer">
                                        <input type="checkbox" class="checkbox checkbox-error" wire:model.live="confirmDeleteProfile" />
                                        <span class="label-text ml-2">I understand this action is irreversible and I want to proceed</span>
                                    </label>
                                </div>
                            </div>

                            <div class="flex justify-end gap-3 mt-6">
                                <x-button label="Cancel" wire:click="$set('showDeleteProfileModal', false)" />
                                <form method="POST" action="{{ route('profile.destroy') }}">
                                    @csrf
                                    @method('DELETE')
                                    <x-button type="submit" label="Delete My Profile" icon="fas.user-slash" class="btn-error" :disabled="!$confirmDeleteProfile || $mathAnswerProfile !== $correctAnswer" />
                                </form>
                            </div>
                        </div>
                    </x-card>
                </x-modal>
            </div>
        </div>
        @endif

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
                            <x-badge
                                color="{{ $userRole === 'owner' ? 'primary' : ($userRole === 'admin' ? 'secondary' : 'neutral') }}">
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
                        <!-- STORAGE USAGE -->
                        @php $storage = $workspace->storageUsage; @endphp
                        <div>
                            <div class="text-sm text-gray-500 mb-1">Storage Usage</div>
                            <div class="bg-base-200 rounded-xl p-4 flex flex-col gap-2 min-w-[220px] shadow-inner border border-base-300">
                                <div class="flex items-center gap-2 text-lg font-semibold">
                                    <i class="fas fa-database text-primary"></i>
                                    <span>{{ $storage ? $storage->formatted_used : '0B' }}</span>
                                    <span class="text-gray-400 font-normal text-base">/</span>
                                    <span>{{ $storage ? $storage->formatted_limit : '—' }}</span>
                                </div>
                                <div class="relative w-full h-3 rounded bg-base-300 overflow-hidden mt-1">
                                    <div class="absolute top-0 left-0 h-3 rounded bg-gradient-to-r from-primary to-accent transition-all"
                                         style="width: {{ $storage && $storage->limit_bytes > 0 ? min(100, round($storage->used_bytes / $storage->limit_bytes * 100)) : 0 }}%"></div>
                                    <div class="absolute inset-0 flex justify-center items-center text-xs text-gray-100 font-bold drop-shadow">
                                        {{ $storage ? $storage->usage_percent : 0 }}%
                                    </div>
                                </div>
                                <div class="flex justify-between text-xs mt-1">
                                    <span class="text-gray-400 flex items-center gap-1"><i class="fas fa-layer-group"></i>Plan: <span class="font-semibold text-primary">{{ $storage ? ucfirst($storage->plan_name) : '—' }}</span></span>
                                    <span class="text-gray-400 flex items-center gap-1"><i class="fas fa-users"></i>{{ $workspace->members->count() }} members</span>
                                </div>
                            </div>
                        </div>
                        <!-- END STORAGE USAGE -->
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
                            <x-button link="/projects/{{ $project->id }}" icon="fas.arrow-right"
                                      class="btn-sm btn-primary">
                                Open Project
                            </x-button>
                        </div>
                    </div>
                </div>
            @endforeach

            @if($projects->isEmpty())
                <div
                    class="col-span-full flex flex-col items-center justify-center py-12 bg-base-100 rounded-lg shadow-sm">
                    <x-icon name="fas.folder" class="w-16 h-16 text-gray-400"/>
                    <p class="mt-4 text-lg font-medium">No projects found</p>
                    <p class="text-gray-500">Create a new project to get started</p>

                    @if($userRole === 'owner' || $userRole === 'admin')
                        <x-button @click="$wire.showCreateProjectModal = true" icon="fas.plus"
                                  class="btn-primary mt-4">
                            Create Project
                        </x-button>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- Create Project Modal -->
    <x-modal wire:model="showCreateProjectModal" name="create-project-modal">
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

                <div class="form-control">
                    <div class="flex items-end gap-2">
                        <div class="flex-1">
                            <x-input
                                wire:model="newProjectKey"
                                label="Project Key"
                                placeholder="e.g., PRJ"
                                required
                                error="{{ $errors->first('newProjectKey') }}"
                            />
                        </div>
                        <x-button x-bind:disabled="!$wire.newProjectName" type="button" wire:click="generateProjectKey" label="Generate" class="btn-sm"/>
                    </div>
                    <span
                        class="text-sm text-gray-500 mt-1">This will be used as a prefix for all tasks (e.g., PRJ-123)</span>
                </div>

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
