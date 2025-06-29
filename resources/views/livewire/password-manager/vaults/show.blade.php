<?php

use App\Models\PasswordVault;
use Illuminate\Support\Str;

new class extends Livewire\Volt\Component {
    use \Mary\Traits\Toast;

    public PasswordVault $vault;
    public $entries = [];
    public $categories = [];

    public $search = '';
    public $selectedCategory = null;
    public $sortField = 'title';
    public $sortDirection = 'asc';

    // Master password handling
    public $showMasterPasswordModal = false;
    public $masterPassword = '';

    public function mount(PasswordVault $vault): void
    {
        $this->vault = $vault;

        if ($vault->user_id !== auth()->id() && !($vault->is_shared && $vault->workspace->members()->where('user_id', auth()->id())->exists())) {
            abort(403, 'Unauthorized');
        }

        $this->loadCategories();

        if (!$vault->is_locked) {
            $this->loadEntries();
        }
    }

    /* ---------------------------------- Data Loaders ---------------------------------*/
    public function loadCategories()
    {
        $this->categories = $this->vault->categories()->withCount('entries')->get();
    }

    public function loadEntries()
    {
        $this->entries = $this->vault->entries()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('title', 'like', "%{$this->search}%")
                        ->orWhere('username', 'like', "%{$this->search}%")
                        ->orWhere('url', 'like', "%{$this->search}%");
                });
            })
            ->when($this->selectedCategory, fn($q) => $q->where('password_category_id', $this->selectedCategory))
            ->with('category')
            ->orderBy($this->sortField, $this->sortDirection)
            ->get();
    }

    /* ---------------------------------- Helpers ---------------------------------- */
    public function updatedSearch()
    {
        $this->loadEntries();
    }

    public function updatedSelectedCategory()
    {
        $this->loadEntries();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->loadEntries();
    }

    public function toggleFavorite($entryId)
    {
        $entry = \App\Models\PasswordEntry::find($entryId);
        if (!$entry || $entry->password_vault_id !== $this->vault->id) {
            return;
        }
        $entry->update(['is_favorite' => !$entry->is_favorite]);
        $this->loadEntries();
    }

    public function deleteEntry($entryId)
    {
        $entry = \App\Models\PasswordEntry::find($entryId);
        if (!$entry || $entry->password_vault_id !== $this->vault->id) {
            return;
        }
        $entry->delete();
        $this->success('Password moved to trash.');
        $this->loadEntries();
        $this->loadCategories();
    }

    /* ---------------------------- Master Password Logic --------------------------- */
    public function unlockVault()
    {
        $this->validate(['masterPassword' => 'required|string']);

        if ($this->vault->unlock($this->masterPassword)) {
            $this->showMasterPasswordModal = false;
            $this->masterPassword = '';
            $this->loadEntries();
            $this->success('Vault unlocked for 1 minute.');
        } else {
            $this->error('Invalid master password.');
        }
    }

    public function lockVault()
    {
        $this->vault->lock();
        $this->success('Vault locked.');
        return redirect()->route('password-manager.vaults.show', $this->vault);
    }
};

?>

