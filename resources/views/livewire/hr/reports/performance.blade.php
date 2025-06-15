<?php
use Livewire\Volt\Component;
use App\Models\PerformanceReview;
use App\Models\Employee;
use App\Models\OkrGoal;

new class extends Component {
    public $selectedPeriod = 'this_quarter';
    public $selectedDepartment = '';

    public function with()
    {
        $workspaceId = session('workspace_id');
        $dateRange = $this->getDateRange();

        // Performance Reviews Data
        $reviews = PerformanceReview::whereHas('employee', fn($q) => $q->where('workspace_id', $workspaceId))
            ->with(['employee.user'])
            ->whereBetween('created_at', $dateRange)
            ->get();

        // OKR Data
        $okrs = OkrGoal::where('workspace_id', $workspaceId)
            ->with(['employee.user'])
            ->whereBetween('created_at', $dateRange)
            ->get();

        // Performance by Department
        $departmentPerformance = $reviews->groupBy('employee.department')
            ->map(function($deptReviews) {
                return [
                    'count' => $deptReviews->count(),
                    'avg_rating' => $deptReviews->avg('overall_rating'),
                    'completed' => $deptReviews->where('status', 'completed')->count()
                ];
            });

        // Top Performers
        $topPerformers = $reviews->where('status', 'completed')
            ->sortByDesc('overall_rating')
            ->take(10);

        // OKR Progress by Employee
        $okrProgress = $okrs->groupBy('employee_id')
            ->map(function($empOkrs) {
                return [
                    'employee' => $empOkrs->first()->employee,
                    'total_okrs' => $empOkrs->count(),
                    'completed_okrs' => $empOkrs->where('status', 'completed')->count(),
                    'avg_progress' => $empOkrs->avg('progress'),
                    'completion_rate' => $empOkrs->count() > 0 ? ($empOkrs->where('status', 'completed')->count() / $empOkrs->count()) * 100 : 0
                ];
            });

        return [
            'reviews' => $reviews,
            'okrs' => $okrs,
            'departmentPerformance' => $departmentPerformance,
            'topPerformers' => $topPerformers,
            'okrProgress' => $okrProgress,

            // Summary Stats
            'totalReviews' => $reviews->count(),
            'completedReviews' => $reviews->where('status', 'completed')->count(),
            'averageRating' => $reviews->where('status', 'completed')->avg('overall_rating'),
            'totalOkrs' => $okrs->count(),
            'completedOkrs' => $okrs->where('status', 'completed')->count(),
            'averageOkrProgress' => $okrs->avg('progress'),
        ];
    }

    private function getDateRange()
    {
        return match($this->selectedPeriod) {
            'this_month' => [now()->startOfMonth(), now()->endOfMonth()],
            'this_quarter' => [now()->startOfQuarter(), now()->endOfQuarter()],
            'this_year' => [now()->startOfYear(), now()->endOfYear()],
            'last_quarter' => [now()->subQuarter()->startOfQuarter(), now()->subQuarter()->endOfQuarter()],
            'last_year' => [now()->subYear()->startOfYear(), now()->subYear()->endOfYear()],
            default => [now()->startOfQuarter(), now()->endOfQuarter()],
        };
    }
}; ?>

