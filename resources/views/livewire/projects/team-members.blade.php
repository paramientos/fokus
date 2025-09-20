<?php

use App\Models\Project;

new class extends Livewire\Volt\Component {
    use \Mary\Traits\Toast;

    public Project $project;
    public $roles = [
        'admin' => 'Administrator',
        'member' => 'Team Member',
        'viewer' => 'Viewer'
    ];

    public function mount()
    {
        $this->project = Project::with(['teamMembers', 'workspace.members', 'workspace.invitations'])->findOrFail($this->project->id);
    }

    public function addWorkspaceMember($userId, $role = 'member')
    {
        // Check if user is already project member
        if ($this->project->teamMembers()->where('user_id', $userId)->exists()) {
            $this->error('This user is already a project member.');
            return;
        }

        // Add to project
        $this->project->teamMembers()->attach($userId, [
            'role' => $role,
        ]);

        // Reload project
        $this->project = Project::with(['teamMembers', 'workspace.members', 'workspace.invitations'])->findOrFail($this->project->id);

        $this->success('User added to project successfully.');
    }

    public function updateRole($userId, $role)
    {
        // Can't change project owner role
        if ($this->project->user_id == $userId) {
            $this->error('Project owner role cannot be changed.');
            return;
        }

        // Update role
        $this->project->teamMembers()->updateExistingPivot($userId, [
            'role' => $role,
        ]);

        // Reload project
        $this->project = Project::with(['teamMembers', 'workspace.members', 'workspace.invitations'])->findOrFail($this->project->id);

        $this->success('Member role updated successfully.');
    }

    public function removeMember($userId)
    {
        // Can't remove project owner
        if ($this->project->user_id == $userId) {
            $this->error('Project owner cannot be removed.');
            return;
        }

        // Remove user from project
        $this->project->teamMembers()->detach($userId);

        // Reload project
        $this->project = Project::with(['teamMembers', 'workspace.members', 'workspace.invitations'])->findOrFail($this->project->id);

        $this->success('Member removed successfully.');
    }

    public function with(): array
    {
        $projectMemberIds = $this->project->teamMembers->pluck('id')->toArray();

        return [
            'availableWorkspaceMembers' => $this->project->workspace->members()
                ->whereNotIn('users.id', $projectMemberIds)
                ->get()
        ];
    }
}
?>