<div>
    <div class="flex justify-between items-center mb-6">
        <div class="flex items-center space-x-3">
            <x-button link="{{ route('password-manager.vaults.index') }}" variant="outline" size="sm"
                      icon="fas.arrow-left"/>
            <h1 class="text-2xl font-bold">{{ $vault->name }}</h1>
            @if($vault->is_shared)
                <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">Shared</span>
            @endif
        </div>
        <div class="space-x-2">
            <x-button link="{{ route('password-manager.entries.create', $vault) }}" icon="fas.plus">New Password
            </x-button>
            <x-button link="{{ route('password-manager.vaults.edit', $vault) }}" variant="outline" icon="fas.edit"/>
            @if(!$vault->is_locked && $vault->hasMasterPassword())
                <x-button wire:click="lockVault" variant="outline" icon="fas.lock" color="red">Lock</x-button>
            @endif
        </div>
    </div>

    @if($vault->is_locked)
        <x-card class="text-center py-12">
            <h2 class="text-xl font-semibold mb-4"><i class="fas fa-lock mr-2"></i> Vault Locked</h2>
            <p class="mb-6 text-gray-500">This vault is protected by a master password. You must unlock it to view its
                contents.</p>
            <x-button wire:click="$set('showMasterPasswordModal', true)" icon="fas.unlock">Unlock Vault</x-button>
        </x-card>

        <!-- Master Password Modal -->
        <x-modal wire:model="showMasterPasswordModal" title="Unlock Vault">
            <div class="space-y-4">
                <x-input type="password" placeholder="Enter master password" wire:model="masterPassword"
                         wire:keydown.enter="unlockVault"/>
            </div>
            <x-slot name="actions">
                <x-button wire:click="unlockVault" icon="fas.unlock">Unlock</x-button>
            </x-slot>
        </x-modal>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Sidebar Categories -->
            <div class="lg:col-span-1">
                <x-card title="Categories">
                    <ul class="space-y-2">
                        <li>
                            <a href="#" wire:click="selectCategory(null)"
                               class="flex justify-between items-center {{ $selectedCategory ? 'text-gray-600' : 'font-medium text-primary-600' }}">
                                <span>All</span>
                                <span
                                    class="text-xs bg-gray-100 px-2 py-0.5 rounded-full">{{ $vault->entries()->count() }}</span>
                            </a>
                        </li>
                        @foreach($categories as $cat)
                            <li>
                                <a href="#" wire:click="selectCategory({{ $cat->id }})"
                                   class="flex justify-between items-center {{ $selectedCategory === $cat->id ? 'font-medium text-primary-600' : 'text-gray-600' }}">
                                    <span><i class="{{ $cat->icon }} mr-2"></i>{{ $cat->name }}</span>
                                    <span
                                        class="text-xs bg-gray-100 px-2 py-0.5 rounded-full">{{ $cat->entries_count }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </x-card>
            </div>

            <!-- Main Content -->
            <div class="lg:col-span-3">
                <x-card>
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
                        <x-input placeholder="Search passwords..." icon="fas.search" wire:model.live="search"
                                 class="w-full md:w-1/3"/>
                        <div class="flex gap-2">
                            <x-button variant="link" wire:click="sortBy('title')">Title @if($sortField==='title')
                                    <i class="fas fa-sort-{{ $sortDirection==='asc'?'up':'down' }} ml-1"></i>
                                @endif</x-button>
                            <x-button variant="link" wire:click="sortBy('security_level')">
                                Security @if($sortField==='security_level')
                                    <i class="fas fa-sort-{{ $sortDirection==='asc'?'up':'down' }} ml-1"></i>
                                @endif</x-button>
                            <x-button variant="link" wire:click="sortBy('created_at')">
                                Created @if($sortField==='created_at')
                                    <i class="fas fa-sort-{{ $sortDirection==='asc'?'up':'down' }} ml-1"></i>
                                @endif</x-button>
                        </div>
                    </div>

                    @if($entries->isEmpty())
                        <div class="py-6 text-center text-gray-500">No passwords found.</div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                <tr class="border-b text-left">
                                    <th class="px-4 py-3">Title</th>
                                    <th class="px-4 py-3">Username</th>
                                    <th class="px-4 py-3">Security</th>
                                    <th class="px-4 py-3 text-center">Fav</th>
                                    <th class="px-4 py-3 text-right">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($entries as $entry)
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="px-4 py-3">
                                            <div class="flex items-center">
                                                <div
                                                    class="rounded-full bg-{{ $entry->category? $entry->category->color : 'gray-400' }} p-2 mr-3">
                                                    <i class="{{ $entry->category? $entry->category->icon : 'fas fa-key' }} text-white"></i>
                                                </div>
                                                <a href="{{ route('password-manager.entries.show', ['vault'=>$vault, 'entry'=>$entry]) }}"
                                                   class="font-medium hover:text-primary-600">{{ $entry->title }}</a>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-gray-600">{{ Str::limit($entry->username,20) }}</td>
                                        <td class="px-4 py-3">
                                            @php $level=$entry->security_level; @endphp
                                            <span
                                                class="text-sm px-2 py-1 rounded-full {{ $level>=4?'bg-green-100 text-green-700':($level>=3?'bg-yellow-100 text-yellow-700':'bg-red-100 text-red-700') }}">
                                                    {{ $level >=4 ? 'Strong' : ($level>=3? 'Medium':'Weak') }}
                                                </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <button wire:click="toggleFavorite({{ $entry->id }})"
                                                    class="{{ $entry->is_favorite?'text-yellow-500':'text-gray-400 hover:text-yellow-500' }}">
                                                <i class="fas fa-star"></i>
                                            </button>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="flex justify-end space-x-2">
                                                <x-button
                                                    link="{{ route('password-manager.entries.show', ['vault'=>$vault,'entry'=>$entry]) }}"
                                                    size="sm" icon="fas.eye" variant="ghost"/>
                                                <x-button
                                                    link="{{ route('password-manager.entries.edit', ['vault'=>$vault,'entry'=>$entry]) }}"
                                                    size="sm" icon="fas.edit" variant="ghost"/>
                                                <x-button wire:click="deleteEntry({{ $entry->id }})" size="sm"
                                                          icon="fas.trash" variant="ghost" class="text-red-500"
                                                          wire:confirm="Delete this password?"/>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </x-card>
            </div>
        </div>
    @endif
</div>
