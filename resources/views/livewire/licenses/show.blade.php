<?php

use Livewire\Volt\Component;
use App\Models\SoftwareLicense;
use App\Models\User;
use App\Models\LicenseAssignment;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public SoftwareLicense $license;
    public bool $showAssignModal = false;
    public bool $showRevokeModal = false;
    public string $assignedUserId = '';
    public string $assignmentNotes = '';
    public string $revokeNotes = '';
    public int $assignmentToRevoke = 0;

    public function mount(SoftwareLicense $license): void
    {
        $this->license = $license->load(['createdBy', 'assignments.user', 'assignments.assignedBy']);
    }

    public function assignLicense(): void
    {
        $this->validate([
            'assignedUserId' => 'required|exists:users,id',
            'assignmentNotes' => 'nullable|string',
        ]);

        if ($this->license->available_licenses <= 0) {
            $this->error('No available licenses to assign!');
            return;
        }

        $user = User::find($this->assignedUserId);
        
        $this->license->assignTo($user, auth()->user(), $this->assignmentNotes);
        
        $this->success('License assigned successfully!');
        $this->showAssignModal = false;
        $this->assignedUserId = '';
        $this->assignmentNotes = '';
        $this->license->refresh();
    }

    public function revokeLicense(): void
    {
        $this->validate([
            'revokeNotes' => 'nullable|string',
            'assignmentToRevoke' => 'required|exists:license_assignments,id',
        ]);

        $assignment = LicenseAssignment::find($this->assignmentToRevoke);
        $this->license->revokeFrom($assignment->user, auth()->user(), $this->revokeNotes);
        
        $this->success('License revoked successfully!');
        $this->showRevokeModal = false;
        $this->revokeNotes = '';
        $this->assignmentToRevoke = 0;
        $this->license->refresh();
    }

    public function with(): array
    {
        $users = User::whereHas('workspaceMembers', function ($query) {
            $query->where('workspace_id', get_workspace_id());
        })->get();

        $activeAssignments = $this->license->assignments()->where('is_active', true)->with('user')->get();

        return [
            'users' => $users,
            'activeAssignments' => $activeAssignments,
        ];
    }
};

