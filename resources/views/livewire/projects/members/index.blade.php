<?php
new class extends Livewire\Volt\Component {
    public \App\Models\Project $project;
    public $newMemberEmail = '';
    public $newMemberRole = 'member';
    public $roles = [
        'admin' => 'Administrator',
        'member' => 'Team Member',
        'viewer' => 'Viewer'
    ];

    public function mount()
    {
        $this->project = \App\Models\Project::with(['teamMembers'])->findOrFail($this->project->id);
    }

    public function inviteMember()
    {
        $this->validate([
            'newMemberEmail' => 'required|email|exists:users,email',
            'newMemberRole' => 'required|in:admin,member,viewer',
        ], [
            'newMemberEmail.exists' => 'Bu e-posta adresi ile kayıtlı bir kullanıcı bulunamadı.'
        ]);

        // Kullanıcıyı e-posta adresine göre bul
        $user = \App\Models\User::where('email', $this->newMemberEmail)->first();

        // Kullanıcı zaten projede mi kontrol et
        if ($this->project->teamMembers()->where('user_id', $user->id)->exists()) {
            $this->addError('newMemberEmail', 'Bu kullanıcı zaten projenin bir üyesi.');
            return;
        }

        // Kullanıcıyı projeye ekle
        $this->project->teamMembers()->attach($user->id, [
            'role' => $this->newMemberRole,
        ]);

        // Formu temizle
        $this->newMemberEmail = '';
        $this->newMemberRole = 'member';

        // Projeyi yeniden yükle
        $this->project = \App\Models\Project::with(['teamMembers'])->findOrFail($this->project->id);

        $this->dispatch('notify', [
            'message' => 'Kullanıcı projeye başarıyla eklendi.',
            'type' => 'success'
        ]);
    }

    public function updateRole($userId, $role)
    {
        // Proje sahibinin rolünü değiştiremeyiz
        if ($this->project->user_id == $userId) {
            $this->dispatch('notify', [
                'message' => 'Proje sahibinin rolü değiştirilemez.',
                'type' => 'error'
            ]);
            return;
        }

        // Rolü güncelle
        $this->project->teamMembers()->updateExistingPivot($userId, [
            'role' => $role,
        ]);

        // Projeyi yeniden yükle
        $this->project = \App\Models\Project::with(['teamMembers'])->findOrFail($this->project->id);

        $this->dispatch('notify', [
            'message' => 'Üye rolü başarıyla güncellendi.',
            'type' => 'success'
        ]);
    }

    public function removeMember($userId)
    {
        // Proje sahibini çıkaramayız
        if ($this->project->user_id == $userId) {
            $this->dispatch('notify', [
                'message' => 'Proje sahibi projeden çıkarılamaz.',
                'type' => 'error'
            ]);
            return;
        }

        // Kullanıcıyı projeden çıkar
        $this->project->teamMembers()->detach($userId);

        // Projeyi yeniden yükle
        $this->project = \App\Models\Project::with(['teamMembers'])->findOrFail($this->project->id);

        $this->dispatch('notify', [
            'message' => 'Üye projeden başarıyla çıkarıldı.',
            'type' => 'success'
        ]);
    }
}
?>

<div>
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold">{{ $project->name }} - Team Members</h1>
            <p class="text-gray-500">Manage project team members and their roles</p>
        </div>
        <div>
            <x-button link="{{ route('projects.show', $project) }}" icon="fas.arrow-left" class="btn-outline">
                Back to Project
            </x-button>
        </div>
    </div>

    <!-- Invite New Member Form -->
    <div class="card bg-base-100 shadow-xl mb-6">
        <div class="card-body">
            <h2 class="card-title">Invite New Member</h2>

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
                        Invite Member
                    </x-button>
                </div>
            </form>
        </div>
    </div>

    <!-- Team Members List -->
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <h2 class="card-title">Team Members</h2>

            <div class="overflow-x-auto mt-4">
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
                        @foreach($project->teamMembers as $member)
                            <tr>
                                <td class="flex items-center gap-2">
                                    <div class="avatar placeholder">
                                        <div class="bg-neutral text-neutral-content rounded-full w-8">
                                            <span>{{ substr($member->name, 0, 1) }}</span>
                                        </div>
                                    </div>
                                    {{ $member->name }}
                                    @if($project->user_id === $member->id)
                                        <x-badge color="primary" size="sm">Owner</x-badge>
                                    @endif
                                </td>
                                <td>{{ $member->email }}</td>
                                <td>
                                    @if($project->user_id === $member->id)
                                        <x-badge color="primary">Administrator</x-badge>
                                    @else
                                        <x-dropdown>
                                            <x-slot:trigger>
                                                <x-button class="btn-sm">
                                                    {{ $roles[$member->pivot->role] }}
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
                                    @if($project->user_id !== $member->id)
                                        <x-button
                                            wire:click="removeMember({{ $member->id }})"
                                            wire:confirm="Are you sure you want to remove this member from the project?"
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

                        @if($project->teamMembers->isEmpty())
                            <tr>
                                <td colspan="4" class="text-center py-4">No team members found.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
