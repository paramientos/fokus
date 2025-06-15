<?php

use Livewire\Volt\Component;
use App\Models\SoftwareLicense;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new class extends Component {
    use WithPagination, Toast;

    public string $search = '';
    public string $status = '';
    public string $sortBy = 'name';
    public string $sortDirection = 'asc';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
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

    public function with(): array
    {
        $licenses = SoftwareLicense::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('vendor', 'like', '%' . $this->search . '%');
            })
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(10);

        $stats = [
            'total' => SoftwareLicense::count(),
            'active' => SoftwareLicense::where('status', 'active')->count(),
            'expired' => SoftwareLicense::where('status', 'expired')->count(),
            'total_cost' => SoftwareLicense::where('status', 'active')->sum('cost'),
            'expiring_soon' => SoftwareLicense::where('expiry_date', '<=', now()->addDays(30))
                ->where('status', 'active')->count(),
        ];

        return [
            'licenses' => $licenses,
            'stats' => $stats,
        ];
    }
};

?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Software Licenses</h1>
            <p class="text-gray-600">Manage your software licenses and subscriptions</p>
        </div>
        <x-button icon="fas.key" :link="route('licenses.create')" class="btn-primary">
            Add License
        </x-button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <x-card class="bg-blue-50 border-blue-200">
            <div class="flex items-center">
                <div class="p-3 bg-blue-500 rounded-lg">
                    <x-icon name="fas.certificate" class="w-6 h-6 text-white" />
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-blue-600">Total Licenses</p>
                    <p class="text-2xl font-bold text-blue-900">{{ $stats['total'] }}</p>
                </div>
            </div>
        </x-card>

        <x-card class="bg-green-50 border-green-200">
            <div class="flex items-center">
                <div class="p-3 bg-green-500 rounded-lg">
                    <x-icon name="fas.check-circle" class="w-6 h-6 text-white" />
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-green-600">Active</p>
                    <p class="text-2xl font-bold text-green-900">{{ $stats['active'] }}</p>
                </div>
            </div>
        </x-card>

        <x-card class="bg-red-50 border-red-200">
            <div class="flex items-center">
                <div class="p-3 bg-red-500 rounded-lg">
                    <x-icon name="fas.times-circle" class="w-6 h-6 text-white" />
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-red-600">Expired</p>
                    <p class="text-2xl font-bold text-red-900">{{ $stats['expired'] }}</p>
                </div>
            </div>
        </x-card>

        <x-card class="bg-yellow-50 border-yellow-200">
            <div class="flex items-center">
                <div class="p-3 bg-yellow-500 rounded-lg">
                    <x-icon name="fas.exclamation-triangle" class="w-6 h-6 text-white" />
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-yellow-600">Expiring Soon</p>
                    <p class="text-2xl font-bold text-yellow-900">{{ $stats['expiring_soon'] }}</p>
                </div>
            </div>
        </x-card>

        <x-card class="bg-purple-50 border-purple-200">
            <div class="flex items-center">
                <div class="p-3 bg-purple-500 rounded-lg">
                    <x-icon name="fas.dollar-sign" class="w-6 h-6 text-white" />
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-purple-600">Monthly Cost</p>
                    <p class="text-2xl font-bold text-purple-900">${{ number_format($stats['total_cost'], 0) }}</p>
                </div>
            </div>
        </x-card>
    </div>

    <!-- Filters -->
    <x-card>
        <div class="flex flex-col md:flex-row gap-4">
            <!-- Search -->
            <div class="flex-1">
                <x-input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search licenses..."
                    icon="fas.search"
                    clearable
                />
            </div>

            <!-- Status Filter -->
            <div class="w-48">
                <x-select
                    wire:model.live="status"
                    placeholder="All Statuses"
                    :options="[
                        ['id' => 'active', 'name' => 'Active'],
                        ['id' => 'expired', 'name' => 'Expired'],
                        ['id' => 'cancelled', 'name' => 'Cancelled'],
                    ]"
                    option-value="id"
                    option-label="name"
                />
            </div>
        </div>
    </x-card>

    <!-- Licenses Table -->
    <x-card>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th wire:click="sortBy('name')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                            <div class="flex items-center gap-1">
                                License Name
                                @if($sortBy === 'name')
                                    <x-icon name="fas.sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}" class="w-3 h-3" />
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Vendor
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Usage
                        </th>
                        <th wire:click="sortBy('expiry_date')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                            <div class="flex items-center gap-1">
                                Expiry Date
                                @if($sortBy === 'expiry_date')
                                    <x-icon name="fas.sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}" class="w-3 h-3" />
                                @endif
                            </div>
                        </th>
                        <th wire:click="sortBy('cost')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                            <div class="flex items-center gap-1">
                                Cost
                                @if($sortBy === 'cost')
                                    <x-icon name="fas.sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}" class="w-3 h-3" />
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($licenses as $license)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ $license->name }}</div>
                                    @if($license->version)
                                        <div class="text-sm text-gray-500">v{{ $license->version }}</div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $license->vendor }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-1 bg-gray-200 rounded-full h-2 mr-2">
                                        <div
                                            class="bg-blue-600 h-2 rounded-full"
                                            style="width: {{ $license->usage_percentage }}%"
                                        ></div>
                                    </div>
                                    <span class="text-sm text-gray-600">
                                        {{ $license->used_licenses }}/{{ $license->total_licenses }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($license->expiry_date)
                                    <div class="{{ $license->is_expired ? 'text-red-600' : ($license->days_until_expiry <= 30 ? 'text-yellow-600' : 'text-gray-900') }}">
                                        {{ $license->expiry_date->format('M d, Y') }}
                                        @if($license->is_expired)
                                            <span class="text-xs">(Expired)</span>
                                        @elseif($license->days_until_expiry <= 30)
                                            <span class="text-xs">({{ $license->days_until_expiry }} days)</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-gray-400">No expiry</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ${{ number_format($license->cost, 2) }}
                                @if($license->billing_cycle)
                                    <span class="text-gray-500">/ {{ $license->billing_cycle }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-badge :value="$license->status_label" :class="'badge-' . $license->status_color" />
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end gap-2">
                                    <x-button icon="fas.eye" link="/licenses/{{ $license->id }}" class="btn-ghost btn-sm" />
                                    <x-button icon="fas.edit" link="/licenses/{{ $license->id }}/edit" class="btn-ghost btn-sm" />
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($licenses->isEmpty())
            <div class="text-center py-12">
                <x-icon name="fas.certificate" class="w-16 h-16 text-gray-300 mx-auto mb-4" />
                <h3 class="text-lg font-medium text-gray-900 mb-2">No licenses found</h3>
                <p class="text-gray-600 mb-6">Get started by adding your first software license.</p>
                <x-button icon="fas.plus" link="/licenses/create" class="btn-primary">
                    Add License
                </x-button>
            </div>
        @endif
    </x-card>

    <!-- Pagination -->
    @if($licenses->hasPages())
        <div class="flex justify-center">
            {{ $licenses->links() }}
        </div>
    @endif
</div>