<div>
    <x-header title="Performance Analytics Report" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-button label="Export Report" icon="fas.download" class="btn-primary" />
        </x-slot:middle>
    </x-header>

    <!-- Filters -->
    <x-card class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-select
                label="Time Period"
                wire:model.live="selectedPeriod"
                :options="[
                    ['id' => 'this_month', 'name' => 'This Month'],
                    ['id' => 'this_quarter', 'name' => 'This Quarter'],
                    ['id' => 'this_year', 'name' => 'This Year'],
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
        </div>
    </x-card>

    <!-- Performance Summary -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <x-stat
            title="Total Reviews"
            :value="$totalReviews"
            icon="fas.clipboard-list"
            class="bg-gradient-to-r from-blue-500 to-blue-600 text-white"
        />

        <x-stat
            title="Completed Reviews"
            :value="$completedReviews"
            icon="fas.check-circle"
            class="bg-gradient-to-r from-green-500 to-green-600 text-white"
        />

        <x-stat
            title="Average Rating"
            :value="number_format($averageRating ?? 0, 1) . '/5.0'"
            icon="fas.star"
            class="bg-gradient-to-r from-yellow-500 to-yellow-600 text-white"
        />

        <x-stat
            title="Completion Rate"
            :value="($totalReviews > 0 ? number_format(($completedReviews / $totalReviews) * 100, 1) : 0) . '%'"
            icon="fas.percentage"
            class="bg-gradient-to-r from-purple-500 to-purple-600 text-white"
        />
    </div>

    <!-- OKR Summary -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <x-stat
            title="Total OKRs"
            :value="$totalOkrs"
            icon="fas.bullseye"
            class="bg-gradient-to-r from-indigo-500 to-indigo-600 text-white"
        />

        <x-stat
            title="Completed OKRs"
            :value="$completedOkrs"
            icon="fas.bullseye"
            class="bg-gradient-to-r from-teal-500 to-teal-600 text-white"
        />

        <x-stat
            title="Average Progress"
            :value="number_format($averageOkrProgress ?? 0, 0) . '%'"
            icon="fas.chart-line"
            class="bg-gradient-to-r from-orange-500 to-orange-600 text-white"
        />

        <x-stat
            title="OKR Completion Rate"
            :value="($totalOkrs > 0 ? number_format(($completedOkrs / $totalOkrs) * 100, 1) : 0) . '%'"
            icon="fas.trophy"
            class="bg-gradient-to-r from-pink-500 to-pink-600 text-white"
        />
    </div>

    <!-- Charts and Analysis -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Department Performance -->
        <x-card title="Performance by Department">
            <div class="space-y-4">
                @forelse($departmentPerformance as $department => $data)
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div>
                            <h4 class="font-medium">{{ ucfirst($department ?: 'Unassigned') }}</h4>
                            <p class="text-sm text-gray-600">{{ $data['count'] }} reviews</p>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-lg">{{ number_format($data['avg_rating'] ?? 0, 1) }}</div>
                            <div class="text-sm text-gray-600">Average Rating</div>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-8">No performance data available for this period.</p>
                @endforelse
            </div>
        </x-card>

        <!-- Top Performers -->
        <x-card title="Top Performers">
            <div class="space-y-3">
                @forelse($topPerformers as $review)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <x-avatar :image="$review->employee->user->avatar" class="!w-8 !h-8" />
                            <div>
                                <div class="font-medium">{{ $review->employee->user->name }}</div>
                                <div class="text-sm text-gray-600">{{ $review->employee->position }}</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-lg text-green-600">{{ number_format($review->overall_rating, 1) }}</div>
                            <div class="text-sm text-gray-600">Rating</div>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-8">No completed reviews available.</p>
                @endforelse
            </div>
        </x-card>
    </div>

    <!-- OKR Progress by Employee -->
    <x-card title="OKR Progress by Employee" class="mb-8">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total OKRs</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completed</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Progress</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completion Rate</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($okrProgress as $empId => $data)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <x-avatar :image="$data['employee']->user->avatar" class="!w-8 !h-8 mr-3" />
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $data['employee']->user->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $data['employee']->position }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $data['total_okrs'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $data['completed_okrs'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $data['avg_progress'] }}%"></div>
                                    </div>
                                    <span class="text-sm text-gray-900">{{ number_format($data['avg_progress'], 0) }}%</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-badge
                                    :value="number_format($data['completion_rate'], 1) . '%'"
                                    class="badge-{{ $data['completion_rate'] >= 80 ? 'success' : ($data['completion_rate'] >= 60 ? 'warning' : 'error') }}"
                                />
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <x-button
                                    label="View OKRs"
                                    link="/hr/okr?employee={{ $empId }}"
                                    class="btn-sm btn-outline"
                                />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                No OKR data available for this period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <!-- Recent Performance Reviews -->
    <x-card title="Recent Performance Reviews">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Review Period</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Overall Rating</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reviewer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($reviews->take(10) as $review)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <x-avatar :image="$review->employee->user->avatar" class="!w-8 !h-8 mr-3" />
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $review->employee->user->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $review->employee->position }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $review->review_period }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($review->overall_rating)
                                    <div class="flex items-center">
                                        <span class="text-lg font-bold mr-2">{{ number_format($review->overall_rating, 1) }}</span>
                                        <div class="flex text-yellow-400">
                                            @for($i = 1; $i <= 5; $i++)
                                                <x-icon
                                                    name="fas.star"
                                                    class="w-4 h-4 {{ $i <= $review->overall_rating ? 'text-yellow-400' : 'text-gray-300' }}"
                                                />
                                            @endfor
                                        </div>
                                    </div>
                                @else
                                    <span class="text-gray-400">Not rated</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-badge
                                    :value="$review->status"
                                    class="badge-{{ $review->status === 'completed' ? 'success' : ($review->status === 'in_progress' ? 'warning' : 'ghost') }}"
                                />
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $review->reviewer->name ?? 'Not assigned' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <x-button
                                    label="View Review"
                                    link="/hr/performance/{{ $review->id }}"
                                    class="btn-sm btn-outline"
                                />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                No performance reviews available for this period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>
