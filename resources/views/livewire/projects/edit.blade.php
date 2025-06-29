<?php

new class extends Livewire\Volt\Component {
    public $project;
    public $name = '';
    public $key = '';
    public $description = '';
    public $avatar = '';
    public $is_active = true;
    public $gitRepositories = [];

    protected $rules = [
        'name' => 'required|min:3|max:255',
        'key' => 'required|min:2|max:10|alpha_num',
        'description' => 'nullable|max:1000',
        'avatar' => 'nullable|url|max:255',
        'is_active' => 'boolean',
    ];

    public function mount($project)
    {
        $this->project = \App\Models\Project::findOrFail($project);
        $this->name = $this->project->name;
        $this->key = $this->project->key;
        $this->description = $this->project->description;
        $this->avatar = $this->project->avatar;
        $this->is_active = $this->project->is_active;
        $this->gitRepositories = $this->project->gitRepositories()->get();

        // Özel doğrulama kuralı - key benzersiz olmalı ancak mevcut projenin anahtarı hariç
        $this->rules['key'] = 'required|min:2|max:10|alpha_num|unique:projects,key,' . $this->project->id;
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    public function generateKey()
    {
        if (empty($this->name)) {
            return;
        }

        $words = explode(' ', $this->name);
        if (count($words) > 1) {
            $this->key = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        } else {
            $this->key = strtoupper(substr($this->name, 0, 2));
        }

        // Anahtar benzersiz olmalı
        $count = 1;
        $originalKey = $this->key;
        while (\App\Models\Project::where('key', $this->key)->where('id', '!=', $this->project->id)->exists()) {
            $this->key = $originalKey . $count;
            $count++;
        }
    }

    public function save()
    {
        $this->validate();

        $this->project->update([
            'name' => $this->name,
            'key' => strtoupper($this->key),
            'description' => $this->description,
            'avatar' => $this->avatar,
            'is_active' => $this->is_active,
        ]);

        session()->flash('message', 'Proje başarıyla güncellendi!');

        return $this->redirect('/projects/' . $this->project->id, navigate: true);
    }
    
    public function removeRepository($repositoryId)
    {
        try {
            $repository = \App\Models\GitRepository::findOrFail($repositoryId);
            
            if ($repository->project_id !== $this->project->id) {
                $this->error('Bu repository bu projeye ait değil!');
                return;
            }
            
            $repositoryName = $repository->name;
            $repository->delete();
            
            $this->gitRepositories = $this->project->gitRepositories()->get();
            $this->success('Git repository başarıyla kaldırıldı: ' . $repositoryName);
        } catch (\Exception $e) {
            $this->error('Repository kaldırılırken bir hata oluştu: ' . $e->getMessage());
        }
    }
}

?>

<div>
    <x-slot:title>{{ $project->name }} - Düzenle</x-slot:title>

    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-primary">Edit Project</h1>
            <x-button link="/projects/{{ $project->id }}" label="Back to Project" icon="o-arrow-left" class="btn-ghost"/>
        </div>

        <div class="card bg-base-100 shadow-xl mb-6">
            <div class="card-body">
                <form wire:submit="save">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-control">
                            <x-input label="Project Name" wire:model="name" placeholder="Enter project name" required/>
                            @error('name') <span class="text-error text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-control">
                            <div class="flex items-end gap-2">
                                <div class="flex-1">
                                    <x-input label="Project Key" wire:model="key" placeholder="e.g., PRJ" required/>
                                    @error('key') <span class="text-error text-sm">{{ $message }}</span> @enderror
                                </div>
                                <x-button wire:click="generateKey" label="Generate" class="btn-outline btn-sm"/>
                            </div>
                        </div>

                        <div class="form-control md:col-span-2">
                            <x-textarea label="Description" wire:model="description" placeholder="Enter project description"/>
                            @error('description') <span class="text-error text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-control">
                            <x-input label="Avatar URL" wire:model="avatar" placeholder="https://example.com/avatar.png"/>
                            @error('avatar') <span class="text-error text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-control">
                            <x-checkbox label="Active" wire:model="is_active"/>
                            @error('is_active') <span class="text-error text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <x-button type="submit" label="Save Changes" icon="o-check" class="btn-primary"/>
                    </div>
                </form>
            </div>
        </div>

        <!-- Git Repository Integration -->
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h2 class="card-title text-xl font-bold mb-4">Git Repository Integration</h2>
                
                <!-- Connect with SSO -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-3">Connect with Single Sign-On</h3>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('git.sso.redirect', ['provider' => 'github', 'projectId' => $project->id]) }}" 
                           class="btn btn-outline">
                            <i class="fas fa-github mr-2"></i> Connect with GitHub
                        </a>
                        <a href="{{ route('git.sso.redirect', ['provider' => 'gitlab', 'projectId' => $project->id]) }}" 
                           class="btn btn-outline">
                            <i class="fas fa-gitlab mr-2"></i> Connect with GitLab
                        </a>
                    </div>
                    <p class="text-sm text-gray-500 mt-2">
                        Connect your project with GitHub or GitLab using OAuth authentication.
                        This will allow Fokus to access your repositories and create webhooks.
                    </p>
                </div>
                
                <!-- Connected Repositories -->
                <div>
                    <h3 class="text-lg font-semibold mb-3">Connected Repositories</h3>
                    
                    @if($gitRepositories->isEmpty())
                        <div class="alert">
                            <i class="fas fa-info-circle"></i>
                            <span>No Git repositories connected yet.</span>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="table table-zebra">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Provider</th>
                                        <th>Default Branch</th>
                                        <th>Authentication</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($gitRepositories as $repo)
                                        <tr>
                                            <td>
                                                <div class="font-medium">{{ $repo->name }}</div>
                                                <div class="text-sm opacity-50">{{ $repo->repository_url }}</div>
                                            </td>
                                            <td>
                                                <div class="badge badge-outline">
                                                    @if($repo->provider == 'github')
                                                        <i class="fas fa-github mr-1"></i> GitHub
                                                    @elseif($repo->provider == 'gitlab')
                                                        <i class="fas fa-gitlab mr-1"></i> GitLab
                                                    @elseif($repo->provider == 'bitbucket')
                                                        <i class="fas fa-bitbucket mr-1"></i> Bitbucket
                                                    @endif
                                                </div>
                                            </td>
                                            <td>{{ $repo->default_branch }}</td>
                                            <td>
                                                @if($repo->refresh_token)
                                                    <span class="badge badge-success">SSO</span>
                                                @else
                                                    <span class="badge">API Token</span>
                                                @endif
                                            </td>
                                            <td>
                                                <button wire:click="removeRepository({{ $repo->id }})" 
                                                        wire:confirm="Are you sure you want to remove this repository?"
                                                        class="btn btn-error btn-sm">
                                                    <i class="fas fa-trash"></i>
                                                </button>
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
