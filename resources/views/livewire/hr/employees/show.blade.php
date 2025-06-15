<?php
use Livewire\Volt\Component;
use App\Models\Employee;

new class extends Component {
    public Employee $employee;

    public function mount(Employee $employee)
    {
        $this->employee = $employee->load([
            'user',
            'leaveRequests.leaveType',
            'performanceReviews',
            'trainings',
            'payrolls' => function($query) {
                $query->latest()->take(5);
            },
            'employeeCertifications.certification',
            'okrGoals' => function($query) {
                $query->latest()->take(5);
            }
        ]);
    }

    public function with()
    {
        return [
            'recentLeaves' => $this->employee->leaveRequests()
                ->with('leaveType')
                ->latest()
                ->take(5)
                ->get(),
            'upcomingReviews' => $this->employee->performanceReviews()
                ->where('next_review_date', '>=', now())
                ->orderBy('next_review_date')
                ->take(3)
                ->get(),
            'activeCertifications' => $this->employee->employeeCertifications()
                ->with('certification')
                ->where('status', 'active')
                ->get(),
            'expiredCertifications' => $this->employee->employeeCertifications()
                ->with('certification')
                ->where('status', 'expired')
                ->get(),
            'currentOkrs' => $this->employee->okrGoals()
                ->where('status', '!=', 'completed')
                ->where('end_date', '>=', now())
                ->get(),
            'latestPayroll' => $this->employee->payrolls()->latest()->first()
        ];
    }
}; ?>

