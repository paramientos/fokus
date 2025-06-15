<?php

use Livewire\Volt\Component;
use App\Models\Asset;
use App\Models\User;
use App\Models\AssetAssignment;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public Asset $asset;
    public bool $showAssignModal = false;
    public bool $showReturnModal = false;
    public string $assignedUserId = '';
    public string $assignmentNotes = '';
    public string $returnNotes = '';
    public string $returnCondition = 'good';

    public function mount(Asset $asset): void
    {
        $this->asset = $asset->load(['category', 'assignedTo', 'createdBy', 'assignments.user', 'assignments.assignedBy']);
    }

    public function assignAsset(): void
    {
        $this->validate([
            'assignedUserId' => 'required|exists:users,id',
            'assignmentNotes' => 'nullable|string',
        ]);

        $user = User::find($this->assignedUserId);

        $this->asset->assignTo($user, auth()->user(), $this->assignmentNotes);

        $this->success('Asset assigned successfully!');
        $this->showAssignModal = false;
        $this->assignedUserId = '';
        $this->assignmentNotes = '';
        $this->asset->refresh();
    }

    public function returnAsset(): void
    {
        $this->validate([
            'returnNotes' => 'nullable|string',
            'returnCondition' => 'required|in:excellent,good,fair,poor',
        ]);

        $this->asset->returnAsset(auth()->user(), $this->returnNotes, $this->returnCondition);

        $this->success('Asset returned successfully!');
        $this->showReturnModal = false;
        $this->returnNotes = '';
        $this->returnCondition = 'good';
        $this->asset->refresh();
    }

    public function with(): array
    {
        $users = User::whereHas('workspaceMembers', function ($query) {
            $query->where('workspace_id', get_workspace_id());
        })->get();

        return [
            'users' => $users,
        ];
    }
};

