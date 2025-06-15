<?php

use Livewire\Volt\Component;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\User;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public string $name = '';
    public string $description = '';
    public string $asset_category_id = '';
    public string $brand = '';
    public string $model = '';
    public string $serial_number = '';
    public string $purchase_price = '';
    public string $purchase_date = '';
    public string $warranty_expiry = '';
    public string $location = '';
    public string $room = '';
    public string $desk = '';
    public string $notes = '';
    public string $status = 'available';

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'asset_category_id' => 'required|exists:asset_categories,id',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'purchase_price' => 'nullable|numeric|min:0',
            'purchase_date' => 'nullable|date',
            'warranty_expiry' => 'nullable|date|after:purchase_date',
            'location' => 'nullable|string|max:255',
            'room' => 'nullable|string|max:255',
            'desk' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'status' => 'required|in:available,assigned,maintenance,retired,lost',
        ];
    }

    public function save(): void
    {
        $this->validate();

        Asset::create([
            'workspace_id' => get_workspace_id(),
            'created_by' => auth()->id(),
            'name' => $this->name,
            'description' => $this->description,
            'asset_category_id' => $this->asset_category_id,
            'brand' => $this->brand,
            'model' => $this->model,
            'serial_number' => $this->serial_number,
            'purchase_price' => $this->purchase_price ?: null,
            'purchase_date' => $this->purchase_date ?: null,
            'warranty_expiry' => $this->warranty_expiry ?: null,
            'location' => $this->location,
            'room' => $this->room,
            'desk' => $this->desk,
            'notes' => $this->notes,
            'status' => $this->status,
        ]);

        $this->success('Asset created successfully!');
        $this->redirect('/assets');
    }

    public function with(): array
    {
        $categories = AssetCategory::where('is_active', true)->get();

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
            <h1 class="text-2xl font-bold text-gray-900">Create Asset</h1>
            <p class="text-gray-600">Add a new asset to your inventory</p>
        </div>
        <x-button icon="fas.arrow-left" link="/assets" class="btn-outline">
            Back to Assets
        </x-button>
    </div>

    <!-- Form -->
    <x-form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Left Column -->
            <x-card>
                <x-slot:title>Basic Information</x-slot:title>

                <div class="space-y-4">
                    <!-- Name -->
                    <x-input
                        wire:model="name"
                        label="Asset Name"
                        placeholder="Enter asset name"
                        required
                    />

                    <!-- Category -->
                    <x-select
                        wire:model="asset_category_id"
                        label="Category"
                        placeholder="Select category"
                        :options="$categories"
                        option-value="id"
                        option-label="name"
                        required
                    />

                    <!-- Description -->
                    <x-textarea
                        wire:model="description"
                        label="Description"
                        placeholder="Enter asset description"
                        rows="3"
                    />

                    <!-- Status -->
                    <x-select
                        wire:model="status"
                        label="Status"
                        :options="[
                            ['id' => 'available', 'name' => 'Available'],
                            ['id' => 'assigned', 'name' => 'Assigned'],
                            ['id' => 'maintenance', 'name' => 'Maintenance'],
                            ['id' => 'retired', 'name' => 'Retired'],
                            ['id' => 'lost', 'name' => 'Lost'],
                        ]"
                        option-value="id"
                        option-label="name"
                        required
                    />
                </div>
            </x-card>

            <!-- Right Column -->
            <x-card>
                <x-slot:title>Asset Details</x-slot:title>

                <div class="space-y-4">
                    <!-- Brand -->
                    <x-input
                        wire:model="brand"
                        label="Brand"
                        placeholder="Enter brand name"
                    />

                    <!-- Model -->
                    <x-input
                        wire:model="model"
                        label="Model"
                        placeholder="Enter model name"
                    />

                    <!-- Serial Number -->
                    <x-input
                        wire:model="serial_number"
                        label="Serial Number"
                        placeholder="Enter serial number"
                    />

                    <!-- Purchase Price -->
                    <x-input
                        wire:model="purchase_price"
                        label="Purchase Price"
                        placeholder="0.00"
                        type="number"
                        step="0.01"
                        min="0"
                        prefix="$"
                    />

                    <!-- Purchase Date -->
                    <x-input
                        wire:model="purchase_date"
                        label="Purchase Date"
                        type="date"
                    />

                    <!-- Warranty Expiry -->
                    <x-input
                        wire:model="warranty_expiry"
                        label="Warranty Expiry"
                        type="date"
                    />
                </div>
            </x-card>
        </div>

        <!-- Location Information -->
        <x-card>
            <x-slot:title>Location Information</x-slot:title>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Location -->
                <x-input
                    wire:model="location"
                    label="Location"
                    placeholder="e.g., Office, Remote, Storage"
                />

                <!-- Room -->
                <x-input
                    wire:model="room"
                    label="Room"
                    placeholder="e.g., Dev Team, Meeting Room A"
                />

                <!-- Desk -->
                <x-input
                    wire:model="desk"
                    label="Desk/Position"
                    placeholder="e.g., Desk 12, Workstation 3"
                />
            </div>
        </x-card>

        <!-- Additional Notes -->
        <x-card>
            <x-slot:title>Additional Notes</x-slot:title>

            <x-textarea
                wire:model="notes"
                label="Notes"
                placeholder="Any additional notes about this asset"
                rows="4"
            />
        </x-card>

        <!-- Submit Button -->
        <div class="flex justify-end gap-3">
            <x-button link="/assets" class="btn-outline">
                Cancel
            </x-button>
            <x-button type="submit" class="btn-primary" icon="fas.save">
                Create Asset
            </x-button>
        </div>
    </x-form>
</div>