<div>
    <x-header :title="$employee->user->name" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-button 
                label="Edit Employee" 
                icon="fas.edit" 
                link="/hr/employees/{{ $employee->id }}/edit" 
                class="btn-primary"
            />
            <x-button 
                label="Back to Employees" 
                icon="fas.arrow-left" 
                link="/hr/employees" 
                class="btn-ghost"
            />
        </x-slot:middle>
    </x-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Employee Profile -->
        <div class="lg:col-span-1">
            <x-card title="Employee Profile" class="mb-6">
                <div class="text-center mb-6">
                    <x-avatar :image="$employee->user->avatar" class="!w-24 !h-24 mx-auto mb-4" />
                    <h3 class="text-xl font-semibold">{{ $employee->user->name }}</h3>
                    <p class="text-gray-600">{{ $employee->position }}</p>
                    <x-badge :value="$employee->department" class="badge-outline mt-2" />
                </div>

                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Employee ID:</span>
                        <span class="font-mono">{{ $employee->employee_id }}</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-gray-600">Email:</span>
                        <a href="mailto:{{ $employee->user->email }}" class="text-blue-600 hover:underline">
                            {{ $employee->user->email }}
                        </a>
                    </div>
                    
                    @if($employee->phone)
                    <div class="flex justify-between">
                        <span class="text-gray-600">Phone:</span>
                        <span>{{ $employee->phone }}</span>
                    </div>
                    @endif
                    
                    <div class="flex justify-between">
                        <span class="text-gray-600">Hire Date:</span>
                        <span>{{ $employee->hire_date?->format('M d, Y') }}</span>
                    </div>
                    
                    @if($employee->birth_date)
                    <div class="flex justify-between">
                        <span class="text-gray-600">Birth Date:</span>
                        <span>{{ $employee->birth_date->format('M d, Y') }}</span>
                    </div>
                    @endif
                    
                    <div class="flex justify-between">
                        <span class="text-gray-600">Status:</span>
                        @if($employee->user->deleted_at)
                            <x-badge value="Inactive" class="badge-error" />
                        @else
                            <x-badge value="Active" class="badge-success" />
                        @endif
                    </div>
                </div>

                @if($employee->address)
                <div class="mt-6 pt-6 border-t">
                    <h4 class="font-medium mb-2">Address</h4>
                    <p class="text-gray-600 text-sm">{{ $employee->address }}</p>
                </div>
                @endif

                @if($employee->emergency_contact_name)
                <div class="mt-6 pt-6 border-t">
                    <h4 class="font-medium mb-2">Emergency Contact</h4>
                    <div class="space-y-1">
                        <p class="text-sm">{{ $employee->emergency_contact_name }}</p>
                        @if($employee->emergency_contact_phone)
                        <p class="text-sm text-gray-600">{{ $employee->emergency_contact_phone }}</p>
                        @endif
                    </div>
                </div>
                @endif
            </x-card>

            <!-- Quick Actions -->
            <x-card title="Quick Actions">
                <div class="space-y-2">
                    <x-button 
                        label="Create Performance Review" 
                        icon="fas.clipboard-check" 
                        link="/hr/performance/create?employee={{ $employee->id }}" 
                        class="btn-primary w-full"
                    />
                    
                    <x-button 
                        label="Process Payroll" 
                        icon="fas.money-bill-wave" 
                        link="/hr/payroll/create?employee={{ $employee->id }}" 
                        class="btn-secondary w-full"
                    />
                    
                    <x-button 
                        label="Assign Training" 
                        icon="fas.graduation-cap" 
                        link="/hr/trainings/assign?employee={{ $employee->id }}" 
                        class="btn-accent w-full"
                    />
                    
                    <x-button 
                        label="Add Certification" 
                        icon="fas.certificate" 
                        link="/hr/certifications/create?employee={{ $employee->id }}" 
                        class="btn-info w-full"
                    />
                </div>
            </x-card>
        </div>

        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Current Salary & Latest Payroll -->
            @if($latestPayroll)
            <x-card title="Current Salary Information">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center p-4 bg-blue-50 rounded-lg">
                        <p class="text-2xl font-bold text-blue-600">₺{{ number_format($latestPayroll->base_salary, 0) }}</p>
                        <p class="text-sm text-gray-600">Base Salary</p>
                    </div>
                    
                    <div class="text-center p-4 bg-green-50 rounded-lg">
                        <p class="text-2xl font-bold text-green-600">₺{{ number_format($latestPayroll->total_allowances, 0) }}</p>
                        <p class="text-sm text-gray-600">Allowances</p>
                    </div>
                    
                    <div class="text-center p-4 bg-red-50 rounded-lg">
                        <p class="text-2xl font-bold text-red-600">₺{{ number_format($latestPayroll->total_deductions, 0) }}</p>
                        <p class="text-sm text-gray-600">Deductions</p>
                    </div>
                    
                    <div class="text-center p-4 bg-purple-50 rounded-lg">
                        <p class="text-2xl font-bold text-purple-600">₺{{ number_format($latestPayroll->net_pay, 0) }}</p>
                        <p class="text-sm text-gray-600">Net Pay</p>
                    </div>
                </div>
                
                <div class="mt-4 flex justify-between items-center">
                    <span class="text-sm text-gray-600">Last payroll: {{ $latestPayroll->pay_period_start->format('M Y') }}</span>
                    <x-button label="View Payroll History" link="/hr/payroll?employee={{ $employee->id }}" class="btn-sm btn-outline" />
                </div>
            </x-card>
            @endif

            <!-- Active Certifications -->
            <x-card title="Certifications">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @forelse($activeCertifications as $empCert)
                        <div class="p-4 border rounded-lg">
                            <div class="flex justify-between items-start mb-2">
                                <h4 class="font-medium">{{ $empCert->certification->name }}</h4>
                                <x-badge value="Active" class="badge-success badge-sm" />
                            </div>
                            <p class="text-sm text-gray-600 mb-2">{{ $empCert->certification->issuing_organization }}</p>
                            <div class="text-xs text-gray-500">
                                <p>Obtained: {{ $empCert->obtained_date->format('M d, Y') }}</p>
                                @if($empCert->expiry_date)
                                <p>Expires: {{ $empCert->expiry_date->format('M d, Y') }}</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 col-span-2 text-center py-4">No active certifications</p>
                    @endforelse
                </div>
                
                @if($expiredCertifications->count() > 0)
                <div class="mt-4 pt-4 border-t">
                    <h4 class="font-medium text-red-600 mb-2">Expired Certifications ({{ $expiredCertifications->count() }})</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        @foreach($expiredCertifications as $expCert)
                            <div class="p-2 bg-red-50 rounded text-sm">
                                <span class="font-medium">{{ $expCert->certification->name }}</span>
                                <span class="text-red-600 ml-2">(Expired {{ $expCert->expiry_date->format('M Y') }})</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif
                
                <x-slot:actions>
                    <x-button label="Manage Certifications" link="/hr/certifications?employee={{ $employee->id }}" class="btn-sm btn-outline" />
                </x-slot:actions>
            </x-card>

            <!-- Current OKRs -->
            @if($currentOkrs->count() > 0)
            <x-card title="Current OKR Goals">
                <div class="space-y-4">
                    @foreach($currentOkrs as $okr)
                        <div class="p-4 border rounded-lg">
                            <div class="flex justify-between items-start mb-2">
                                <h4 class="font-medium">{{ $okr->title }}</h4>
                                <x-badge :value="$okr->status" class="badge-{{ $okr->status === 'completed' ? 'success' : ($okr->status === 'in_progress' ? 'warning' : 'ghost') }}" />
                            </div>
                            
                            @if($okr->description)
                            <p class="text-sm text-gray-600 mb-3">{{ $okr->description }}</p>
                            @endif
                            
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm text-gray-500">Progress:</span>
                                    <div class="w-32 bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $okr->progress }}%"></div>
                                    </div>
                                    <span class="text-sm font-medium">{{ $okr->progress }}%</span>
                                </div>
                                
                                <span class="text-xs text-gray-500">
                                    Due: {{ $okr->end_date->format('M d, Y') }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <x-slot:actions>
                    <x-button label="View All OKRs" link="/hr/performance/okr?employee={{ $employee->id }}" class="btn-sm btn-outline" />
                </x-slot:actions>
            </x-card>
            @endif

            <!-- Recent Leave Requests -->
            <x-card title="Recent Leave Requests">
                <div class="space-y-3">
                    @forelse($recentLeaves as $leave)
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div>
                                <p class="font-medium">{{ $leave->leaveType->name ?? $leave->leave_type }}</p>
                                <p class="text-sm text-gray-600">
                                    {{ $leave->start_date->format('M d') }} - {{ $leave->end_date->format('M d, Y') }}
                                    ({{ $leave->days }} days)
                                </p>
                            </div>
                            <x-badge :value="$leave->status" class="badge-{{ $leave->status === 'approved' ? 'success' : ($leave->status === 'rejected' ? 'error' : 'warning') }}" />
                        </div>
                    @empty
                        <p class="text-gray-500 text-center py-4">No recent leave requests</p>
                    @endforelse
                </div>
                
                <x-slot:actions>
                    <x-button label="View Leave History" link="/hr/leaves?employee={{ $employee->id }}" class="btn-sm btn-outline" />
                </x-slot:actions>
            </x-card>

            <!-- Upcoming Performance Reviews -->
            @if($upcomingReviews->count() > 0)
            <x-card title="Upcoming Performance Reviews">
                <div class="space-y-3">
                    @foreach($upcomingReviews as $review)
                        <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg">
                            <div>
                                <p class="font-medium">{{ $review->review_period }}</p>
                                <p class="text-sm text-gray-600">Due: {{ $review->next_review_date->format('M d, Y') }}</p>
                            </div>
                            <x-badge :value="$review->status" class="badge-warning" />
                        </div>
                    @endforeach
                </div>
                
                <x-slot:actions>
                    <x-button label="View Performance History" link="/hr/performance?employee={{ $employee->id }}" class="btn-sm btn-outline" />
                </x-slot:actions>
            </x-card>
            @endif
        </div>
    </div>
</div>
