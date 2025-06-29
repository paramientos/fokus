<?php
new class extends Livewire\Volt\Component {
    use \Mary\Traits\Toast;
    
    public $trashedEntries = [];
    public $search = '';
    public $sortField = 'deleted_at';
    public $sortDirection = 'desc';
    
    public function mount()
    {
        $this->loadTrashedEntries();
    }
    
    public function loadTrashedEntries()
    {
        $query = \App\Models\PasswordEntry::onlyTrashed()
            ->where(function($query) {
                // Filter by user's vaults or shared vaults they have access to
                $query->whereHas('vault', function($query) {
                    $query->where('user_id', auth()->id())
                        ->orWhere(function($query) {
                            $query->where('is_shared', true)
                                ->whereHas('workspace', function($query) {
                                    $query->whereHas('users', function($query) {
                                        $query->where('users.id', auth()->id());
                                    });
                                });
                        });
                });
            });
            
        // Apply search filter
        if ($this->search) {
            $query->where(function($query) {
                $query->where('title', 'like', '%' . $this->search . '%')
                    ->orWhere('username', 'like', '%' . $this->search . '%')
                    ->orWhere('url', 'like', '%' . $this->search . '%')
                    ->orWhere('notes', 'like', '%' . $this->search . '%');
            });
        }
        
        // Apply sorting
        $query->orderBy($this->sortField, $this->sortDirection);
        
        $this->trashedEntries = $query->get();
    }
    
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        
        $this->loadTrashedEntries();
    }
    
    public function restore($entryId)
    {
        $entry = \App\Models\PasswordEntry::onlyTrashed()->find($entryId);
        
        if (!$entry) {
            $this->error('Entry not found.');
            return;
        }
        
        // Check if user has access to the vault
        $vault = $entry->vault;
        if (!$vault || ($vault->user_id !== auth()->id() && !($vault->is_shared && $vault->workspace->users->contains(auth()->id())))) {
            $this->error('You do not have permission to restore this entry.');
            return;
        }
        
        $entry->restore();
        $this->success('Entry restored successfully.');
        $this->loadTrashedEntries();
    }
    
    public function permanentlyDelete($entryId)
    {
        $entry = \App\Models\PasswordEntry::onlyTrashed()->find($entryId);
        
        if (!$entry) {
            $this->error('Entry not found.');
            return;
        }
        
        // Check if user has access to the vault
        $vault = $entry->vault;
        if (!$vault || ($vault->user_id !== auth()->id() && !($vault->is_shared && $vault->workspace->users->contains(auth()->id())))) {
            $this->error('You do not have permission to delete this entry.');
            return;
        }
        
        $entry->forceDelete();
        $this->success('Entry permanently deleted.');
        $this->loadTrashedEntries();
    }
    
    public function emptyTrash()
    {
        // Get all trashed entries the user has access to
        $entries = \App\Models\PasswordEntry::onlyTrashed()
            ->whereHas('vault', function($query) {
                $query->where('user_id', auth()->id())
                    ->orWhere(function($query) {
                        $query->where('is_shared', true)
                            ->whereHas('workspace', function($query) {
                                $query->whereHas('users', function($query) {
                                    $query->where('users.id', auth()->id());
                                });
                            });
                    });
            })
            ->get();
            
        foreach ($entries as $entry) {
            $entry->forceDelete();
        }
        
        $this->success('Trash emptied successfully.');
        $this->trashedEntries = [];
    }
    
    public function updatedSearch()
    {
        $this->loadTrashedEntries();
    }
};
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold flex items-center gap-2">
            <i class="fas fa-trash"></i>
            Trash
        </h1>
        <div>
            <x-button link="{{ route('password-manager.dashboard') }}" variant="ghost">
                <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
            </x-button>
        </div>
    </div>
    
    <x-card>
        <div class="flex justify-between items-center mb-4">
            <div class="w-1/3">
                <x-input placeholder="Search trash..." wire:model.live.debounce.300ms="search">
                    <x-slot:prepend>
                        <i class="fas fa-search"></i>
                    </x-slot:prepend>
                </x-input>
            </div>
            
            @if(count($trashedEntries) > 0)
                <x-button wire:click="emptyTrash" wire:confirm="Are you sure you want to permanently delete all items in the trash? This action cannot be undone." class="btn-error">
                    <i class="fas fa-trash mr-2"></i> Empty Trash
                </x-button>
            @endif
        </div>
        
        @if(count($trashedEntries) > 0)
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th wire:click="sortBy('title')" class="cursor-pointer">
                                Title
                                @if($sortField === 'title')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>
                                @endif
                            </th>
                            <th wire:click="sortBy('username')" class="cursor-pointer">
                                Username
                                @if($sortField === 'username')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>
                                @endif
                            </th>
                            <th>Vault</th>
                            <th wire:click="sortBy('deleted_at')" class="cursor-pointer">
                                Deleted At
                                @if($sortField === 'deleted_at')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>
                                @endif
                            </th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($trashedEntries as $entry)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-key text-gray-400"></i>
                                        {{ $entry->title }}
                                    </div>
                                </td>
                                <td>{{ $entry->username }}</td>
                                <td>{{ $entry->vault->name }}</td>
                                <td>{{ $entry->deleted_at->format('M d, Y H:i') }}</td>
                                <td>
                                    <div class="flex gap-2">
                                        <x-button wire:click="restore('{{ $entry->id }}')" class="btn-sm btn-success">
                                            <i class="fas fa-trash-restore"></i>
                                        </x-button>
                                        <x-button wire:click="permanentlyDelete('{{ $entry->id }}')" wire:confirm="Are you sure you want to permanently delete this entry? This action cannot be undone." class="btn-sm btn-error">
                                            <i class="fas fa-trash"></i>
                                        </x-button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-12">
                <div class="text-5xl text-gray-300 mb-4">
                    <i class="fas fa-trash"></i>
                </div>
                <h3 class="text-xl font-medium text-gray-500 mb-2">Trash is Empty</h3>
                <p class="text-gray-400">No deleted password entries found</p>
            </div>
        @endif
    </x-card>
</div>
