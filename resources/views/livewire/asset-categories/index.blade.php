<?php

use Livewire\Volt\Component;
use App\Models\AssetCategory;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new class extends Component {
    use WithPagination, Toast;

    public string $search = '';
    public string $sortBy = 'name';
    public string $sortDirection = 'asc';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function toggleStatus(AssetCategory $category): void
    {
        $category->update(['is_active' => !$category->is_active]);
        
        $status = $category->is_active ? 'activated' : 'deactivated';
        $this->success("Category {$status} successfully!");
    }

    public function with(): array
    {
        $categories = AssetCategory::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->withCount('assets')
            ->paginate(10);

        return [
            'categories' => $categories,
        ];
    }
};

?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Asset Categories</h1>
            <p class="text-gray-600">Manage your asset categories</p>
        </div>
        <x-button icon="fas.plus" link="/asset-categories/create" class="btn-primary">
            Create Category
        </x-button>
    </div>

    <!-- Filters -->
    <x-card>
        <div class="flex flex-col md:flex-row gap-4">
            <!-- Search -->
            <div class="flex-1">
                <x-input 
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search categories..."
                    icon="fas.search"
                    clearable
                />
            </div>
        </div>
    </x-card>

    <!-- Categories Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($categories as $category)
            <x-card class="hover:shadow-lg transition-shadow">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div 
                            class="w-12 h-12 rounded-lg flex items-center justify-center text-white"
                            style="background-color: {{ $category->color }}"
                        >
                            <x-icon :name="$category->icon" class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="font-semibold text-lg">{{ $category->name }}</h3>
                            <p class="text-sm text-gray-600">{{ $category->assets_count }} assets</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        @if($category->is_active)
                            <x-badge value="Active" class="badge-success" />
                        @else
                            <x-badge value="Inactive" class="badge-secondary" />
                        @endif
                        
                        <x-dropdown>
                            <x-slot:trigger>
                                <x-button icon="fas.ellipsis-v" class="btn-ghost btn-sm" />
                            </x-slot:trigger>
                            
                            <x-menu-item title="Edit" icon="fas.edit" link="/asset-categories/{{ $category->id }}/edit" />
                            <x-menu-item 
                                title="{{ $category->is_active ? 'Deactivate' : 'Activate' }}" 
                                icon="{{ $category->is_active ? 'fas.eye-slash' : 'fas.eye' }}"
                                wire:click="toggleStatus({{ $category->id }})"
                            />
                        </x-dropdown>
                    </div>
                </div>
                
                @if($category->description)
                    <p class="mt-3 text-gray-600 text-sm">{{ $category->description }}</p>
                @endif
                
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <div class="flex justify-between text-sm text-gray-500">
                        <span>Created {{ $category->created_at->format('M d, Y') }}</span>
                        <span>{{ $category->slug }}</span>
                    </div>
                </div>
            </x-card>
        @endforeach
    </div>

    @if($categories->isEmpty())
        <x-card>
            <div class="text-center py-12">
                <x-icon name="fas.folder-open" class="w-16 h-16 text-gray-300 mx-auto mb-4" />
                <h3 class="text-lg font-medium text-gray-900 mb-2">No categories found</h3>
                <p class="text-gray-600 mb-6">Get started by creating your first asset category.</p>
                <x-button icon="fas.plus" link="/asset-categories/create" class="btn-primary">
                    Create Category
                </x-button>
            </div>
        </x-card>
    @endif

    <!-- Pagination -->
    @if($categories->hasPages())
        <div class="flex justify-center">
            {{ $categories->links() }}
        </div>
    @endif
</div>
