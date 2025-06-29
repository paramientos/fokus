<?php

new class extends Livewire\Volt\Component {
    use \Mary\Traits\Toast;

    public $name = '';
    public $description = '';
    public $is_shared = false;
    public $workspace_id;
    public $master_password = '';
    public $master_password_confirmation = '';
    public $use_master_password = true;

    public $workspaces = [];

    public function mount()
    {
        $this->workspaces = \App\Models\Workspace::whereHas('members', function ($query) {
            $query->where('user_id', auth()->id());
        })->get();

        // Set default workspace if available
        if ($this->workspaces->isNotEmpty()) {
            $this->workspace_id = $this->workspaces->first()->id;
        }
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'workspace_id' => 'required|exists:workspaces,id',
            'is_shared' => 'boolean',
            'master_password' => $this->use_master_password ? 'required|string|min:8|confirmed' : 'nullable',
        ]);

        // Create the vault
        $vault = \App\Models\PasswordVault::create([
            'name' => $this->name,
            'description' => $this->description,
            'workspace_id' => $this->workspace_id,
            'user_id' => auth()->id(),
            'is_shared' => $this->is_shared,
            'master_password_hash' => $this->use_master_password && $this->master_password ?
                password_hash($this->master_password, PASSWORD_BCRYPT) : null,
        ]);

        // Create a default "Uncategorized" category
        \App\Models\PasswordCategory::create([
            'password_vault_id' => $vault->id,
            'name' => 'Uncategorized',
            'color' => 'gray-500',
            'icon' => 'fas fa-folder',
        ]);

        $this->success('Password vault created successfully.');

        return redirect()->route('password-manager.vaults.show', $vault);
    }
}

?>

<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Create Password Vault</h1>
        <div>
            <x-button href="{{ route('password-manager.vaults.index') }}" icon="fas.arrow-left" variant="outline">
                Back to Vaults
            </x-button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <x-card>
                <form wire:submit="save">
                    <div class="space-y-6">
                        <div>
                            <x-input label="Name" id="name" wire:model="name" placeholder="e.g. Personal Passwords" />
                        </div>

                        <div>
                            <x-textarea label="Description" id="description" wire:model="description" placeholder="Describe the purpose of this vault" rows="3" />
                        </div>

                        <div>
                            <x-select label="Workspace" id="workspace_id" wire:model="workspace_id" :options="$workspaces->select('name', 'id')" />
                            <div class="mt-1 text-sm text-gray-500">
                                The workspace this vault belongs to.
                            </div>
                        </div>

                        <div>
                            <x-checkbox id="is_shared" wire:model="is_shared" label="Share with workspace members" />
                            <div class="mt-1 text-sm text-gray-500">
                                If checked, all members of the selected workspace will have access to this vault.
                            </div>
                        </div>

                        <div class="border-t pt-4">
                            <x-checkbox id="use_master_password" wire:model.live="use_master_password" label="Protect with master password" disabled checked />
                            <div class="mt-1 text-sm text-gray-500">
                                Add an extra layer of security with a master password.
                            </div>
                        </div>

                        @if($use_master_password)
                            <div class="bg-gray-50 p-4 rounded-lg space-y-4">
                                <div>
                                    <x-input label="Master Password" id="master_password" type="password" wire:model="master_password" />
                                </div>

                                <div>
                                    <x-input label="Confirm Master Password" id="master_password_confirmation" type="password" wire:model="master_password_confirmation" />
                                </div>

                                <div class="text-sm text-amber-600 bg-amber-50 p-3 rounded-lg flex items-start">
                                    <i class="fas fa-exclamation-triangle mr-2 mt-0.5"></i>
                                    <div>
                                        <strong>Important:</strong> If you forget your master password, you will not be able to access the passwords in this vault. There is no way to recover a lost master password.
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="flex justify-end">
                            <x-button type="submit" icon="fas.save">
                                Create Vault
                            </x-button>
                        </div>
                    </div>
                </form>
            </x-card>
        </div>

        <div class="lg:col-span-1">
            <x-card title="About Password Vaults">
                <div class="space-y-4 text-sm text-gray-600">
                    <p>
                        <strong class="text-gray-800">Password vaults</strong> are secure containers for your passwords and sensitive information.
                    </p>

                    <div class="flex items-start">
                        <div class="rounded-full bg-primary-100 p-2 mr-3 mt-1">
                            <i class="fas fa-users text-primary-500"></i>
                        </div>
                        <div>
                            <strong class="text-gray-800">Shared vaults</strong> allow team members in your workspace to access the passwords.
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="rounded-full bg-primary-100 p-2 mr-3 mt-1">
                            <i class="fas fa-lock text-primary-500"></i>
                        </div>
                        <div>
                            <strong class="text-gray-800">Master passwords</strong> add an extra layer of security, requiring an additional password to access the vault.
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="rounded-full bg-primary-100 p-2 mr-3 mt-1">
                            <i class="fas fa-folder text-primary-500"></i>
                        </div>
                        <div>
                            <strong class="text-gray-800">Categories</strong> help you organize passwords within a vault. A default "Uncategorized" category will be created automatically.
                        </div>
                    </div>
                </div>
            </x-card>
        </div>
    </div>
</div>
