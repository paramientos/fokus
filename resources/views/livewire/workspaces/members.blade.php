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

    public function mount(): void
    {
        $this->workspace = Workspace::with(['members', 'invitations'])->findOrFail($this->workspace->id);
    }

    public function inviteMember(): void
    {
        $this->validate([
            'newMemberEmail' => 'required|email',
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
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7),
            'invited_by' => auth()->id(),
        ]);

        // Send notification
        Notification::route('mail', $this->newMemberEmail)
            ->notify(new WorkspaceInvitationNotification($invitation, $this->workspace));

        $this->newMemberEmail = '';

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

<div class="bg-gradient-to-br from-base-100 to-base-200 min-h-screen">
    <x-slot:title>{{ $workspace->name }} - Members</x-slot:title>

    <div class="p-6 max-w-7xl mx-auto">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <a href="/workspaces" class="text-base-content/70 hover:text-primary transition-colors duration-200">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="text-3xl font-bold text-primary">{{ $workspace->name }}</h1>
                </div>
                <p class="text-base-content/70">Manage workspace members and their roles</p>
            </div>

            <div class="flex items-center gap-2">
                <div class="avatar-group -space-x-3">
                    @foreach($workspace->members->take(5) as $member)
                        <div class="avatar placeholder">
                            <div class="bg-primary/10 text-primary rounded-full w-8 h-8 ring ring-base-100">
                                <span class="text-xs font-medium">{{ substr($member->name, 0, 1) }}</span>
                            </div>
                        </div>
                    @endforeach
                    
                    @if($workspace->members->count() > 5)
                        <div class="avatar placeholder">
                            <div class="bg-base-300 text-base-content rounded-full w-8 h-8 ring ring-base-100">
                                <span class="text-xs font-medium">+{{ $workspace->members->count() - 5 }}</span>
                            </div>
                        </div>
                    @endif
                </div>
                <span class="text-sm font-medium">{{ $workspace->members->count() }} members</span>
            </div>
        </div>

        <livewire:components.all-info-component/>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column: Invite Form -->
            <div class="lg:col-span-1">
                <div class="card bg-base-100 shadow-xl border border-base-300 overflow-hidden h-full">
                    <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center gap-3">
                        <span class="p-2 rounded-full bg-primary/10 text-primary">
                            <i class="fas fa-user-plus text-lg"></i>
                        </span>
                        <h2 class="text-xl font-semibold">Invite Members</h2>
                    </div>

                    <div class="card-body p-5">
                        <p class="text-base-content/80 mb-6">Invite new members to collaborate in this workspace</p>

                        <form wire:submit="inviteMember" class="space-y-6">
                            <div>
                                <x-input
                                    wire:model="newMemberEmail"
                                    placeholder="colleague@example.com"
                                    label="Email Address"
                                    icon="fas.envelope"
                                    class="transition-all duration-300 focus:ring-2 focus:ring-primary/30"
                                    error="{{ $errors->first('newMemberEmail') }}"
                                />
                                <p class="text-xs text-base-content/60 mt-1">The invitation will be valid for 7 days</p>
                            </div>

                            <x-button 
                                type="submit" 
                                icon="fas.paper-plane" 
                                class="btn-primary w-full hover:shadow-lg transition-all duration-300"
                            >
                                Send Invitation
                            </x-button>
                        </form>
                        
                        @if($workspace->invitations->where('accepted_at', null)->count() > 0)
                            <div class="mt-8 pt-6 border-t border-base-200">
                                <h3 class="font-medium mb-4 flex items-center gap-2">
                                    <i class="fas fa-clock text-warning"></i>
                                    <span>Pending Invitations</span>
                                    <span class="badge badge-warning badge-sm ml-2">{{ $workspace->invitations->where('accepted_at', null)->count() }}</span>
                                </h3>
                                
                                <div class="space-y-3">
                                    @foreach($workspace->invitations->where('accepted_at', null) as $invitation)
                                        <div class="bg-base-200/50 p-3 rounded-lg flex justify-between items-center">
                                            <div>
                                                <p class="font-medium">{{ $invitation->email }}</p>
                                                <p class="text-xs text-base-content/60">
                                                    Invited by {{ $invitation->invitedBy->name }} · 
                                                    Expires {{ $invitation->expires_at->format('M d, Y') }}
                                                </p>
                                            </div>
                                            <div>
                                                @if($invitation->isExpired())
                                                    <span class="badge badge-error">Expired</span>
                                                @else
                                                    <span class="badge badge-warning">Pending</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right Column: Members List -->
            <div class="lg:col-span-2">
                <div class="card bg-base-100 shadow-xl border border-base-300 overflow-hidden">
                    <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center gap-3">
                        <span class="p-2 rounded-full bg-primary/10 text-primary">
                            <i class="fas fa-users text-lg"></i>
                        </span>
                        <h2 class="text-xl font-semibold">Workspace Members</h2>
                    </div>

                    <div class="card-body p-0">
                        @if($workspace->members->isEmpty())
                            <div class="flex flex-col items-center justify-center py-16 text-center">
                                <div class="p-6 rounded-full bg-base-200 mb-4">
                                    <i class="fas fa-user-friends text-3xl text-base-content/50"></i>
                                </div>
                                <h3 class="text-xl font-bold mb-2">No members yet</h3>
                                <p class="text-base-content/70 max-w-md mb-4">Invite team members to start collaborating</p>
                            </div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="table w-full">
                                    <thead class="bg-base-200/50">
                                        <tr>
                                            <th>Member</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($workspace->members as $member)
                                            <tr class="hover:bg-base-200/30 transition-colors duration-150">
                                                <td class="flex items-center gap-3">
                                                    <div class="avatar placeholder">
                                                        <div class="bg-primary/10 text-primary rounded-full w-10 h-10 flex items-center justify-center">
                                                            <span class="font-medium">{{ substr($member->name, 0, 1) }}</span>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <p class="font-medium">{{ $member->name }}</p>
                                                        @if($workspace->owner_id === $member->id)
                                                            <span class="badge badge-primary badge-sm">Owner</span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td>{{ $member->email }}</td>
                                                <td>
                                                    @if($workspace->owner_id === $member->id)
                                                        <span class="badge badge-primary">Owner</span>
                                                    @else
                                                        <x-dropdown>
                                                            <x-slot:trigger>
                                                                <x-button class="btn-sm btn-outline transition-all duration-300">
                                                                    {{ ucfirst($member->pivot->role ?? 'Member') }}
                                                                    <i class="fas fa-chevron-down ml-2"></i>
                                                                </x-button>
                                                            </x-slot:trigger>

                                                            <x-menu>
                                                                <x-menu-item
                                                                    wire:click="updateRole({{ $member->id }}, 'admin')"
                                                                    class="{{ $member->pivot->role === 'admin' ? 'bg-primary/10' : '' }}"
                                                                >
                                                                    <i class="fas fa-user-shield mr-2 {{ $member->pivot->role === 'admin' ? 'text-primary' : 'text-base-content/70' }}"></i>
                                                                    Administrator
                                                                </x-menu-item>
                                                                <x-menu-item
                                                                    wire:click="updateRole({{ $member->id }}, 'member')"
                                                                    class="{{ $member->pivot->role === 'member' ? 'bg-primary/10' : '' }}"
                                                                >
                                                                    <i class="fas fa-user mr-2 {{ $member->pivot->role === 'member' ? 'text-primary' : 'text-base-content/70' }}"></i>
                                                                    Member
                                                                </x-menu-item>
                                                                <x-menu-item
                                                                    wire:click="updateRole({{ $member->id }}, 'viewer')"
                                                                    class="{{ $member->pivot->role === 'viewer' ? 'bg-primary/10' : '' }}"
                                                                >
                                                                    <i class="fas fa-eye mr-2 {{ $member->pivot->role === 'viewer' ? 'text-primary' : 'text-base-content/70' }}"></i>
                                                                    Viewer
                                                                </x-menu-item>
                                                            </x-menu>
                                                        </x-dropdown>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($workspace->owner_id !== $member->id)
                                                        <x-button
                                                            wire:click="removeMember({{ $member->id }})"
                                                            wire:confirm="Are you sure you want to remove {{ $member->name }} from the workspace?"
                                                            color="error"
                                                            class="btn-sm hover:shadow-md transition-all duration-300"
                                                            icon="fas.user-minus"
                                                        >
                                                            Remove
                                                        </x-button>
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
            </div>
        </div>
    </div>
</div>
