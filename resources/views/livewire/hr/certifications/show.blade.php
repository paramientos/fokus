<?php

use App\Models\Certification;
use App\Models\EmployeeCertification;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public Certification $certification;
    public $employeeCertifications;
    public $stats;

    public function mount(Certification $certification)
    {
        $this->certification = $certification;
        $this->loadData();
    }

    public function loadData()
    {
        // Load employee certifications
        $this->employeeCertifications = EmployeeCertification::with(['employee.user'])
            ->where('certification_id', $this->certification->id)
            ->whereHas('employee', function($q) {
                $q->where('workspace_id', session('workspace_id'));
            })
            ->latest()
            ->get();

        // Calculate stats
        $this->stats = [
            'total_employees' => $this->employeeCertifications->count(),
            'active_certifications' => $this->employeeCertifications->where('status', 'active')->count(),
            'expired_certifications' => $this->employeeCertifications->where('status', 'expired')->count(),
            'pending_certifications' => $this->employeeCertifications->where('status', 'pending')->count(),
        ];
    }

    public function getStatusColor($status)
    {
        return match($status) {
            'active' => 'success',
            'expired' => 'error',
            'pending' => 'warning',
            'revoked' => 'error',
            default => 'ghost'
        };
    }

    public function refreshData()
    {
        $this->loadData();
        $this->success('Data refreshed successfully!');
    }
}
?>

<div>
    <x-header title="Certification Details" separator>
        <x-slot:middle class="!justify-end">
            <x-button icon="fas.rotate" class="btn-circle btn-ghost btn-sm" wire:click="refreshData" />
        </x-slot:middle>
        <x-slot:actions>
            <x-button label="Edit" icon="fas.edit" link="{{ route('hr.certifications.edit', $certification) }}" class="btn-primary" />
            <x-button label="Back to List" link="{{ route('hr.certifications.index') }}" />
        </x-slot:actions>
    </x-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Certification Details -->
        <div class="lg:col-span-2 space-y-6">
            <x-card title="Certification Information">
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-700">Name</label>
                            <p class="text-lg font-semibold">{{ $certification->name }}</p>
                        </div>
                        
                        <div>
                            <label class="text-sm font-medium text-gray-700">Category</label>
                            <x-badge :value="$certification->category" class="badge-primary" />
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Issuing Organization</label>
                        <p class="text-gray-900">{{ $certification->issuing_organization }}</p>
                    </div>

                    @if($certification->description)
                    <div>
                        <label class="text-sm font-medium text-gray-700">Description</label>
                        <p class="text-gray-900">{{ $certification->description }}</p>
                    </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @if($certification->validity_months)
                        <div>
                            <label class="text-sm font-medium text-gray-700">Validity Period</label>
                            <p class="text-gray-900">{{ $certification->validity_months }} months</p>
                        </div>
                        @endif

                        @if($certification->cost)
                        <div>
                            <label class="text-sm font-medium text-gray-700">Cost</label>
                            <p class="text-gray-900">${{ number_format($certification->cost, 2) }}</p>
                        </div>
                        @endif

                        <div>
                            <label class="text-sm font-medium text-gray-700">Status</label>
                            <div class="flex items-center space-x-2">
                                <x-badge :value="$certification->is_active ? 'Active' : 'Inactive'" 
                                         class="badge-{{ $certification->is_active ? 'success' : 'error' }}" />
                                @if($certification->is_mandatory)
                                    <x-badge value="Mandatory" class="badge-warning" />
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($certification->certification_url)
                    <div>
                        <label class="text-sm font-medium text-gray-700">Certification URL</label>
                        <a href="{{ $certification->certification_url }}" target="_blank" 
                           class="text-blue-600 hover:text-blue-800 underline">
                            {{ $certification->certification_url }}
                        </a>
                    </div>
                    @endif

                    @if($certification->requirements)
                    <div>
                        <label class="text-sm font-medium text-gray-700">Requirements</label>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            @foreach($certification->requirements as $requirement)
                                <p class="text-gray-900">{{ $requirement }}</p>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </x-card>

            <!-- Employee Certifications List -->
            <x-card title="Employee Certifications">
                @if($employeeCertifications->count() > 0)
                    <div class="space-y-3">
                        @foreach($employeeCertifications as $empCert)
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <x-avatar :image="$empCert->employee->user->avatar ?? ''" 
                                              :title="$empCert->employee->user->name" 
                                              class="!w-10 !h-10" />
                                    <div>
                                        <p class="font-medium">{{ $empCert->employee->user->name }}</p>
                                        <p class="text-sm text-gray-600">{{ $empCert->employee->position ?? 'N/A' }}</p>
                                        @if($empCert->obtained_date)
                                            <p class="text-xs text-gray-500">
                                                Obtained: {{ $empCert->obtained_date->format('M d, Y') }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="text-right">
                                    <x-badge :value="ucfirst($empCert->status)" 
                                             class="badge-{{ $this->getStatusColor($empCert->status) }}" />
                                    @if($empCert->expiry_date)
                                        <p class="text-xs text-gray-500 mt-1">
                                            Expires: {{ $empCert->expiry_date->format('M d, Y') }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <x-icon name="fas.certificate" class="w-12 h-12 text-gray-400 mx-auto mb-3" />
                        <p class="text-gray-500">No employees have this certification yet</p>
                    </div>
                @endif
            </x-card>
        </div>

        <!-- Right Column - Statistics -->
        <div class="space-y-6">
            <x-card title="Statistics">
                <div class="space-y-4">
                    <div class="text-center">
                        <p class="text-3xl font-bold text-blue-600">{{ $stats['total_employees'] }}</p>
                        <p class="text-sm text-gray-600">Total Employees</p>
                    </div>
                    
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Active</span>
                            <x-badge :value="$stats['active_certifications']" class="badge-success" />
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Expired</span>
                            <x-badge :value="$stats['expired_certifications']" class="badge-error" />
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Pending</span>
                            <x-badge :value="$stats['pending_certifications']" class="badge-warning" />
                        </div>
                    </div>
                </div>
            </x-card>

            <x-card title="Quick Actions">
                <div class="space-y-3">
                    <x-button 
                        label="Assign to Employee" 
                        icon="fas.user-plus" 
                        class="btn-primary w-full" 
                        link="/hr/employees"
                    />
                    
                    <x-button 
                        label="View Reports" 
                        icon="fas.chart-bar" 
                        class="btn-secondary w-full" 
                        link="/hr/reports"
                    />
                    
                    <x-button 
                        label="Edit Certification" 
                        icon="fas.edit" 
                        class="btn-outline w-full" 
                        link="{{ route('hr.certifications.edit', $certification) }}"
                    />
                </div>
            </x-card>
        </div>
    </div>
</div>
