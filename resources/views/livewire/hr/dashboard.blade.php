<?php

use App\Models\Employee;
use App\Models\EmployeeCertification;
use App\Models\LeaveRequest;
use App\Models\OkrGoal;
use App\Models\Payroll;
use App\Models\PerformanceReview;
use App\Models\Training;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public $totalEmployees;
    public $activeEmployees;
    public $pendingLeaves;
    public $upcomingReviews;
    public $expiredCertifications;
    public $overdueOkrs;
    public $recentPayrolls;
    public $trainingCompletions;

    public function mount()
    {
        $this->loadDashboardData();
    }

    public function loadDashboardData()
    {
        $workspaceId = session('workspace_id');

        // Employee Statistics
        $this->totalEmployees = Employee::where('workspace_id', $workspaceId)->count();
        $this->activeEmployees = Employee::where('workspace_id', $workspaceId)
            ->whereHas('user')
            ->count();

        // Leave Requests
        $this->pendingLeaves = LeaveRequest::whereHas('employee', function ($q) use ($workspaceId) {
            $q->where('workspace_id', $workspaceId);
        })->where('status', 'pending')->count();

        // Performance Reviews
        $this->upcomingReviews = PerformanceReview::whereHas('employee', function ($q) use ($workspaceId) {
            $q->where('workspace_id', $workspaceId);
        })->where('next_review_date', '<=', now()->addDays(30))
            ->where('status', '!=', 'completed')->count();

        // Certifications
        $this->expiredCertifications = EmployeeCertification::whereHas('employee', function ($q) use ($workspaceId) {
            $q->where('workspace_id', $workspaceId);
        })->where('status', 'expired')
            ->orWhere(function ($q) {
                $q->where('expiry_date', '<', now())
                    ->where('status', 'active');
            })->count();

        // OKR Goals
        $this->overdueOkrs = OkrGoal::where('workspace_id', $workspaceId)
            ->where('end_date', '<', now())
            ->where('status', '!=', 'completed')->count();

        // Recent Payrolls
        $this->recentPayrolls = Payroll::where('workspace_id', $workspaceId)
            ->where('created_at', '>=', now()->subDays(30))->count();

        // Training Completions
        $this->trainingCompletions = Training::where('workspace_id', $workspaceId)
            ->whereHas('employees', function ($q) {
                $q->where('employee_training.status', 'completed')
                    ->where('employee_training.updated_at', '>=', now()->subDays(30));
            })->count();
    }

    public function refreshData()
    {
        $this->loadDashboardData();
        $this->success('Dashboard data refreshed successfully!');
    }
}; ?>

