<?php

new class extends Livewire\Volt\Component {
    public $project;
    public $sprint;
    public $burndownData = [];
    public $idealBurndownData = [];
    public $dates = [];
    public $remainingPoints = [];
    public $idealPoints = [];

    public function mount($project, $sprint)
    {
        $this->project = \App\Models\Project::findOrFail($project);
        $this->sprint = \App\Models\Sprint::findOrFail($sprint);

        $this->calculateBurndownData();
    }

    public function calculateBurndownData()
    {
        // Sprint'in görevlerini yükle
        $this->sprint->load(['tasks.status', 'tasks.user']);

        // Sprint başlangıç ve bitiş tarihleri
        $startDate = $this->sprint->start_date ?? $this->sprint->created_at;
        $endDate = $this->sprint->end_date ?? $startDate->copy()->addDays(14);

        // Toplam story point'leri hesapla
        $totalStoryPoints = $this->sprint->tasks->sum('story_points') ?: count($this->sprint->tasks) * 3; // Varsayılan olarak her görev 3 puan

        // Tarih aralığını oluştur
        $currentDate = $startDate->copy();
        $dateRange = [];

        while ($currentDate->lte($endDate)) {
            $dateRange[] = $currentDate->copy();
            $currentDate->addDay();
        }

        // Günlük kalan puanları hesapla
        $taskCompletionDates = [];

        // Tamamlanan görevlerin tarihlerini topla
        foreach ($this->sprint->tasks as $task) {
            if ($task->status && $task->status->slug === 'done') {
                // Görevin tamamlanma tarihi, son güncelleme tarihi olarak kabul edilir
                $completionDate = $task->updated_at->format('Y-m-d');
                $storyPoints = $task->story_points ?: 3; // Varsayılan olarak 3 puan

                if (!isset($taskCompletionDates[$completionDate])) {
                    $taskCompletionDates[$completionDate] = 0;
                }

                $taskCompletionDates[$completionDate] += $storyPoints;
            }
        }

        // Burndown verilerini oluştur
        $remainingPoints = $totalStoryPoints;
        $burndownData = [];

        foreach ($dateRange as $date) {
            $dateString = $date->format('Y-m-d');

            // Eğer bu tarihte tamamlanan görevler varsa, kalan puanları azalt
            if (isset($taskCompletionDates[$dateString])) {
                $remainingPoints -= $taskCompletionDates[$dateString];
            }

            $burndownData[$dateString] = max(0, $remainingPoints);

            // Bugünden sonraki tarihler için gerçek veriler yerine tahmin kullan
            if ($date->isAfter(now())) {
                break;
            }
        }

        // İdeal burndown çizgisi (doğrusal azalma)
        $sprintDuration = $startDate->diffInDays($endDate) + 1;
        $dailyIdealBurn = $totalStoryPoints / $sprintDuration;
        $idealBurndownData = [];
        $idealRemaining = $totalStoryPoints;

        foreach ($dateRange as $date) {
            $dateString = $date->format('Y-m-d');
            $idealBurndownData[$dateString] = max(0, $idealRemaining);
            $idealRemaining -= $dailyIdealBurn;
        }

        // Chart.js için verileri hazırla
        $this->dates = array_keys($burndownData);
        $this->remainingPoints = array_values($burndownData);
        $this->idealPoints = array_map(function ($date) use ($idealBurndownData) {
            return $idealBurndownData[$date] ?? 0;
        }, $this->dates);
    }
}

?>

