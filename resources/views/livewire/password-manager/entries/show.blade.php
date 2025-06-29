<?php

use App\Models\PasswordEntry;
use App\Models\PasswordVault;

new class extends Livewire\Volt\Component {
    use \Mary\Traits\Toast;

    public PasswordVault $vault;
    public PasswordEntry $entry;

    public $showPassword = false;
    public $decryptedPassword = null;

    public function mount(PasswordVault $vault, PasswordEntry $entry)
    {
        $this->vault = $vault;
        $this->entry = $entry;

        // Check if vault is locked
        if ($this->vault->is_locked) {
            $this->error('This vault is locked. Please unlock it first.');
            return redirect()->route('password-manager.vaults.show', $this->vault);
        }

        // Check if entry belongs to this vault
        if ($this->entry->password_vault_id !== $this->vault->id) {
            $this->error('This password entry does not belong to the selected vault.');
            return redirect()->route('password-manager.vaults.show', $this->vault);
        }

        // Extend vault unlock time
        $this->vault->extendUnlockTime();

        // Update last used timestamp
        $this->entry->last_used_at = now();
        $this->entry->save();
    }

    public function togglePassword()
    {
        if (!$this->showPassword) {
            try {
                $this->decryptedPassword = $this->entry->getDecryptedPassword();
                $this->showPassword = true;
            } catch (\Exception $e) {
                $this->error('Could not decrypt password. Please try again.');
            }
        } else {
            $this->showPassword = false;
            $this->decryptedPassword = null;
        }
    }

    public function copyPassword()
    {
        try {
            $this->decryptedPassword = $this->entry->getDecryptedPassword();
            $this->success('Password copied to clipboard.');
        } catch (\Exception $e) {
            $this->error('Could not decrypt password. Please try again.');
        }
    }

    public function copyUsername()
    {
        $this->success('Username copied to clipboard.');
    }
};
?>

