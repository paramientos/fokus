<?php

use App\Models\PasswordEntry;
use App\Models\PasswordVault;
use Mary\Traits\Toast;

new class extends Livewire\Volt\Component {
    use Toast;

    public PasswordVault $vault;
    public PasswordEntry $entry;

    public $title;
    public $username;
    public $password;
    public $url;
    public $notes;
    public $category_id;
    public $is_favorite = false;
    public $expires_at = null;

    public function mount(PasswordVault $vault, PasswordEntry $entry): void
    {
        $this->vault = $vault;
        $this->entry = $entry;

        // Check if vault is locked
        if ($this->vault->is_locked) {
            $this->error('This vault is locked. Please unlock it first.');
            return;
        }

        // Check if entry belongs to this vault
        if ($this->entry->password_vault_id !== $this->vault->id) {
            $this->error('This password entry does not belong to the selected vault.');
            return;
        }

        // Extend vault unlock time
        $this->vault->extendUnlockTime();

        // Fill form with entry data
        $this->title = $entry->title;
        $this->username = $entry->username;
        $this->url = $entry->url;
        $this->notes = $entry->notes;
        $this->category_id = $entry->password_category_id;
        $this->is_favorite = $entry->is_favorite;
        $this->expires_at = $entry->expires_at;
    }

    public function save()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string',
            'url' => 'nullable|url|max:255',
            'notes' => 'nullable|string',
            'category_id' => 'nullable|exists:password_categories,id',
            'expires_at' => 'nullable|date',
        ]);

        $this->entry->update([
            'title' => $this->title,
            'username' => $this->username,
            'url' => $this->url,
            'notes' => $this->notes,
            'category_id' => $this->category_id ?: null,
            'is_favorite' => $this->is_favorite,
            'expires_at' => $this->expires_at,
        ]);

        // Only update password if provided
        if ($this->password) {
            $this->entry->setPassword($this->password);
        }

        $this->success('Password entry updated successfully.');

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
};
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold flex items-center gap-2">
            <i class="fas fa-key"></i>
            Edit Password Entry
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
                <div class="space-y-4">
                    <div>
                        <x-input label="Title" wire:model="title" placeholder="e.g. Gmail Account"/>
                    </div>

                    <div>
                        <x-input label="Username / Email" wire:model="username"
                                 placeholder="e.g. john.doe@example.com"/>
                    </div>

                    <div>
                        <x-input label="Password" wire:model="password" type="password"
                                 placeholder="Leave blank to keep current password"/>
                        <div class="text-xs text-gray-500 mt-1">Leave blank to keep current password</div>
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
                        <x-textarea label="Notes" wire:model="notes" placeholder="Add any additional notes here..."/>
                    </div>

                    <div>
                        <x-checkbox label="Mark as favorite" wire:model="is_favorite"/>
                    </div>

                    <div>
                        <x-input type="date" label="Expiration Date (Optional)" wire:model="expires_at"/>
                    </div>

                    <div class="flex justify-end pt-4">
                        <x-button
                            wire:click="save"
                            spinner="save"
                            class="btn-primary"
                        >
                            Update Password
                        </x-button>
                    </div>
                </div>
            </x-card>
        </div>

        <div>
            <x-card>
                <h3 class="font-medium text-lg mb-4">Password Entry Details</h3>

                <div class="space-y-4">
                    <div>
                        <span class="text-sm text-gray-500">Created</span>
                        <p>{{ $entry->created_at->format('M d, Y H:i') }}</p>
                    </div>

                    <div>
                        <span class="text-sm text-gray-500">Last Updated</span>
                        <p>{{ $entry->updated_at->format('M d, Y H:i') }}</p>
                    </div>

                    <div>
                        <span class="text-sm text-gray-500">Last Used</span>
                        <p>{{ $entry->last_used_at ? $entry->last_used_at->format('M d, Y H:i') : 'Never' }}</p>
                    </div>

                    <div>
                        <span class="text-sm text-gray-500">Security Level</span>
                        <div class="mt-1 font-bold text-{{ $entry->getStrengthColor() }}">
                           {{ $entry->getStrengthLabel() }}
                        </div>
                    </div>
                </div>
            </x-card>
        </div>
    </div>
</div>
