<?php

new class extends Livewire\Volt\Component {
    use \Mary\Traits\Toast;

    public $vaults = [];
    public $search = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';

    public function mount()
    {
        $this->loadVaults();
    }

    public function loadVaults()
    {
        $this->vaults = \App\Models\PasswordVault::where(function($query) {
                $query->where('user_id', auth()->id())
                    ->orWhere(function ($q) {
                        $q->where('is_shared', true)
                            ->whereHas('workspace', function ($q) {
                                $q->whereHas('members', function ($q) {
                                    $q->where('user_id', auth()->id());
                                });
                            });
                    });
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
            ->withCount('entries')
            ->withCount('categories')
            ->orderBy($this->sortField, $this->sortDirection)
            ->get();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->loadVaults();
    }

    public function deleteVault($vaultId)
    {
        $vault = \App\Models\PasswordVault::find($vaultId);

        if (!$vault) {
            $this->error('Vault not found.');
            return;
        }

        // Check if user is authorized to delete this vault
        if ($vault->user_id !== auth()->id()) {
            $this->error('You are not authorized to delete this vault.');
            return;
        }

        // Delete the vault
        $vault->delete();

        $this->success('Vault deleted successfully.');
        $this->loadVaults();
    }

    public function updatedSearch()
    {
        $this->loadVaults();
    }
}

?>

<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Password Vaults</h1>
        <div>
            <x-button icon="fas.plus" href="{{ route('password-manager.vaults.create') }}">
                New Vault
            </x-button>
        </div>
    </div>

    <x-card>
        <div class="flex flex-col md:flex-row justify-between mb-4 gap-4">
            <div class="w-full md:w-1/3">
                <x-input placeholder="Search vaults..." wire:model.live="search" icon="fas.search" />
            </div>
            <div class="flex gap-2">
                <x-button link="{{ route('password-manager.dashboard') }}" icon="fas.arrow-left" variant="outline">
                    Back to Dashboard
                </x-button>
            </div>
        </div>

        @if($vaults->isEmpty())
            <div class="py-8 text-center">
                <div class="inline-flex rounded-full bg-primary-100 p-4 mb-4">
                    <i class="fas fa-vault text-primary-500 text-2xl"></i>
                </div>
                <h3 class="text-lg font-medium mb-2">No vaults found</h3>
                <p class="text-gray-500 mb-4">
                    @if($search)
                        No vaults match your search criteria. Try a different search term.
                    @else
                        You haven't created any password vaults yet.
                    @endif
                </p>
                <x-button link="{{ route('password-manager.vaults.create') }}" icon="fas.plus">
                    Create Your First Vault
                </x-button>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b">
                            <th class="px-4 py-3 text-left">
                                <button wire:click="sortBy('name')" class="flex items-center font-medium">
                                    Name
                                    @if($sortField === 'name')
                                        <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1 text-xs"></i>
                                    @endif
                                </button>
                            </th>
                            <th class="px-4 py-3 text-left">Description</th>
                            <th class="px-4 py-3 text-center">
                                <button wire:click="sortBy('entries_count')" class="flex items-center font-medium">
                                    Passwords
                                    @if($sortField === 'entries_count')
                                        <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1 text-xs"></i>
                                    @endif
                                </button>
                            </th>
                            <th class="px-4 py-3 text-center">
                                <button wire:click="sortBy('categories_count')" class="flex items-center font-medium">
                                    Categories
                                    @if($sortField === 'categories_count')
                                        <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1 text-xs"></i>
                                    @endif
                                </button>
                            </th>
                            <th class="px-4 py-3 text-center">Shared</th>
                            <th class="px-4 py-3 text-center">Protected</th>
                            <th class="px-4 py-3 text-center">
                                <button wire:click="sortBy('created_at')" class="flex items-center font-medium">
                                    Created
                                    @if($sortField === 'created_at')
                                        <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1 text-xs"></i>
                                    @endif
                                </button>
                            </th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($vaults as $vault)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <div class="flex items-center">
                                        <div class="rounded-full bg-primary-100 p-2 mr-3">
                                            <i class="fas fa-vault text-primary-500"></i>
                                        </div>
                                        <a href="{{ route('password-manager.vaults.show', $vault) }}" class="font-medium hover:text-primary-600">
                                            {{ $vault->name }}
                                        </a>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-500">
                                    {{ Str::limit($vault->description, 50) }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2 py-1 bg-primary-50 text-primary-700 rounded-full text-sm">
                                        {{ $vault->entries_count }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-sm">
                                        {{ $vault->categories_count }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($vault->is_shared)
                                        <span class="text-green-500"><i class="fas fa-check"></i></span>
                                    @else
                                        <span class="text-gray-400"><i class="fas fa-times"></i></span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($vault->hasMasterPassword())
                                        <span class="text-green-500"><i class="fas fa-lock"></i></span>
                                    @else
                                        <span class="text-gray-400"><i class="fas fa-unlock"></i></span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center text-sm text-gray-500">
                                    {{ $vault->created_at->format('M d, Y') }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end space-x-2">
                                        <x-button link="{{ route('password-manager.vaults.show', $vault) }}" size="sm" icon="fas.eye" variant="ghost"></x-button>
                                        <x-button link="{{ route('password-manager.vaults.edit', $vault) }}" size="sm" icon="fas.edit" variant="ghost"></x-button>
                                        @if($vault->user_id === auth()->id())
                                            <x-button wire:click="deleteVault({{ $vault->id }})" size="sm" icon="fas.trash" variant="ghost" class="text-red-500" wire:confirm="Are you sure you want to delete this vault? This will delete all passwords inside it."></x-button>
                                        @endif
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
