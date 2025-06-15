<?php
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Certification;
use App\Models\EmployeeCertification;
use App\Models\Employee;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $employee_id = '';
    public $certification_id = '';
    public $status = '';
    public $expiry_filter = '';
    public $sortBy = 'obtained_date';
    public $sortDirection = 'desc';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->employee_id = '';
        $this->certification_id = '';
        $this->status = '';
        $this->expiry_filter = '';
        $this->resetPage();
    }

    public function renewCertification($employeeCertificationId)
    {
        $employeeCertification = EmployeeCertification::findOrFail($employeeCertificationId);
        $employeeCertification->update([
            'status' => 'active',
            'obtained_date' => now(),
            'expiry_date' => now()->addYears(2) // Default 2 years validity
        ]);
        $this->success('Certification renewed successfully!');
    }

    public function markExpired($employeeCertificationId)
    {
        $employeeCertification = EmployeeCertification::findOrFail($employeeCertificationId);
        $employeeCertification->update(['status' => 'expired']);
        $this->warning('Certification marked as expired.');
    }

    public function with()
    {
        $workspaceId = session('workspace_id');
        
        $employeeCertifications = EmployeeCertification::whereHas('employee', function($query) use ($workspaceId) {
                $query->where('workspace_id', $workspaceId);
            })
            ->with(['employee.user', 'certification'])
            ->when($this->search, function($query) {
                $query->whereHas('employee.user', function($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                })->orWhereHas('certification', function($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                })->orWhere('certificate_number', 'like', '%' . $this->search . '%');
            })
            ->when($this->employee_id, function($query) {
                $query->where('employee_id', $this->employee_id);
            })
            ->when($this->certification_id, function($query) {
                $query->where('certification_id', $this->certification_id);
            })
            ->when($this->status, function($query) {
                $query->where('status', $this->status);
            })
            ->when($this->expiry_filter, function($query) {
                if ($this->expiry_filter === 'expiring_soon') {
                    $query->where('expiry_date', '<=', now()->addDays(30))
                          ->where('expiry_date', '>', now());
                } elseif ($this->expiry_filter === 'expired') {
                    $query->where('expiry_date', '<', now());
                }
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(15);

        $employees = Employee::where('workspace_id', $workspaceId)
            ->with('user')
            ->get()
            ->map(fn($emp) => ['id' => $emp->id, 'name' => $emp->user->name]);

        $certifications = Certification::all()
            ->map(fn($cert) => ['id' => $cert->id, 'name' => $cert->name]);

        return [
            'employeeCertifications' => $employeeCertifications,
            'employees' => $employees,
            'certifications' => $certifications
        ];
    }
}; ?>

<div>
    <x-header title="Certification Management" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-button label="Add Certification" icon="fas.plus" link="/hr/certifications/create" class="btn-primary" />
        </x-slot:middle>
    </x-header>

    <!-- Filters -->
    <x-card class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
            <x-input 
                placeholder="Search certifications..." 
                wire:model.live.debounce.300ms="search" 
                icon="fas.search"
            />
            
            <x-select 
                placeholder="Employee" 
                wire:model.live="employee_id"
                :options="$employees"
            />
            
            <x-select 
                placeholder="Certification" 
                wire:model.live="certification_id"
                :options="$certifications"
            />
            
            <x-select 
                placeholder="Status" 
                wire:model.live="status"
                :options="[
                    ['id' => 'active', 'name' => 'Active'],
                    ['id' => 'expired', 'name' => 'Expired'],
                    ['id' => 'pending', 'name' => 'Pending'],
                    ['id' => 'suspended', 'name' => 'Suspended']
                ]"
            />
            
            <x-select 
                placeholder="Expiry Filter" 
                wire:model.live="expiry_filter"
                :options="[
                    ['id' => 'expiring_soon', 'name' => 'Expiring Soon (30 days)'],
                    ['id' => 'expired', 'name' => 'Expired']
                ]"
            />
            
            <x-button 
                label="Reset Filters" 
                icon="fas.times" 
                wire:click="resetFilters" 
                class="btn-ghost"
            />
        </div>
    </x-card>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <x-stat 
            title="Total Certifications" 
            :value="$employeeCertifications->total()" 
            icon="fas.certificate" 
            class="bg-gradient-to-r from-blue-500 to-blue-600 text-white"
        />
        
        <x-stat 
            title="Active Certifications" 
            :value="\App\Models\EmployeeCertification::whereHas('employee', fn($q) => $q->where('workspace_id', session('workspace_id')))->where('status', 'active')->count()" 
            icon="fas.check-circle" 
            class="bg-gradient-to-r from-green-500 to-green-600 text-white"
        />
        
        <x-stat 
            title="Expiring Soon" 
            :value="\App\Models\EmployeeCertification::whereHas('employee', fn($q) => $q->where('workspace_id', session('workspace_id')))->where('expiry_date', '<=', now()->addDays(30))->where('expiry_date', '>', now())->count()" 
            icon="fas.exclamation-triangle" 
            class="bg-gradient-to-r from-yellow-500 to-yellow-600 text-white"
        />
        
        <x-stat 
            title="Expired" 
            :value="\App\Models\EmployeeCertification::whereHas('employee', fn($q) => $q->where('workspace_id', session('workspace_id')))->where('expiry_date', '<', now())->count()" 
            icon="fas.times-circle" 
            class="bg-gradient-to-r from-red-500 to-red-600 text-white"
        />
    </div>

    <!-- Certification Cards Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
        @foreach($employeeCertifications as $empCert)
            @php
                $isExpired = $empCert->expiry_date && $empCert->expiry_date < now();
                $isExpiringSoon = $empCert->expiry_date && $empCert->expiry_date <= now()->addDays(30) && !$isExpired;
            @endphp
            
            <x-card class="border-l-4 {{ $isExpired ? 'border-red-500' : ($isExpiringSoon ? 'border-yellow-500' : 'border-green-500') }}">
                <div class="flex justify-between items-start mb-3">
                    <div class="flex items-center space-x-2">
                        <x-icon 
                            name="fas.certificate" 
                            class="w-5 h-5 {{ $isExpired ? 'text-red-500' : ($isExpiringSoon ? 'text-yellow-500' : 'text-green-500') }}"
                        />
                        <x-badge 
                            :value="$empCert->certification->name ?? 'Unknown'" 
                            class="badge-outline badge-sm"
                        />
                    </div>
                    
                    <x-dropdown>
                        <x-slot:trigger>
                            <x-button icon="fas.ellipsis-v" class="btn-ghost btn-sm" />
                        </x-slot:trigger>
                        
                        <x-menu-item title="View Details" link="/hr/certifications/{{ $empCert->id }}" icon="fas.eye" />
                        <x-menu-item title="Edit Certification" link="/hr/certifications/{{ $empCert->id }}/edit" icon="fas.edit" />
                        <x-menu-separator />
                        <x-menu-item title="View Employee" link="/hr/employees/{{ $empCert->employee_id }}" icon="fas.user" />
                        
                        @if($empCert->status === 'active' && ($isExpired || $isExpiringSoon))
                        <x-menu-separator />
                        <x-menu-item title="Renew Certification" wire:click="renewCertification({{ $empCert->id }})" icon="fas.redo" />
                        @endif
                        
                        @if($empCert->status === 'active' && $isExpired)
                        <x-menu-item title="Mark Expired" wire:click="markExpired({{ $empCert->id }})" icon="fas.ban" class="text-red-600" />
                        @endif
                    </x-dropdown>
                </div>

                <h3 class="font-semibold text-lg mb-2">{{ $empCert->certification->name ?? 'Unknown Certification' }}</h3>
                
                <!-- Employee Info -->
                <div class="flex items-center space-x-2 mb-4">
                    <x-avatar :image="$empCert->employee->user->avatar" class="!w-6 !h-6" />
                    <span class="text-sm text-gray-600">{{ $empCert->employee->user->name }}</span>
                </div>

                <!-- Certificate Number -->
                @if($empCert->certificate_number)
                <div class="flex items-center space-x-2 mb-4">
                    <x-icon name="fas.hashtag" class="w-4 h-4 text-gray-400" />
                    <span class="text-sm text-gray-600 font-mono">{{ $empCert->certificate_number }}</span>
                </div>
                @endif

                <!-- Issuing Authority -->
                @if($empCert->issuing_authority)
                <div class="flex items-center space-x-2 mb-4">
                    <x-icon name="fas.building" class="w-4 h-4 text-gray-400" />
                    <span class="text-sm text-gray-600">{{ $empCert->issuing_authority }}</span>
                </div>
                @endif

                <!-- Status -->
                <div class="flex justify-between items-center mb-4">
                    <x-badge 
                        :value="$empCert->status" 
                        class="badge-{{ $empCert->status === 'active' ? 'success' : ($empCert->status === 'expired' ? 'error' : ($empCert->status === 'pending' ? 'warning' : 'ghost')) }}"
                    />
                    
                    @if($isExpired)
                    <x-badge value="Expired" class="badge-error" />
                    @elseif($isExpiringSoon)
                    <x-badge value="Expiring Soon" class="badge-warning" />
                    @endif
                </div>

                <!-- Dates -->
                <div class="text-xs text-gray-500 space-y-1">
                    <div class="flex justify-between">
                        <span>Obtained:</span>
                        <span>{{ $empCert->obtained_date->format('M d, Y') }}</span>
                    </div>
                    @if($empCert->expiry_date)
                    <div class="flex justify-between">
                        <span>Expires:</span>
                        <span class="{{ $isExpired ? 'text-red-600 font-medium' : ($isExpiringSoon ? 'text-yellow-600 font-medium' : '') }}">
                            {{ $empCert->expiry_date->format('M d, Y') }}
                        </span>
                    </div>
                    @endif
                    @if($empCert->verification_date)
                    <div class="flex justify-between">
                        <span>Verified:</span>
                        <span class="text-green-600 font-medium">{{ $empCert->verification_date->format('M d, Y') }}</span>
                    </div>
                    @endif
                </div>

                <!-- Validity Progress Bar -->
                @if($empCert->expiry_date && $empCert->obtained_date)
                @php
                    $totalDays = $empCert->obtained_date->diffInDays($empCert->expiry_date);
                    $remainingDays = now()->diffInDays($empCert->expiry_date, false);
                    $progress = $totalDays > 0 ? max(0, min(100, ($remainingDays / $totalDays) * 100)) : 0;
                @endphp
                <div class="mt-4">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium">Validity</span>
                        <span class="text-sm font-bold">{{ max(0, $remainingDays) }} days left</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div 
                            class="h-2 rounded-full transition-all duration-300 {{ $progress > 30 ? 'bg-green-500' : ($progress > 10 ? 'bg-yellow-500' : 'bg-red-500') }}" 
                            style="width: {{ $progress }}%"
                        ></div>
                    </div>
                </div>
                @endif

                <!-- Quick Actions -->
                @if($isExpired || $isExpiringSoon)
                <x-slot:actions>
                    <div class="flex space-x-2">
                        <x-button 
                            label="Renew" 
                            wire:click="renewCertification({{ $empCert->id }})" 
                            class="btn-xs btn-primary"
                        />
                        
                        @if($isExpired && $empCert->status === 'active')
                        <x-button 
                            label="Mark Expired" 
                            wire:click="markExpired({{ $empCert->id }})" 
                            class="btn-xs btn-error"
                        />
                        @endif
                    </div>
                </x-slot:actions>
                @endif
            </x-card>
        @endforeach
    </div>

    <!-- Pagination -->
    @if($employeeCertifications->hasPages())
    <div class="mt-6">
        {{ $employeeCertifications->links() }}
    </div>
    @endif
</div>
