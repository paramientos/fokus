<?php

namespace App\Livewire\Workspaces;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Str;
use Livewire\Component;
use Mary\Traits\Toast;

class Members extends Component
{
    use Toast;

    public Workspace $workspace;
    public string $newMemberEmail = '';
    public string $newMemberName = '';
    public string $newMemberRole = 'member';
    public array $roles = [
        'admin' => 'Administrator',
        'member' => 'Member',
        'viewer' => 'Viewer'
    ];

    public function mount(): void
    {
        $this->workspace = Workspace::with(['members'])->findOrFail($this->workspace->id);
    }

    public function inviteMember(): void
    {
        $this->validate([
            'newMemberEmail' => 'required|email',
            'newMemberRole' => 'required|in:admin,member,viewer',
            'newMemberName' => 'required_if:createNewUser,true|min:3',
        ]);

        $user = User::firstWhere('email', $this->newMemberEmail);

        if (!$user) {
            if (empty($this->newMemberName)) {
                $this->addError('newMemberName', 'Yeni kullanıcı oluşturmak için isim girmelisiniz.');

                return;
            }

            $password = Str::random(10);

            $user = User::create([
                'name' => $this->newMemberName,
                'email' => $this->newMemberEmail,
                'password' => bcrypt($password),
            ]);

            // Burada e-posta gönderme işlemi yapılabilir
            // Mail::to($user->email)->send(new WorkspaceInvitationMail($user, $password, $this->workspace));

            $this->success('Yeni kullanıcı oluşturuldu ve workspace\'e davet edildi. Şifre: ' . $password);
        }

        if ($this->workspace->members()->where('user_id', $user->id)->exists()) {
            $this->addError('newMemberEmail', 'Bu kullanıcı zaten workspace\'in bir üyesi.');

            return;
        }

        $this->workspace->members()->attach($user->id, [
            'role' => $this->newMemberRole,
        ]);

        $this->newMemberEmail = '';
        $this->newMemberName = '';
        $this->newMemberRole = 'member';

        $this->workspace = Workspace::with(['members'])->findOrFail($this->workspace->id);

        $this->success('Kullanıcı workspace\'e başarıyla eklendi.');
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

        $this->workspace = Workspace::with(['members'])->findOrFail($this->workspace->id);

        $this->success('Üye rolü başarıyla güncellendi.');
    }

    public function removeMember($userId): void
    {
        if ($this->workspace->owner_id == $userId) {
            $this->error('Workspace sahibi workspace\'den çıkarılamaz.');

            return;
        }

        $this->workspace->members()->detach($userId);

        $this->workspace = Workspace::with(['members'])->findOrFail($this->workspace->id);

        $this->success('Üye workspace\'den başarıyla çıkarıldı.');
    }

    public function render()
    {
        return view('livewire.workspaces.members');
    }
}
