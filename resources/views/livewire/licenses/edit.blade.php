<?php

use Livewire\Volt\Component;
use App\Models\SoftwareLicense;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public SoftwareLicense $license;
    public string $name = '';
    public string $vendor = '';
    public string $version = '';
    public string $license_type = '';
    public string $license_key = '';
    public string $purchase_date = '';
    public string $expiry_date = '';
    public string $cost = '';
    public string $billing_cycle = '';
    public string $total_licenses = '';
    public string $description = '';
    public string $status = '';
    public bool $auto_renewal = false;

    public function mount(SoftwareLicense $license): void
    {
        $this->license = $license;
        $this->name = $license->name;
        $this->vendor = $license->vendor;
        $this->version = $license->version ?? '';
        $this->license_type = $license->license_type;
        $this->license_key = $license->license_key ?? '';
        $this->purchase_date = $license->purchase_date?->format('Y-m-d') ?? '';
        $this->expiry_date = $license->expiry_date?->format('Y-m-d') ?? '';
        $this->cost = $license->cost ? (string) $license->cost : '';
        $this->billing_cycle = $license->billing_cycle ?? '';
        $this->total_licenses = (string) $license->total_licenses;
        $this->description = $license->description ?? '';
        $this->status = $license->status;
        $this->auto_renewal = $license->auto_renewal;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'vendor' => 'required|string|max:255',
            'version' => 'nullable|string|max:255',
            'license_type' => 'required|in:perpetual,subscription,trial',
            'license_key' => 'nullable|string',
            'purchase_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:purchase_date',
            'cost' => 'nullable|numeric|min:0',
            'billing_cycle' => 'nullable|in:monthly,yearly,one-time',
            'total_licenses' => 'required|integer|min:1|min:' . $this->license->used_licenses,
            'description' => 'nullable|string',
            'status' => 'required|in:active,expired,cancelled',
            'auto_renewal' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'total_licenses.min' => 'Total licenses cannot be less than currently used licenses (' . $this->license->used_licenses . ').',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $this->license->update([
            'name' => $this->name,
            'vendor' => $this->vendor,
            'version' => $this->version ?: null,
            'license_type' => $this->license_type,
            'license_key' => $this->license_key ?: null,
            'purchase_date' => $this->purchase_date ?: null,
            'expiry_date' => $this->expiry_date ?: null,
            'cost' => $this->cost ?: null,
            'billing_cycle' => $this->billing_cycle ?: null,
            'total_licenses' => (int) $this->total_licenses,
            'description' => $this->description ?: null,
            'status' => $this->status,
            'auto_renewal' => $this->auto_renewal,
        ]);

        $this->success('Software license updated successfully!');
        $this->redirect('/licenses/' . $this->license->id);
    }
};