<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold flex items-center gap-2">
            <i class="fas fa-key"></i>
            {{ $entry->title }}
        </h1>
        <div class="flex items-center gap-2">
            <x-button link="{{ route('password-manager.entries.edit', ['vault' => $vault, 'entry' => $entry]) }}"
                      variant="outline">
                <i class="fas fa-edit mr-2"></i> Edit
            </x-button>
            <x-button link="{{ route('password-manager.vaults.show', $vault) }}" variant="ghost">
                <i class="fas fa-arrow-left mr-2"></i> Back to Vault
            </x-button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <x-card>
                <div class="space-y-6">
                    <!-- Username/Email -->
                    <div>
                        <label class="text-sm font-medium text-gray-600 block mb-1">Username / Email</label>
                        <div class="flex items-center gap-2">
                            <div class="flex-1 bg-gray-50 p-3 rounded-md border border-gray-200">
                                {{ $entry->username ?: 'No username provided' }}
                            </div>
                            <x-button
                                wire:click="copyUsername"
                                variant="outline"
                                size="sm"
                                icon="fas.copy"
                                x-data
                                x-on:click="
                                    navigator.clipboard.writeText('{{ $entry->username }}');
                                "
                            >
                                Copy
                            </x-button>
                        </div>
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="text-sm font-medium text-gray-600 block mb-1">Password</label>
                        <div class="flex items-center gap-2">
                            <div class="flex-1 bg-gray-50 p-3 rounded-md border border-gray-200 font-mono">
                                @if($showPassword)
                                    {{ $decryptedPassword }}
                                @else
                                    ••••••••••••••••
                                @endif
                            </div>
                            <x-button
                                wire:click="togglePassword"
                                variant="outline"
                                size="sm"
                                icon="{{ $showPassword ? 'fas.eye-slash' : 'fas.eye' }}"
                            >
                                {{ $showPassword ? 'Hide' : 'Show' }}
                            </x-button>

                            <x-button
                                wire:click="copyPassword"
                                variant="outline"
                                size="sm"
                                icon="fas.copy"
                                x-data
                                x-on:click="
                                    $wire.copyPassword();
                                    navigator.clipboard.writeText('{{ $decryptedPassword ?? '' }}');
                                "
                            >
                                Copy
                            </x-button>
                        </div>
                    </div>

                    <!-- URL -->
                    @if($entry->url)
                        <div>
                            <label class="text-sm font-medium text-gray-600 block mb-1">Website URL</label>
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-gray-50 p-3 rounded-md border border-gray-200 break-all">
                                    <a href="{{ $entry->url }}" target="_blank" class="text-blue-600 hover:underline">
                                        {{ $entry->url }}
                                    </a>
                                </div>
                                <x-button
                                    variant="outline"
                                    size="sm"
                                    icon="fas.external-link-alt"
                                    tag="a"
                                    href="{{ $entry->url }}"
                                    target="_blank"
                                >
                                    Visit
                                </x-button>
                            </div>
                        </div>
                    @endif

                    <!-- Notes -->
                    @if($entry->notes)
                        <div>
                            <label class="text-sm font-medium text-gray-600 block mb-1">Notes</label>
                            <div class="bg-gray-50 p-3 rounded-md border border-gray-200 whitespace-pre-wrap">
                                {{ $entry->notes }}
                            </div>
                        </div>
                    @endif
                </div>
            </x-card>
        </div>

        <div>
            <x-card>
                <h3 class="font-medium text-lg mb-4">Password Details</h3>

                <div class="space-y-4">
                    <!-- Category -->
                    <div class="flex justify-between">
                        <span class="text-gray-600">Category</span>
                        <span class="font-medium">
                            @if($entry->password_category_id)
                                {{ $entry->category->name }}
                            @else
                                <span class="text-gray-400">Uncategorized</span>
                            @endif
                        </span>
                    </div>

                    <!-- Favorite -->
                    <div class="flex justify-between">
                        <span class="text-gray-600">Favorite</span>
                        <span>
                            @if($entry->is_favorite)
                                <i class="fas fa-star text-amber-400"></i> Yes
                            @else
                                <span class="text-gray-400">No</span>
                            @endif
                        </span>
                    </div>

                    <!-- Password Strength -->
                    <div class="flex justify-between">
                        <span class="text-gray-600">Password Strength</span>
                        <span class="font-medium text-{{ $entry->getStrengthColor() }}">
                            {{ $entry->getStrengthDescription() }}
                        </span>
                    </div>

                    <!-- Created -->
                    <div class="flex justify-between">
                        <span class="text-gray-600">Created</span>
                        <span>{{ $entry->created_at->format('M d, Y') }}</span>
                    </div>

                    <!-- Last Updated -->
                    <div class="flex justify-between">
                        <span class="text-gray-600">Last Updated</span>
                        <span>{{ $entry->updated_at->format('M d, Y') }}</span>
                    </div>

                    <!-- Last Used -->
                    <div class="flex justify-between">
                        <span class="text-gray-600">Last Used</span>
                        <span>{{ $entry->last_used_at ? $entry->last_used_at->format('M d, Y') : 'Never' }}</span>
                    </div>

                    <!-- Expiration -->
                    @if($entry->expires_at)
                        <div class="flex justify-between">
                            <span class="text-gray-600">Expires</span>
                            <span class="{{ $entry->expires_at->isPast() ? 'text-red-500' : '' }}">
                            {{ $entry->expires_at->format('M d, Y') }}
                                @if($entry->expires_at->isPast())
                                    <i class="fas fa-exclamation-circle ml-1"></i>
                                @endif
                        </span>
                        </div>
                    @endif
                </div>

                <div class="mt-6 flex flex-col gap-2">
                    <x-button
                        link="{{ route('password-manager.entries.edit', ['vault' => $vault, 'entry' => $entry]) }}"
                        icon="fas.edit"
                        class="w-full"
                    >
                        Edit Password
                    </x-button>

                    <x-button
                        wire:click="$dispatch('openModal', { component: 'password-manager.modals.delete-entry', arguments: { entryId: {{ $entry->id }} }})"
                        icon="fas.trash"
                        variant="outline"
                        class="w-full text-red-500 border-red-300 hover:bg-red-50"
                    >
                        Delete Password
                    </x-button>
                </div>
            </x-card>
        </div>
    </div>
</div>
