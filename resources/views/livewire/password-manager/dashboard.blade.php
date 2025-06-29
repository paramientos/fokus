<?php

new class extends Livewire\Volt\Component {
    use \Mary\Traits\Toast;

    public $vaults = [];
    /** @var \App\Models\PasswordEntry[] */
    public $recentEntries = [];
    public $favoriteEntries = [];
    public $totalPasswords = 0;
    public $weakPasswords = 0;
    public $expiredPasswords = 0;

    public function mount()
    {
        $this->loadVaults();
        $this->loadRecentEntries();
        $this->loadFavoriteEntries();
        $this->loadStats();
    }

    public function loadVaults()
    {
        $this->vaults = \App\Models\PasswordVault::where('user_id', auth()->id())
            ->orWhere(function ($query) {
                $query->where('is_shared', true)
                    ->whereHas('workspace', function ($q) {
                        $q->whereHas('members', function ($q) {
                            $q->where('user_id', auth()->id());
                        });
                    });
            })
            ->withCount('entries')
            ->withCount('categories')
            ->latest()
            ->take(5)
            ->get();
    }

    public function loadRecentEntries()
    {
        $vaultIds = \App\Models\PasswordVault::where('user_id', auth()->id())
            ->orWhere(function ($query) {
                $query->where('is_shared', true)
                    ->whereHas('workspace', function ($q) {
                        $q->whereHas('members', function ($q) {
                            $q->where('user_id', auth()->id());
                        });
                    });
            })
            ->pluck('id');

        $this->recentEntries = \App\Models\PasswordEntry::whereIn('password_vault_id', $vaultIds)
            ->with(['vault', 'category'])
            ->latest()
            ->take(5)
            ->get();
    }

    public function loadFavoriteEntries()
    {
        $vaultIds = \App\Models\PasswordVault::where('user_id', auth()->id())
            ->orWhere(function ($query) {
                $query->where('is_shared', true)
                    ->whereHas('workspace', function ($q) {
                        $q->whereHas('members', function ($q) {
                            $q->where('user_id', auth()->id());
                        });
                    });
            })
            ->pluck('id');

        $this->favoriteEntries = \App\Models\PasswordEntry::whereIn('password_vault_id', $vaultIds)
            ->where('is_favorite', true)
            ->with(['vault', 'category'])
            ->latest()
            ->take(5)
            ->get();
    }

    public function loadStats()
    {
        $vaultIds = \App\Models\PasswordVault::where('user_id', auth()->id())
            ->orWhere(function ($query) {
                $query->where('is_shared', true)
                    ->whereHas('workspace', function ($q) {
                        $q->whereHas('members', function ($q) {
                            $q->where('user_id', auth()->id());
                        });
                    });
            })
            ->pluck('id');

        $this->totalPasswords = \App\Models\PasswordEntry::whereIn('password_vault_id', $vaultIds)->count();
        $this->weakPasswords = \App\Models\PasswordEntry::whereIn('password_vault_id', $vaultIds)
            ->where('security_level', '<', 3)
            ->count();
        $this->expiredPasswords = \App\Models\PasswordEntry::whereIn('password_vault_id', $vaultIds)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->count();
    }

    public function toggleFavorite($entryId)
    {
        $entry = \App\Models\PasswordEntry::find($entryId);

        if (!$entry) {
            $this->error('Password entry not found.');
            return;
        }

        $entry->update([
            'is_favorite' => !$entry->is_favorite
        ]);

        $this->success('Favorite status updated.');
        $this->loadFavoriteEntries();
        $this->loadRecentEntries();
    }
}

?>

