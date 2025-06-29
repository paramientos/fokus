<?php

use App\Models\PasswordVault;
use Mary\Traits\Toast;

new class extends Livewire\Volt\Component {
    use Toast;

    public PasswordVault $vault;

    public $name;
    public $description;
    public $icon;
    public $color;
    public bool $change_master_password = false;
    public $current_master_password;
    public $new_master_password;
    public $new_master_password_confirmation;

    public function mount(\App\Models\PasswordVault $vault)
    {
        $this->vault = $vault;

        // Check if vault is locked
        if ($this->vault->is_locked) {
            $this->error('This vault is locked. Please unlock it first.');
            return redirect()->route('password-manager.vaults.show', $this->vault);
        }

        // Extend vault unlock time
        $this->vault->extendUnlockTime();

        // Fill form with vault data
        $this->name = $vault->name;
        $this->description = $vault->description;
        $this->icon = $vault->icon ?? 'fas.lock';
        $this->color = $vault->color ?? 'blue';

        // Check if vault is locked
        if ($this->vault->is_locked) {
            $this->error('This vault is locked. Please unlock it first.');
            return redirect()->route('password-manager.vaults.show', $this->vault);
        }
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'required|string',
            'color' => 'required|string',
            'current_master_password' => $this->change_master_password ? 'required' : 'nullable',
            'new_master_password' => $this->change_master_password ? 'required|min:8|confirmed' : 'nullable',
        ]);

        // Verify current master password if changing it
        if ($this->change_master_password) {
            if (!$this->vault->verifyMasterPassword($this->current_master_password)) {
                $this->error('Current master password is incorrect.');
                return;
            }
        }

        // Update vault details
        $this->vault->name = $this->name;
        $this->vault->description = $this->description;
        $this->vault->icon = $this->icon;
        $this->vault->color = $this->color;

        // Update master password if requested
        if ($this->change_master_password) {
            $this->vault->setMasterPassword($this->new_master_password);
            $this->success('Vault updated with new master password. All passwords have been re-encrypted.');
        } else {
            $this->vault->save();
            $this->success('Vault updated successfully.');
        }

        return redirect()->route('password-manager.vaults.show', $this->vault);
    }
};
?>

<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold flex items-center gap-2">
            <i class="fas fa-{{ $vault->icon }} text-{{ $vault->color }}-500"></i>
            Edit Vault: {{ $vault->name }}
        </h1>
        <div>
            <x-button link="{{ route('password-manager.vaults.show', $vault) }}" variant="ghost">
                <i class="fas fa-arrow-left mr-2"></i> Back to Vault
            </x-button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <x-card>
                <form wire:submit="save" class="space-y-6">
                    <div>
                        <x-input label="Vault Name" wire:model="name" placeholder="e.g. Personal Passwords"/>
                    </div>

                    <div>
                        <x-textarea label="Description (Optional)" wire:model="description"
                                    placeholder="Add a description for this vault..."/>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-600 block mb-1">Vault Icon</label>
                            <div class="grid grid-cols-6 gap-2">
                                @foreach(['vault', 'lock', 'shield', 'key', 'user-shield', 'fingerprint', 'id-card', 'credit-card', 'briefcase', 'home', 'building', 'university', 'landmark', 'hospital', 'laptop', 'server', 'cloud', 'database'] as $iconOption)
                                    <div
                                        wire:click="$set('icon', '{{ $iconOption }}')"
                                        class="aspect-square flex items-center justify-center rounded-md cursor-pointer {{ $icon === $iconOption ? 'bg-primary-50 border-2 border-primary-500' : 'bg-gray-50 border border-gray-200 hover:bg-gray-100' }}"
                                    >
                                        <x-icon name="fas.{{ $iconOption }}"
                                                class="text-xl {{ $icon === $iconOption ? 'text-primary-500' : 'text-gray-600' }}"/>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-600 block mb-1">Vault Color</label>
                            <div class="grid grid-cols-6 gap-2">
                                @foreach(['blue', 'purple', 'red', 'orange', 'yellow', 'green','gray'] as $colorOption)
                                    <div
                                        wire:click="$set('color', '{{ $colorOption }}')"
                                        class="aspect-square flex items-center justify-center rounded-md cursor-pointer bg-{{ $colorOption }}-100 border {{ $color === $colorOption ? 'border-2 border-' . $colorOption . '-500' : 'border-' . $colorOption . '-200 hover:border-' . $colorOption . '-300' }}"
                                    >

                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 pt-6">
                        <div class="mb-4">
                            <x-checkbox label="Change Master Password" wire:model.live="change_master_password"/>
                        </div>

                        @if($change_master_password)
                            <div class="space-y-4 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                                <div class="flex items-start gap-2 text-amber-700">
                                    <i class="fas fa-exclamation-triangle mt-1"></i>
                                    <p class="text-sm">
                                        <strong>Warning:</strong> Changing the master password will re-encrypt all
                                        passwords in this vault.
                                        Make sure to remember your new master password as it cannot be recovered.
                                    </p>
                                </div>

                                <div>
                                    <x-input
                                        label="Current Master Password"
                                        wire:model="current_master_password"
                                        type="password"
                                        placeholder="Enter your current master password"
                                    />
                                </div>

                                <div>
                                    <x-input
                                        label="New Master Password"
                                        wire:model="new_master_password"
                                        type="password"
                                        placeholder="Enter a new master password"
                                    />
                                </div>

                                <div>
                                    <x-input
                                        label="Confirm New Master Password"
                                        wire:model="new_master_password_confirmation"
                                        type="password"
                                        placeholder="Confirm your new master password"
                                    />
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="flex justify-end pt-4">
                        <x-button
                            type="submit"
                            spinner="save"
                            icon="fas.save"
                        >
                            Save Changes
                        </x-button>
                    </div>
                </form>
            </x-card>
        </div>

        <div>
            <x-card>
                <h3 class="font-medium text-lg mb-4">Vault Information</h3>

                <div class="space-y-4">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Created</span>
                        <span>{{ $vault->created_at->format('M d, Y') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600">Last Updated</span>
                        <span>{{ $vault->updated_at->format('M d, Y') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600">Password Entries</span>
                        <span>{{ $vault->entries->count() }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600">Categories</span>
                        <span>{{ $vault->categories->count() }}</span>
                    </div>
                </div>

                <div class="mt-6 border-t border-gray-200 pt-6">
                    <h4 class="font-medium text-red-600 mb-2">Danger Zone</h4>

                    <div class="space-y-3">
                        <x-button
                            wire:click="$dispatch('openModal', { component: 'password-manager.modals.delete-vault', arguments: { vaultId: {{ $vault->id }} }})"
                            icon="fas.trash"
                            variant="outline"
                            class="w-full text-red-500 border-red-300 hover:bg-red-50"
                        >
                            Delete Vault
                        </x-button>
                    </div>
                </div>
            </x-card>
        </div>
    </div>
</div>
