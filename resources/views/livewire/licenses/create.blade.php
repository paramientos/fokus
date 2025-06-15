<?php

use Livewire\Volt\Component;
use App\Models\SoftwareLicense;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public string $name = '';
    public string $vendor = '';
    public string $version = '';
    public string $license_type = 'subscription';
    public string $license_key = '';
    public string $purchase_date = '';
    public string $expiry_date = '';
    public string $cost = '';
    public string $billing_cycle = 'monthly';
    public string $total_licenses = '';
    public string $description = '';
    public string $status = 'active';
    public bool $auto_renewal = false;

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
            'total_licenses' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'status' => 'required|in:active,expired,cancelled',
            'auto_renewal' => 'boolean',
        ];
    }

    public function save(): void
    {
        $this->validate();

        SoftwareLicense::create([
            'workspace_id' => get_workspace_id(),
            'created_by' => auth()->id(),
            'name' => $this->name,
            'vendor' => $this->vendor,
            'version' => $this->version,
            'license_type' => $this->license_type,
            'license_key' => $this->license_key,
            'purchase_date' => $this->purchase_date ?: null,
            'expiry_date' => $this->expiry_date ?: null,
            'cost' => $this->cost ?: null,
            'billing_cycle' => $this->billing_cycle,
            'total_licenses' => (int) $this->total_licenses,
            'used_licenses' => 0,
            'description' => $this->description,
            'status' => $this->status,
            'auto_renewal' => $this->auto_renewal,
        ]);

        $this->success('Software license created successfully!');
        $this->redirect('/licenses');
    }
};

?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Create Software License</h1>
            <p class="text-gray-600">Add a new software license to your inventory</p>
        </div>
        <x-button icon="fas.arrow-left" link="/licenses" class="btn-outline">
            Back to Licenses
        </x-button>
    </div>

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
                        min="1"
                        required
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

        <!-- Submit Button -->
        <div class="flex justify-end gap-3">
            <x-button link="/licenses" class="btn-outline">
                Cancel
            </x-button>
            <x-button type="submit" class="btn-primary" icon="fas.save">
                Create License
            </x-button>
        </div>
    </x-form>
</div>
