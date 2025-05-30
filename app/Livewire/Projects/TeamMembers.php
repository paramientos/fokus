<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Component;

class TeamMembers extends Component
{
    public Project $project;
    public $newMemberEmail = '';
    public $newMemberName = '';
    public $newMemberRole = 'member';
    public $roles = [
        'admin' => 'Administrator',
        'member' => 'Team Member',
        'viewer' => 'Viewer'
    ];
    
    public function mount()
    {
        $this->project = Project::with(['teamMembers'])->findOrFail($this->project->id);
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
            // Mail::to($user->email)->send(new InvitationMail($user, $password, $this->project));
            
            $this->dispatch('notify', [
                'message' => 'Yeni kullanıcı oluşturuldu ve projeye davet edildi. Şifre: ' . $password,
                'type' => 'success'
            ]);
        }
        
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
        $this->newMemberName = '';
        $this->newMemberRole = 'member';
        
        // Projeyi yeniden yükle
        $this->project = Project::with(['teamMembers'])->findOrFail($this->project->id);
        
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
        $this->project = Project::with(['teamMembers'])->findOrFail($this->project->id);
        
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
        $this->project = Project::with(['teamMembers'])->findOrFail($this->project->id);
        
        $this->dispatch('notify', [
            'message' => 'Üye projeden başarıyla çıkarıldı.',
            'type' => 'success'
        ]);
    }
    
    public function render()
    {
        return view('livewire.projects.team-members');
    }
}