<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Password Manager</h1>
        <div>
            <x-button icon="fas.plus" link="{{ route('password-manager.vaults.create') }}">
                New Vault
            </x-button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <x-card>
            <div class="flex items-center">
                <div class="rounded-full bg-primary-100 p-3 mr-4">
                    <i class="fas fa-key text-primary-500 text-xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold">Total Passwords</h3>
                    <p class="text-2xl font-bold">{{ $totalPasswords }}</p>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="flex items-center">
                <div class="rounded-full bg-red-100 p-3 mr-4">
                    <i class="fas fa-shield-alt text-red-500 text-xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold">Weak Passwords</h3>
                    <p class="text-2xl font-bold">{{ $weakPasswords }}</p>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="flex items-center">
                <div class="rounded-full bg-yellow-100 p-3 mr-4">
                    <i class="fas fa-clock text-yellow-500 text-xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold">Expired Passwords</h3>
                    <p class="text-2xl font-bold">{{ $expiredPasswords }}</p>
                </div>
            </div>
        </x-card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Your Vaults -->
        <x-card title="Your Vaults" class="mb-6">
            @if($vaults->isEmpty())
                <div class="py-4 text-center">
                    <p class="text-gray-500">You don't have any vaults yet.</p>
                    <x-button icon="fas.plus" link="{{ route('password-manager.vaults.create') }}" class="mt-2">
                        Create Your First Vault
                    </x-button>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($vaults as $vault)
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <div class="flex items-center">
                                <div class="rounded-full bg-primary-100 p-2 mr-3">
                                    <i class="fas fa-vault text-primary-500"></i>
                                </div>
                                <div>
                                    <a href="{{ route('password-manager.vaults.show', $vault) }}"
                                       class="font-medium hover:text-primary-600">
                                        {{ $vault->name }}
                                    </a>
                                    <p class="text-sm text-gray-500">
                                        {{ $vault->entries_count }} passwords • {{ $vault->categories_count }}
                                        categories
                                    </p>
                                </div>
                            </div>
                            <div>
                                <x-button link="{{ route('password-manager.vaults.show', $vault) }}" size="sm"
                                          icon="fas.chevron-right"></x-button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 text-center">
                    <x-button link="{{ route('password-manager.vaults.index') }}" variant="link" icon="fas.arrow-right">
                        View All Vaults
                    </x-button>
                </div>
            @endif
        </x-card>

        <!-- Quick Actions -->
        <x-card title="Quick Actions" class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <a href="{{ route('password-manager.generator') }}"
                   class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                    <div class="rounded-full bg-green-100 p-3 mb-2">
                        <i class="fas fa-dice text-green-500 text-xl"></i>
                    </div>
                    <h3 class="font-medium">Password Generator</h3>
                    <p class="text-sm text-gray-500 text-center">Create strong, secure passwords</p>
                </a>

                <a href="{{ route('password-manager.security-check') }}"
                   class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                    <div class="rounded-full bg-blue-100 p-3 mb-2">
                        <i class="fas fa-shield-check text-blue-500 text-xl"></i>
                    </div>
                    <h3 class="font-medium">Security Check</h3>
                    <p class="text-sm text-gray-500 text-center">Analyze your password security</p>
                </a>

                <a href="{{ route('password-manager.vaults.create') }}"
                   class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                    <div class="rounded-full bg-purple-100 p-3 mb-2">
                        <i class="fas fa-vault text-purple-500 text-xl"></i>
                    </div>
                    <h3 class="font-medium">New Vault</h3>
                    <p class="text-sm text-gray-500 text-center">Create a new password vault</p>
                </a>

                <a href="{{ route('password-manager.trash') }}"
                   class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                    <div class="rounded-full bg-red-100 p-3 mb-2">
                        <i class="fas fa-trash text-red-500 text-xl"></i>
                    </div>
                    <h3 class="font-medium">Trash</h3>
                    <p class="text-sm text-gray-500 text-center">Manage deleted passwords</p>
                </a>
            </div>
        </x-card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        <!-- Recent Passwords -->
        <x-card title="Recent Passwords" class="mb-6">
            @if($recentEntries->isEmpty())
                <div class="py-4 text-center">
                    <p class="text-gray-500">No recent passwords.</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($recentEntries as $entry)
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <div class="flex items-center">
                                <div class="rounded-full bg-{{ $entry->category ? $entry->category->color : 'gray-400' }} p-2 mr-3">
                                    <i class="{{ $entry->category ? $entry->category->icon : 'fas fa-key' }} text-white"></i>
                                </div>
                                <div>
                                    <a href="{{ route('password-manager.entries.show', ['vault' => $entry->vault, 'entry' => $entry]) }}"
                                       class="font-medium hover:text-primary-600">
                                        {{ $entry->title }}
                                    </a>
                                    <p class="text-sm text-gray-500">
                                        {{ $entry->username }} • {{ $entry->vault->name }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <button wire:click="toggleFavorite({{ $entry->id }})"
                                        class="text-gray-400 hover:text-yellow-500">
                                    <i class="fas {{ $entry->is_favorite ? 'fa-star text-yellow-500' : 'fa-star' }}"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>

        <!-- Favorite Passwords -->
        <x-card title="Favorite Passwords" class="mb-6">
            @if($favoriteEntries->isEmpty())
                <div class="py-4 text-center">
                    <p class="text-gray-500">No favorite passwords.</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($favoriteEntries as $entry)
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <div class="flex items-center">
                                <div class="rounded-full bg-{{ $entry->category ? $entry->category->color : 'gray-400' }} p-2 mr-3">
                                    <i class="{{ $entry->category ? $entry->category->icon : 'fas fa-key' }} text-white"></i>
                                </div>
                                <div>
                                    <a href="{{ route('password-manager.entries.show', ['vault' => $entry->vault, 'entry' => $entry]) }}"
                                       class="font-medium hover:text-primary-600">
                                        {{ $entry->title }}
                                    </a>
                                    <p class="text-sm text-gray-500">
                                        {{ $entry->username }} • {{ $entry->vault->name }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <button wire:click="toggleFavorite({{ $entry->id }})"
                                        class="text-yellow-500 hover:text-gray-400">
                                    <i class="fas fa-star"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>
</div>
