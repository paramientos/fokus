<?php

use App\Models\PasswordEntry;

new class extends Livewire\Volt\Component {
    use \Mary\Traits\Toast;

    public \App\Models\PasswordVault $vault;

    public $title = '';
    public $username = '';
    public $password = '';
    public $url = '';
    public $notes = '';
    public $category_id = null;
    public $is_favorite = false;
    public $expires_at = null;

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
    }

    public function save()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'username' => 'nullable|string|max:255',
            'password' => 'required|string',
            'url' => 'nullable|url|max:255',
            'notes' => 'nullable|string',
            'category_id' => 'nullable|exists:password_categories,id',
            'expires_at' => 'nullable|date',
        ]);

        $entry = new PasswordEntry();
        $entry->title = $this->title;
        $entry->username = $this->username;
        $entry->password_vault_id = $this->vault->id;
        $entry->password_category_id = $this->category_id ?: null;

        $entry->setPassword($this->password);

        $entry->url = $this->url;
        $entry->notes = $this->notes;
        $entry->is_favorite = $this->is_favorite;
        $entry->expires_at = $this->expires_at;
        $entry->save();

        $this->success('Password entry created successfully.');

        return redirect()->route('password-manager.vaults.show', $this->vault);
    }

    public function getVaultCategoriesProperty()
    {
        return \App\Models\PasswordCategory::where('password_vault_id', $this->vault->id)
            ->orderBy('name')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name
                ];
            });
    }

    public function generatePassword()
    {
        // Generate a secure password
        $length = 16;
        $useUppercase = true;
        $useLowercase = true;
        $useNumbers = true;
        $useSpecial = true;

        $chars = '';
        if ($useLowercase) $chars .= 'abcdefghjkmnpqrstuvwxyz';
        if ($useUppercase) $chars .= 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        if ($useNumbers) $chars .= '23456789';
        if ($useSpecial) $chars .= '!@#$%^&*()-_=+[]{}|;:,.<>?';

        $password = '';
        $max = strlen($chars) - 1;

        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $max)];
        }

        $this->password = $password;
    }
};
?>

<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold flex items-center gap-2">
            <i class="fas fa-plus-circle"></i>
            Create New Password
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
                <form wire:submit="save">
                    <div class="space-y-4">
                        <div>
                            <x-input label="Title" wire:model="title" placeholder="e.g. Gmail Account"/>
                        </div>

                        <div>
                            <x-input label="Username / Email" wire:model="username"
                                     placeholder="e.g. john.doe@example.com"/>
                        </div>

                        <div>
                            <div class="flex items-center gap-2">
                                <div class="flex-1">
                                    <x-input label="Password" wire:model="password" type="password"
                                             placeholder="Enter a secure password"/>
                                </div>
                                <div class="pt-5">
                                    <x-button
                                        type="button"
                                        wire:click="generatePassword"
                                        variant="outline"
                                        size="sm"
                                        icon="fas.bolt"
                                    >
                                        Generate
                                    </x-button>
                                </div>
                            </div>
                        </div>

                        <div>
                            <x-input label="Website URL" wire:model="url" placeholder="e.g. https://gmail.com"/>
                        </div>

                        <div>
                            <x-select
                                label="Category"
                                wire:model="category_id"
                                placeholder="Select a category (optional)"
                                :options="$this->vaultCategories"
                            />
                        </div>

                        <div>
                            <x-textarea label="Notes" wire:model="notes"
                                        placeholder="Add any additional notes here..."/>
                        </div>

                        <div>
                            <x-checkbox label="Mark as favorite" wire:model="is_favorite"/>
                        </div>

                        <div>
                            <x-input type="date" label="Expiration Date (Optional)" wire:model="expires_at"/>
                        </div>

                        <div class="flex justify-end pt-4">
                            <x-button
                                type="submit"
                                spinner="save"
                                icon="fas.save"
                            >
                                Save Password
                            </x-button>
                        </div>
                    </div>
                </form>
            </x-card>
        </div>

        <div>
            <x-card>
                <h3 class="font-medium text-lg mb-4">Password Tips</h3>

                <div class="space-y-4 text-sm">
                    <div class="flex items-start gap-2">
                        <i class="fas fa-shield-alt text-green-500 mt-1"></i>
                        <p>Use a strong, unique password for each account.</p>
                    </div>

                    <div class="flex items-start gap-2">
                        <i class="fas fa-random text-blue-500 mt-1"></i>
                        <p>Mix uppercase, lowercase, numbers, and special characters.</p>
                    </div>

                    <div class="flex items-start gap-2">
                        <i class="fas fa-ruler-horizontal text-amber-500 mt-1"></i>
                        <p>Aim for at least 12 characters in length.</p>
                    </div>

                    <div class="flex items-start gap-2">
                        <i class="fas fa-bolt text-purple-500 mt-1"></i>
                        <p>Use the "Generate" button to create a secure random password.</p>
                    </div>

                    <div class="flex items-start gap-2">
                        <i class="fas fa-calendar-alt text-red-500 mt-1"></i>
                        <p>Set an expiration date for passwords that should be changed regularly.</p>
                    </div>
                </div>

                <div class="mt-6 p-3 bg-blue-50 rounded-lg">
                    <div class="flex items-start gap-2">
                        <i class="fas fa-info-circle text-blue-500 mt-1"></i>
                        <p class="text-sm text-blue-700">
                            All passwords are encrypted with your vault's master password. Make sure to remember your
                            master password as it cannot be recovered.
                        </p>
                    </div>
                </div>
            </x-card>
        </div>
    </div>
</div>
