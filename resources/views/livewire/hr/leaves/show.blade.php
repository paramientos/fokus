<?php

use Livewire\Component;
use App\Models\LeaveRequest;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public LeaveRequest $leave;

    public function mount(LeaveRequest $leave)
    {
        // Check workspace access
        if ($leave->workspace_id !== session('workspace_id')) {
            abort(403);
        }

        $this->leave = $leave->load(['employee.user', 'leaveType', 'approver.user']);
    }

    public function approveLeave()
    {
        $this->leave->update([
            'status' => 'approved',
            'approved_by' => auth()->user()->employee->id ?? null,
            'approved_at' => now()
        ]);

        $this->leave->refresh();
        $this->success('Leave request approved successfully!');
    }

    public function rejectLeave()
    {
        $this->leave->update([
            'status' => 'rejected',
            'approved_by' => auth()->user()->employee->id ?? null,
            'approved_at' => now()
        ]);

        $this->leave->refresh();
        $this->success('Leave request rejected successfully!');
    }

    public function cancelLeave()
    {
        $this->leave->update(['status' => 'cancelled']);
        $this->leave->refresh();
        $this->success('Leave request cancelled successfully!');
    }

    public function deleteLeave()
    {
        $this->leave->delete();
        $this->success('Leave request deleted successfully!');
        return redirect()->route('hr.leaves.index');
    }

    public function getStatusColor($status)
    {
        return match($status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'approved' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
            'cancelled' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }
};
?>

