<?php

use Livewire\Volt\Component;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\SoftwareLicense;

new class extends Component {
    public function with(): array
    {
        $totalAssets = Asset::count();
        $availableAssets = Asset::where('status', 'available')->count();
        $assignedAssets = Asset::where('status', 'assigned')->count();
        $maintenanceAssets = Asset::where('status', 'maintenance')->count();

        $totalLicenses = SoftwareLicense::sum('total_licenses');
        $usedLicenses = SoftwareLicense::sum('used_licenses');
        $availableLicenses = $totalLicenses - $usedLicenses;
        $expiringSoonLicenses = SoftwareLicense::where('expiry_date', '<=', now()->addDays(30))
            ->where('expiry_date', '>', now())
            ->count();

        $categories = AssetCategory::withCount(['assets', 'activeAssets'])
            ->where('is_active', true)
            ->get();

        $recentAssets = Asset::with(['category', 'assignedTo'])
            ->latest()
            ->limit(5)
            ->get();

        $warrantyExpiring = Asset::where('warranty_expiry', '<=', now()->addDays(30))
            ->where('warranty_expiry', '>', now())
            ->with(['category'])
            ->limit(5)
            ->get();

        $maintenanceDue = Asset::where('next_maintenance', '<=', now())
            ->with(['category'])
            ->limit(5)
            ->get();

        return [
            'totalAssets' => $totalAssets,
            'availableAssets' => $availableAssets,
            'assignedAssets' => $assignedAssets,
            'maintenanceAssets' => $maintenanceAssets,
            'totalLicenses' => $totalLicenses,
            'usedLicenses' => $usedLicenses,
            'availableLicenses' => $availableLicenses,
            'expiringSoonLicenses' => $expiringSoonLicenses,
            'categories' => $categories,
            'recentAssets' => $recentAssets,
            'warrantyExpiring' => $warrantyExpiring,
            'maintenanceDue' => $maintenanceDue,
        ];
    }
};

