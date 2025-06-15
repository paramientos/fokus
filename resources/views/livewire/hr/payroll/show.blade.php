<?php

use Livewire\Component;
use App\Models\Payroll;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public Payroll $payroll;

    public function mount(Payroll $payroll)
    {
        // Check workspace access
        if ($payroll->workspace_id !== session('workspace_id')) {
            abort(403);
        }

        $this->payroll = $payroll->load(['employee.user']);
    }

    public function approvePayroll()
    {
        $this->payroll->update(['status' => 'approved']);
        $this->payroll->refresh();
        $this->success('Payroll approved successfully!');
    }

    public function processPayroll()
    {
        $this->payroll->update(['status' => 'paid']);
        $this->payroll->refresh();
        $this->success('Payroll processed successfully!');
    }

    public function cancelPayroll()
    {
        $this->payroll->update(['status' => 'cancelled']);
        $this->payroll->refresh();
        $this->success('Payroll cancelled successfully!');
    }

    public function deletePayroll()
    {
        $this->payroll->delete();
        $this->success('Payroll deleted successfully!');
        return redirect()->route('hr.payroll.index');
    }

    public function getStatusColor($status)
    {
        return match($status) {
            'draft' => 'bg-gray-100 text-gray-800',
            'approved' => 'bg-blue-100 text-blue-800',
            'paid' => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    public function calculateNetPay()
    {
        return $this->payroll->base_salary + 
               $this->payroll->overtime_pay + 
               $this->payroll->allowances - 
               $this->payroll->other_deductions - 
               $this->payroll->tax_deduction;
    }
};
?>

<div>
    <x-header title="Payroll Details" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            @if($payroll->status === 'draft')
            <x-button 
                label="Approve" 
                icon="fas.check" 
                wire:click="approvePayroll"
                wire:confirm="Are you sure you want to approve this payroll?"
                class="btn-success" 
                spinner="approvePayroll"
            />
            @endif
            @if($payroll->status === 'approved')
            <x-button 
                label="Process Payment" 
                icon="fas.credit-card" 
                wire:click="processPayroll"
                wire:confirm="Are you sure you want to process this payment?"
                class="btn-primary" 
                spinner="processPayroll"
            />
            @endif
            <x-button 
                label="Back" 
                icon="fas.arrow-left" 
                link="/hr/payroll" 
                class="btn-ghost" 
            />
        </x-slot:middle>
    </x-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Payroll Overview -->
            <x-card title="Payroll Overview">
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">Employee</h4>
                            <p class="text-gray-600">{{ $payroll->employee->user->name }}</p>
                        </div>

                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">Pay Period</h4>
                            <p class="text-gray-600">{{ $payroll->payroll_period }}</p>
                        </div>

                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">Pay Date</h4>
                            <p class="text-gray-600">{{ $payroll->pay_date->format('M d, Y') }}</p>
                        </div>

                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">Status</h4>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $this->getStatusColor($payroll->status) }}">
                                {{ ucwords($payroll->status) }}
                            </span>
                        </div>
                    </div>

                    <!-- Net Pay Highlight -->
                    <div class="p-6 bg-gradient-to-r from-green-50 to-blue-50 rounded-lg border border-green-200">
                        <div class="text-center">
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Net Pay</h3>
                            <div class="text-3xl font-bold text-green-600">
                                ${{ number_format($this->calculateNetPay(), 2) }}
                            </div>
                            <p class="text-gray-600 text-sm mt-1">For {{ $payroll->payroll_period }}</p>
                        </div>
                    </div>
                </div>
            </x-card>

            <!-- Salary Breakdown -->
            <x-card title="Salary Breakdown">
                <div class="space-y-4">
                    <!-- Earnings -->
                    <div>
                        <h4 class="font-medium text-gray-900 mb-3 flex items-center">
                            <x-icon name="fas.plus-circle" class="w-5 h-5 text-green-600 mr-2" />
                            Earnings
                        </h4>
                        <div class="space-y-3 pl-7">
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-gray-600">Base Salary</span>
                                <span class="font-medium text-green-600">+${{ number_format($payroll->base_salary, 2) }}</span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-gray-600">Overtime Pay</span>
                                <span class="font-medium text-green-600">+${{ number_format($payroll->overtime_pay, 2) }}</span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-gray-600">Allowances</span>
                                <span class="font-medium text-green-600">+${{ number_format($payroll->allowances, 2) }}</span>
                            </div>
                            <div class="flex justify-between items-center py-2 font-medium bg-green-50 px-3 rounded">
                                <span class="text-green-800">Total Earnings</span>
                                <span class="text-green-800">+${{ number_format($payroll->base_salary + $payroll->overtime_pay + $payroll->allowances, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Deductions -->
                    <div>
                        <h4 class="font-medium text-gray-900 mb-3 flex items-center">
                            <x-icon name="fas.minus-circle" class="w-5 h-5 text-red-600 mr-2" />
                            Deductions
                        </h4>
                        <div class="space-y-3 pl-7">
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-gray-600">General Deductions</span>
                                <span class="font-medium text-red-600">-${{ number_format($payroll->other_deductions, 2) }}</span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-gray-600">Tax Deduction</span>
                                <span class="font-medium text-red-600">-${{ number_format($payroll->tax_deduction, 2) }}</span>
                            </div>
                            <div class="flex justify-between items-center py-2 font-medium bg-red-50 px-3 rounded">
                                <span class="text-red-800">Total Deductions</span>
                                <span class="text-red-800">-${{ number_format($payroll->other_deductions + $payroll->tax_deduction, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Net Pay Summary -->
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <div class="flex justify-between items-center text-lg font-semibold">
                            <span class="text-gray-900">Net Pay</span>
                            <span class="text-green-600">${{ number_format($this->calculateNetPay(), 2) }}</span>
                        </div>
                    </div>
                </div>
            </x-card>

            <!-- Payment History -->
            <x-card title="Payment Timeline">
                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                            <x-icon name="fas.plus" class="w-4 h-4 text-blue-600" />
                        </div>
                        <div>
                            <h5 class="font-medium text-gray-900">Payroll Created</h5>
                            <p class="text-gray-600 text-sm">{{ $payroll->created_at->format('M d, Y H:i') }}</p>
                        </div>
                    </div>

                    @if($payroll->status !== 'draft')
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 {{ $payroll->status === 'approved' ? 'bg-blue-100' : 'bg-green-100' }} rounded-full flex items-center justify-center">
                            <x-icon name="fas.{{ $payroll->status === 'approved' ? 'check' : 'credit-card' }}" class="w-4 h-4 {{ $payroll->status === 'approved' ? 'text-blue-600' : 'text-green-600' }}" />
                        </div>
                        <div>
                            <h5 class="font-medium text-gray-900">Payroll {{ ucwords($payroll->status) }}</h5>
                            <p class="text-gray-600 text-sm">{{ $payroll->updated_at->format('M d, Y H:i') }}</p>
                        </div>
                    </div>
                    @endif

                    @if($payroll->status === 'paid')
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                            <x-icon name="fas.check-double" class="w-4 h-4 text-green-600" />
                        </div>
                        <div>
                            <h5 class="font-medium text-gray-900">Payment Completed</h5>
                            <p class="text-gray-600 text-sm">{{ $payroll->pay_date->format('M d, Y') }}</p>
                            <p class="text-green-600 text-sm font-medium">${{ number_format($this->calculateNetPay(), 2) }} paid</p>
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
                    @if($payroll->status === 'draft')
                    <x-button
                        label="Approve Payroll"
                        icon="fas.check"
                        wire:click="approvePayroll"
                        wire:confirm="Are you sure you want to approve this payroll?"
                        class="btn-success w-full"
                        spinner="approvePayroll"
                    />
                    @endif

                    @if($payroll->status === 'approved')
                    <x-button
                        label="Process Payment"
                        icon="fas.credit-card"
                        wire:click="processPayroll"
                        wire:confirm="Are you sure you want to process this payment?"
                        class="btn-primary w-full"
                        spinner="processPayroll"
                    />
                    @endif

                    @if(in_array($payroll->status, ['draft', 'approved']))
                    <x-button
                        label="Cancel Payroll"
                        icon="fas.ban"
                        wire:click="cancelPayroll"
                        wire:confirm="Are you sure you want to cancel this payroll?"
                        class="btn-warning w-full"
                        spinner="cancelPayroll"
                    />
                    @endif

                    <x-button
                        label="Print Payslip"
                        icon="fas.print"
                        onclick="window.print()"
                        class="btn-outline w-full"
                    />

                    <x-button
                        label="Download PDF"
                        icon="fas.download"
                        class="btn-outline w-full"
                    />

                    <x-button
                        label="Delete Payroll"
                        icon="fas.trash"
                        wire:click="deletePayroll"
                        wire:confirm="Are you sure you want to delete this payroll?"
                        class="btn-error w-full"
                        spinner="deletePayroll"
                    />
                </div>
            </x-card>

            <!-- Payroll Summary -->
            <x-card title="Payroll Summary">
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Period:</span>
                        <span class="font-medium">{{ $payroll->payroll_period }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600">Pay Date:</span>
                        <span class="font-medium">{{ $payroll->pay_date->format('M d, Y') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600">Created:</span>
                        <span class="font-medium">{{ $payroll->created_at->format('M d, Y') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600">Updated:</span>
                        <span class="font-medium">{{ $payroll->updated_at->format('M d, Y') }}</span>
                    </div>

                    <hr class="my-3">

                    <div class="flex justify-between text-lg font-semibold">
                        <span class="text-gray-900">Net Pay:</span>
                        <span class="text-green-600">${{ number_format($this->calculateNetPay(), 2) }}</span>
                    </div>
                </div>
            </x-card>

            <!-- Employee Info -->
            <x-card title="Employee Details">
                <div class="text-center mb-4">
                    <div class="w-16 h-16 bg-gray-300 rounded-full mx-auto mb-3 flex items-center justify-center">
                        <x-icon name="fas.user" class="w-6 h-6 text-gray-600" />
                    </div>
                    <h3 class="font-semibold">{{ $payroll->employee->user->name }}</h3>
                    <p class="text-gray-600 text-sm">{{ $payroll->employee->position }}</p>
                </div>

                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Department:</span>
                        <span class="font-medium">{{ $payroll->employee->department }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600">Hire Date:</span>
                        <span class="font-medium">{{ $payroll->employee->hire_date->format('M d, Y') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600">Status:</span>
                        <span class="font-medium capitalize text-green-600">{{ $payroll->employee->status }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600">Base Salary:</span>
                        <span class="font-medium">${{ number_format($payroll->employee->salary, 2) }}</span>
                    </div>
                </div>
            </x-card>

            <!-- Status Card -->
            <x-card title="Payment Status">
                <div class="text-center">
                    <div class="w-16 h-16 {{ $this->getStatusColor($payroll->status) }} rounded-full mx-auto mb-3 flex items-center justify-center">
                        <x-icon name="fas.{{ $payroll->status === 'draft' ? 'edit' : ($payroll->status === 'approved' ? 'check' : ($payroll->status === 'paid' ? 'credit-card' : 'ban')) }}" class="w-6 h-6" />
                    </div>
                    <h3 class="font-semibold text-lg capitalize">{{ $payroll->status }}</h3>
                    <p class="text-gray-600 text-sm mt-1">
                        @if($payroll->status === 'draft')
                            Awaiting approval
                        @elseif($payroll->status === 'approved')
                            Ready for payment
                        @elseif($payroll->status === 'paid')
                            Payment completed
                        @else
                            Payroll cancelled
                        @endif
                    </p>
                </div>
            </x-card>
        </div>
    </div>
</div>