<div>
    <x-header title="Leave Request Details" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            @if($leave->status === 'pending')
            <x-button 
                label="Approve" 
                icon="fas.check" 
                wire:click="approveLeave"
                wire:confirm="Are you sure you want to approve this leave request?"
                class="btn-success" 
                spinner="approveLeave"
            />
            <x-button 
                label="Reject" 
                icon="fas.times" 
                wire:click="rejectLeave"
                wire:confirm="Are you sure you want to reject this leave request?"
                class="btn-error" 
                spinner="rejectLeave"
            />
            @endif
            <x-button 
                label="Back" 
                icon="fas.arrow-left" 
                link="/hr/leaves" 
                class="btn-ghost" 
            />
        </x-slot:middle>
    </x-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Leave Overview -->
            <x-card title="Leave Request Overview">
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">Employee</h4>
                            <p class="text-gray-600">{{ $leave->employee->user->name }}</p>
                        </div>

                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">Leave Type</h4>
                            <p class="text-gray-600">{{ $leave->leaveType->name }}</p>
                        </div>

                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">Start Date</h4>
                            <p class="text-gray-600">{{ $leave->start_date->format('M d, Y') }}</p>
                        </div>

                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">End Date</h4>
                            <p class="text-gray-600">{{ $leave->end_date->format('M d, Y') }}</p>
                        </div>

                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">Duration</h4>
                            <p class="text-gray-600">{{ $leave->days_requested }} days</p>
                        </div>

                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">Status</h4>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $this->getStatusColor($leave->status) }}">
                                {{ ucwords($leave->status) }}
                            </span>
                        </div>
                    </div>

                    <!-- Duration Visualization -->
                    <div class="p-4 bg-blue-50 rounded-lg">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-blue-900">Leave Duration</span>
                            <span class="text-sm text-blue-700">{{ $leave->days_requested }} days</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-blue-700">
                            <x-icon name="fas.calendar-alt" class="w-4 h-4" />
                            <span>{{ $leave->start_date->format('M d') }} - {{ $leave->end_date->format('M d, Y') }}</span>
                        </div>
                    </div>
                </div>
            </x-card>

            <!-- Reason -->
            @if($leave->reason)
            <x-card title="Reason for Leave">
                <p class="text-gray-600">{{ $leave->reason }}</p>
            </x-card>
            @endif

            <!-- Approval Information -->
            @if($leave->approved_by)
            <x-card title="Approval Information">
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Approved/Rejected by:</span>
                        <span class="font-medium">{{ $leave->approver->user->name ?? 'System' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Date:</span>
                        <span class="font-medium">{{ $leave->approved_at ? $leave->approved_at->format('M d, Y H:i') : 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Status:</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $this->getStatusColor($leave->status) }}">
                            {{ ucwords($leave->status) }}
                        </span>
                    </div>
                </div>
            </x-card>
            @endif

            <!-- Timeline -->
            <x-card title="Request Timeline">
                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                            <x-icon name="fas.plus" class="w-4 h-4 text-blue-600" />
                        </div>
                        <div>
                            <h5 class="font-medium text-gray-900">Request Created</h5>
                            <p class="text-gray-600 text-sm">{{ $leave->created_at->format('M d, Y H:i') }}</p>
                        </div>
                    </div>

                    @if($leave->approved_at)
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 {{ $leave->status === 'approved' ? 'bg-green-100' : 'bg-red-100' }} rounded-full flex items-center justify-center">
                            <x-icon name="fas.{{ $leave->status === 'approved' ? 'check' : 'times' }}" class="w-4 h-4 {{ $leave->status === 'approved' ? 'text-green-600' : 'text-red-600' }}" />
                        </div>
                        <div>
                            <h5 class="font-medium text-gray-900">Request {{ ucwords($leave->status) }}</h5>
                            <p class="text-gray-600 text-sm">{{ $leave->approved_at->format('M d, Y H:i') }}</p>
                            @if($leave->approver)
                            <p class="text-gray-600 text-sm">by {{ $leave->approver->user->name }}</p>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if($leave->status === 'approved' && $leave->start_date->isFuture())
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                            <x-icon name="fas.clock" class="w-4 h-4 text-yellow-600" />
                        </div>
                        <div>
                            <h5 class="font-medium text-gray-900">Leave Starts</h5>
                            <p class="text-gray-600 text-sm">{{ $leave->start_date->format('M d, Y') }}</p>
                            <p class="text-gray-600 text-sm">{{ $leave->start_date->diffForHumans() }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </x-card>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Quick Actions -->
            <x-card title="Actions">
                <div class="space-y-3">
                    @if($leave->status === 'pending')
                    <x-button
                        label="Approve Request"
                        icon="fas.check"
                        wire:click="approveLeave"
                        wire:confirm="Are you sure you want to approve this leave request?"
                        class="btn-success w-full"
                        spinner="approveLeave"
                    />

                    <x-button
                        label="Reject Request"
                        icon="fas.times"
                        wire:click="rejectLeave"
                        wire:confirm="Are you sure you want to reject this leave request?"
                        class="btn-error w-full"
                        spinner="rejectLeave"
                    />
                    @endif

                    @if(in_array($leave->status, ['pending', 'approved']))
                    <x-button
                        label="Cancel Request"
                        icon="fas.ban"
                        wire:click="cancelLeave"
                        wire:confirm="Are you sure you want to cancel this leave request?"
                        class="btn-warning w-full"
                        spinner="cancelLeave"
                    />
                    @endif

                    <x-button
                        label="Print Request"
                        icon="fas.print"
                        onclick="window.print()"
                        class="btn-outline w-full"
                    />

                    <x-button
                        label="Delete Request"
                        icon="fas.trash"
                        wire:click="deleteLeave"
                        wire:confirm="Are you sure you want to delete this leave request?"
                        class="btn-error w-full"
                        spinner="deleteLeave"
                    />
                </div>
            </x-card>

            <!-- Leave Info -->
            <x-card title="Leave Information">
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Created:</span>
                        <span class="font-medium">{{ $leave->created_at->format('M d, Y') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600">Updated:</span>
                        <span class="font-medium">{{ $leave->updated_at->format('M d, Y') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600">Days Requested:</span>
                        <span class="font-medium">{{ $leave->days_requested }}</span>
                    </div>

                    @if($leave->start_date->isFuture())
                    <div class="flex justify-between">
                        <span class="text-gray-600">Starts in:</span>
                        <span class="font-medium">{{ $leave->start_date->diffForHumans() }}</span>
                    </div>
                    @elseif($leave->start_date->isPast() && $leave->end_date->isFuture())
                    <div class="flex justify-between">
                        <span class="text-gray-600">Status:</span>
                        <span class="font-medium text-blue-600">Currently on leave</span>
                    </div>
                    @elseif($leave->end_date->isPast())
                    <div class="flex justify-between">
                        <span class="text-gray-600">Status:</span>
                        <span class="font-medium text-gray-600">Leave completed</span>
                    </div>
                    @endif
                </div>
            </x-card>

            <!-- Employee Info -->
            <x-card title="Employee Details">
                <div class="text-center mb-4">
                    <div class="w-16 h-16 bg-gray-300 rounded-full mx-auto mb-3 flex items-center justify-center">
                        <x-icon name="fas.user" class="w-6 h-6 text-gray-600" />
                    </div>
                    <h3 class="font-semibold">{{ $leave->employee->user->name }}</h3>
                    <p class="text-gray-600 text-sm">{{ $leave->employee->position }}</p>
                </div>

                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Department:</span>
                        <span class="font-medium">{{ $leave->employee->department }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600">Hire Date:</span>
                        <span class="font-medium">{{ $leave->employee->hire_date->format('M d, Y') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600">Status:</span>
                        <span class="font-medium capitalize text-green-600">{{ $leave->employee->status }}</span>
                    </div>
                </div>
            </x-card>
        </div>
    </div>
</div>