<div>
    <x-header title="HR Dashboard" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-button icon="fas.rotate" class="btn-circle btn-ghost btn-sm" wire:click="refreshData"/>
        </x-slot:middle>
    </x-header>

    <!-- Quick Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Employees -->
        <x-stat
            title="Total Employees"
            :value="$totalEmployees"
            icon="fas.users"
            class="bg-gradient-to-r from-blue-500 to-blue-600 text-white"
        />

        <!-- Active Employees -->
        <x-stat
            title="Active Employees"
            :value="$activeEmployees"
            icon="fas.user-check"
            class="bg-gradient-to-r from-green-500 to-green-600 text-white"
        />

        <!-- Pending Leaves -->
        <x-stat
            title="Pending Leaves"
            :value="$pendingLeaves"
            icon="fas.calendar-times"
            class="bg-gradient-to-r from-yellow-500 to-yellow-600 text-white"
        />

        <!-- Upcoming Reviews -->
        <x-stat
            title="Upcoming Reviews"
            :value="$upcomingReviews"
            icon="fas.clipboard-check"
            class="bg-gradient-to-r from-purple-500 to-purple-600 text-white"
        />
    </div>

    <!-- Alert Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Expired Certifications -->
        <x-card title="Expired Certifications" class="border-l-4 border-red-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-3xl font-bold text-red-600">{{ $expiredCertifications }}</p>
                    <p class="text-sm text-gray-600">Need immediate attention</p>
                </div>
                <x-icon name="fas.certificate" class="w-12 h-12 text-red-500"/>
            </div>
            <x-slot:actions>
                <x-button label="View Details" link="/hr/certifications" class="btn-sm btn-outline"/>
            </x-slot:actions>
        </x-card>

        <!-- Overdue OKRs -->
        <x-card title="Overdue OKRs" class="border-l-4 border-orange-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-3xl font-bold text-orange-600">{{ $overdueOkrs }}</p>
                    <p class="text-sm text-gray-600">Past due date</p>
                </div>
                <x-icon name="fas.bullseye" class="w-12 h-12 text-orange-500"/>
            </div>
            <x-slot:actions>
                <x-button label="Review OKRs" link="/hr/performance" class="btn-sm btn-outline"/>
            </x-slot:actions>
        </x-card>

        <!-- Recent Payrolls -->
        <x-card title="Recent Payrolls" class="border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-3xl font-bold text-green-600">{{ $recentPayrolls }}</p>
                    <p class="text-sm text-gray-600">Last 30 days</p>
                </div>
                <x-icon name="fas.money-bill-wave" class="w-12 h-12 text-green-500"/>
            </div>
            <x-slot:actions>
                <x-button label="View Payrolls" link="/hr/payroll" class="btn-sm btn-outline"/>
            </x-slot:actions>
        </x-card>

        <!-- Training Completions -->
        <x-card title="Training Completions" class="border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-3xl font-bold text-blue-600">{{ $trainingCompletions }}</p>
                    <p class="text-sm text-gray-600">This month</p>
                </div>
                <x-icon name="fas.graduation-cap" class="w-12 h-12 text-blue-500"/>
            </div>
            <x-slot:actions>
                <x-button label="View Trainings" link="/hr/trainings" class="btn-sm btn-outline"/>
            </x-slot:actions>
        </x-card>
    </div>

    <!-- Quick Actions -->
    <x-card title="Quick Actions" class="mb-8">
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4">
            <x-button
                label="Employees"
                icon="fas.user"
                link="/hr/employees"
                class="btn-primary flex-col h-20"
            />

            <x-button
                label="Create Review"
                icon="fas.clipboard-list"
                link="/hr/performance/create"
                class="btn-secondary flex-col h-20"
            />

            <x-button
                label="Process Payroll"
                icon="fas.calculator"
                link="/hr/payroll/create"
                class="btn-accent flex-col h-20"
            />

            <x-button
                label="Schedule Training"
                icon="fas.chalkboard-teacher"
                link="/hr/trainings/create"
                class="btn-info flex-col h-20"
            />

            <x-button
                label="Leave Requests"
                icon="fas.calendar-alt"
                link="/hr/leaves"
                class="btn-warning flex-col h-20"
            />

            <x-button
                label="Manage OKRs"
                icon="fas.bullseye"
                link="/hr/okr"
                class="btn-ghost flex-col h-20"
            />

            <x-button
                label="Certifications"
                icon="fas.certificate"
                link="/hr/certifications"
                class="btn-outline flex-col h-20"
            />

            <x-button
                label="HR Reports"
                icon="fas.chart-bar"
                link="/hr/reports"
                class="btn-success flex-col h-20"
            />
        </div>
    </x-card>

    <!-- Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Leave Requests -->
        <x-card title="Recent Leave Requests">
            <div class="space-y-3">
                @forelse(\App\Models\LeaveRequest::whereHas('employee', function($q) {
                    $q->where('workspace_id', session('workspace_id'));
                })->latest()->take(5)->get() as $leave)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <p class="font-medium">{{ $leave->employee->user->name }}</p>
                            <p class="text-sm text-gray-600">{{ $leave->leave_type }}
                                - {{ $leave->start_date->format('M d') }} to {{ $leave->end_date->format('M d') }}</p>
                        </div>
                        <x-badge :value="$leave->status"
                                 class="badge-{{ $leave->status === 'approved' ? 'success' : ($leave->status === 'rejected' ? 'error' : 'warning') }}"/>
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-4">No recent leave requests</p>
                @endforelse
            </div>
            <x-slot:actions>
                <x-button label="View All" link="/hr/leaves" class="btn-sm btn-ghost"/>
            </x-slot:actions>
        </x-card>

        <!-- Upcoming Performance Reviews -->
        <x-card title="Upcoming Performance Reviews">
            <div class="space-y-3">
                @forelse(\App\Models\PerformanceReview::whereHas('employee', function($q) {
                    $q->where('workspace_id', session('workspace_id'));
                })->where('next_review_date', '>=', now())->orderBy('next_review_date')->take(5)->get() as $review)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <p class="font-medium">{{ $review->employee->user->name }}</p>
                            <p class="text-sm text-gray-600">Due: {{ $review->next_review_date->format('M d, Y') }}</p>
                        </div>
                        <x-badge :value="$review->status"
                                 class="badge-{{ $review->status === 'completed' ? 'success' : 'warning' }}"/>
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-4">No upcoming reviews</p>
                @endforelse
            </div>
            <x-slot:actions>
                <x-button label="View All" link="/hr/performance" class="btn-sm btn-ghost"/>
            </x-slot:actions>
        </x-card>
    </div>
</div>
