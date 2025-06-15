<?php

use App\Models\Certification;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public Certification $certification;
    public string $name = '';
    public string $description = '';
    public string $issuing_organization = '';
    public string $category = '';
    public ?int $validity_months = null;
    public ?float $cost = null;
    public string $certification_url = '';
    public array $requirements = [];
    public bool $is_mandatory = false;
    public bool $is_active = true;

    public array $categories = [
        'Technical' => 'Technical',
        'Management' => 'Management',
        'Safety' => 'Safety',
        'Compliance' => 'Compliance',
        'Professional' => 'Professional',
        'Language' => 'Language',
        'Other' => 'Other'
    ];

    public function mount(Certification $certification)
    {
        $this->certification = $certification;
        $this->name = $certification->name;
        $this->description = $certification->description ?? '';
        $this->issuing_organization = $certification->issuing_organization;
        $this->category = $certification->category ?? '';
        $this->validity_months = $certification->validity_months;
        $this->cost = $certification->cost;
        $this->certification_url = $certification->certification_url ?? '';
        $this->requirements = $certification->requirements ?? [];
        $this->is_mandatory = $certification->is_mandatory;
        $this->is_active = $certification->is_active;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'issuing_organization' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'validity_months' => 'nullable|integer|min:1|max:120',
            'cost' => 'nullable|numeric|min:0',
            'certification_url' => 'nullable|url',
            'requirements' => 'nullable|array',
            'is_mandatory' => 'boolean',
            'is_active' => 'boolean'
        ];
    }

    public function save()
    {
        $this->validate();

        try {
            $this->certification->update([
                'name' => $this->name,
                'description' => $this->description,
                'issuing_organization' => $this->issuing_organization,
                'category' => $this->category,
                'validity_months' => $this->validity_months,
                'cost' => $this->cost,
                'certification_url' => $this->certification_url,
                'requirements' => $this->requirements,
                'is_mandatory' => $this->is_mandatory,
                'is_active' => $this->is_active,
            ]);

            $this->success('Certification updated successfully!');
            return redirect()->route('hr.certifications.show', $this->certification);
        } catch (\Exception $e) {
            $this->error('Failed to update certification: ' . $e->getMessage());
        }
    }

    public function cancel()
    {
        return redirect()->route('hr.certifications.show', $this->certification);
    }
}
?>

<div>
    <x-header title="Edit Certification" separator>
        <x-slot:middle class="!justify-end">
            <x-button label="Cancel" link="{{ route('hr.certifications.show', $certification) }}" />
        </x-slot:middle>
    </x-header>

    <x-form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Left Column -->
            <div class="space-y-6">
                <x-card title="Basic Information">
                    <div class="space-y-4">
                        <x-input 
                            label="Certification Name" 
                            wire:model="name" 
                            placeholder="e.g., AWS Solutions Architect"
                            required
                        />

                        <x-textarea 
                            label="Description" 
                            wire:model="description" 
                            placeholder="Brief description of the certification"
                            rows="3"
                        />

                        <x-input 
                            label="Issuing Organization" 
                            wire:model="issuing_organization" 
                            placeholder="e.g., Amazon Web Services"
                            required
                        />

                        <x-select 
                            label="Category" 
                            wire:model="category" 
                            :options="$categories"
                            placeholder="Select category"
                            required
                        />
                    </div>
                </x-card>

                <x-card title="Settings">
                    <div class="space-y-4">
                        <div class="flex items-center space-x-4">
                            <x-checkbox 
                                label="Mandatory Certification" 
                                wire:model="is_mandatory" 
                            />
                            
                            <x-checkbox 
                                label="Active" 
                                wire:model="is_active" 
                            />
                        </div>
                    </div>
                </x-card>
            </div>

            <!-- Right Column -->
            <div class="space-y-6">
                <x-card title="Additional Details">
                    <div class="space-y-4">
                        <x-input 
                            label="Validity Period (Months)" 
                            wire:model="validity_months" 
                            type="number"
                            placeholder="e.g., 36"
                            min="1"
                            max="120"
                        />

                        <x-input 
                            label="Cost" 
                            wire:model="cost" 
                            type="number"
                            step="0.01"
                            placeholder="0.00"
                            prefix="$"
                        />

                        <x-input 
                            label="Certification URL" 
                            wire:model="certification_url" 
                            placeholder="https://..."
                            type="url"
                        />
                    </div>
                </x-card>

                <x-card title="Requirements">
                    <div class="space-y-4">
                        <x-textarea 
                            label="Prerequisites & Requirements" 
                            wire:model="requirements.0" 
                            placeholder="List any prerequisites, experience requirements, or preparation needed"
                            rows="4"
                        />
                        
                        <div class="text-sm text-gray-500">
                            <p><strong>Examples:</strong></p>
                            <ul class="list-disc list-inside mt-1 space-y-1">
                                <li>2+ years of cloud experience</li>
                                <li>Basic networking knowledge</li>
                                <li>Completion of prerequisite courses</li>
                            </ul>
                        </div>
                    </div>
                </x-card>
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Cancel" wire:click="cancel" />
            <x-button label="Update Certification" class="btn-primary" type="submit" spinner="save" />
        </x-slot:actions>
    </x-form>
</div>