?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $asset->name }}</h1>
            <p class="text-gray-600">{{ $asset->asset_tag }}</p>
        </div>
        <div class="flex gap-3">
            @if($asset->status === 'available')
                <x-button wire:click="$set('showAssignModal', true)" icon="fas.user-plus" class="btn-primary">
                    Assign Asset
                </x-button>
            @elseif($asset->status === 'assigned')
                <x-button wire:click="$set('showReturnModal', true)" icon="fas.undo" class="btn-warning">
                    Return Asset
                </x-button>
            @endif
            <x-button icon="fas.edit" link="/assets/{{ $asset->id }}/edit" class="btn-outline">
                Edit
            </x-button>
            <x-button icon="fas.arrow-left" link="/assets" class="btn-outline">
                Back
            </x-button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Asset Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Information -->
            <x-card>
                <x-slot:title>Asset Information</x-slot:title>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <label class="text-sm font-medium text-gray-500">Asset Tag</label>
                            <p class="text-lg font-semibold">{{ $asset->asset_tag }}</p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-500">Category</label>
                            <div class="flex items-center gap-2 mt-1">
                                <div class="w-3 h-3 rounded-full" style="background-color: {{ $asset->category->color }}"></div>
                                <span>{{ $asset->category->name }}</span>
                            </div>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-500">Status</label>
                            <div class="mt-1">
                                <x-badge :value="$asset->status_label" :class="'badge-' . $asset->status_color" />
                            </div>
                        </div>

                        @if($asset->brand || $asset->model)
                            <div>
                                <label class="text-sm font-medium text-gray-500">Brand & Model</label>
                                <p>{{ $asset->brand }} {{ $asset->model }}</p>
                            </div>
                        @endif

                        @if($asset->serial_number)
                            <div>
                                <label class="text-sm font-medium text-gray-500">Serial Number</label>
                                <p class="font-mono">{{ $asset->serial_number }}</p>
                            </div>
                        @endif
                    </div>

                    <div class="space-y-4">
                        @if($asset->purchase_price)
                            <div>
                                <label class="text-sm font-medium text-gray-500">Purchase Price</label>
                                <p class="text-lg font-semibold">${{ number_format($asset->purchase_price, 2) }}</p>
                            </div>
                        @endif

                        @if($asset->purchase_date)
                            <div>
                                <label class="text-sm font-medium text-gray-500">Purchase Date</label>
                                <p>{{ $asset->purchase_date->format('M d, Y') }}</p>
                            </div>
                        @endif

                        @if($asset->warranty_expiry)
                            <div>
                                <label class="text-sm font-medium text-gray-500">Warranty Expiry</label>
                                <p class="{{ $asset->is_warranty_expired ? 'text-red-600' : 'text-gray-900' }}">
                                    {{ $asset->warranty_expiry->format('M d, Y') }}
                                    @if($asset->is_warranty_expired)
                                        (Expired)
                                    @elseif($asset->days_until_warranty_expiry <= 30)
                                        ({{ $asset->days_until_warranty_expiry }} days left)
                                    @endif
                                </p>
                            </div>
                        @endif

                        @if($asset->location)
                            <div>
                                <label class="text-sm font-medium text-gray-500">Location</label>
                                <p>{{ $asset->location }}</p>
                                @if($asset->room)
                                    <p class="text-sm text-gray-600">{{ $asset->room }}</p>
                                @endif
                                @if($asset->desk)
                                    <p class="text-sm text-gray-600">{{ $asset->desk }}</p>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                @if($asset->description)
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <label class="text-sm font-medium text-gray-500">Description</label>
                        <p class="mt-1">{{ $asset->description }}</p>
                    </div>
                @endif

                @if($asset->notes)
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <label class="text-sm font-medium text-gray-500">Notes</label>
                        <p class="mt-1">{{ $asset->notes }}</p>
                    </div>
                @endif
            </x-card>

            <!-- Assignment History -->
            <x-card>
                <x-slot:title>Assignment History</x-slot:title>

                @if($asset->assignments->count() > 0)
                    <div class="space-y-4">
                        @foreach($asset->assignments->sortByDesc('assigned_at') as $assignment)
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
                                                @if($assignment->returned_at)
                                                    - Returned {{ $assignment->returned_at->format('M d, Y') }}
                                                @endif
                                            </p>
                                        </div>
                                    </div>

                                    @if($assignment->assignment_notes)
                                        <p class="mt-2 text-sm text-gray-600">{{ $assignment->assignment_notes }}</p>
                                    @endif

                                    @if($assignment->return_notes)
                                        <p class="mt-2 text-sm text-gray-600">
                                            <strong>Return notes:</strong> {{ $assignment->return_notes }}
                                        </p>
                                    @endif
                                </div>

                                <div class="text-right">
                                    @if($assignment->is_active)
                                        <x-badge value="Active" class="badge-success" />
                                    @else
                                        <x-badge value="Returned" class="badge-secondary" />
                                    @endif

                                    @if($assignment->condition_on_return)
                                        <div class="mt-1">
                                            <x-badge
                                                :value="ucfirst($assignment->condition_on_return)"
                                                :class="'badge-' . $assignment->condition_color"
                                            />
                                        </div>
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
            <!-- Current Assignment -->
            @if($asset->assignedTo)
                <x-card>
                    <x-slot:title>Currently Assigned To</x-slot:title>

                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-gray-300 rounded-full flex items-center justify-center">
                            <span class="text-sm font-medium text-gray-700">
                                {{ substr($asset->assignedTo->name, 0, 2) }}
                            </span>
                        </div>
                        <div>
                            <p class="font-medium">{{ $asset->assignedTo->name }}</p>
                            <p class="text-sm text-gray-600">{{ $asset->assignedTo->email }}</p>
                        </div>
                    </div>

                    @if($assignment = $asset->currentAssignment())
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <p class="text-sm text-gray-600">
                                Assigned {{ $assignment->assigned_at->format('M d, Y') }}
                            </p>
                            <p class="text-sm text-gray-600">
                                Duration: {{ $assignment->duration }}
                            </p>
                        </div>
                    @endif
                </x-card>
            @endif

            <!-- Quick Actions -->
            <x-card>
                <x-slot:title>Quick Actions</x-slot:title>

                <div class="space-y-3">
                    @if($asset->status === 'available')
                        <x-button wire:click="$set('showAssignModal', true)" icon="fas.user-plus" class="btn-primary w-full">
                            Assign Asset
                        </x-button>
                    @elseif($asset->status === 'assigned')
                        <x-button wire:click="$set('showReturnModal', true)" icon="fas.undo" class="btn-warning w-full">
                            Return Asset
                        </x-button>
                    @endif

                    <x-button icon="fas.edit" link="/assets/{{ $asset->id }}/edit" class="btn-outline w-full">
                        Edit Asset
                    </x-button>

                    @if($asset->status !== 'maintenance')
                        <x-button icon="fas.wrench" class="btn-outline w-full">
                            Mark for Maintenance
                        </x-button>
                    @endif
                </div>
            </x-card>

            <!-- Asset Metadata -->
            <x-card>
                <x-slot:title>Metadata</x-slot:title>

                <div class="space-y-3 text-sm">
                    <div>
                        <label class="text-gray-500">Created by</label>
                        <p>{{ $asset->createdBy->name }}</p>
                    </div>

                    <div>
                        <label class="text-gray-500">Created at</label>
                        <p>{{ $asset->created_at->format('M d, Y H:i') }}</p>
                    </div>

                    <div>
                        <label class="text-gray-500">Last updated</label>
                        <p>{{ $asset->updated_at->format('M d, Y H:i') }}</p>
                    </div>
                </div>
            </x-card>
        </div>
    </div>

    <!-- Assign Modal -->
    <x-modal wire:model="showAssignModal" title="Assign Asset">
        <div class="space-y-4">
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
            <x-button wire:click="assignAsset" class="btn-primary">
                Assign Asset
            </x-button>
        </x-slot:actions>
    </x-modal>

    <!-- Return Modal -->
    <x-modal wire:model="showReturnModal" title="Return Asset">
        <div class="space-y-4">
            <x-select
                wire:model="returnCondition"
                label="Asset Condition"
                :options="[
                    ['id' => 'excellent', 'name' => 'Excellent'],
                    ['id' => 'good', 'name' => 'Good'],
                    ['id' => 'fair', 'name' => 'Fair'],
                    ['id' => 'poor', 'name' => 'Poor'],
                ]"
                option-value="id"
                option-label="name"
                required
            />

            <x-textarea
                wire:model="returnNotes"
                label="Return Notes"
                placeholder="Optional notes about the asset condition or return"
                rows="3"
            />
        </div>

        <x-slot:actions>
            <x-button wire:click="$set('showReturnModal', false)" class="btn-outline">
                Cancel
            </x-button>
            <x-button wire:click="returnAsset" class="btn-warning">
                Return Asset
            </x-button>
        </x-slot:actions>
    </x-modal>
</div>
