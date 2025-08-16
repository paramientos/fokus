<?php

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\Workspace;
use Mary\Traits\Toast;

new class extends Livewire\Volt\Component {
    use Toast;

    public Workspace $workspace;

    public Team $team;
    public $members;
    public $users;
    public $selectedUser = '';
    public $role = 'member';
    public $showAddMemberModal = false;
    public $roleFilter = 'all';
    public $activeTab = 'members';
    public $showAssignProjectModal = false;
    public $projects = [];
    public $selectedProject = '';

    public function mount()
    {
        $this->team = Team::with(['creator', 'projects'])->findOrFail($this->team->id);
        $this->members = TeamMember::where('team_id', $this->team->id)->with('user')->get();
        $this->users = User::whereIn('id', function ($query) {
            $query->select('user_id')->from('workspace_members')->where('workspace_id', $this->workspace->id);
        })->get();

        $this->loadProjects();
    }

    public function addMember()
    {
        $this->validate([
            'selectedUser' => 'required|exists:users,id',
            'role' => 'required|string',
        ]);

        if ($this->team->members()->where('user_id', $this->selectedUser)->exists()) {
            $this->error('User already in team!');
            return;
        }

        $this->team->members()->create([
            'user_id' => $this->selectedUser,
            'role' => $this->role,
            'joined_at' => now(),
        ]);

        $this->success('Member added!');
        $this->members = $this->team->members()->with('user')->get();
        $this->showAddMemberModal = false;
    }

    public function removeMember($memberId)
    {
        $member = $this->team->members()->findOrFail($memberId);
        $member->delete();
        $this->success('Member removed!');
        $this->members = $this->team->members()->with('user')->get();
    }

    public function filterByRole($role): void
    {
        $this->roleFilter = $role;

        if ($role === 'all') {
            $this->members = $this->team->members()->with('user')->get();
        } else {
            $this->members = $this->team->members()->with('user')->where('role', $role)->get();
        }
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function loadProjects()
    {
        $this->projects = \App\Models\Project::where('workspace_id', $this->workspace->id)
            ->where('is_active', true)
            ->where('is_archived', false)
            ->get();
    }

    public function assignProject()
    {
        if (empty($this->selectedProject)) {
            $this->error('Please select a project');
            return;
        }

        try {
            // Seçilen projeyi al
            $project = \App\Models\Project::findOrFail($this->selectedProject);

            // Takım üyelerini al
            $teamMembers = $this->team->members()->with('user')->get();

            if ($teamMembers->isEmpty()) {
                $this->error('This team has no members to assign to the project');
                return;
            }

            $assignedCount = 0;

            // Her takım üyesini projeye ekle
            foreach ($teamMembers as $member) {
                // Kullanıcının zaten projede olup olmadığını kontrol et
                $existingMember = $project->teamMembers()->where('user_id', $member->user_id)->exists();

                if (!$existingMember) {
                    // Kullanıcıyı projeye ekle (takımdaki rolünü koru)
                    $project->teamMembers()->attach($member->user_id, [
                        'role' => $member->role
                    ]);
                    $assignedCount++;
                }
            }

            // Takım-proje ilişkisini de kaydet
            \App\Models\TeamProject::create([
                'team_id' => $this->team->id,
                'project_id' => $this->selectedProject,
                'assigned_by' => auth()->id(),
                'assigned_at' => now(),
            ]);

            if ($assignedCount > 0) {
                $this->success("$assignedCount team members have been assigned to the project!");
            } else {
                $this->info('All team members were already assigned to this project');
            }

            $this->showAssignProjectModal = false;
            $this->selectedProject = '';

        } catch (\Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
        }
    }
}
?>

<div class="mx-auto max-w-6xl py-10">
    <x-card>
        <!-- Header -->
        <div class="mb-8 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-bold text-primary"><i class="fas fa-users mr-2"></i>{{ $team->name }}</h1>
                    <span class="badge badge-primary">{{ $members->count() }} members</span>
                </div>
                <div class="text-gray-500 mt-1">{{ $team->description ?: 'No description provided' }}</div>
                <div class="text-xs text-gray-400 mt-2">
                    <span class="inline-flex items-center gap-1">
                        <i class="fas fa-calendar-alt"></i> Created {{ $team->created_at->diffForHumans() }}
                    </span>
                    @if($team->creator)
                        <span class="inline-flex items-center gap-1 ml-3">
                            <i class="fas fa-user-shield"></i> Created by {{ $team->creator->name }}
                        </span>
                    @endif
                </div>
            </div>
            <div class="flex gap-2">
                <x-button link="{{ route('workspaces.teams.index', $workspace->id) }}" icon="fas.arrow-left"/>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs tabs-boxed mb-6 bg-base-200">
            <a class="tab {{ $activeTab === 'members' ? 'tab-active' : '' }}" wire:click="setActiveTab('members')">Members</a>
            <a class="tab {{ $activeTab === 'projects' ? 'tab-active' : '' }}" wire:click="setActiveTab('projects')">Projects</a>
            <a class="tab {{ $activeTab === 'activity' ? 'tab-active' : '' }}" wire:click="setActiveTab('activity')">Activity</a>
        </div>

        <!-- Members Section -->
        @if($activeTab === 'members')
            <div>
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold"><i class="fas fa-user-friends mr-2"></i>Team Members</h2>
                    <div class="flex gap-2">
                        <div class="dropdown dropdown-end">
                            <x-button class="btn-sm btn-ghost" tabindex="0" icon="fas.filter">
                                @if($roleFilter === 'all')
                                    All Members
                                @elseif($roleFilter === 'admin')
                                    Admins
                                @else
                                    Members
                                @endif
                            </x-button>

                            <div tabindex="0"
                                 class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-52 cursor-pointer">
                                <a class="dropdown-item" wire:click="filterByRole('all')">All Members</a>
                                <a class="dropdown-item" wire:click="filterByRole('admin')">Admins</a>
                                <a class="dropdown-item" wire:click="filterByRole('member')">Members</a>
                            </div>
                        </div>

                        <x-button @click="$wire.showAddMemberModal = true" class="btn-sm btn-primary">
                            <i class="fas fa-user-plus mr-1"></i> Add Member
                        </x-button>
                    </div>
                </div>

                <!-- Members List -->
                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <thead>
                        <tr>
                            <th>Member</th>
                            <th>Role</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($members as $member)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="avatar">
                                            <div
                                                class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center">
                                                {{ substr($member->user->name ?? 'U', 0, 1) }}
                                            </div>
                                        </div>
                                        <div>
                                            <div class="font-bold">{{ $member->user->name }}</div>
                                            <div class="text-sm opacity-50">{{ $member->user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                        <span
                                            class="badge {{ $member->role === 'admin' ? 'badge-primary' : 'badge-ghost' }}">
                                            {{ ucfirst($member->role) }}
                                        </span>
                                </td>
                                <td>{{ $member->joined_at ? $member->joined_at->diffForHumans() : 'N/A' }}</td>
                                <td>
                                    <div class="flex gap-1">
                                        <x-button wire:click="removeMember({{ $member->id }})"
                                                  class="btn-xs btn-ghost text-error">
                                            <i class="fas fa-user-minus"></i>
                                        </x-button>
                                        <div class="dropdown dropdown-end">
                                            <x-button class="btn-xs btn-ghost" tabindex="0">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </x-button>
                                            <div tabindex="0"
                                                 class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-52">
                                                <a class="dropdown-item">Change Role</a>
                                                <a class="dropdown-item text-error">Remove</a>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                @if($members->isEmpty())
                    <div class="flex flex-col items-center justify-center py-12 text-center">
                        <div class="text-6xl text-gray-300 mb-4">
                            <i class="fas fa-user-slash"></i>
                        </div>
                        <h3 class="text-xl font-medium text-gray-500 mb-2">No members yet</h3>
                        <p class="text-gray-400 mb-6">Add team members to start collaborating</p>
                        <x-button @click="$wire.showAddMemberModal = true" class="btn-primary">
                            <i class="fas fa-user-plus mr-2"></i> Add Member
                        </x-button>
                    </div>
                @endif
            </div>
        @endif

        @if($activeTab === 'projects')
            <!-- Projects Tab Content -->
            <div x-show="$wire.activeTab === 'projects'" class="mt-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-medium">Projects</h3>
                    <x-button @click="$wire.showAssignProjectModal = true" class="btn-primary">
                        <i class="fas fa-plus mr-2"></i> Assign Project
                    </x-button>
                </div>

                <!-- Projects List -->
                @if(!$projects->isEmpty())
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($team->projects as $project)
                            <div class="card bg-base-100 shadow-sm">
                                <div class="card-body">
                                    <div class="flex items-center justify-between">
                                        <h3 class="card-title">{{ $project->name }}</h3>
                                        <div class="badge badge-primary">{{ $project->key }}</div>
                                    </div>
                                    <p class="text-sm text-gray-500 line-clamp-2 mb-2">{{ $project->description }}</p>
                                    <div class="flex justify-between items-center text-xs text-gray-500">
                                        <span>Assigned {{ $project->pivot->assigned_at ? \Carbon\Carbon::parse($project->pivot->assigned_at)->diffForHumans() : 'recently' }}</span>
                                        <span>By {{ $project->pivot->assignedBy->name ?? 'Unknown' }}</span>
                                    </div>
                                    <div class="card-actions justify-end mt-4">
                                        <a href="{{ route('projects.show', $project) }}" class="btn btn-sm btn-outline">
                                            <i class="fas fa-external-link-alt mr-1"></i> View Project
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <!-- Empty State -->
                    <div class="flex flex-col items-center justify-center py-12 text-center">
                        <div class="text-6xl text-gray-300 mb-4">
                            <i class="fas fa-folder-open"></i>
                        </div>
                        <h3 class="text-xl font-medium text-gray-500 mb-2">No projects assigned yet</h3>
                        <p class="text-gray-400 mb-6">Assign projects to this team to start collaborating</p>
                        <x-button @click="$wire.showAssignProjectModal = true" class="btn-primary">
                            <i class="fas fa-plus mr-2"></i> Assign Project
                        </x-button>
                    </div>
                @endif
            </div>
        @endif

        @if($activeTab === 'activity')
            <!-- Activity Section -->
            <div>
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold"><i class="fas fa-history mr-2"></i>Team Activity</h2>
                    <div class="flex gap-2">
                        <div class="dropdown dropdown-end">
                            <x-button class="btn-sm btn-ghost" tabindex="0" icon="fas.filter">
                                All Activity
                            </x-button>
                            <div tabindex="0"
                                 class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-52 cursor-pointer">
                                <a class="dropdown-item">All Activity</a>
                                <a class="dropdown-item">Member Changes</a>
                                <a class="dropdown-item">Project Updates</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Activity Timeline -->
                <div class="flex flex-col gap-4 py-4">
                    <div class="flex gap-4">
                        <div class="flex flex-col items-center">
                            <div class="avatar">
                                <div
                                    class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center">
                                    A
                                </div>
                            </div>
                            <div class="w-0.5 bg-gray-200 grow mt-2"></div>
                        </div>
                        <div class="bg-base-200 rounded-lg p-4 grow">
                            <div class="flex justify-between items-start">
                                <div>
                                    <span class="font-medium">Admin User</span>
                                    <span class="text-gray-500"> created this team</span>
                                </div>
                                <span class="text-xs text-gray-400">2 days ago</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <div class="flex flex-col items-center">
                            <div class="avatar">
                                <div
                                    class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center">
                                    A
                                </div>
                            </div>
                            <div class="w-0.5 bg-gray-200 grow mt-2"></div>
                        </div>
                        <div class="bg-base-200 rounded-lg p-4 grow">
                            <div class="flex justify-between items-start">
                                <div>
                                    <span class="font-medium">Admin User</span>
                                    <span class="text-gray-500"> added </span>
                                    <span class="font-medium">John Doe</span>
                                    <span class="text-gray-500"> to the team</span>
                                </div>
                                <span class="text-xs text-gray-400">1 day ago</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </x-card>

    <!-- Add Member Modal -->
    <x-modal wire:model="showAddMemberModal" name="add-member-modal">
        <x-card title="Add Team Member">
            <form wire:submit.prevent="addMember">
                <div class="mb-4">
                    <x-choices-offline
                        single
                        :options="$users->select('name','id')->toArray()"
                        wire:model.defer="selectedUser"
                        label="Select Workspace Member"
                    />
                </div>

                <div class="mb-6">
                    <x-choices-offline
                        single
                        :options="[['id'=>'member','name'=>'Member'],['id'=>'admin','name'=>'Admin']]"
                        wire:model.defer="role"
                        label="Assign Role"
                    />
                    <div class="text-xs text-gray-500 mt-1">
                        <p><strong>Admin:</strong> Can manage team settings and members</p>
                        <p><strong>Member:</strong> Can view and participate in team activities</p>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <x-button @click="$wire.showAddMemberModal = false" class="btn-ghost">
                        Cancel
                    </x-button>
                    <x-button type="submit" class="btn-primary">
                        <i class="fas fa-user-plus mr-1"></i> Add Member
                    </x-button>
                </div>
            </form>
        </x-card>
    </x-modal>

    <!-- Assign Project Modal -->
    <x-modal wire:model="showAssignProjectModal" name="assign-project-modal">
        <x-card title="Assign Project to Team">
            <form wire:submit.prevent="assignProject">
                <div class="mb-4">
                    <x-choices-offline
                        single
                        :options="$projects->select('name','id')->toArray()"
                        wire:model.defer="selectedProject"
                        label="Select Project"
                    />
                </div>

                <div class="flex justify-end gap-2">
                    <x-button @click="$wire.showAssignProjectModal = false" class="btn-ghost">
                        Cancel
                    </x-button>
                    <x-button type="submit" class="btn-primary">
                        <i class="fas fa-plus mr-1"></i> Assign Project
                    </x-button>
                </div>
            </form>
        </x-card>
    </x-modal>
</div>
