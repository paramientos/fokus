<?php
use Livewire\Volt\Component;
use App\Models\Payroll;
use App\Models\Employee;

new class extends Component {
    public $selectedPeriod = 'this_month';
    public $selectedDepartment = '';
    public $selectedStatus = '';
    
    public function with()
    {
        $workspaceId = session('workspace_id');
        $dateRange = $this->getDateRange();
        
        // Payroll Data
        $payrolls = Payroll::where('workspace_id', $workspaceId)
            ->with(['employee.user'])
            ->whereBetween('pay_date', $dateRange);
        
        if ($this->selectedDepartment) {
            $payrolls->whereHas('employee', function($q) {
                $q->where('department', $this->selectedDepartment);
            });
        }
        
        if ($this->selectedStatus) {
            $payrolls->where('status', $this->selectedStatus);
        }
        
        $payrolls = $payrolls->get();
        
        // Payroll Statistics by Department
        $departmentStats = $payrolls->groupBy('employee.department')
            ->map(function($deptPayrolls) {
                $processed = $deptPayrolls->where('status', 'paid');
                return [
                    'total_payrolls' => $deptPayrolls->count(),
                    'processed_payrolls' => $processed->count(),
                    'total_gross_pay' => $processed->sum('gross_pay'),
                    'total_net_pay' => $processed->sum('net_pay'),
                    'total_deductions' => $processed->sum('total_deductions'),
                    'avg_gross_pay' => $processed->avg('gross_pay'),
                    'avg_net_pay' => $processed->avg('net_pay')
                ];
            });
        
        // Employee Payroll Summary
        $employeePayrolls = $payrolls->groupBy('employee_id')
            ->map(function($empPayrolls) {
                $processed = $empPayrolls->where('status', 'paid');
                return [
                    'employee' => $empPayrolls->first()->employee,
                    'total_payrolls' => $empPayrolls->count(),
                    'processed_payrolls' => $processed->count(),
                    'total_gross_pay' => $processed->sum('gross_pay'),
                    'total_net_pay' => $processed->sum('net_pay'),
                    'total_deductions' => $processed->sum('total_deductions'),
                    'avg_gross_pay' => $processed->avg('gross_pay'),
                    'avg_net_pay' => $processed->avg('net_pay'),
                    'latest_payroll' => $empPayrolls->sortByDesc('pay_date')->first()
                ];
            });
        
        // Cost Analysis
        $totalCosts = [
            'gross_pay' => $payrolls->where('status', 'paid')->sum('gross_pay'),
            'net_pay' => $payrolls->where('status', 'paid')->sum('net_pay'),
            'total_deductions' => $payrolls->where('status', 'paid')->sum('total_deductions'),
        ];
        
        return [
            'payrolls' => $payrolls,
            'departmentStats' => $departmentStats,
            'employeePayrolls' => $employeePayrolls,
            'totalCosts' => $totalCosts,
            
            // Summary Statistics
            'totalPayrolls' => $payrolls->count(),
            'processedPayrolls' => $payrolls->where('status', 'paid')->count(),
            'pendingPayrolls' => $payrolls->where('status', 'draft')->count(),
            'totalGrossPay' => $payrolls->where('status', 'paid')->sum('gross_pay'),
            'totalNetPay' => $payrolls->where('status', 'paid')->sum('net_pay'),
            'totalDeductions' => $payrolls->where('status', 'paid')->sum('total_deductions'),
            'averageGrossPay' => $payrolls->where('status', 'paid')->avg('gross_pay'),
            'averageNetPay' => $payrolls->where('status', 'paid')->avg('net_pay'),
            'totalEmployerCosts' => $payrolls->where('status', 'paid')->sum('gross_pay')
        ];
    }
    
    private function getDateRange()
    {
        return match($this->selectedPeriod) {
            'this_month' => [now()->startOfMonth(), now()->endOfMonth()],
            'this_quarter' => [now()->startOfQuarter(), now()->endOfQuarter()],
            'this_year' => [now()->startOfYear(), now()->endOfYear()],
            'last_month' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'last_quarter' => [now()->subQuarter()->startOfQuarter(), now()->subQuarter()->endOfQuarter()],
            'last_year' => [now()->subYear()->startOfYear(), now()->subYear()->endOfYear()],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }
}; ?>

<div>
    <x-header title="Payroll Analytics Report" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-button label="Export Report" icon="fas.download" class="btn-primary" />
        </x-slot:middle>
    </x-header>

    <!-- Filters -->
    <x-card class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-select 
                label="Time Period" 
                wire:model.live="selectedPeriod"
                :options="[
                    ['id' => 'this_month', 'name' => 'This Month'],
                    ['id' => 'this_quarter', 'name' => 'This Quarter'],
                    ['id' => 'this_year', 'name' => 'This Year'],
                    ['id' => 'last_month', 'name' => 'Last Month'],
                    ['id' => 'last_quarter', 'name' => 'Last Quarter'],
                    ['id' => 'last_year', 'name' => 'Last Year']
                ]"
            />
            
            <x-select 
                label="Department" 
                wire:model.live="selectedDepartment"
                placeholder="All Departments"
                :options="[
                    ['id' => 'engineering', 'name' => 'Engineering'],
                    ['id' => 'marketing', 'name' => 'Marketing'],
                    ['id' => 'sales', 'name' => 'Sales'],
                    ['id' => 'hr', 'name' => 'Human Resources'],
                    ['id' => 'finance', 'name' => 'Finance']
                ]"
            />
            
            <x-select 
                label="Status" 
                wire:model.live="selectedStatus"
                placeholder="All Statuses"
                :options="[
                    ['id' => 'draft', 'name' => 'Draft'],
                    ['id' => 'approved', 'name' => 'Approved'],
                    ['id' => 'paid', 'name' => 'Paid']
                ]"
            />
        </div>
    </x-card>

    <!-- Summary Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <x-stat 
            title="Total Payrolls" 
            :value="$totalPayrolls" 
            icon="fas.file-invoice-dollar" 
            class="bg-gradient-to-r from-blue-500 to-blue-600 text-white"
        />
        
        <x-stat 
            title="Processed Payrolls" 
            :value="$processedPayrolls" 
            icon="fas.check-circle" 
            class="bg-gradient-to-r from-green-500 to-green-600 text-white"
        />
        
        <x-stat 
            title="Total Gross Pay" 
            :value="'₺' . number_format($totalGrossPay, 0)" 
            icon="fas.money-bill-wave" 
            class="bg-gradient-to-r from-yellow-500 to-yellow-600 text-white"
        />
        
        <x-stat 
            title="Total Net Pay" 
            :value="'₺' . number_format($totalNetPay, 0)" 
            icon="fas.hand-holding-usd" 
            class="bg-gradient-to-r from-purple-500 to-purple-600 text-white"
        />
    </div>

    <!-- Additional Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <x-stat 
            title="Total Deductions" 
            :value="'₺' . number_format($totalDeductions, 0)" 
            icon="fas.minus-circle" 
            class="bg-gradient-to-r from-red-500 to-red-600 text-white"
        />
        
        <x-stat 
            title="Avg Gross Pay" 
            :value="'₺' . number_format($averageGrossPay ?? 0, 0)" 
            icon="fas.chart-bar" 
            class="bg-gradient-to-r from-indigo-500 to-indigo-600 text-white"
        />
        
        <x-stat 
            title="Employer Costs" 
            :value="'₺' . number_format($totalEmployerCosts, 0)" 
            icon="fas.building" 
            class="bg-gradient-to-r from-teal-500 to-teal-600 text-white"
        />
    </div>

    <!-- Cost Breakdown -->
    <x-card title="Cost Breakdown Analysis" class="mb-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="text-center p-4 bg-blue-50 rounded-lg">
                <div class="text-2xl font-bold text-blue-600">₺{{ number_format($totalCosts['gross_pay'], 0) }}</div>
                <div class="text-sm text-gray-600">Total Gross Pay</div>
            </div>
            
            <div class="text-center p-4 bg-green-50 rounded-lg">
                <div class="text-2xl font-bold text-green-600">₺{{ number_format($totalCosts['net_pay'], 0) }}</div>
                <div class="text-sm text-gray-600">Total Net Pay</div>
            </div>
            
            <div class="text-center p-4 bg-red-50 rounded-lg">
                <div class="text-2xl font-bold text-red-600">₺{{ number_format($totalCosts['total_deductions'], 0) }}</div>
                <div class="text-sm text-gray-600">Total Deductions</div>
            </div>
        </div>
    </x-card>

    <!-- Department Analysis -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Payroll by Department -->
        <x-card title="Payroll by Department">
            <div class="space-y-4">
                @forelse($departmentStats as $department => $stats)
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <div class="flex justify-between items-center mb-2">
                            <h4 class="font-medium">{{ ucfirst($department ?: 'Unassigned') }}</h4>
                            <span class="text-sm text-gray-600">{{ $stats['processed_payrolls'] }}/{{ $stats['total_payrolls'] }} processed</span>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-600">Total Gross:</span>
                                <span class="font-medium ml-1">₺{{ number_format($stats['total_gross_pay'], 0) }}</span>
                            </div>
                            <div>
                                <span class="text-gray-600">Total Net:</span>
                                <span class="font-medium ml-1">₺{{ number_format($stats['total_net_pay'], 0) }}</span>
                            </div>
                            <div>
                                <span class="text-gray-600">Avg Gross:</span>
                                <span class="font-medium ml-1">₺{{ number_format($stats['avg_gross_pay'] ?? 0, 0) }}</span>
                            </div>
                            <div>
                                <span class="text-gray-600">Deductions:</span>
                                <span class="font-medium ml-1">₺{{ number_format($stats['total_deductions'], 0) }}</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-8">No payroll data available for this period.</p>
                @endforelse
            </div>
        </x-card>

        <!-- Processing Status -->
        <x-card title="Processing Status Overview">
            <div class="space-y-4">
                <div class="p-4 bg-green-50 rounded-lg">
                    <div class="flex justify-between items-center">
                        <div>
                            <h4 class="font-medium text-green-800">Processed Payrolls</h4>
                            <p class="text-sm text-green-600">Successfully completed</p>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-green-600">{{ $processedPayrolls }}</div>
                            <div class="text-sm text-green-600">
                                {{ $totalPayrolls > 0 ? number_format(($processedPayrolls / $totalPayrolls) * 100, 1) : 0 }}%
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="p-4 bg-yellow-50 rounded-lg">
                    <div class="flex justify-between items-center">
                        <div>
                            <h4 class="font-medium text-yellow-800">Draft Payrolls</h4>
                            <p class="text-sm text-yellow-600">Awaiting processing</p>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-yellow-600">{{ $pendingPayrolls }}</div>
                            <div class="text-sm text-yellow-600">
                                {{ $totalPayrolls > 0 ? number_format(($pendingPayrolls / $totalPayrolls) * 100, 1) : 0 }}%
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="p-4 bg-blue-50 rounded-lg">
                    <div class="flex justify-between items-center">
                        <div>
                            <h4 class="font-medium text-blue-800">Processing Efficiency</h4>
                            <p class="text-sm text-blue-600">Overall completion rate</p>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-blue-600">
                                {{ $totalPayrolls > 0 ? number_format(($processedPayrolls / $totalPayrolls) * 100, 1) : 0 }}%
                            </div>
                            <div class="text-sm text-blue-600">Efficiency Rate</div>
                        </div>
                    </div>
                </div>
            </div>
        </x-card>
    </div>

    <!-- Employee Payroll Summary -->
    <x-card title="Employee Payroll Summary" class="mb-8">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payrolls</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Gross</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Net</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Gross</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deductions</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Latest Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($employeePayrolls->take(20) as $empId => $payrollData)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <x-avatar :image="$payrollData['employee']->user->avatar" class="!w-8 !h-8 mr-3" />
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $payrollData['employee']->user->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $payrollData['employee']->position }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $payrollData['processed_payrolls'] }}/{{ $payrollData['total_payrolls'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ₺{{ number_format($payrollData['total_gross_pay'], 0) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ₺{{ number_format($payrollData['total_net_pay'], 0) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ₺{{ number_format($payrollData['avg_gross_pay'] ?? 0, 0) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ₺{{ number_format($payrollData['total_deductions'], 0) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-badge 
                                    :value="ucfirst($payrollData['latest_payroll']->status ?? 'N/A')" 
                                    class="badge-{{ ($payrollData['latest_payroll']->status ?? '') === 'paid' ? 'success' : (($payrollData['latest_payroll']->status ?? '') === 'approved' ? 'info' : (($payrollData['latest_payroll']->status ?? '') === 'draft' ? 'warning' : 'danger')) }}"
                                />
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <x-button 
                                    label="View Details" 
                                    link="/hr/employees/{{ $payrollData['employee']->id }}" 
                                    class="btn-sm btn-outline"
                                />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                                No employee payroll data available.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <!-- Recent Payrolls -->
    <x-card title="Recent Payroll Records">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pay Period</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gross Pay</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Net Pay</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deductions</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($payrolls->take(15) as $payroll)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <x-avatar :image="$payroll->employee->user->avatar" class="!w-8 !h-8 mr-3" />
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $payroll->employee->user->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $payroll->employee->position }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $payroll->pay_date->format('M d') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ₺{{ number_format($payroll->gross_pay, 0) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ₺{{ number_format($payroll->net_pay, 0) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ₺{{ number_format($payroll->total_deductions, 0) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-badge 
                                    :value="ucfirst($payroll->status)" 
                                    class="badge-{{ $payroll->status === 'paid' ? 'success' : ($payroll->status === 'approved' ? 'info' : ($payroll->status === 'draft' ? 'warning' : 'danger')) }}"
                                />
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <x-button 
                                    label="View" 
                                    link="/hr/payroll/{{ $payroll->id }}" 
                                    class="btn-sm btn-outline"
                                />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                No payroll records available for this period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>