<div>
    <x-slot:title>Burndown Chart - {{ $sprint->name }}</x-slot:title>

    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <div class="flex items-center gap-2">
                <x-button link="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}" icon="o-arrow-left" class="btn-ghost btn-sm" />
                <h1 class="text-2xl font-bold text-primary">Burndown Chart: {{ $sprint->name }}</h1>
                <div class="badge {{ $sprint->is_completed ? 'badge-info' : ($sprint->is_active ? 'badge-success' : 'badge-warning') }}">
                    {{ $sprint->is_completed ? 'Completed' : ($sprint->is_active ? 'Active' : 'Planned') }}
                </div>
            </div>

            <div class="flex gap-2">
                <x-button link="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}/report" label="View Report" icon="o-chart-bar" class="btn-outline" />
                <x-button link="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}/board" label="Task Board" icon="o-view-columns" class="btn-outline" />
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Burndown Chart -->
            <div class="card bg-base-100 shadow-xl lg:col-span-2">
                <div class="card-body">
                    <h2 class="card-title">Burndown Chart</h2>
                    <p class="text-sm text-gray-500">Shows the remaining work over time compared to the ideal burndown.</p>

                    <div class="h-80 mt-4">
                        <canvas id="burndownChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Sprint Summary -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">Sprint Summary</h2>

                    <div class="mt-4 space-y-4">
                        <div>
                            <h3 class="font-medium text-sm">Sprint Duration</h3>
                            <p>
                                {{ ($sprint->start_date ?? $sprint->created_at)->format('M d, Y') }} -
                                {{ ($sprint->end_date ?? ($sprint->start_date ?? $sprint->created_at)->copy()->addDays(14))->format('M d, Y') }}
                            </p>
                        </div>

                        <div>
                            <h3 class="font-medium text-sm">Total Story Points</h3>
                            <p>{{ $sprint->tasks->sum('story_points') ?: count($sprint->tasks) * 3 }}</p>
                        </div>

                        <div>
                            <h3 class="font-medium text-sm">Completed Story Points</h3>
                            <p>
                                {{ $sprint->tasks->filter(function($task) { return $task->status && $task->status->slug === 'done'; })->sum('story_points') ?:
                                   $sprint->tasks->filter(function($task) { return $task->status && $task->status->slug === 'done'; })->count() * 3 }}
                            </p>
                        </div>

                        <div>
                            <h3 class="font-medium text-sm">Completion Rate</h3>
                            @php
                                $totalPoints = $sprint->tasks->sum('story_points') ?: count($sprint->tasks) * 3;
                                $completedPoints = $sprint->tasks->filter(function($task) {
                                    return $task->status && $task->status->slug === 'done';
                                })->sum('story_points') ?:
                                $sprint->tasks->filter(function($task) {
                                    return $task->status && $task->status->slug === 'done';
                                })->count() * 3;

                                $completionRate = $totalPoints > 0 ? round(($completedPoints / $totalPoints) * 100) : 0;
                            @endphp
                            <div class="flex items-center gap-2">
                                <progress class="progress progress-primary w-full" value="{{ $completionRate }}" max="100"></progress>
                                <span>{{ $completionRate }}%</span>
                            </div>
                        </div>

                        <div>
                            <h3 class="font-medium text-sm">Sprint Velocity</h3>
                            @php
                                $startDate = $sprint->start_date ?? $sprint->created_at;
                                $endDate = $sprint->end_date ?? $startDate->copy()->addDays(14);
                                $sprintDuration = $startDate->diffInDays($endDate) + 1;
                                $daysElapsed = min($sprintDuration, $startDate->diffInDays(now()) + 1);
                                $velocity = $daysElapsed > 0 ? round($completedPoints / $daysElapsed, 1) : 0;
                            @endphp
                            <p>{{ $velocity }} points per day</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('burndownChart').getContext('2d');

        const dates = @json($dates);
        const remainingPoints = @json($remainingPoints);
        const idealPoints = @json($idealPoints);

        const burndownChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [
                    {
                        label: 'Actual Burndown',
                        data: remainingPoints,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 2,
                        tension: 0.1,
                        fill: true
                    },
                    {
                        label: 'Ideal Burndown',
                        data: idealPoints,
                        borderColor: '#ef4444',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        tension: 0,
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Remaining Story Points'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    },
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
    });
</script>
