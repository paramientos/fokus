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

<div class="bg-gradient-to-br from-base-100 to-base-200 min-h-screen">
    <x-slot:title>Burndown Chart - {{ $sprint->name }}</x-slot:title>

    <div class="max-w-7xl mx-auto p-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div class="flex items-center gap-3">
                <x-button 
                    link="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}" 
                    icon="fas.arrow-left" 
                    class="btn-ghost btn-sm hover:bg-base-200 transition-all duration-200"
                    tooltip="Back to Sprint"
                />
                <div>
                    <h1 class="text-2xl font-bold text-primary">Burndown Chart</h1>
                    <div class="flex items-center gap-2 text-base-content/70">
                        <span class="font-medium">{{ $sprint->name }}</span>
                        <div class="badge {{ $sprint->is_completed ? 'badge-info' : ($sprint->is_active ? 'badge-success' : 'badge-warning') }}">
                            @if($sprint->is_completed)
                                <i class="fas fa-check-circle mr-1"></i> Completed
                            @elseif($sprint->is_active)
                                <i class="fas fa-play-circle mr-1"></i> Active
                            @else
                                <i class="fas fa-clock mr-1"></i> Planned
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <x-button 
                    link="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}/report" 
                    label="View Report" 
                    icon="fas.chart-bar" 
                    class="btn-outline btn-sm hover:bg-base-200 transition-all duration-200"
                    tooltip="Sprint Report"
                />
                <x-button 
                    link="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}/board" 
                    label="Task Board" 
                    icon="fas.columns" 
                    class="btn-outline btn-sm hover:bg-base-200 transition-all duration-200"
                    tooltip="Sprint Board"
                />
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Burndown Chart -->
            <div class="bg-base-100 rounded-xl shadow-xl border border-base-300 overflow-hidden lg:col-span-2">
                <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center gap-3">
                    <span class="p-2 rounded-full bg-primary/10 text-primary">
                        <i class="fas fa-chart-line text-lg"></i>
                    </span>
                    <div>
                        <h2 class="text-xl font-semibold">Burndown Chart</h2>
                        <p class="text-sm text-base-content/70">Remaining work over time compared to ideal burndown</p>
                    </div>
                </div>
                
                <div class="p-6">
                    <div class="h-80">
                        <canvas id="burndownChart"></canvas>
                    </div>
                    
                    <div class="mt-4 flex justify-center gap-6 text-sm text-base-content/70">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-primary"></span>
                            <span>Actual Burndown</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-error"></span>
                            <span>Ideal Burndown</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sprint Summary -->
            <div class="bg-base-100 rounded-xl shadow-xl border border-base-300 overflow-hidden">
                <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center gap-3">
                    <span class="p-2 rounded-full bg-primary/10 text-primary">
                        <i class="fas fa-info-circle text-lg"></i>
                    </span>
                    <h2 class="text-xl font-semibold">Sprint Summary</h2>
                </div>
                
                <div class="p-6 space-y-6">
                    <!-- Sprint Duration -->
                    <div class="flex items-center gap-4">
                        <div class="p-3 rounded-full bg-primary/10 text-primary">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div>
                            <h3 class="font-medium text-sm text-base-content/70">Sprint Duration</h3>
                            <p class="font-semibold">
                                {{ ($sprint->start_date ?? $sprint->created_at)->format('M d, Y') }} -
                                {{ ($sprint->end_date ?? ($sprint->start_date ?? $sprint->created_at)->copy()->addDays(14))->format('M d, Y') }}
                            </p>
                        </div>
                    </div>
                    
                    <!-- Story Points -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-base-200/50 p-4 rounded-lg border border-base-300 text-center">
                            <div class="text-2xl font-bold text-primary">
                                {{ $sprint->tasks->sum('story_points') ?: count($sprint->tasks) * 3 }}
                            </div>
                            <div class="text-xs text-base-content/70 mt-1">Total Points</div>
                        </div>
                        
                        <div class="bg-base-200/50 p-4 rounded-lg border border-base-300 text-center">
                            <div class="text-2xl font-bold text-success">
                                {{ $sprint->tasks->filter(function($task) { return $task->status && $task->status->slug === 'done'; })->sum('story_points') ?:
                                   $sprint->tasks->filter(function($task) { return $task->status && $task->status->slug === 'done'; })->count() * 3 }}
                            </div>
                            <div class="text-xs text-base-content/70 mt-1">Completed Points</div>
                        </div>
                    </div>
                    
                    <!-- Completion Rate -->
                    <div>
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
                        
                        <div class="mb-2 flex justify-between items-center">
                            <h3 class="font-medium text-sm text-base-content/70">Completion Rate</h3>
                            <span class="font-bold {{ $completionRate >= 70 ? 'text-success' : ($completionRate >= 30 ? 'text-warning' : 'text-base-content') }}">{{ $completionRate }}%</span>
                        </div>
                        
                        <div class="w-full h-2 bg-base-200 rounded-full overflow-hidden">
                            <div class="h-full {{ $completionRate >= 70 ? 'bg-success' : ($completionRate >= 30 ? 'bg-warning' : 'bg-primary') }} transition-all duration-500" style="width: {{ $completionRate }}%"></div>
                        </div>
                    </div>
                    
                    <!-- Sprint Velocity -->
                    <div class="flex items-center gap-4">
                        <div class="p-3 rounded-full bg-primary/10 text-primary">
                            <i class="fas fa-tachometer-alt"></i>
                        </div>
                        <div>
                            @php
                                $startDate = $sprint->start_date ?? $sprint->created_at;
                                $endDate = $sprint->end_date ?? $startDate->copy()->addDays(14);
                                $sprintDuration = $startDate->diffInDays($endDate) + 1;
                                $daysElapsed = min($sprintDuration, $startDate->diffInDays(now()) + 1);
                                $velocity = $daysElapsed > 0 ? round($completedPoints / $daysElapsed, 1) : 0;
                            @endphp
                            <h3 class="font-medium text-sm text-base-content/70">Sprint Velocity</h3>
                            <p class="font-semibold">{{ $velocity }} <span class="text-xs text-base-content/70">points per day</span></p>
                        </div>
                    </div>
                    
                    <!-- Days Remaining -->
                    @php
                        $now = now();
                        $daysRemaining = max(0, $endDate->diffInDays($now));
                        $isEnded = $now > $endDate;
                    @endphp
                    <div class="flex items-center gap-4">
                        <div class="p-3 rounded-full {{ $isEnded ? 'bg-success/10 text-success' : 'bg-warning/10 text-warning' }}">
                            <i class="fas {{ $isEnded ? 'fa-flag-checkered' : 'fa-hourglass-half' }}"></i>
                        </div>
                        <div>
                            <h3 class="font-medium text-sm text-base-content/70">{{ $isEnded ? 'Sprint Ended' : 'Days Remaining' }}</h3>
                            <p class="font-semibold">{{ $isEnded ? 'Completed' : $daysRemaining . ' days' }}</p>
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
        
        // Format dates for better display
        const formattedDates = dates.map(date => {
            const d = new Date(date);
            return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        });
        
        // Get theme colors from CSS variables
        const getThemeColor = (colorName) => {
            const style = getComputedStyle(document.documentElement);
            return style.getPropertyValue(`--${colorName}`) || null;
        };
        
        // Chart theme colors
        const primaryColor = getThemeColor('p') || '#3b82f6'; // primary color
        const errorColor = getThemeColor('er') || '#ef4444';  // error color
        const gridColor = 'rgba(200, 200, 200, 0.1)';
        const textColor = 'rgba(150, 150, 150, 0.8)';
        
        const burndownChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: formattedDates,
                datasets: [
                    {
                        label: 'Actual Burndown',
                        data: remainingPoints,
                        borderColor: primaryColor,
                        backgroundColor: `${primaryColor}20`, // 20 = 12% opacity
                        borderWidth: 3,
                        tension: 0.3,
                        fill: true,
                        pointBackgroundColor: primaryColor,
                        pointBorderColor: '#fff',
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointHoverBorderWidth: 2
                    },
                    {
                        label: 'Ideal Burndown',
                        data: idealPoints,
                        borderColor: errorColor,
                        borderWidth: 2,
                        borderDash: [5, 5],
                        tension: 0,
                        fill: false,
                        pointBackgroundColor: errorColor,
                        pointBorderColor: '#fff',
                        pointRadius: 3,
                        pointHoverRadius: 5
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Date',
                            color: textColor,
                            font: {
                                weight: 'bold'
                            }
                        },
                        grid: {
                            display: true,
                            color: gridColor,
                            borderColor: gridColor
                        },
                        ticks: {
                            color: textColor
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Remaining Story Points',
                            color: textColor,
                            font: {
                                weight: 'bold'
                            }
                        },
                        grid: {
                            display: true,
                            color: gridColor,
                            borderColor: gridColor
                        },
                        ticks: {
                            color: textColor,
                            precision: 0 // Only show whole numbers
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(50, 50, 50, 0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: 'rgba(255, 255, 255, 0.2)',
                        borderWidth: 1,
                        padding: 10,
                        cornerRadius: 6,
                        titleFont: {
                            weight: 'bold'
                        },
                        callbacks: {
                            title: function(tooltipItems) {
                                return tooltipItems[0].label;
                            },
                            label: function(context) {
                                return `${context.dataset.label}: ${context.parsed.y} points`;
                            }
                        }
                    },
                    legend: {
                        position: 'top',
                        labels: {
                            color: textColor,
                            usePointStyle: true,
                            padding: 20,
                            font: {
                                size: 12
                            }
                        }
                    },
                    annotation: {
                        annotations: {
                            today: {
                                type: 'line',
                                xMin: new Date().toISOString().split('T')[0],
                                xMax: new Date().toISOString().split('T')[0],
                                borderColor: 'rgba(150, 150, 150, 0.5)',
                                borderWidth: 2,
                                borderDash: [2, 2],
                                label: {
                                    content: 'Today',
                                    enabled: true,
                                    position: 'top'
                                }
                            }
                        }
                    }
                },
                animation: {
                    duration: 1500,
                    easing: 'easeOutQuart'
                }
            }
        });
        
        // Update chart on theme change if your app supports theme switching
        document.addEventListener('themeChanged', function() {
            // Re-fetch theme colors and update chart
            const newPrimaryColor = getThemeColor('p') || '#3b82f6';
            const newErrorColor = getThemeColor('er') || '#ef4444';
            
            burndownChart.data.datasets[0].borderColor = newPrimaryColor;
            burndownChart.data.datasets[0].backgroundColor = `${newPrimaryColor}20`;
            burndownChart.data.datasets[0].pointBackgroundColor = newPrimaryColor;
            
            burndownChart.data.datasets[1].borderColor = newErrorColor;
            burndownChart.data.datasets[1].pointBackgroundColor = newErrorColor;
            
            burndownChart.update();
        });
    });
</script>
