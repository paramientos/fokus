<?php

namespace App\Livewire\Workspaces;

use App\Models\User;
use App\Models\Workspace;
use App\Notifications\WorkspaceInvitationNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public Workspace $workspace;
    public string $newMemberEmail = '';
    public string $newMemberRole = 'member';
    public array $roles = [
        'admin' => 'Administrator',
        'member' => 'Member',
        'viewer' => 'Viewer'
    ];

    public function mount(): void
    {
        $this->workspace = Workspace::with(['members', 'invitations'])->findOrFail($this->workspace->id);
    }

    public function inviteMember(): void
    {
        $this->validate([
            'newMemberEmail' => 'required|email',
            'newMemberRole' => 'required|in:admin,member,viewer',
        ]);

        // Check if user already exists and is workspace member
        $existingUser = User::where('email', $this->newMemberEmail)->first();

        if ($existingUser && $this->workspace->members()->where('user_id', $existingUser->id)->exists()) {
            $this->addError('newMemberEmail', 'Bu kullanıcı zaten workspace\'in bir üyesi.');
            return;
        }

        // Check if invitation already exists
        $existingInvitation = $this->workspace->invitations()
            ->where('email', $this->newMemberEmail)
            ->first();
            
        if ($existingInvitation) {
            if ($existingInvitation->accepted_at) {
                // Invitation was accepted but user is no longer a member - delete old invitation
                $existingInvitation->delete();
            } else {
                // Pending invitation exists
                $this->addError('newMemberEmail', 'Bu e-posta adresine zaten davet gönderilmiş.');
                return;
            }
        }

        // Create workspace invitation
        $invitation = $this->workspace->invitations()->create([
            'email' => $this->newMemberEmail,
            'role' => $this->newMemberRole,
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7),
            'invited_by' => auth()->id(),
        ]);

        // Send notification
        Notification::route('mail', $this->newMemberEmail)
            ->notify(new WorkspaceInvitationNotification($invitation, $this->workspace));

        $this->newMemberEmail = '';
        $this->newMemberRole = 'member';

        $this->workspace = Workspace::with(['members', 'invitations'])->findOrFail($this->workspace->id);

        $this->success('Davet başarıyla gönderildi.');
    }

    public function updateRole($userId, $role): void
    {
        // Workspace sahibinin rolünü değiştiremeyiz
        if ($this->workspace->owner_id == $userId) {
            $this->error('Workspace sahibinin rolü değiştirilemez.');

            return;
        }

        $this->workspace->members()->updateExistingPivot($userId, [
            'role' => $role,
        ]);

        $this->workspace = Workspace::with(['members', 'invitations'])->findOrFail($this->workspace->id);

        $this->success('Üye rolü başarıyla güncellendi.');
    }

    public function removeMember($userId): void
    {
        if ($this->workspace->owner_id == $userId) {
            $this->error('Workspace sahibi workspace\'den çıkarılamaz.');

            return;
        }

        $this->workspace->members()->detach($userId);

        $this->workspace = Workspace::with(['members', 'invitations'])->findOrFail($this->workspace->id);

        $this->success('Üye workspace\'den başarıyla çıkarıldı.');
    }
}
?>

<div>
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <h2 class="card-title">Workspace Members</h2>
            <p class="text-gray-500">Manage workspace members and their roles</p>

            <!-- Invite New Member Form -->
            <div class="mt-6 p-4 bg-base-200 rounded-lg">
                <h3 class="font-medium mb-4">Invite New Member</h3>

                <form wire:submit="inviteMember" class="mt-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-2">
                            <x-input
                                wire:model="newMemberEmail"
                                placeholder="Email address"
                                label="Email Address"
                                error="{{ $errors->first('newMemberEmail') }}"
                            />
                        </div>
                        <div>
                            <x-select
                                wire:model="newMemberRole"
                                label="Role"
                                error="{{ $errors->first('newMemberRole') }}"
                                :options="collect($roles)->map(function($label, $value) {
                                return ['name' => $label, 'id' => $value];
                            })->values()"
                            />
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end">
                        <x-button type="submit" icon="fas.user-plus" color="primary">
                            Send Invitation
                        </x-button>
                    </div>
                </form>
            </div>

            <!-- Pending Invitations -->
            @if($workspace->invitations->where('accepted_at', null)->count() > 0)
                <div class="mt-6 p-4 bg-warning/10 rounded-lg">
                    <h3 class="font-medium mb-4 text-warning">Pending Invitations</h3>

                    <div class="overflow-x-auto">
                        <table class="table table-zebra w-full">
                            <thead>
                            <tr>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Invited By</th>
                                <th>Expires</th>
                                <th>Status</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($workspace->invitations->where('accepted_at', null) as $invitation)
                                <tr>
                                    <td>{{ $invitation->email }}</td>
                                    <td>
                                        <div class="badge badge-outline">{{ ucfirst($invitation->role) }}</div>
                                    </td>
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
            @endif

            <!-- Workspace Members List -->
            <div class="overflow-x-auto mt-6">
                <table class="table table-zebra w-full">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($workspace->members as $member)
                        <tr>
                            <td class="flex items-center gap-2">
                                <div class="avatar placeholder">
                                    <div class="bg-neutral text-neutral-content rounded-full w-8">
                                        <span>{{ substr($member->name, 0, 1) }}</span>
                                    </div>
                                </div>
                                {{ $member->name }}
                                @if($workspace->owner_id === $member->id)
                                    <x-badge color="primary" size="sm">Owner</x-badge>
                                @endif
                            </td>
                            <td>{{ $member->email }}</td>
                            <td>
                                @if($workspace->owner_id === $member->id)
                                    <x-badge color="primary">Owner</x-badge>
                                @else
                                    <x-dropdown>
                                        <x-slot:trigger>
                                            <x-button class="btn-sm">
                                                {{ $roles[$member->pivot->role] ?? 'Member' }}
                                                <i class="fas fa-chevron-down ml-2"></i>
                                            </x-button>
                                        </x-slot:trigger>

                                        <x-menu>
                                            @foreach($roles as $value => $label)
                                                <x-menu-item
                                                    wire:click="updateRole({{ $member->id }}, '{{ $value }}')"
                                                    class="{{ $member->pivot->role === $value ? 'bg-primary/10' : '' }}"
                                                >
                                                    {{ $label }}
                                                </x-menu-item>
                                            @endforeach
                                        </x-menu>
                                    </x-dropdown>
                                @endif
                            </td>
                            <td>
                                @if($workspace->owner_id !== $member->id)
                                    <x-button
                                        wire:click="removeMember({{ $member->id }})"
                                        wire:confirm="Are you sure you want to remove this member from the workspace?"
                                        color="error"
                                        class="btn-sm"
                                        icon="fas.user-minus"
                                    >
                                        Remove
                                    </x-button>
                                @endif
                            </td>
                        </tr>
                    @endforeach

                    @if($workspace->members->isEmpty())
                        <tr>
                            <td colspan="4" class="text-center py-4">No workspace members found.</td>
                        </tr>
                    @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
