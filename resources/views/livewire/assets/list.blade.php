<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\User;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $status = '';
    public string $category = '';
    public string $assignedTo = '';
    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    public function updatedAssignedTo(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->status = '';
        $this->category = '';
        $this->assignedTo = '';
        $this->resetPage();
    }

    public function with(): array
    {
        $assets = Asset::with(['category', 'assignedTo', 'createdBy'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('asset_tag', 'like', '%' . $this->search . '%')
                      ->orWhere('brand', 'like', '%' . $this->search . '%')
                      ->orWhere('model', 'like', '%' . $this->search . '%')
                      ->orWhere('serial_number', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })
            ->when($this->category, function ($query) {
                $query->where('asset_category_id', $this->category);
            })
            ->when($this->assignedTo, function ($query) {
                $query->where('assigned_to', $this->assignedTo);
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(15);

        $categories = AssetCategory::where('is_active', true)->get();
        $users = User::whereHas('workspaceMembers', function ($query) {
            $query->where('workspace_id', get_workspace_id());
        })->get();

        return [
            'assets' => $assets,
            'categories' => $categories,
            'users' => $users,
        ];
    }
};

?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Assets</h1>
            <p class="text-gray-600">Manage and track all your organization's assets</p>
        </div>
        <x-button icon="fas.plus" link="/assets/create" class="btn-primary">
            Add Asset
        </x-button>
    </div>

    <!-- Filters -->
    <x-card>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <!-- Search -->
            <div>
                <x-input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search assets..."
                    icon="fas.search"
                />
            </div>

            <!-- Status Filter -->
            <div>
                <x-select wire:model.live="status" placeholder="All Statuses" :options="[
                    ['id' => '', 'name' => 'All Statuses'],
                    ['id' => 'available', 'name' => 'Available'],
                    ['id' => 'assigned', 'name' => 'Assigned'],
                    ['id' => 'maintenance', 'name' => 'Maintenance'],
                    ['id' => 'retired', 'name' => 'Retired'],
                    ['id' => 'lost', 'name' => 'Lost'],
                ]" />
            </div>

            <!-- Category Filter -->
            <div>
                <x-select wire:model.live="category" placeholder="All Categories" :options="$categories" option-value="id" option-label="name" />
            </div>

            <!-- Assigned To Filter -->
            <div>
                <x-select wire:model.live="assignedTo" placeholder="All Users" :options="$users->map(fn($user) => ['id' => $user->id, 'name' => $user->name])->toArray()" />
            </div>

            <!-- Clear Filters -->
            <div>
                <x-button wire:click="clearFilters" class="btn-outline w-full">
                    Clear Filters
                </x-button>
            </div>
        </div>
    </x-card>

    <!-- Assets Table -->
    <x-card>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th wire:click="sortBy('asset_tag')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                            <div class="flex items-center gap-1">
                                Asset Tag
                                @if($sortBy === 'asset_tag')
                                    <x-icon name="fas.sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}" class="w-3 h-3" />
                                @endif
                            </div>
                        </th>
                        <th wire:click="sortBy('name')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                            <div class="flex items-center gap-1">
                                Name
                                @if($sortBy === 'name')
                                    <x-icon name="fas.sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}" class="w-3 h-3" />
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Category
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Assigned To
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Location
                        </th>
                        <th wire:click="sortBy('created_at')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                            <div class="flex items-center gap-1">
                                Created
                                @if($sortBy === 'created_at')
                                    <x-icon name="fas.sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}" class="w-3 h-3" />
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($assets as $asset)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-medium text-gray-900">{{ $asset->asset_tag }}</div>
                                @if($asset->serial_number)
                                    <div class="text-sm text-gray-500">S/N: {{ $asset->serial_number }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-medium text-gray-900">{{ $asset->name }}</div>
                                @if($asset->brand || $asset->model)
                                    <div class="text-sm text-gray-500">
                                        {{ $asset->brand }} {{ $asset->model }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full" style="background-color: {{ $asset->category->color }}"></div>
                                    <span class="text-sm text-gray-900">{{ $asset->category->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-badge :value="$asset->status_label" :class="'badge-' . $asset->status_color" />
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($asset->assignedTo)
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                                            <span class="text-xs font-medium text-gray-700">
                                                {{ substr($asset->assignedTo->name, 0, 2) }}
                                            </span>
                                        </div>
                                        <span class="text-sm text-gray-900">{{ $asset->assignedTo->name }}</span>
                                    </div>
                                @else
                                    <span class="text-sm text-gray-500">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $asset->location ?? '-' }}</div>
                                @if($asset->room)
                                    <div class="text-sm text-gray-500">{{ $asset->room }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $asset->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center gap-2">
                                    <x-button icon="fas.eye" link="/assets/{{ $asset->id }}" class="btn-sm btn-outline">
                                        View
                                    </x-button>
                                    <x-button icon="fas.edit" link="/assets/{{ $asset->id }}/edit" class="btn-sm btn-outline">
                                        Edit
                                    </x-button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="text-gray-500">
                                    <x-icon name="fas.box" class="w-12 h-12 mx-auto mb-4 text-gray-300" />
                                    <p class="text-lg font-medium">No assets found</p>
                                    <p class="text-sm">Get started by adding your first asset.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($assets->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $assets->links() }}
            </div>
        @endif
    </x-card>
</div>
