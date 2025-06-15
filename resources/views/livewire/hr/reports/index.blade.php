<?php
use Livewire\Volt\Component;
use App\Models\Employee;
use App\Models\PerformanceReview;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\Training;
use App\Models\EmployeeCertification;
use App\Models\OkrGoal;

new class extends Component {
    public $selectedPeriod = 'this_month';
    public $selectedDepartment = '';
    public $selectedReport = 'overview';

    public function with()
    {
        $workspaceId = session('workspace_id');

        // Date range based on selected period
        $dateRange = $this->getDateRange();

        return [
            'totalEmployees' => Employee::where('workspace_id', $workspaceId)->count(),
            'activeEmployees' => Employee::where('workspace_id', $workspaceId)->where('employment_type', 'full-time')->count(),
            'newHires' => Employee::where('workspace_id', $workspaceId)
                ->whereBetween('hire_date', $dateRange)
                ->count(),
            'partTimeEmployees' => Employee::where('workspace_id', $workspaceId)
                ->where('employment_type', 'part-time')
                ->count(),

            // Performance metrics
            'completedReviews' => PerformanceReview::whereHas('employee', fn($q) => $q->where('workspace_id', $workspaceId))
                ->where('status', 'completed')
                ->whereBetween('created_at', $dateRange)
                ->count(),
            'averageRating' => PerformanceReview::whereHas('employee', fn($q) => $q->where('workspace_id', $workspaceId))
                ->where('status', 'completed')
                ->whereBetween('created_at', $dateRange)
                ->avg('overall_rating'),

            // Leave metrics
            'totalLeaveRequests' => LeaveRequest::whereHas('employee', fn($q) => $q->where('workspace_id', $workspaceId))
                ->whereBetween('created_at', $dateRange)
                ->count(),
            'approvedLeaves' => LeaveRequest::whereHas('employee', fn($q) => $q->where('workspace_id', $workspaceId))
                ->where('status', 'approved')
                ->whereBetween('start_date', $dateRange)
                ->count(),
            'totalLeaveDays' => LeaveRequest::whereHas('employee', fn($q) => $q->where('workspace_id', $workspaceId))
                ->where('status', 'approved')
                ->whereBetween('start_date', $dateRange)
                ->sum('days_requested'),

            // Payroll metrics
            'totalPayrollAmount' => Payroll::where('workspace_id', $workspaceId)
                ->where('status', 'paid')
                ->whereBetween('pay_date', $dateRange)
                ->sum('net_pay'),
            'processedPayrolls' => Payroll::where('workspace_id', $workspaceId)
                ->where('status', 'paid')
                ->whereBetween('pay_date', $dateRange)
                ->count(),

            // Training metrics
            'totalTrainings' => Training::where('workspace_id', $workspaceId)
                ->whereBetween('start_date', $dateRange)
                ->count(),
            'ongoingTrainings' => Training::where('workspace_id', $workspaceId)
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->count(),
            'trainingCost' => Training::where('workspace_id', $workspaceId)
                ->whereBetween('start_date', $dateRange)
                ->sum('cost'),

            // Certification metrics
            'activeCertifications' => EmployeeCertification::whereHas('employee', fn($q) => $q->where('workspace_id', $workspaceId))
                ->where('status', 'active')
                ->count(),
            'expiringSoon' => EmployeeCertification::whereHas('employee', fn($q) => $q->where('workspace_id', $workspaceId))
                ->where('expiry_date', '<=', now()->addDays(30))
                ->where('expiry_date', '>', now())
                ->count(),

            // OKR metrics
            'totalOkrs' => OkrGoal::where('workspace_id', $workspaceId)
                ->whereBetween('start_date', $dateRange)
                ->count(),
            'completedOkrs' => OkrGoal::where('workspace_id', $workspaceId)
                ->where('status', 'completed')
                ->whereBetween('start_date', $dateRange)
                ->count(),
            'averageOkrProgress' => OkrGoal::where('workspace_id', $workspaceId)
                ->where('status', 'in_progress')
                ->avg('progress_percentage'),
        ];
    }

    private function getDateRange(): array
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
    <x-header title="HR Reports & Analytics" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-button label="Export Report" icon="fas.download" class="btn-primary" />
        </x-slot:middle>
    </x-header>

    <!-- Report Filters -->
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
                label="Report Type"
                wire:model.live="selectedReport"
                :options="[
                    ['id' => 'overview', 'name' => 'Overview'],
                    ['id' => 'performance', 'name' => 'Performance'],
                    ['id' => 'attendance', 'name' => 'Attendance'],
                    ['id' => 'payroll', 'name' => 'Payroll'],
                    ['id' => 'training', 'name' => 'Training']
                ]"
            />
        </div>
    </x-card>

    <!-- Key Metrics Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Employee Metrics -->
        <x-stat
            title="Total Employees"
            :value="$totalEmployees"
            icon="fas.users"
            class="bg-gradient-to-r from-blue-500 to-blue-600 text-white"
        />

        <x-stat
            title="Full-time Employees"
            :value="$activeEmployees"
            icon="fas.user-check"
            class="bg-gradient-to-r from-green-500 to-green-600 text-white"
        />

        <x-stat
            title="New Hires"
            :value="$newHires"
            icon="fas.user-plus"
            class="bg-gradient-to-r from-purple-500 to-purple-600 text-white"
        />

        <x-stat
            title="Part-time Employees"
            :value="$partTimeEmployees"
            icon="fas.user-minus"
            class="bg-gradient-to-r from-red-500 to-red-600 text-white"
        />
    </div>

    <!-- Report Cards Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6 mb-8">
        <!-- Performance Report Card -->
        <x-card title="Performance Analytics" class="border-l-4 border-blue-500">
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Completed Reviews</span>
                    <span class="font-bold text-blue-600">{{ $completedReviews }}</span>
                </div>

                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Average Rating</span>
                    <span class="font-bold text-blue-600">{{ number_format($averageRating ?? 0, 1) }}/5.0</span>
                </div>

                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Active OKRs</span>
                    <span class="font-bold text-blue-600">{{ $totalOkrs }}</span>
                </div>

                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Avg OKR Progress</span>
                    <span class="font-bold text-blue-600">{{ number_format($averageOkrProgress ?? 0, 0) }}%</span>
                </div>
            </div>

            <x-slot:actions>
                <x-button label="View Details" link="/hr/reports/performance" class="btn-sm btn-outline" />
            </x-slot:actions>
        </x-card>

        <!-- Leave & Attendance Report Card -->
        <x-card title="Leave & Attendance" class="border-l-4 border-yellow-500">
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Leave Requests</span>
                    <span class="font-bold text-yellow-600">{{ $totalLeaveRequests }}</span>
                </div>

                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Approved Leaves</span>
                    <span class="font-bold text-yellow-600">{{ $approvedLeaves }}</span>
                </div>

                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Total Leave Days</span>
                    <span class="font-bold text-yellow-600">{{ $totalLeaveDays }}</span>
                </div>

                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Approval Rate</span>
                    <span class="font-bold text-yellow-600">
                        {{ $totalLeaveRequests > 0 ? number_format(($approvedLeaves / $totalLeaveRequests) * 100, 1) : 0 }}%
                    </span>
                </div>
            </div>

            <x-slot:actions>
                <x-button label="View Details" link="/hr/reports/attendance" class="btn-sm btn-outline" />
            </x-slot:actions>
        </x-card>

        <!-- Payroll Report Card -->
        <x-card title="Payroll Analytics" class="border-l-4 border-green-500">
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Total Amount</span>
                    <span class="font-bold text-green-600">₺{{ number_format($totalPayrollAmount, 0) }}</span>
                </div>

                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Processed Payrolls</span>
                    <span class="font-bold text-green-600">{{ $processedPayrolls }}</span>
                </div>

                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Avg per Employee</span>
                    <span class="font-bold text-green-600">
                        ₺{{ $processedPayrolls > 0 ? number_format($totalPayrollAmount / $processedPayrolls, 0) : 0 }}
                    </span>
                </div>

                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Cost per Active Employee</span>
                    <span class="font-bold text-green-600">
                        ₺{{ $activeEmployees > 0 ? number_format($totalPayrollAmount / $activeEmployees, 0) : 0 }}
                    </span>
                </div>
            </div>

            <x-slot:actions>
                <x-button label="View Details" link="/hr/reports/payroll" class="btn-sm btn-outline" />
            </x-slot:actions>
        </x-card>

        <!-- Training Report Card -->
        <x-card title="Training & Development" class="border-l-4 border-purple-500">
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Total Trainings</span>
                    <span class="font-bold text-purple-600">{{ $totalTrainings }}</span>
                </div>

                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Ongoing Trainings</span>
                    <span class="font-bold text-purple-600">{{ $ongoingTrainings }}</span>
                </div>

                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Training Cost</span>
                    <span class="font-bold text-purple-600">₺{{ number_format($trainingCost, 0) }}</span>
                </div>

                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Avg Hours per Employee</span>
                    <span class="font-bold text-purple-600">
                        {{ $activeEmployees > 0 ? number_format($totalTrainings / $activeEmployees, 1) : 0 }}
                    </span>
                </div>
            </div>

            <x-slot:actions>
                <x-button label="View Details" link="/hr/trainings" class="btn-sm btn-outline" />
            </x-slot:actions>
        </x-card>

        <!-- Certification Report Card -->
        <x-card title="Certifications" class="border-l-4 border-indigo-500">
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Active Certifications</span>
                    <span class="font-bold text-indigo-600">{{ $activeCertifications }}</span>
                </div>

                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Expiring Soon</span>
                    <span class="font-bold text-indigo-600">{{ $expiringSoon }}</span>
                </div>

                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Certification Rate</span>
                    <span class="font-bold text-indigo-600">
                        {{ $activeEmployees > 0 ? number_format(($activeCertifications / $activeEmployees) * 100, 1) : 0 }}%
                    </span>
                </div>

                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Renewal Needed</span>
                    <span class="font-bold text-red-600">{{ $expiringSoon }}</span>
                </div>
            </div>

            <x-slot:actions>
                <x-button label="View Details" link="/hr/certifications" class="btn-sm btn-outline" />
            </x-slot:actions>
        </x-card>

        <!-- OKR Report Card -->
        <x-card title="OKR Tracking" class="border-l-4 border-teal-500">
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Total OKRs</span>
                    <span class="font-bold text-teal-600">{{ $totalOkrs }}</span>
                </div>

                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Completed OKRs</span>
                    <span class="font-bold text-teal-600">{{ $completedOkrs }}</span>
                </div>

                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Average Progress</span>
                    <span class="font-bold text-teal-600">{{ number_format($averageOkrProgress ?? 0, 0) }}%</span>
                </div>

                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Completion Rate</span>
                    <span class="font-bold text-teal-600">
                        {{ ($totalOkrs + $completedOkrs) > 0 ? number_format(($completedOkrs / ($totalOkrs + $completedOkrs)) * 100, 1) : 0 }}%
                    </span>
                </div>
            </div>

            <x-slot:actions>
                <x-button label="View Details" link="/hr/okr" class="btn-sm btn-outline" />
            </x-slot:actions>
        </x-card>
    </div>

    <!-- Quick Actions -->
    <x-card title="Report Actions" class="mb-6">
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <x-button
                label="Employee Report"
                icon="fas.users"
                link="/hr/employees"
                class="btn-outline flex-col h-16"
            />

            <x-button
                label="Performance Report"
                icon="fas.chart-line"
                link="/hr/reports/performance"
                class="btn-outline flex-col h-16"
            />

            <x-button
                label="Attendance Report"
                icon="fas.calendar-check"
                link="/hr/reports/attendance"
                class="btn-outline flex-col h-16"
            />

            <x-button
                label="Payroll Report"
                icon="fas.money-bill-wave"
                link="/hr/reports/payroll"
                class="btn-outline flex-col h-16"
            />

            <x-button
                label="Training Report"
                icon="fas.graduation-cap"
                link="/hr/trainings"
                class="btn-outline flex-col h-16"
            />

            <x-button
                label="Export All Data"
                icon="fas.file-export"
                class="btn-primary flex-col h-16"
            />
        </div>
    </x-card>
</div>
