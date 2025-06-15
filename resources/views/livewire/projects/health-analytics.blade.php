<?php

use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Livewire\Volt\Component;

new class extends Component {
    use \Mary\Traits\Toast;

    public Project $project;

    #[\Livewire\Attributes\Url]
    public $dateRange = '30'; // 7, 30, 90 days
    public $healthTrends = [];

    public $velocityTrends = [];
    public $alertTrends = [];
    public $teamPerformance = [];

    public function mount(Request $request, Project $project): void
    {
        $this->project = $project;

        if ($request->filled('dateRange')) {
            $this->dateRange = $request->get('dateRange');
        }

        $this->loadAnalytics();
    }

    public function updatedDateRange(): void
    {
        $this->redirectRoute('projects.health-analytics', ['project' => $this->project->id, 'dateRange' => $this->dateRange]);
    }

    public function loadAnalytics(): void
    {
        $days = (int)$this->dateRange;
        $startDate = now()->subDays($days);

        // Health trends
        $this->healthTrends = $this->project->healthMetrics()
            ->where('metric_date', '>=', $startDate)
            ->orderBy('metric_date')
            ->get()
            ->map(function ($metric) {
                return [
                    'date' => $metric->metric_date->format('M j'),
                    'health_score' => $metric->health_score,
                    'completed_tasks' => $metric->completed_tasks_count,
                    'overdue_tasks' => $metric->overdue_tasks_count,
                    'velocity' => $metric->velocity,
                ];
            });

        // Alert trends
        $this->alertTrends = $this->project->alerts()
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, severity, COUNT(*) as count')
            ->groupBy('date', 'severity')
            ->orderBy('date')
            ->get()
            ->groupBy('date')
            ->map(function ($alerts, $date) {
                return [
                    'date' => Carbon::parse($date)->format('M j'),
                    'critical' => $alerts->where('severity', 'critical')->sum('count'),
                    'high' => $alerts->where('severity', 'high')->sum('count'),
                    'medium' => $alerts->where('severity', 'medium')->sum('count'),
                    'low' => $alerts->where('severity', 'low')->sum('count'),
                ];
            })->values();

        // Team performance
        $this->teamPerformance = $this->project->tasks()
            ->whereNotNull('user_id')
            ->where('updated_at', '>=', $startDate)
            ->with('assignee')
            ->get()
            ->groupBy('user_id')
            ->map(function ($tasks, $userId) {
                $user = $tasks->first()->assignee;
                $completed = $tasks->where('status', 'done')->count();
                $total = $tasks->count();
                $overdue = $tasks->where('due_date', '<', now())->where('status', '!=', 'done')->count();

                return [
                    'user_id' => $userId,
                    'name' => $user->name ?? 'Unknown',
                    'avatar' => $user->avatar ?? null,
                    'total_tasks' => $total,
                    'completed_tasks' => $completed,
                    'overdue_tasks' => $overdue,
                    'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
                    'efficiency_score' => $this->calculateEfficiencyScore($completed, $total, $overdue),
                ];
            })->values()->sortByDesc('efficiency_score');
    }

    private function calculateEfficiencyScore($completed, $total, $overdue)
    {
        if ($total === 0) return 0;

        $completionRate = ($completed / $total) * 100;
        $overdueRate = ($overdue / $total) * 100;

        return max(0, $completionRate - ($overdueRate * 2));
    }

    public function exportHealthReport(): void
    {
        $this->info('Health report export feature coming soon!');
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Health Analytics</h1>
            <p class="text-gray-600">{{ $project->name }} - Detailed Performance Insights</p>
        </div>
        <div class="flex items-center space-x-4">
            <x-select wire:model.live="dateRange" :options="[
                            ['id' => '7', 'name' => 'Last 7 days'],
                            ['id' => '30', 'name' => 'Last 30 days'],
                            ['id' => '90', 'name' => 'Last 90 days']
                        ]"/>
            <x-button wire:click="exportHealthReport" class="btn-secondary">
                <x-icon name="fas.download" class="w-4 h-4 mr-2"/>
                Export Report
            </x-button>
        </div>
    </div>

    <!-- Health Trends Chart -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Health Score Trends</h3>
        @if($healthTrends->count() > 0)
            <div class="h-64">
                <canvas id="healthTrendsChart"></canvas>
            </div>
        @else
            <div class="text-center py-8 text-gray-500">
                <x-icon name="fas.chart-line" class="w-12 h-12 mx-auto mb-4 text-gray-300"/>
                <p>No health data available for the selected period</p>
            </div>
        @endif
    </div>

    <!-- Key Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        @php
            $latestMetric = $healthTrends->last();
            $previousMetric = $healthTrends->count() > 1 ? $healthTrends->get($healthTrends->count() - 2) : null;
        @endphp

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Current Health Score</p>
                    <p class="text-2xl font-bold text-blue-600">
                        {{ $latestMetric ? number_format($latestMetric['health_score'], 1) : '0' }}%
                    </p>
                </div>
                <x-icon name="fas.heartbeat" class="w-8 h-8 text-blue-500"/>
            </div>
            @if($previousMetric)
                @php
                    $change = $latestMetric['health_score'] - $previousMetric['health_score'];
                    $changeColor = $change >= 0 ? 'green' : 'red';
                    $changeIcon = $change >= 0 ? 'fas.arrow-up' : 'fas.arrow-down';
                @endphp
                <div class="flex items-center mt-2 text-sm text-{{ $changeColor }}-600">
                    <x-icon :name="$changeIcon" class="w-3 h-3 mr-1"/>
                    {{ abs(number_format($change, 1)) }}% from previous
                </div>
            @endif
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Average Velocity</p>
                    <p class="text-2xl font-bold text-green-600">
                        {{ $healthTrends->avg('velocity') ? number_format($healthTrends->avg('velocity'), 1) : '0' }}
                    </p>
                </div>
                <x-icon name="fas.tachometer-alt" class="w-8 h-8 text-green-500"/>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Alerts</p>
                    <p class="text-2xl font-bold text-yellow-600">
                        {{ $project->alerts()->where('created_at', '>=', now()->subDays($dateRange))->count() }}
                    </p>
                </div>
                <x-icon name="fas.exclamation-triangle" class="w-8 h-8 text-yellow-500"/>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Resolution Rate</p>
                    @php
                        $totalAlerts = $project->alerts()->where('created_at', '>=', now()->subDays($dateRange))->count();
                        $resolvedAlerts = $project->alerts()->where('created_at', '>=', now()->subDays($dateRange))->where('is_resolved', true)->count();
                        $resolutionRate = $totalAlerts > 0 ? ($resolvedAlerts / $totalAlerts) * 100 : 0;
                    @endphp
                    <p class="text-2xl font-bold text-purple-600">{{ number_format($resolutionRate, 1) }}%</p>
                </div>
                <x-icon name="fas.check-circle" class="w-8 h-8 text-purple-500"/>
            </div>
        </div>
    </div>

    <!-- Team Performance -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Team Performance</h3>
        @if($teamPerformance->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                    <tr class="border-b">
                        <th class="text-left py-3 px-4">Team Member</th>
                        <th class="text-center py-3 px-4">Total Tasks</th>
                        <th class="text-center py-3 px-4">Completed</th>
                        <th class="text-center py-3 px-4">Overdue</th>
                        <th class="text-center py-3 px-4">Completion Rate</th>
                        <th class="text-center py-3 px-4">Efficiency Score</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($teamPerformance as $member)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="py-3 px-4">
                                <div class="flex items-center space-x-3">
                                    @if($member['avatar'])
                                        <img src="{{ $member['avatar'] }}" alt="{{ $member['name'] }}"
                                             class="w-8 h-8 rounded-full">
                                    @else
                                        <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                                                <span class="text-xs font-medium text-gray-600">
                                                    {{ substr($member['name'], 0, 2) }}
                                                </span>
                                        </div>
                                    @endif
                                    <span class="font-medium">{{ $member['name'] }}</span>
                                </div>
                            </td>
                            <td class="text-center py-3 px-4">{{ $member['total_tasks'] }}</td>
                            <td class="text-center py-3 px-4 text-green-600">{{ $member['completed_tasks'] }}</td>
                            <td class="text-center py-3 px-4 text-red-600">{{ $member['overdue_tasks'] }}</td>
                            <td class="text-center py-3 px-4">
                                <div class="flex items-center justify-center">
                                    <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                        <div class="bg-blue-500 h-2 rounded-full"
                                             style="width: {{ $member['completion_rate'] }}%"></div>
                                    </div>
                                    <span class="text-sm">{{ $member['completion_rate'] }}%</span>
                                </div>
                            </td>
                            <td class="text-center py-3 px-4">
                                @php
                                    $scoreColor = match(true) {
                                                                            $member['efficiency_score'] >= 80 => 'green',
                                                                            $member['efficiency_score'] >= 60 => 'blue',
                                                                            $member['efficiency_score'] >= 40 => 'yellow',
                                                                            default => 'red'
                                                                        };
                                @endphp
                                <x-badge :value="number_format($member['efficiency_score'], 1)"
                                         class="badge-{{ $scoreColor }}"/>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8 text-gray-500">
                <x-icon name="fas.users" class="w-12 h-12 mx-auto mb-4 text-gray-300"/>
                <p>No team performance data available</p>
            </div>
        @endif
    </div>

    <!-- Alert Trends -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Alert Trends</h3>
        @if($alertTrends->count() > 0)
            <div class="h-64">
                <canvas id="alertTrendsChart"></canvas>
            </div>
        @else
            <div class="text-center py-8 text-gray-500">
                <x-icon name="fas.bell" class="w-12 h-12 mx-auto mb-4 text-gray-300"/>
                <p>No alert data available for the selected period</p>
            </div>
        @endif
    </div>
</div>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Health Trends Chart
            const healthCtx = document.getElementById('healthTrendsChart');
            if (healthCtx && @json($healthTrends->count()) > 0) {
                new Chart(healthCtx, {
                    type: 'line',
                    data: {
                        labels: @json($healthTrends->pluck('date')),
                        datasets: [{
                            label: 'Health Score',
                            data: @json($healthTrends->pluck('health_score')),
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4
                        }, {
                            label: 'Velocity',
                            data: @json($healthTrends->pluck('velocity')),
                            borderColor: 'rgb(34, 197, 94)',
                            backgroundColor: 'rgba(34, 197, 94, 0.1)',
                            tension: 0.4,
                            yAxisID: 'y1'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                                title: {
                                    display: true,
                                    text: 'Health Score (%)'
                                }
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                title: {
                                    display: true,
                                    text: 'Velocity'
                                },
                                grid: {
                                    drawOnChartArea: false,
                                },
                            }
                        }
                    }
                });
            }

            // Alert Trends Chart
            const alertCtx = document.getElementById('alertTrendsChart');
            if (alertCtx && @json($alertTrends->count()) > 0) {
                new Chart(alertCtx, {
                    type: 'bar',
                    data: {
                        labels: @json($alertTrends->pluck('date')),
                        datasets: [{
                            label: 'Critical',
                            data: @json($alertTrends->pluck('critical')),
                            backgroundColor: 'rgba(239, 68, 68, 0.8)'
                        }, {
                            label: 'High',
                            data: @json($alertTrends->pluck('high')),
                            backgroundColor: 'rgba(245, 101, 101, 0.8)'
                        }, {
                            label: 'Medium',
                            data: @json($alertTrends->pluck('medium')),
                            backgroundColor: 'rgba(251, 191, 36, 0.8)'
                        }, {
                            label: 'Low',
                            data: @json($alertTrends->pluck('low')),
                            backgroundColor: 'rgba(59, 130, 246, 0.8)'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                stacked: true,
                            },
                            y: {
                                stacked: true,
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Number of Alerts'
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
@endpush