<div>
    <!-- Add Team Members Info -->
    <div class="card bg-base-100 shadow-xl border border-base-300 overflow-hidden mb-6">
        <div class="bg-info/5 p-4 border-b border-base-300 flex items-center gap-3">
            <span class="p-2 rounded-full bg-info/10 text-info">
                <i class="fas fa-info-circle text-lg"></i>
            </span>
            <h2 class="text-xl font-semibold">How to Add Team Members</h2>
        </div>
        <div class="card-body p-5">
            <div class="flex flex-col md:flex-row gap-6">
                <div class="flex-1 p-4 border border-info/20 rounded-lg bg-info/5">
                    <div class="flex items-start gap-3">
                        <div class="p-2 rounded-full bg-info/10 text-info">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <h3 class="font-bold mb-2">Workspace Members</h3>
                            <p class="text-base-content/80">Select from available workspace members below to add them directly to this project.</p>
                        </div>
                    </div>
                </div>
                
                <div class="flex-1 p-4 border border-primary/20 rounded-lg bg-primary/5">
                    <div class="flex items-start gap-3">
                        <div class="p-2 rounded-full bg-primary/10 text-primary">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div>
                            <h3 class="font-bold mb-2">New Users</h3>
                            <p class="text-base-content/80">To invite someone who isn't in your workspace yet, go to your
                                <a href="{{ route('workspaces.members', $project->workspace) }}" class="text-primary hover:underline font-medium transition-colors duration-200">Workspace
                                Members</a> and invite them there first.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Current Team Members -->
    <div class="card bg-base-100 shadow-xl border border-base-300 overflow-hidden mb-6">
        <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center gap-3">
            <span class="p-2 rounded-full bg-primary/10 text-primary">
                <i class="fas fa-user-friends text-lg"></i>
            </span>
            <h2 class="text-xl font-semibold">Current Team Members</h2>
        </div>

        <div class="card-body p-0">
            @if($project->teamMembers->isEmpty())
                <div class="flex flex-col items-center justify-center py-12 text-center p-5">
                    <div class="p-6 rounded-full bg-base-200 mb-4">
                        <i class="fas fa-users text-3xl text-base-content/50"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2">No team members yet</h3>
                    <p class="text-base-content/70 max-w-md mb-6">Add members from your workspace to collaborate on this project</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <thead class="bg-base-200/50">
                            <tr>
                                <th>Member</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($project->teamMembers as $member)
                                <tr class="hover:bg-base-200/30 transition-colors duration-150">
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <div class="avatar">
                                                <div class="bg-primary/10 text-primary rounded-lg w-10 h-10 flex items-center justify-center">
                                                    <span class="font-medium">{{ strtoupper(substr($member->name, 0, 2)) }}</span>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="font-medium">{{ $member->name }}</div>
                                                @if($member->id === $project->user_id)
                                                    <div class="text-xs text-base-content/70">Project Owner</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $member->email }}</td>
                                    <td>
                                        @if($member->id === $project->user_id)
                                            <div class="badge badge-primary">Owner</div>
                                        @else
                                            <x-select
                                                wire:change="updateRole({{ $member->id }}, $event.target.value)"
                                                :options="collect($roles)->map(function($label, $value) {
                                                        return ['name' => $label, 'id' => $value];
                                                    })->values()"
                                                :value="$member->pivot->role"
                                                class="select-sm min-w-[140px] transition-all duration-300 focus:ring-2 focus:ring-primary/30"
                                            />
                                        @endif
                                    </td>
                                    <td>
                                        <div class="badge badge-success badge-sm">Active</div>
                                    </td>
                                    <td>
                                        @if($member->id !== $project->user_id)
                                            <x-button
                                                wire:click="removeMember({{ $member->id }})"
                                                icon="fas.user-minus"
                                                class="btn-sm btn-ghost hover:bg-error/10 hover:text-error transition-all duration-200"
                                                wire:confirm="Are you sure you want to remove {{ $member->name }} from this project?"
                                                tooltip="Remove Member"
                                            />
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    @if($availableWorkspaceMembers->count() > 0)
        <div class="card bg-base-100 shadow-xl border border-base-300 overflow-hidden mb-6">
            <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center gap-3">
                <span class="p-2 rounded-full bg-primary/10 text-primary">
                    <i class="fas fa-user-plus text-lg"></i>
                </span>
                <h2 class="text-xl font-semibold">Available Workspace Members</h2>
            </div>
            <div class="card-body p-5">
                <p class="text-base-content/70 mb-6">These workspace members can be added to the project directly.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($availableWorkspaceMembers as $member)
                        <div class="card bg-base-200 shadow-md hover:shadow-lg transition-all duration-300 border border-base-300">
                            <div class="card-body p-5">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="avatar">
                                        <div class="bg-primary/10 text-primary rounded-lg w-12 h-12 flex items-center justify-center">
                                            <span class="font-medium">{{ strtoupper(substr($member->name, 0, 2)) }}</span>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <div class="font-semibold">{{ $member->name }}</div>
                                        <div class="text-xs text-base-content/70">{{ $member->email }}</div>
                                    </div>
                                </div>
                                
                                <x-dropdown class="w-full">
                                    <x-slot:trigger>
                                        <x-button
                                            class="btn-sm btn-primary w-full hover:shadow-md transition-all duration-300"
                                            icon="fas.user-plus"
                                        >
                                            Add to Project <i class="fas fa-chevron-down ml-2"></i>
                                        </x-button>
                                    </x-slot:trigger>
                                    <x-menu>
                                        <x-menu-item wire:click="addWorkspaceMember({{ $member->id }}, 'admin')">
                                            <i class="fas fa-user-shield mr-2"></i> Add as Administrator
                                        </x-menu-item>
                                        <x-menu-item wire:click="addWorkspaceMember({{ $member->id }}, 'member')">
                                            <i class="fas fa-user mr-2"></i> Add as Team Member
                                        </x-menu-item>
                                        <x-menu-item wire:click="addWorkspaceMember({{ $member->id }}, 'viewer')">
                                            <i class="fas fa-eye mr-2"></i> Add as Viewer
                                        </x-menu-item>
                                    </x-menu>
                                </x-dropdown>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Pending Invitations -->
    @if($project->workspace->invitations->where('accepted_at', null)->count() > 0)
        <div class="card bg-base-100 shadow-xl border border-base-300 overflow-hidden">
            <div class="bg-warning/5 p-4 border-b border-base-300 flex items-center gap-3">
                <span class="p-2 rounded-full bg-warning/10 text-warning">
                    <i class="fas fa-clock text-lg"></i>
                </span>
                <h2 class="text-xl font-semibold">Pending Workspace Invitations</h2>
            </div>
            <div class="card-body p-5">
                <p class="text-base-content/70 mb-6">These users have been invited to the workspace but haven't accepted yet.</p>

                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <thead class="bg-base-200/50">
                            <tr>
                                <th>Email</th>
                                <th>Invited By</th>
                                <th>Expires</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($project->workspace->invitations->where('accepted_at', null) as $invitation)
                                <tr class="hover:bg-base-200/30 transition-colors duration-150">
                                    <td class="font-medium">{{ $invitation->email }}</td>
                                    <td>{{ $invitation->invitedBy->name }}</td>
                                    <td>
                                        <div class="flex flex-col">
                                            <span>{{ $invitation->expires_at->format('M d, Y') }}</span>
                                            <span class="text-xs text-base-content/70">{{ $invitation->expires_at->diffForHumans() }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        @if($invitation->isExpired())
                                            <div class="badge badge-error">Expired</div>
                                        @else
                                            <div class="badge badge-warning">Pending</div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
