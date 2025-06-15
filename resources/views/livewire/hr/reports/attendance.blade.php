<?php
use Livewire\Volt\Component;
use App\Models\LeaveRequest;
use App\Models\Employee;

new class extends Component {
    public $selectedPeriod = 'this_month';
    public $selectedDepartment = '';
    public $selectedLeaveType = '';
    
    public function with()
    {
        $workspaceId = session('workspace_id');
        $dateRange = $this->getDateRange();
        
        // Leave Requests Data
        $leaveRequests = LeaveRequest::whereHas('employee', function($q) use ($workspaceId) {
            $q->where('workspace_id', $workspaceId);
            if ($this->selectedDepartment) {
                $q->where('department', $this->selectedDepartment);
            }
        })
        ->with(['employee.user'])
        ->whereBetween('start_date', $dateRange);
        
        if ($this->selectedLeaveType) {
            $leaveRequests->where('leave_type', $this->selectedLeaveType);
        }
        
        $leaveRequests = $leaveRequests->get();
        
        // Leave Statistics by Department
        $departmentStats = $leaveRequests->groupBy('employee.department')
            ->map(function($deptLeaves) {
                return [
                    'total_requests' => $deptLeaves->count(),
                    'approved_requests' => $deptLeaves->where('status', 'approved')->count(),
                    'total_days' => $deptLeaves->where('status', 'approved')->sum('days'),
                    'pending_requests' => $deptLeaves->where('status', 'pending')->count(),
                    'approval_rate' => $deptLeaves->count() > 0 ? ($deptLeaves->where('status', 'approved')->count() / $deptLeaves->count()) * 100 : 0
                ];
            });
        
        // Leave Statistics by Type
        $leaveTypeStats = $leaveRequests->groupBy('leave_type')
            ->map(function($typeLeaves) {
                return [
                    'total_requests' => $typeLeaves->count(),
                    'approved_requests' => $typeLeaves->where('status', 'approved')->count(),
                    'total_days' => $typeLeaves->where('status', 'approved')->sum('days'),
                    'avg_duration' => $typeLeaves->where('status', 'approved')->avg('days')
                ];
            });
        
        // Employee Attendance Summary
        $employeeAttendance = Employee::where('workspace_id', $workspaceId)
            ->with(['user', 'leaveRequests' => function($q) use ($dateRange) {
                $q->whereBetween('start_date', $dateRange);
            }])
            ->get()
            ->map(function($employee) {
                $approvedLeaves = $employee->leaveRequests->where('status', 'approved');
                return [
                    'employee' => $employee,
                    'total_leave_requests' => $employee->leaveRequests->count(),
                    'approved_leaves' => $approvedLeaves->count(),
                    'total_leave_days' => $approvedLeaves->sum('days'),
                    'sick_days' => $approvedLeaves->where('leave_type', 'sick')->sum('days'),
                    'vacation_days' => $approvedLeaves->where('leave_type', 'vacation')->sum('days'),
                    'personal_days' => $approvedLeaves->where('leave_type', 'personal')->sum('days')
                ];
            });
        
        return [
            'leaveRequests' => $leaveRequests,
            'departmentStats' => $departmentStats,
            'leaveTypeStats' => $leaveTypeStats,
            'employeeAttendance' => $employeeAttendance,
            
            // Summary Statistics
            'totalRequests' => $leaveRequests->count(),
            'approvedRequests' => $leaveRequests->where('status', 'approved')->count(),
            'pendingRequests' => $leaveRequests->where('status', 'pending')->count(),
            'rejectedRequests' => $leaveRequests->where('status', 'rejected')->count(),
            'totalLeaveDays' => $leaveRequests->where('status', 'approved')->sum('days'),
            'averageLeaveDuration' => $leaveRequests->where('status', 'approved')->avg('days'),
            'approvalRate' => $leaveRequests->count() > 0 ? ($leaveRequests->where('status', 'approved')->count() / $leaveRequests->count()) * 100 : 0
        ];
    }
    
    private function getDateRange()
    {
        return match($this->selectedPeriod) {
            'this_week' => [now()->startOfWeek(), now()->endOfWeek()],
            'this_month' => [now()->startOfMonth(), now()->endOfMonth()],
            'this_quarter' => [now()->startOfQuarter(), now()->endOfQuarter()],
            'this_year' => [now()->startOfYear(), now()->endOfYear()],
            'last_month' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'last_quarter' => [now()->subQuarter()->startOfQuarter(), now()->subQuarter()->endOfQuarter()],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }
}; ?>

<div>
    <x-header title="Attendance & Leave Report" separator progress-indicator>
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
                    ['id' => 'this_week', 'name' => 'This Week'],
                    ['id' => 'this_month', 'name' => 'This Month'],
                    ['id' => 'this_quarter', 'name' => 'This Quarter'],
                    ['id' => 'this_year', 'name' => 'This Year'],
                    ['id' => 'last_month', 'name' => 'Last Month'],
                    ['id' => 'last_quarter', 'name' => 'Last Quarter']
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
                label="Leave Type" 
                wire:model.live="selectedLeaveType"
                placeholder="All Leave Types"
                :options="[
                    ['id' => 'vacation', 'name' => 'Vacation'],
                    ['id' => 'sick', 'name' => 'Sick Leave'],
                    ['id' => 'personal', 'name' => 'Personal Leave'],
                    ['id' => 'maternity', 'name' => 'Maternity Leave'],
                    ['id' => 'paternity', 'name' => 'Paternity Leave'],
                    ['id' => 'emergency', 'name' => 'Emergency Leave']
                ]"
            />
        </div>
    </x-card>

    <!-- Summary Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <x-stat 
            title="Total Requests" 
            :value="$totalRequests" 
            icon="fas.calendar-alt" 
            class="bg-gradient-to-r from-blue-500 to-blue-600 text-white"
        />
        
        <x-stat 
            title="Approved Requests" 
            :value="$approvedRequests" 
            icon="fas.check-circle" 
            class="bg-gradient-to-r from-green-500 to-green-600 text-white"
        />
        
        <x-stat 
            title="Total Leave Days" 
            :value="$totalLeaveDays" 
            icon="fas.calendar-day" 
            class="bg-gradient-to-r from-yellow-500 to-yellow-600 text-white"
        />
        
        <x-stat 
            title="Approval Rate" 
            :value="number_format($approvalRate, 1) . '%'" 
            icon="fas.percentage" 
            class="bg-gradient-to-r from-purple-500 to-purple-600 text-white"
        />
    </div>

    <!-- Additional Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <x-stat 
            title="Pending Requests" 
            :value="$pendingRequests" 
            icon="fas.clock" 
            class="bg-gradient-to-r from-orange-500 to-orange-600 text-white"
        />
        
        <x-stat 
            title="Rejected Requests" 
            :value="$rejectedRequests" 
            icon="fas.times-circle" 
            class="bg-gradient-to-r from-red-500 to-red-600 text-white"
        />
        
        <x-stat 
            title="Avg Leave Duration" 
            :value="number_format($averageLeaveDuration ?? 0, 1) . ' days'" 
            icon="fas.chart-bar" 
            class="bg-gradient-to-r from-indigo-500 to-indigo-600 text-white"
        />
    </div>

    <!-- Charts and Analysis -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Leave Statistics by Department -->
        <x-card title="Leave Statistics by Department">
            <div class="space-y-4">
                @forelse($departmentStats as $department => $stats)
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <div class="flex justify-between items-center mb-2">
                            <h4 class="font-medium">{{ ucfirst($department ?: 'Unassigned') }}</h4>
                            <span class="text-sm text-gray-600">{{ $stats['total_requests'] }} requests</span>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-600">Approved:</span>
                                <span class="font-medium ml-1">{{ $stats['approved_requests'] }}</span>
                            </div>
                            <div>
                                <span class="text-gray-600">Total Days:</span>
                                <span class="font-medium ml-1">{{ $stats['total_days'] }}</span>
                            </div>
                            <div>
                                <span class="text-gray-600">Pending:</span>
                                <span class="font-medium ml-1">{{ $stats['pending_requests'] }}</span>
                            </div>
                            <div>
                                <span class="text-gray-600">Approval Rate:</span>
                                <span class="font-medium ml-1">{{ number_format($stats['approval_rate'], 1) }}%</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-8">No leave data available for this period.</p>
                @endforelse
            </div>
        </x-card>

        <!-- Leave Statistics by Type -->
        <x-card title="Leave Statistics by Type">
            <div class="space-y-4">
                @forelse($leaveTypeStats as $leaveType => $stats)
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <div class="flex justify-between items-center mb-2">
                            <h4 class="font-medium">{{ ucfirst($leaveType) }}</h4>
                            <span class="text-sm text-gray-600">{{ $stats['total_requests'] }} requests</span>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-600">Approved:</span>
                                <span class="font-medium ml-1">{{ $stats['approved_requests'] }}</span>
                            </div>
                            <div>
                                <span class="text-gray-600">Total Days:</span>
                                <span class="font-medium ml-1">{{ $stats['total_days'] }}</span>
                            </div>
                            <div>
                                <span class="text-gray-600">Avg Duration:</span>
                                <span class="font-medium ml-1">{{ number_format($stats['avg_duration'] ?? 0, 1) }} days</span>
                            </div>
                            <div>
                                <span class="text-gray-600">Utilization:</span>
                                <span class="font-medium ml-1">
                                    {{ $stats['total_requests'] > 0 ? number_format(($stats['approved_requests'] / $stats['total_requests']) * 100, 1) : 0 }}%
                                </span>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-8">No leave type data available.</p>
                @endforelse
            </div>
        </x-card>
    </div>

    <!-- Employee Attendance Summary -->
    <x-card title="Employee Attendance Summary" class="mb-8">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Requests</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Approved</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Days</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sick Days</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vacation Days</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Personal Days</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($employeeAttendance->take(20) as $attendance)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <x-avatar :image="$attendance['employee']->user->avatar" class="!w-8 !h-8 mr-3" />
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $attendance['employee']->user->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $attendance['employee']->position }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $attendance['total_leave_requests'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $attendance['approved_leaves'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-badge 
                                    :value="$attendance['total_leave_days'] . ' days'" 
                                    class="badge-{{ $attendance['total_leave_days'] > 20 ? 'warning' : ($attendance['total_leave_days'] > 10 ? 'info' : 'success') }}"
                                />
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $attendance['sick_days'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $attendance['vacation_days'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $attendance['personal_days'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <x-button 
                                    label="View Details" 
                                    link="/hr/employees/{{ $attendance['employee']->id }}" 
                                    class="btn-sm btn-outline"
                                />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                                No employee attendance data available.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <!-- Recent Leave Requests -->
    <x-card title="Recent Leave Requests">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Leave Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($leaveRequests->take(15) as $leave)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <x-avatar :image="$leave->employee->user->avatar" class="!w-8 !h-8 mr-3" />
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $leave->employee->user->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $leave->employee->position }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-badge :value="ucfirst($leave->leave_type)" class="badge-outline" />
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $leave->start_date->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $leave->end_date->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $leave->days }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-badge 
                                    :value="ucfirst($leave->status)" 
                                    class="badge-{{ $leave->status === 'approved' ? 'success' : ($leave->status === 'pending' ? 'warning' : 'error') }}"
                                />
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <x-button 
                                    label="View" 
                                    link="/hr/leaves/{{ $leave->id }}" 
                                    class="btn-sm btn-outline"
                                />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                No leave requests available for this period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>
