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
    <div class="card bg-base-100 shadow-xl mb-6">
        <div class="card-body">
            <h2 class="card-title">Add Team Members</h2>
            <div class="alert alert-info">
                <x-icon name="fas.info-circle" class="w-5 h-5"/>
                <div>
                    <h3 class="font-bold">How to add members to your project:</h3>
                    <div class="text-sm mt-1">
                        <p>• <strong>Workspace Members:</strong> Select from available workspace members below to add
                            them directly to this project.</p>
                        <p>• <strong>New Users:</strong> To invite someone who isn't in your workspace yet, go to your
                            <a href="{{ route('workspaces.members', $project->workspace) }}" class="link link-primary">Workspace
                                Members</a> and invite them there first.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Current Team Members -->
    <div class="card bg-base-100 shadow-xl mb-6">
        <div class="card-body">
            <h2 class="card-title">Current Team Members</h2>

            <div class="overflow-x-auto mt-4">
                <table class="table table-zebra w-full">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($project->teamMembers as $member)
                        <tr>
                            <td>
                                <div class="flex items-center space-x-3">
                                    <div class="avatar">
                                        <div class="mask mask-squircle w-12 h-12">
                                            <div
                                                class="bg-primary text-primary-content flex items-center justify-center">
                                                {{ strtoupper(substr($member->name, 0, 2)) }}
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="font-bold">{{ $member->name }}</div>
                                        @if($member->id === $project->user_id)
                                            <div class="text-sm opacity-50">Project Owner</div>
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
                                        class="select-sm"
                                    />
                                @endif
                            </td>
                            <td>
                                <div class="badge badge-success">Active</div>
                            </td>
                            <td>
                                @if($member->id !== $project->user_id)
                                    <x-button
                                        wire:click="removeMember({{ $member->id }})"
                                        icon="fas.user-minus"
                                        class="btn-sm btn-error btn-outline"
                                        wire:confirm="Are you sure you want to remove this member?"
                                    />
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($availableWorkspaceMembers->count() > 0)
        <div class="card bg-base-100 shadow-xl mb-6">
            <div class="card-body">
                <h2 class="card-title">Available Workspace Members</h2>
                <p class="text-sm text-gray-500 mb-4">These workspace members can be added to the project directly.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($availableWorkspaceMembers as $member)
                        <div class="card bg-base-200 shadow">
                            <div class="card-body p-4">
                                <div class="flex items-center space-x-3">
                                    <div class="avatar">
                                        <div class="mask mask-squircle w-10 h-10">
                                            <div
                                                class="bg-primary text-primary-content flex items-center justify-center text-sm">
                                                {{ strtoupper(substr($member->name, 0, 2)) }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <div class="font-semibold text-sm">{{ $member->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $member->email }}</div>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <x-dropdown class="w-full">
                                        <x-slot:trigger>
                                            <x-button
                                                class="btn-sm btn-primary w-full"
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
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Pending Invitations -->
    @if($project->workspace->invitations->where('accepted_at', null)->count() > 0)
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h2 class="card-title">Pending Workspace Invitations</h2>
                <p class="text-sm text-gray-500 mb-4">These users have been invited to the workspace but haven't
                    accepted yet.</p>

                <div class="overflow-x-auto">
                    <table class="table table-zebra w-full">
                        <thead>
                        <tr>
                            <th>Email</th>
                            <th>Invited By</th>
                            <th>Expires</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($project->workspace->invitations->where('accepted_at', null) as $invitation)
                            <tr>
                                <td>{{ $invitation->email }}</td>
                                <td>{{ $invitation->invitedBy->name }}</td>
                                <td>{{ $invitation->expires_at->format('M d, Y') }}</td>
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
