<?php

namespace App\Livewire\Workspaces;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Component;
use Mary\Traits\Toast;

class Members extends Component
{
    use Toast;
    
    public Workspace $workspace;
    public $newMemberEmail = '';
    public $newMemberName = '';
    public $newMemberRole = 'member';
    public $roles = [
        'admin' => 'Administrator',
        'member' => 'Member',
        'viewer' => 'Viewer'
    ];
    
    public function mount()
    {
        $this->workspace = Workspace::with(['members'])->findOrFail($this->workspace->id);
    }
    
    public function inviteMember()
    {
        $this->validate([
            'newMemberEmail' => 'required|email',
            'newMemberRole' => 'required|in:admin,member,viewer',
            'newMemberName' => 'required_if:createNewUser,true|min:3',
        ]);
        
        // Kullanıcıyı e-posta adresine göre bul
        $user = User::where('email', $this->newMemberEmail)->first();
        
        // Kullanıcı bulunamadı mı? Yeni kullanıcı oluştur
        if (!$user) {
            // İsim girilmiş mi kontrol et
            if (empty($this->newMemberName)) {
                $this->addError('newMemberName', 'Yeni kullanıcı oluşturmak için isim girmelisiniz.');
                return;
            }
            
            // Rastgele şifre oluştur
            $password = Str::random(10);
            
            // Yeni kullanıcı oluştur
            $user = User::create([
                'name' => $this->newMemberName,
                'email' => $this->newMemberEmail,
                'password' => Hash::make($password),
            ]);
            
            // Burada e-posta gönderme işlemi yapılabilir
            // Mail::to($user->email)->send(new WorkspaceInvitationMail($user, $password, $this->workspace));
            
            $this->success('Yeni kullanıcı oluşturuldu ve workspace\'e davet edildi. Şifre: ' . $password);
        }
        
        // Kullanıcı zaten workspace'de mi kontrol et
        if ($this->workspace->members()->where('user_id', $user->id)->exists()) {
            $this->addError('newMemberEmail', 'Bu kullanıcı zaten workspace\'in bir üyesi.');
            return;
        }
        
        // Kullanıcıyı workspace'e ekle
        $this->workspace->members()->attach($user->id, [
            'role' => $this->newMemberRole,
        ]);
        
        // Formu temizle
        $this->newMemberEmail = '';
        $this->newMemberName = '';
        $this->newMemberRole = 'member';
        
        // Workspace'i yeniden yükle
        $this->workspace = Workspace::with(['members'])->findOrFail($this->workspace->id);
        
        $this->success('Kullanıcı workspace\'e başarıyla eklendi.');
    }
    
    public function updateRole($userId, $role)
    {
        // Workspace sahibinin rolünü değiştiremeyiz
        if ($this->workspace->owner_id == $userId) {
            $this->error('Workspace sahibinin rolü değiştirilemez.');
            return;
        }
        
        // Rolü güncelle
        $this->workspace->members()->updateExistingPivot($userId, [
            'role' => $role,
        ]);
        
        // Workspace'i yeniden yükle
        $this->workspace = Workspace::with(['members'])->findOrFail($this->workspace->id);
        
        $this->success('Üye rolü başarıyla güncellendi.');
    }
    
    public function removeMember($userId)
    {
        // Workspace sahibini çıkaramayız
        if ($this->workspace->owner_id == $userId) {
            $this->error('Workspace sahibi workspace\'den çıkarılamaz.');
            return;
        }
        
        // Kullanıcıyı workspace'den çıkar
        $this->workspace->members()->detach($userId);
        
        // Workspace'i yeniden yükle
        $this->workspace = Workspace::with(['members'])->findOrFail($this->workspace->id);
        
        $this->success('Üye workspace\'den başarıyla çıkarıldı.');
    }
    
    public function render()
    {
        return view('livewire.workspaces.members');
    }
}
