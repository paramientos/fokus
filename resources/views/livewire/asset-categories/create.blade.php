<?php

use Livewire\Volt\Component;
use App\Models\AssetCategory;
use Mary\Traits\Toast;
use Illuminate\Support\Str;

new class extends Component {
    use Toast;

    public string $name = '';
    public string $slug = '';
    public string $description = '';
    public string $color = '#3b82f6';
    public string $icon = 'fas.box';

    public function updatedName(): void
    {
        $this->slug = Str::slug($this->name);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:asset_categories,slug',
            'description' => 'nullable|string',
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'icon' => 'required|string|max:255',
        ];
    }

    public function save(): void
    {
        $this->validate();

        AssetCategory::create([
            'workspace_id' => auth()->user()->current_workspace_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'color' => $this->color,
            'icon' => $this->icon,
            'is_active' => true,
        ]);

        $this->success('Asset category created successfully!');
        $this->redirect('/asset-categories');
    }

    public function with(): array
    {
        $iconOptions = [
            ['id' => 'fas.laptop', 'name' => 'Laptop'],
            ['id' => 'fas.desktop', 'name' => 'Desktop'],
            ['id' => 'fas.mobile-alt', 'name' => 'Mobile'],
            ['id' => 'fas.tablet-alt', 'name' => 'Tablet'],
            ['id' => 'fas.print', 'name' => 'Printer'],
            ['id' => 'fas.keyboard', 'name' => 'Keyboard'],
            ['id' => 'fas.mouse', 'name' => 'Mouse'],
            ['id' => 'fas.headphones', 'name' => 'Headphones'],
            ['id' => 'fas.camera', 'name' => 'Camera'],
            ['id' => 'fas.tv', 'name' => 'Monitor/TV'],
            ['id' => 'fas.chair', 'name' => 'Chair'],
            ['id' => 'fas.couch', 'name' => 'Furniture'],
            ['id' => 'fas.car', 'name' => 'Vehicle'],
            ['id' => 'fas.tools', 'name' => 'Tools'],
            ['id' => 'fas.box', 'name' => 'Box/Package'],
            ['id' => 'fas.server', 'name' => 'Server'],
            ['id' => 'fas.network-wired', 'name' => 'Network'],
            ['id' => 'fas.wifi', 'name' => 'WiFi'],
            ['id' => 'fas.phone', 'name' => 'Phone'],
            ['id' => 'fas.fax', 'name' => 'Fax'],
        ];

        $colorOptions = [
            ['id' => '#3b82f6', 'name' => 'Blue'],
            ['id' => '#10b981', 'name' => 'Green'],
            ['id' => '#f59e0b', 'name' => 'Yellow'],
            ['id' => '#ef4444', 'name' => 'Red'],
            ['id' => '#8b5cf6', 'name' => 'Purple'],
            ['id' => '#06b6d4', 'name' => 'Cyan'],
            ['id' => '#f97316', 'name' => 'Orange'],
            ['id' => '#84cc16', 'name' => 'Lime'],
            ['id' => '#ec4899', 'name' => 'Pink'],
            ['id' => '#6b7280', 'name' => 'Gray'],
        ];

        return [
            'iconOptions' => $iconOptions,
            'colorOptions' => $colorOptions,
        ];
    }
};

?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Create Asset Category</h1>
            <p class="text-gray-600">Add a new category for organizing your assets</p>
        </div>
        <x-button icon="fas.arrow-left" link="/asset-categories" class="btn-outline">
            Back to Categories
        </x-button>
    </div>

    <!-- Form -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form Fields -->
        <div class="lg:col-span-2">
            <x-form wire:submit="save">
                <x-card>
                    <x-slot:title>Category Information</x-slot:title>
                    
                    <div class="space-y-4">
                        <!-- Name -->
                        <x-input 
                            wire:model.live="name"
                            label="Category Name"
                            placeholder="Enter category name"
                            required
                        />

                        <!-- Slug -->
                        <x-input 
                            wire:model="slug"
                            label="Slug"
                            placeholder="category-slug"
                            hint="URL-friendly identifier (auto-generated from name)"
                            required
                        />

                        <!-- Description -->
                        <x-textarea 
                            wire:model="description"
                            label="Description"
                            placeholder="Describe what assets belong to this category"
                            rows="3"
                        />

                        <!-- Icon -->
                        <x-select 
                            wire:model="icon"
                            label="Icon"
                            placeholder="Select an icon"
                            :options="$iconOptions"
                            option-value="id"
                            option-label="name"
                            required
                        />

                        <!-- Color -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Color</label>
                            <div class="grid grid-cols-5 gap-2">
                                @foreach($colorOptions as $colorOption)
                                    <button
                                        type="button"
                                        wire:click="$set('color', '{{ $colorOption['id'] }}')"
                                        class="w-12 h-12 rounded-lg border-2 transition-all {{ $color === $colorOption['id'] ? 'border-gray-900 scale-110' : 'border-gray-300 hover:border-gray-400' }}"
                                        style="background-color: {{ $colorOption['id'] }}"
                                        title="{{ $colorOption['name'] }}"
                                    ></button>
                                @endforeach
                            </div>
                            <x-input 
                                wire:model="color"
                                type="color"
                                class="mt-2 w-20 h-10"
                            />
                        </div>
                    </div>
                </x-card>

                <!-- Submit Button -->
                <div class="flex justify-end gap-3">
                    <x-button link="/asset-categories" class="btn-outline">
                        Cancel
                    </x-button>
                    <x-button type="submit" class="btn-primary" icon="fas.save">
                        Create Category
                    </x-button>
                </div>
            </x-form>
        </div>

        <!-- Preview -->
        <div class="lg:col-span-1">
            <x-card>
                <x-slot:title>Preview</x-slot:title>
                
                <div class="space-y-4">
                    <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-lg">
                        <div 
                            class="w-12 h-12 rounded-lg flex items-center justify-center text-white"
                            style="background-color: {{ $color }}"
                        >
                            <x-icon :name="$icon" class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="font-semibold">{{ $name ?: 'Category Name' }}</h3>
                            <p class="text-sm text-gray-600">0 assets</p>
                        </div>
                    </div>
                    
                    @if($description)
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <p class="text-sm text-gray-600">{{ $description }}</p>
                        </div>
                    @endif
                    
                    <div class="text-xs text-gray-500">
                        <p><strong>Slug:</strong> {{ $slug ?: 'category-slug' }}</p>
                        <p><strong>Color:</strong> {{ $color }}</p>
                        <p><strong>Icon:</strong> {{ $icon }}</p>
                    </div>
                </div>
            </x-card>

            <!-- Icon Reference -->
            <x-card class="mt-6">
                <x-slot:title>Available Icons</x-slot:title>
                
                <div class="grid grid-cols-4 gap-2 text-center">
                    @foreach(array_slice($iconOptions, 0, 12) as $iconOption)
                        <button
                            type="button"
                            wire:click="$set('icon', '{{ $iconOption['id'] }}')"
                            class="p-2 rounded hover:bg-gray-100 transition-colors {{ $icon === $iconOption['id'] ? 'bg-blue-100 text-blue-600' : '' }}"
                            title="{{ $iconOption['name'] }}"
                        >
                            <x-icon :name="$iconOption['id']" class="w-4 h-4 mx-auto" />
                        </button>
                    @endforeach
                </div>
                
                <p class="text-xs text-gray-500 mt-2 text-center">
                    And {{ count($iconOptions) - 12 }} more icons available in the dropdown
                </p>
            </x-card>
        </div>
    </div>
</div>