?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Asset Management</h1>
            <p class="text-gray-600">Manage your organization's assets and software licenses</p>
        </div>
        <div class="flex gap-3">
            <x-button icon="fas.plus" link="/assets/create" class="btn-primary">
                Add Asset
            </x-button>
            <x-button icon="fas.key" link="/licenses/create" class="btn-outline">
                Add License
            </x-button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Assets -->
        <x-card class="bg-gradient-to-r from-blue-500 to-blue-600 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100">Total Assets</p>
                    <p class="text-3xl font-bold">{{ $totalAssets }}</p>
                </div>
                <x-icon name="fas.box" class="w-8 h-8 text-blue-200" />
            </div>
        </x-card>

        <!-- Available Assets -->
        <x-card class="bg-gradient-to-r from-green-500 to-green-600 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100">Available</p>
                    <p class="text-3xl font-bold">{{ $availableAssets }}</p>
                </div>
                <x-icon name="fas.check-circle" class="w-8 h-8 text-green-200" />
            </div>
        </x-card>

        <!-- Assigned Assets -->
        <x-card class="bg-gradient-to-r from-purple-500 to-purple-600 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100">Assigned</p>
                    <p class="text-3xl font-bold">{{ $assignedAssets }}</p>
                </div>
                <x-icon name="fas.user" class="w-8 h-8 text-purple-200" />
            </div>
        </x-card>

        <!-- Maintenance -->
        <x-card class="bg-gradient-to-r from-orange-500 to-orange-600 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-100">Maintenance</p>
                    <p class="text-3xl font-bold">{{ $maintenanceAssets }}</p>
                </div>
                <x-icon name="fas.wrench" class="w-8 h-8 text-orange-200" />
            </div>
        </x-card>
    </div>

    <!-- License Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Licenses -->
        <x-card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600">Total Licenses</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $totalLicenses }}</p>
                </div>
                <x-icon name="fas.key" class="w-6 h-6 text-gray-400" />
            </div>
        </x-card>

        <!-- Used Licenses -->
        <x-card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600">Used Licenses</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $usedLicenses }}</p>
                </div>
                <x-icon name="fas.user-check" class="w-6 h-6 text-gray-400" />
            </div>
        </x-card>

        <!-- Available Licenses -->
        <x-card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600">Available</p>
                    <p class="text-2xl font-bold text-green-600">{{ $availableLicenses }}</p>
                </div>
                <x-icon name="fas.unlock" class="w-6 h-6 text-green-400" />
            </div>
        </x-card>

        <!-- Expiring Soon -->
        <x-card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600">Expiring Soon</p>
                    <p class="text-2xl font-bold text-red-600">{{ $expiringSoonLicenses }}</p>
                </div>
                <x-icon name="fas.exclamation-triangle" class="w-6 h-6 text-red-400" />
            </div>
        </x-card>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Asset Categories -->
        <x-card>
            <x-slot:title>
                <div class="flex items-center justify-between">
                    <span>Asset Categories</span>
                    <x-button icon="fas.plus" link="/asset-categories" class="btn-sm btn-outline">
                        Manage
                    </x-button>
                </div>
            </x-slot:title>

            <div class="space-y-3">
                @forelse($categories as $category)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 rounded-full" style="background-color: {{ $category->color }}"></div>
                            <div>
                                <p class="font-medium">{{ $category->name }}</p>
                                <p class="text-sm text-gray-600">{{ $category->assets_count }} assets</p>
                            </div>
                        </div>
                        <x-icon name="{{ $category->icon }}" class="w-5 h-5 text-gray-400" />
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-4">No categories found</p>
                @endforelse
            </div>
        </x-card>

        <!-- Recent Assets -->
        <x-card>
            <x-slot:title>
                <div class="flex items-center justify-between">
                    <span>Recent Assets</span>
                    <x-button icon="fas.eye" link="/assets/list" class="btn-sm btn-outline">
                        View All
                    </x-button>
                </div>
            </x-slot:title>

            <div class="space-y-3">
                @forelse($recentAssets as $asset)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <p class="font-medium">{{ $asset->name }}</p>
                            <p class="text-sm text-gray-600">{{ $asset->asset_tag }}</p>
                            <p class="text-xs text-gray-500">{{ $asset->category->name }}</p>
                        </div>
                        <x-badge :value="$asset->status_label" :class="'badge-' . $asset->status_color" />
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-4">No assets found</p>
                @endforelse
            </div>
        </x-card>

        <!-- Alerts -->
        <x-card>
            <x-slot:title>
                <span class="flex items-center gap-2">
                    <x-icon name="fas.exclamation-triangle" class="w-5 h-5 text-orange-500" />
                    Alerts & Notifications
                </span>
            </x-slot:title>

            <div class="space-y-3">
                @if($warrantyExpiring->count() > 0)
                    <div class="p-3 bg-orange-50 border border-orange-200 rounded-lg">
                        <p class="font-medium text-orange-800">Warranty Expiring</p>
                        <p class="text-sm text-orange-600">{{ $warrantyExpiring->count() }} assets</p>
                    </div>
                @endif

                @if($maintenanceDue->count() > 0)
                    <div class="p-3 bg-red-50 border border-red-200 rounded-lg">
                        <p class="font-medium text-red-800">Maintenance Due</p>
                        <p class="text-sm text-red-600">{{ $maintenanceDue->count() }} assets</p>
                    </div>
                @endif

                @if($expiringSoonLicenses > 0)
                    <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <p class="font-medium text-yellow-800">Licenses Expiring</p>
                        <p class="text-sm text-yellow-600">{{ $expiringSoonLicenses }} licenses</p>
                    </div>
                @endif

                @if($warrantyExpiring->count() == 0 && $maintenanceDue->count() == 0 && $expiringSoonLicenses == 0)
                    <div class="p-3 bg-green-50 border border-green-200 rounded-lg">
                        <p class="font-medium text-green-800">All Good!</p>
                        <p class="text-sm text-green-600">No alerts at this time</p>
                    </div>
                @endif
            </div>
        </x-card>
    </div>
</div>