?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $license->name }}</h1>
            <p class="text-gray-600">{{ $license->vendor }} @if($license->version) v{{ $license->version }} @endif</p>
        </div>
        <div class="flex gap-3">
            @if($license->available_licenses > 0)
                <x-button wire:click="$set('showAssignModal', true)" icon="fas.user-plus" class="btn-primary">
                    Assign License
                </x-button>
            @endif
            <x-button icon="fas.edit" link="/licenses/{{ $license->id }}/edit" class="btn-outline">
                Edit
            </x-button>
            <x-button icon="fas.arrow-left" link="/licenses" class="btn-outline">
                Back
            </x-button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- License Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Information -->
            <x-card>
                <x-slot:title>License Information</x-slot:title>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <label class="text-sm font-medium text-gray-500">License Type</label>
                            <p class="text-lg font-semibold capitalize">{{ $license->license_type }}</p>
                        </div>
                        
                        <div>
                            <label class="text-sm font-medium text-gray-500">Status</label>
                            <div class="mt-1">
                                <x-badge :value="$license->status_label" :class="'badge-' . $license->status_color" />
                            </div>
                        </div>

                        @if($license->purchase_date)
                            <div>
                                <label class="text-sm font-medium text-gray-500">Purchase Date</label>
                                <p>{{ $license->purchase_date->format('M d, Y') }}</p>
                            </div>
                        @endif

                        @if($license->expiry_date)
                            <div>
                                <label class="text-sm font-medium text-gray-500">Expiry Date</label>
                                <p class="{{ $license->is_expired ? 'text-red-600' : ($license->days_until_expiry <= 30 ? 'text-yellow-600' : 'text-gray-900') }}">
                                    {{ $license->expiry_date->format('M d, Y') }}
                                    @if($license->is_expired)
                                        (Expired)
                                    @elseif($license->days_until_expiry <= 30)
                                        ({{ $license->days_until_expiry }} days left)
                                    @endif
                                </p>
                            </div>
                        @endif
                    </div>

                    <div class="space-y-4">
                        @if($license->cost)
                            <div>
                                <label class="text-sm font-medium text-gray-500">Cost</label>
                                <p class="text-lg font-semibold">
                                    ${{ number_format($license->cost, 2) }}
                                    @if($license->billing_cycle)
                                        <span class="text-sm text-gray-600">/ {{ $license->billing_cycle }}</span>
                                    @endif
                                </p>
                            </div>
                        @endif

                        <div>
                            <label class="text-sm font-medium text-gray-500">License Usage</label>
                            <div class="mt-2">
                                <div class="flex justify-between text-sm text-gray-600 mb-1">
                                    <span>{{ $license->used_licenses }} of {{ $license->total_licenses }} used</span>
                                    <span>{{ number_format($license->usage_percentage, 1) }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div 
                                        class="bg-blue-600 h-2 rounded-full" 
                                        style="width: {{ $license->usage_percentage }}%"
                                    ></div>
                                </div>
                            </div>
                        </div>

                        @if($license->auto_renewal)
                            <div>
                                <label class="text-sm font-medium text-gray-500">Auto Renewal</label>
                                <div class="mt-1">
                                    <x-badge value="Enabled" class="badge-success" />
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                @if($license->description)
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <label class="text-sm font-medium text-gray-500">Description</label>
                        <p class="mt-1">{{ $license->description }}</p>
                    </div>
                @endif

                @if($license->license_key)
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <label class="text-sm font-medium text-gray-500">License Key</label>
                        <div class="mt-1 p-3 bg-gray-50 rounded-lg">
                            <code class="text-sm font-mono break-all">{{ $license->license_key }}</code>
                        </div>
                    </div>
                @endif
            </x-card>

            <!-- Current Assignments -->
            <x-card>
                <x-slot:title>Current Assignments ({{ $activeAssignments->count() }})</x-slot:title>
                
                @if($activeAssignments->count() > 0)
                    <div class="space-y-4">
                        @foreach($activeAssignments as $assignment)
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center">
                                        <span class="text-sm font-medium text-gray-700">
                                            {{ substr($assignment->user->name, 0, 2) }}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ $assignment->user->name }}</p>
                                        <p class="text-sm text-gray-600">{{ $assignment->user->email }}</p>
                                        <p class="text-sm text-gray-500">
                                            Assigned {{ $assignment->assigned_at->format('M d, Y') }}
                                        </p>
                                    </div>
                                </div>
                                
                                <x-button 
                                    wire:click="$set('assignmentToRevoke', {{ $assignment->id }}); $set('showRevokeModal', true)"
                                    icon="fas.times" 
                                    class="btn-outline btn-sm text-red-600 hover:bg-red-50"
                                >
                                    Revoke
                                </x-button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-center py-8">No active assignments</p>
                @endif
            </x-card>

            <!-- Assignment History -->
            <x-card>
                <x-slot:title>Assignment History</x-slot:title>
                
                @if($license->assignments->count() > 0)
                    <div class="space-y-4">
                        @foreach($license->assignments->sortByDesc('assigned_at') as $assignment)
                            <div class="flex items-start justify-between p-4 bg-gray-50 rounded-lg">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                                            <span class="text-xs font-medium text-gray-700">
                                                {{ substr($assignment->user->name, 0, 2) }}
                                            </span>
                                        </div>
                                        <div>
                                            <p class="font-medium">{{ $assignment->user->name }}</p>
                                            <p class="text-sm text-gray-600">
                                                Assigned {{ $assignment->assigned_at->format('M d, Y') }}
                                                @if($assignment->revoked_at)
                                                    - Revoked {{ $assignment->revoked_at->format('M d, Y') }}
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                    
                                    @if($assignment->assignment_notes)
                                        <p class="mt-2 text-sm text-gray-600">{{ $assignment->assignment_notes }}</p>
                                    @endif
                                    
                                    @if($assignment->revocation_notes)
                                        <p class="mt-2 text-sm text-gray-600">
                                            <strong>Revocation notes:</strong> {{ $assignment->revocation_notes }}
                                        </p>
                                    @endif
                                </div>
                                
                                <div class="text-right">
                                    @if($assignment->is_active)
                                        <x-badge value="Active" class="badge-success" />
                                    @else
                                        <x-badge value="Revoked" class="badge-secondary" />
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-center py-8">No assignment history</p>
                @endif
            </x-card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Quick Stats -->
            <x-card>
                <x-slot:title>Quick Stats</x-slot:title>
                
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total Licenses</span>
                        <span class="font-semibold">{{ $license->total_licenses }}</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-gray-600">Used</span>
                        <span class="font-semibold text-blue-600">{{ $license->used_licenses }}</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-gray-600">Available</span>
                        <span class="font-semibold text-green-600">{{ $license->available_licenses }}</span>
                    </div>
                    
                    @if($license->cost)
                        <div class="flex justify-between">
                            <span class="text-gray-600">Monthly Cost</span>
                            <span class="font-semibold">${{ number_format($license->cost * $license->used_licenses, 2) }}</span>
                        </div>
                    @endif
                </div>
            </x-card>

            <!-- Quick Actions -->
            <x-card>
                <x-slot:title>Quick Actions</x-slot:title>
                
                <div class="space-y-3">
                    @if($license->available_licenses > 0)
                        <x-button wire:click="$set('showAssignModal', true)" icon="fas.user-plus" class="btn-primary w-full">
                            Assign License
                        </x-button>
                    @endif
                    
                    <x-button icon="fas.edit" link="/licenses/{{ $license->id }}/edit" class="btn-outline w-full">
                        Edit License
                    </x-button>
                </div>
            </x-card>

            <!-- License Metadata -->
            <x-card>
                <x-slot:title>Metadata</x-slot:title>
                
                <div class="space-y-3 text-sm">
                    <div>
                        <label class="text-gray-500">Created by</label>
                        <p>{{ $license->createdBy->name }}</p>
                    </div>
                    
                    <div>
                        <label class="text-gray-500">Created at</label>
                        <p>{{ $license->created_at->format('M d, Y H:i') }}</p>
                    </div>
                    
                    <div>
                        <label class="text-gray-500">Last updated</label>
                        <p>{{ $license->updated_at->format('M d, Y H:i') }}</p>
                    </div>
                </div>
            </x-card>
        </div>
    </div>

    <!-- Assign Modal -->
    <x-modal wire:model="showAssignModal" title="Assign License">
        <div class="space-y-4">
            <div class="p-4 bg-blue-50 rounded-lg">
                <p class="text-sm text-blue-800">
                    <strong>Available licenses:</strong> {{ $license->available_licenses }} of {{ $license->total_licenses }}
                </p>
            </div>
            
            <x-select 
                wire:model="assignedUserId"
                label="Assign to User"
                placeholder="Select user"
                :options="$users"
                option-value="id"
                option-label="name"
                required
            />
            
            <x-textarea 
                wire:model="assignmentNotes"
                label="Assignment Notes"
                placeholder="Optional notes about this assignment"
                rows="3"
            />
        </div>
        
        <x-slot:actions>
            <x-button wire:click="$set('showAssignModal', false)" class="btn-outline">
                Cancel
            </x-button>
            <x-button wire:click="assignLicense" class="btn-primary">
                Assign License
            </x-button>
        </x-slot:actions>
    </x-modal>

    <!-- Revoke Modal -->
    <x-modal wire:model="showRevokeModal" title="Revoke License">
        <div class="space-y-4">
            <div class="p-4 bg-yellow-50 rounded-lg">
                <p class="text-sm text-yellow-800">
                    Are you sure you want to revoke this license assignment? This action cannot be undone.
                </p>
            </div>
            
            <x-textarea 
                wire:model="revokeNotes"
                label="Revocation Notes"
                placeholder="Optional notes about why this license is being revoked"
                rows="3"
            />
        </div>
        
        <x-slot:actions>
            <x-button wire:click="$set('showRevokeModal', false)" class="btn-outline">
                Cancel
            </x-button>
            <x-button wire:click="revokeLicense" class="btn-warning">
                Revoke License
            </x-button>
        </x-slot:actions>
    </x-modal>
</div>