?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Software License</h1>
            <p class="text-gray-600">Update license information and settings</p>
        </div>
        <div class="flex gap-3">
            <x-button icon="fas.eye" link="/licenses/{{ $license->id }}" class="btn-outline">
                View License
            </x-button>
            <x-button icon="fas.arrow-left" link="/licenses" class="btn-outline">
                Back to Licenses
            </x-button>
        </div>
    </div>

    <!-- Current Usage Warning -->
    @if($license->used_licenses > 0)
        <x-alert icon="fas.info-circle" class="alert-info">
            <x-slot:title>License Usage Notice</x-slot:title>
            This license is currently assigned to {{ $license->used_licenses }} user(s). 
            You cannot reduce the total licenses below this number.
        </x-alert>
    @endif

    <!-- Form -->
    <x-form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Left Column -->
            <x-card>
                <x-slot:title>License Information</x-slot:title>
                
                <div class="space-y-4">
                    <!-- Name -->
                    <x-input 
                        wire:model="name"
                        label="License Name"
                        placeholder="e.g., Adobe Creative Cloud"
                        required
                    />

                    <!-- Vendor -->
                    <x-input 
                        wire:model="vendor"
                        label="Vendor"
                        placeholder="e.g., Adobe, Microsoft"
                        required
                    />

                    <!-- Version -->
                    <x-input 
                        wire:model="version"
                        label="Version"
                        placeholder="e.g., 2024, v1.0"
                    />

                    <!-- License Type -->
                    <x-select 
                        wire:model="license_type"
                        label="License Type"
                        :options="[
                            ['id' => 'perpetual', 'name' => 'Perpetual'],
                            ['id' => 'subscription', 'name' => 'Subscription'],
                            ['id' => 'trial', 'name' => 'Trial'],
                        ]"
                        option-value="id"
                        option-label="name"
                        required
                    />

                    <!-- License Key -->
                    <x-textarea 
                        wire:model="license_key"
                        label="License Key"
                        placeholder="Enter license key or activation code"
                        rows="3"
                    />

                    <!-- Description -->
                    <x-textarea 
                        wire:model="description"
                        label="Description"
                        placeholder="Brief description of the software"
                        rows="3"
                    />
                </div>
            </x-card>

            <!-- Right Column -->
            <x-card>
                <x-slot:title>Pricing & Usage</x-slot:title>
                
                <div class="space-y-4">
                    <!-- Purchase Date -->
                    <x-input 
                        wire:model="purchase_date"
                        label="Purchase Date"
                        type="date"
                    />

                    <!-- Expiry Date -->
                    <x-input 
                        wire:model="expiry_date"
                        label="Expiry Date"
                        type="date"
                    />

                    <!-- Cost -->
                    <x-input 
                        wire:model="cost"
                        label="Cost"
                        placeholder="0.00"
                        type="number"
                        step="0.01"
                        min="0"
                        prefix="$"
                    />

                    <!-- Billing Cycle -->
                    <x-select 
                        wire:model="billing_cycle"
                        label="Billing Cycle"
                        :options="[
                            ['id' => 'monthly', 'name' => 'Monthly'],
                            ['id' => 'yearly', 'name' => 'Yearly'],
                            ['id' => 'one-time', 'name' => 'One-time'],
                        ]"
                        option-value="id"
                        option-label="name"
                    />

                    <!-- Total Licenses -->
                    <x-input 
                        wire:model="total_licenses"
                        label="Total Licenses"
                        placeholder="Number of available licenses"
                        type="number"
                        min="{{ $license->used_licenses }}"
                        required
                        hint="Currently {{ $license->used_licenses }} licenses are in use"
                    />

                    <!-- Status -->
                    <x-select 
                        wire:model="status"
                        label="Status"
                        :options="[
                            ['id' => 'active', 'name' => 'Active'],
                            ['id' => 'expired', 'name' => 'Expired'],
                            ['id' => 'cancelled', 'name' => 'Cancelled'],
                        ]"
                        option-value="id"
                        option-label="name"
                        required
                    />

                    <!-- Auto Renewal -->
                    <x-checkbox 
                        wire:model="auto_renewal"
                        label="Auto Renewal"
                        hint="Automatically renew this license"
                    />
                </div>
            </x-card>
        </div>

        <!-- Current Usage Summary -->
        <x-card>
            <x-slot:title>Current Usage Summary</x-slot:title>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600">{{ $license->total_licenses }}</div>
                    <div class="text-sm text-gray-600">Total Licenses</div>
                </div>
                
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600">{{ $license->used_licenses }}</div>
                    <div class="text-sm text-gray-600">Used</div>
                </div>
                
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600">{{ $license->available_licenses }}</div>
                    <div class="text-sm text-gray-600">Available</div>
                </div>
                
                <div class="text-center">
                    <div class="text-2xl font-bold text-orange-600">{{ number_format($license->usage_percentage, 1) }}%</div>
                    <div class="text-sm text-gray-600">Usage</div>
                </div>
            </div>
            
            <div class="mt-4">
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div 
                        class="bg-blue-600 h-2 rounded-full" 
                        style="width: {{ $license->usage_percentage }}%"
                    ></div>
                </div>
            </div>
        </x-card>

        <!-- Submit Button -->
        <div class="flex justify-end gap-3">
            <x-button link="/licenses/{{ $license->id }}" class="btn-outline">
                Cancel
            </x-button>
            <x-button type="submit" class="btn-primary" icon="fas.save">
                Update License
            </x-button>
        </div>
    </x-form>
</div>
