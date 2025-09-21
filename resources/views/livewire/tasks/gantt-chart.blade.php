<?php
new class extends Livewire\Volt\Component {
    public \App\Models\Project $project;

    public $tasks = [];
    public $dateRange = 'month';
    public $viewMode = 'Day';
    public $customStartDate = null;
    public $customEndDate = null;
    public $selectedTaskId = null;

    public function mount($project)
    {
        $this->loadTasks();
    }

    public function loadTasks()
    {
        $query = $this->project->tasks();

        // Eğer özel tarih aralığı seçilmişse, o aralıktaki görevleri getir
        if ($this->customStartDate && $this->customEndDate) {
            $query->where(function ($q) {
                $q->whereBetween('started_at', [$this->customStartDate, $this->customEndDate])
                    ->orWhereBetween('due_date', [$this->customStartDate, $this->customEndDate])
                    ->orWhereBetween('completed_at', [$this->customStartDate, $this->customEndDate]);
            });
        } else {
            // Varsayılan olarak son 1 aylık görevleri getir
            $startDate = now()->subMonth();
            $endDate = now()->addMonths(2);

            if ($this->dateRange === 'week') {
                $startDate = now()->subWeek();
                $endDate = now()->addWeeks(2);
            } elseif ($this->dateRange === 'quarter') {
                $startDate = now()->subMonths(3);
                $endDate = now()->addMonths(3);
            } elseif ($this->dateRange === 'year') {
                $startDate = now()->subYear();
                $endDate = now()->addYear();
            }

            $query->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('started_at', [$startDate, $endDate])
                    ->orWhereBetween('due_date', [$startDate, $endDate])
                    ->orWhereBetween('completed_at', [$startDate, $endDate])
                    ->orWhereNull('started_at')
                    ->orWhereNull('due_date');
            });
        }

        $this->tasks = $query->with(['status', 'assignee', 'dependencies', 'tags'])->get();

        $this->dispatch('tasksUpdated');
    }

    public function updateDateRange($range)
    {
        $this->dateRange = $range;
        $this->loadTasks();
    }

    public function updateViewMode($mode)
    {
        $this->viewMode = $mode;
        $this->dispatch('viewModeChanged', $mode);
    }

    public function setCustomDateRange()
    {
        $this->loadTasks();
    }

    public function updateTaskDates($taskId, $start, $end)
    {
        $task = \App\Models\Task::find($taskId);
        if (!$task) return;

        $task->started_at = $start;
        $task->due_date = $end;
        $task->save();

        // Aktivite kaydı oluştur
        \App\Models\Activity::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'action' => 'updated',
            'description' => 'Task dates updated via Gantt chart',
            'changes' => json_encode([
                'started_at' => $start,
                'due_date' => $end
            ])
        ]);

        $this->loadTasks();
    }

    public function updateTaskProgress($taskId, $progress)
    {
        $task = \App\Models\Task::find($taskId);
        if (!$task || !$task->time_estimate) return;

        // İlerleme yüzdesine göre harcanan zamanı hesapla
        $task->time_spent = ($progress / 100) * $task->time_estimate;

        // Eğer ilerleme %100 ise, tamamlanma tarihini ayarla
        if ($progress >= 100) {
            $task->completed_at = now();
        } elseif ($progress > 0 && !$task->started_at) {
            $task->started_at = now();
        }

        $task->save();

        // Aktivite kaydı oluştur
        \App\Models\Activity::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'action' => 'updated',
            'description' => 'Task progress updated via Gantt chart',
            'changes' => json_encode([
                'progress' => $progress,
                'time_spent' => $task->time_spent
            ])
        ]);

        $this->loadTasks();
    }

    public function selectTask($taskId)
    {
        $this->selectedTaskId = $taskId;
    }

    public function getGanttTasksProperty()
    {
        return $this->tasks->map(function ($task) {
            $startDate = $task->started_at ?? $task->created_at;
            $endDate = $task->completed_at ?? $task->due_date ?? $startDate->copy()->addDays(1);

            // Eğer başlangıç ve bitiş tarihi aynıysa, bitiş tarihini 1 gün ileri al
            if ($startDate->isSameDay($endDate)) {
                $endDate = $endDate->copy()->addDay();
            }

            $dependencies = $task->dependencies->pluck('id')->map(function ($id) {
                return (string)$id;
            })->toArray();

            $progress = 0;
            if ($task->time_estimate && $task->time_spent) {
                $progress = min(100, round(($task->time_spent / $task->time_estimate) * 100));
            } elseif ($task->completed_at) {
                $progress = 100;
            } elseif ($task->started_at) {
                $progress = 50;
            }

            return [
                'id' => (string)$task->id,
                'name' => $task->title,
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
                'progress' => $progress,
                'dependencies' => implode(', ', $dependencies),
                'custom_class' => $this->getTaskClass($task),
                'assignee' => $task->assignee ? $task->assignee->name : null,
                'description' => $task->description ? substr(strip_tags($task->description), 0, 100) . '...' : null,
                'status' => $task->status ? $task->status->name : null,
                'priority' => $task->priority ? $task->priority->value : null,
                'tags' => $task->tags->pluck('name')->toArray(),
            ];
        })->toArray();
    }

    private function getTaskClass($task)
    {
        // Görevin durumuna göre renk sınıfı belirle
        $statusMap = [
            'To Do' => 'task-todo',
            'In Progress' => 'task-progress',
            'Done' => 'task-done',
            'Blocked' => 'task-blocked',
            'Review' => 'task-review',
        ];

        $statusName = $task->status->name ?? '';
        return $statusMap[$statusName] ?? 'task-default';
    }

    public function exportGanttToCSV()
    {
        $csvContent = "ID,Title,Start Date,End Date,Progress,Status,Assignee\n";

        foreach ($this->tasks as $task) {
            $startDate = $task->started_at ? $task->started_at->format('Y-m-d') : ($task->created_at ? $task->created_at->format('Y-m-d') : '');
            $endDate = $task->completed_at ? $task->completed_at->format('Y-m-d') : ($task->due_date ? $task->due_date->format('Y-m-d') : '');

            $progress = 0;
            if ($task->time_estimate && $task->time_spent) {
                $progress = min(100, round(($task->time_spent / $task->time_estimate) * 100));
            } elseif ($task->completed_at) {
                $progress = 100;
            } elseif ($task->started_at) {
                $progress = 50;
            }

            $csvContent .= implode(',', [
                    $task->id,
                    '"' . str_replace('"', '""', $task->title) . '"',
                    $startDate,
                    $endDate,
                    $progress,
                    $task->status ? '"' . str_replace('"', '""', $task->status->name) . '"' : '',
                    $task->assignee ? '"' . str_replace('"', '""', $task->assignee->name) . '"' : ''
                ]) . "\n";
        }

        $filename = $this->project->name . '_gantt_' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($csvContent) {
            echo $csvContent;
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
?>

<div class="bg-gradient-to-br from-base-100 to-base-200 min-h-screen p-6">
    <div class="max-w-7xl mx-auto mb-8">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <span class="text-xs font-mono bg-primary/10 text-primary px-2 py-1 rounded">
                        {{ $project->key }}
                    </span>
                    <h1 class="text-2xl font-bold text-primary">Gantt Chart</h1>
                </div>
                <p class="text-base-content/70">Visualize and manage task timelines</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <x-button 
                    wire:click="exportGanttToCSV" 
                    icon="fas.file-csv" 
                    class="btn-outline btn-sm hover:bg-base-200 transition-all duration-200"
                    tooltip="Export as CSV"
                >
                    Export CSV
                </x-button>
                <x-button 
                    id="export-png-btn" 
                    icon="fas.image" 
                    class="btn-primary btn-sm hover:shadow-md transition-all duration-300"
                    tooltip="Export as PNG"
                >
                    Export PNG
                </x-button>
            </div>
        </div>

        <div class="bg-base-100 rounded-xl shadow-xl border border-base-300 p-5 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Tarih Aralığı Seçimi -->
                <div>
                    <label class="block text-sm font-medium mb-2">Date Range</label>
                    <x-select 
                        wire:model.live="dateRange" 
                        wire:change="loadTasks" 
                        class="w-full focus:border-primary/50 transition-all duration-300"
                        icon="fas.calendar-range"
                        :options="[
                            ['id' => 'week', 'name' => 'Last Week'],
                            ['id' => 'month', 'name' => 'Last Month'],
                            ['id' => 'quarter', 'name' => 'Last Quarter'],
                            ['id' => 'year', 'name' => 'Last Year'],
                        ]"
                    >
                    </x-select>
                </div>

                <!-- Görünüm Modu Seçimi -->
                <div>
                    <label class="block text-sm font-medium mb-2">View Mode</label>
                    <x-select 
                        wire:model.live="viewMode" 
                        wire:change="updateViewMode($event.target.value)"
                        class="w-full focus:border-primary/50 transition-all duration-300"
                        icon="fas.eye"
                        :options="[
                            ['id' => 'Quarter Day', 'name' => 'Quarter Day'],
                            ['id' => 'Half Day', 'name' => 'Half Day'],
                            ['id' => 'Day', 'name' => 'Day'],
                            ['id' => 'Week', 'name' => 'Week'],
                            ['id' => 'Month', 'name' => 'Month'],
                        ]"
                    >
                    </x-select>
                </div>

                <!-- Özel Tarih Aralığı -->
                <div>
                    <label class="block text-sm font-medium mb-2">Custom Date Range</label>
                    <div class="flex gap-2">
                        <x-input 
                            type="date" 
                            placeholder="Start Date" 
                            wire:model="customStartDate" 
                            class="w-full focus:border-primary/50 transition-all duration-300"
                            icon="fas.calendar-day"
                        />
                        <x-input 
                            type="date" 
                            placeholder="End Date" 
                            wire:model="customEndDate" 
                            class="w-full focus:border-primary/50 transition-all duration-300"
                            icon="fas.calendar-day"
                        />
                        <x-button 
                            wire:click="setCustomDateRange" 
                            icon="fas.check" 
                            class="btn-primary hover:shadow-md transition-all duration-300"
                        >
                            Apply
                        </x-button>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex flex-wrap justify-center gap-3 border-t border-base-300 pt-4">
                <x-button 
                    id="scroll-to-today-btn" 
                    icon="fas.calendar-day" 
                    class="btn-sm btn-outline hover:bg-base-200 transition-all duration-200"
                    tooltip="Scroll to today"
                >
                    Today
                </x-button>
                <x-button 
                    id="zoom-in-btn" 
                    icon="fas.search-plus" 
                    class="btn-sm btn-outline hover:bg-base-200 transition-all duration-200"
                    tooltip="Zoom in"
                >
                </x-button>
                <x-button 
                    id="zoom-out-btn" 
                    icon="fas.search-minus" 
                    class="btn-sm btn-outline hover:bg-base-200 transition-all duration-200"
                    tooltip="Zoom out"
                >
                </x-button>
                <x-button 
                    id="fit-all-btn" 
                    icon="fas.expand" 
                    class="btn-sm btn-outline hover:bg-base-200 transition-all duration-200"
                    tooltip="Fit all tasks"
                >
                    Fit All
                </x-button>
            </div>
        </div>

        <!-- Gantt Chart Container -->
        <div class="bg-base-100 rounded-xl shadow-xl border border-base-300 overflow-hidden">
            <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center gap-3">
                <span class="p-2 rounded-full bg-primary/10 text-primary">
                    <i class="fas fa-chart-gantt"></i>
                </span>
                <div>
                    <h2 class="text-lg font-semibold">Timeline Visualization</h2>
                    <p class="text-sm text-base-content/70">Drag tasks to adjust dates and dependencies</p>
                </div>
            </div>
            <div class="p-4">
                <div id="gantt-chart" class="gantt-container rounded-lg border border-base-300" style="height: 600px; width: 100%"></div>
            </div>
        </div>
    </div>

    <!-- Task List -->
    <div class="max-w-7xl mx-auto mb-8">
        <div class="bg-base-100 rounded-xl shadow-xl border border-base-300 overflow-hidden">
            <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="p-2 rounded-full bg-primary/10 text-primary">
                        <i class="fas fa-list-check"></i>
                    </span>
                    <div>
                        <h2 class="text-lg font-semibold">Task List</h2>
                        <p class="text-sm text-base-content/70">{{ count($tasks) }} tasks in timeline</p>
                    </div>
                </div>
                <div>
                    <x-input 
                        type="search" 
                        placeholder="Search tasks..." 
                        class="w-64 focus:border-primary/50 transition-all duration-300"
                        icon="fas.search"
                    />
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-base-200/50 border-b border-base-300">
                            <th class="px-4 py-3 text-left font-medium text-sm">ID</th>
                            <th class="px-4 py-3 text-left font-medium text-sm">Title</th>
                            <th class="px-4 py-3 text-left font-medium text-sm">Status</th>
                            <th class="px-4 py-3 text-left font-medium text-sm">Assignee</th>
                            <th class="px-4 py-3 text-left font-medium text-sm">Start Date</th>
                            <th class="px-4 py-3 text-left font-medium text-sm">Due Date</th>
                            <th class="px-4 py-3 text-left font-medium text-sm">Progress</th>
                            <th class="px-4 py-3 text-left font-medium text-sm">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tasks as $task)
                            <tr class="border-b border-base-300 hover:bg-base-200/30 transition-colors duration-200 {{ $selectedTaskId == $task->id ? 'bg-primary/5 border-primary/20' : '' }}">
                                <td class="px-4 py-3">
                                    <span class="text-xs font-mono bg-primary/10 text-primary px-1.5 py-0.5 rounded">
                                        {{ $project->key }}-{{ $task->task_id ?? $task->id }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center">
                                        <span class="font-medium">{{ $task->title }}</span>
                                        @if($task->tags->count() > 0)
                                            <div class="ml-2 flex gap-1">
                                                @foreach($task->tags as $tag)
                                                    <span class="inline-block w-2 h-2 rounded-full" 
                                                          style="background-color: {{ $tag->color }}" 
                                                          title="{{ $tag->name }}"></span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="badge" style="background-color: {{ $task->status->color ?? '#64748b' }}; color: white;">
                                        {{ $task->status->name }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    @if($task->assignee)
                                        <div class="flex items-center gap-2">
                                            <div class="bg-primary/10 text-primary rounded-full w-6 h-6 flex items-center justify-center">
                                                @if($task->assignee->avatar_url)
                                                    <img src="{{ $task->assignee->avatar_url }}" alt="{{ $task->assignee->name }}" class="rounded-full">
                                                @else
                                                    <span class="text-xs font-medium">{{ substr($task->assignee->name, 0, 1) }}</span>
                                                @endif
                                            </div>
                                            <span class="text-sm">{{ $task->assignee->name }}</span>
                                        </div>
                                    @else
                                        <span class="text-sm text-base-content/50 flex items-center gap-1">
                                            <i class="fas fa-user-slash text-xs"></i>
                                            <span>Unassigned</span>
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if($task->started_at)
                                        <span class="flex items-center gap-1">
                                            <i class="fas fa-play text-success text-xs"></i>
                                            {{ $task->started_at->format('Y-m-d') }}
                                        </span>
                                    @else
                                        <span class="text-base-content/50">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if($task->due_date)
                                        <span class="flex items-center gap-1 {{ $task->due_date < now() ? 'text-error' : '' }}">
                                            <i class="fas fa-calendar-day text-xs {{ $task->due_date < now() ? 'text-error' : 'text-primary' }}"></i>
                                            {{ $task->due_date->format('Y-m-d') }}
                                        </span>
                                    @else
                                        <span class="text-base-content/50">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $progress = 0;
                                        if ($task->time_estimate && $task->time_spent) {
                                            $progress = min(100, round(($task->time_spent / $task->time_estimate) * 100));
                                        } elseif ($task->completed_at) {
                                            $progress = 100;
                                        } elseif ($task->started_at) {
                                            $progress = 50;
                                        }
                                        
                                        $progressColor = 'bg-primary';
                                        if ($progress >= 100) {
                                            $progressColor = 'bg-success';
                                        } elseif ($progress >= 70) {
                                            $progressColor = 'bg-info';
                                        } elseif ($progress >= 30) {
                                            $progressColor = 'bg-warning';
                                        } elseif ($progress > 0) {
                                            $progressColor = 'bg-error';
                                        }
                                    @endphp
                                    <div class="w-full bg-base-300 rounded-full h-1.5">
                                        <div class="{{ $progressColor }} h-1.5 rounded-full transition-all duration-500" style="width: {{ $progress }}%"></div>
                                    </div>
                                    <span class="text-xs text-base-content/70 mt-1 block">{{ $progress }}%</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex gap-1">
                                        <x-button 
                                            link="{{ route('tasks.show', ['project' => $project->id, 'task' => $task->id]) }}" 
                                            icon="fas.eye" 
                                            class="btn-ghost btn-xs hover:bg-base-200 transition-colors duration-200"
                                            tooltip="View details"
                                        />
                                        <x-button 
                                            link="{{ route('tasks.edit', ['project' => $project->id, 'task' => $task->id]) }}" 
                                            icon="fas.pen" 
                                            class="btn-ghost btn-xs hover:bg-base-200 transition-colors duration-200"
                                            tooltip="Edit task"
                                        />
                                        <x-button 
                                            wire:click="selectTask({{ $task->id }})" 
                                            icon="fas.search" 
                                            class="btn-ghost btn-xs hover:bg-primary/10 transition-colors duration-200 {{ $selectedTaskId == $task->id ? 'bg-primary/10 text-primary' : '' }}"
                                            tooltip="Focus on timeline"
                                        />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        
                        @if(count($tasks) === 0)
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-base-content/50">
                                    <div class="flex flex-col items-center justify-center">
                                        <i class="fas fa-calendar-xmark text-3xl mb-2"></i>
                                        <p>No tasks found in the selected date range</p>
                                        <p class="text-xs mt-1">Try adjusting your filters or date range</p>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/frappe-gantt/dist/frappe-gantt.css">
    <style>
        /* Gantt chart container styles */
        .gantt-container {
            position: relative;
            overflow-x: auto;
            margin-bottom: 1rem;
            border-radius: 0.5rem;
            background-color: var(--b1);
        }
        
        /* Task status colors */
        .task-todo .bar {
            fill: hsl(var(--p)) !important;
        }
        
        .task-progress .bar {
            fill: hsl(var(--wa)) !important;
        }
        
        .task-done .bar {
            fill: hsl(var(--su)) !important;
        }
        
        .task-blocked .bar {
            fill: hsl(var(--er)) !important;
        }
        
        .task-review .bar {
            fill: hsl(var(--in)) !important;
        }
        
        .task-default .bar {
            fill: hsl(var(--nc)) !important;
        }
        
        /* Task progress colors */
        .task-todo .bar-progress {
            fill: hsl(var(--p) / 0.7) !important;
        }
        
        .task-progress .bar-progress {
            fill: hsl(var(--wa) / 0.7) !important;
        }
        
        .task-done .bar-progress {
            fill: hsl(var(--su) / 0.7) !important;
        }
        
        .task-blocked .bar-progress {
            fill: hsl(var(--er) / 0.7) !important;
        }
        
        .task-review .bar-progress {
            fill: hsl(var(--in) / 0.7) !important;
        }
        
        .task-default .bar-progress {
            fill: hsl(var(--nc) / 0.7) !important;
        }
        
        /* Handle styles */
        .handle {
            fill: hsl(var(--p)) !important;
            cursor: ew-resize;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .bar-wrapper:hover .handle {
            opacity: 1;
        }
        
        /* Arrow styles */
        .arrow {
            stroke: hsl(var(--p) / 0.7) !important;
            stroke-width: 2;
        }
        
        /* Grid styles */
        .grid-line {
            stroke: hsl(var(--b3) / 0.5) !important;
        }
        
        .tick {
            stroke: hsl(var(--b3)) !important;
        }
        
        .today-highlight {
            fill: hsl(var(--p) / 0.1) !important;
        }
        
        /* Popup styles */
        .popup-wrapper {
            background: hsl(var(--b1));
            border: 1px solid hsl(var(--b3));
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            padding: 1rem;
            max-width: 300px;
            z-index: 50;
            animation: fadeIn 0.2s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Hover effects */
        .bar-wrapper {
            transition: transform 0.2s ease;
        }
        
        .bar-wrapper:hover {
            transform: translateY(-2px);
        }
        
        /* Selected task highlight */
        .bar-wrapper.selected .bar {
            stroke: hsl(var(--p)) !important;
            stroke-width: 2px;
            stroke-dasharray: none;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('livewire:initialized', () => {
            let ganttChart = null;

            function initGantt() {
                const tasks = @json($this->ganttTasks);

                if (tasks.length === 0) {
                    document.getElementById('gantt-chart').innerHTML = `
                        <div class="flex flex-col items-center justify-center py-12 text-base-content/50">
                            <i class="fas fa-calendar-xmark text-4xl mb-3"></i>
                            <h3 class="text-lg font-medium">No tasks found</h3>
                            <p class="text-sm text-center max-w-md">Try adjusting your date range or filters</p>
                        </div>
                    `;
                    return;
                }

                document.getElementById('gantt-chart').innerHTML = '';

                ganttChart = new Gantt('#gantt-chart', tasks, {
                    header_height: 60,
                    column_width: 30,
                    step: 24,
                    view_mode: @json($viewMode),
                    bar_height: 24,
                    bar_corner_radius: 4,
                    arrow_curve: 6,
                    padding: 20,
                    date_format: 'YYYY-MM-DD',
                    custom_popup_html: function (task) {
                        // Prepare tags HTML
                        let tagsHtml = '';
                        if (task.tags && task.tags.length > 0) {
                            tagsHtml = `
                                <div class="mt-3">
                                    <div class="text-xs font-medium text-base-content/70 mb-1">Tags</div>
                                    <div class="flex flex-wrap gap-1">
                                        ${task.tags.map(tag => `<span class="badge badge-sm badge-outline">${tag}</span>`).join('')}
                                    </div>
                                </div>
                            `;
                        }

                        // Prepare status HTML with color
                        let statusHtml = '';
                        if (task.status) {
                            let statusClass = '';
                            if (task.status.toLowerCase().includes('done')) statusClass = 'badge-success';
                            else if (task.status.toLowerCase().includes('progress')) statusClass = 'badge-warning';
                            else if (task.status.toLowerCase().includes('block')) statusClass = 'badge-error';
                            else if (task.status.toLowerCase().includes('review')) statusClass = 'badge-info';
                            else statusClass = 'badge-primary';
                            
                            statusHtml = `
                                <div class="mt-2">
                                    <div class="text-xs font-medium text-base-content/70 mb-1">Status</div>
                                    <span class="badge ${statusClass}">${task.status}</span>
                                </div>
                            `;
                        }

                        // Prepare assignee HTML
                        let assigneeHtml = '';
                        if (task.assignee) {
                            assigneeHtml = `
                                <div class="mt-2">
                                    <div class="text-xs font-medium text-base-content/70 mb-1">Assignee</div>
                                    <div class="flex items-center gap-2">
                                        <div class="bg-primary/10 text-primary rounded-full w-6 h-6 flex items-center justify-center">
                                            <span class="text-xs font-medium">${task.assignee.charAt(0)}</span>
                                        </div>
                                        <span>${task.assignee}</span>
                                    </div>
                                </div>
                            `;
                        }

                        // Prepare progress bar
                        const progressColor = task.progress >= 100 ? 'bg-success' : 
                                            task.progress >= 70 ? 'bg-info' : 
                                            task.progress >= 30 ? 'bg-warning' : 
                                            task.progress > 0 ? 'bg-error' : 'bg-primary';

                        return `
                            <div class="bg-base-100 rounded-lg shadow-lg border border-base-300 p-4 max-w-xs">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-mono bg-primary/10 text-primary px-1.5 py-0.5 rounded">
                                        ${@json($project->key)}-${task.id}
                                    </span>
                                    <span class="text-xs text-base-content/70">${task.start} - ${task.end}</span>
                                </div>
                                
                                <h4 class="font-bold text-lg mt-2 text-primary/90">${task.name}</h4>
                                
                                <div class="mt-3">
                                    <div class="text-xs font-medium text-base-content/70 mb-1">Progress</div>
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 bg-base-300 rounded-full h-1.5">
                                            <div class="${progressColor} h-1.5 rounded-full" style="width: ${task.progress}%"></div>
                                        </div>
                                        <span class="text-xs font-medium">${task.progress}%</span>
                                    </div>
                                </div>
                                
                                ${statusHtml}
                                ${assigneeHtml}
                                ${tagsHtml}
                                
                                <div class="mt-4 pt-3 border-t border-base-300 flex justify-between items-center">
                                    <a href="/projects/${@json($project->id)}/tasks/${task.id}" 
                                       class="text-primary hover:text-primary/80 text-sm flex items-center gap-1 transition-colors duration-200">
                                        <i class="fas fa-eye text-xs"></i>
                                        <span>View Details</span>
                                    </a>
                                    <a href="/projects/${@json($project->id)}/tasks/${task.id}/edit" 
                                       class="text-primary hover:text-primary/80 text-sm flex items-center gap-1 transition-colors duration-200">
                                        <i class="fas fa-pen text-xs"></i>
                                        <span>Edit</span>
                                    </a>
                                </div>
                            </div>
                        `;
                    },
                    on_click: function (task) {
                        // Highlight selected task
                        document.querySelectorAll('.bar-wrapper').forEach(el => {
                            el.classList.remove('selected');
                        });
                        
                        const taskElement = document.querySelector(`.bar-wrapper[data-id="${task.id}"]`);
                        if (taskElement) {
                            taskElement.classList.add('selected');
                        }
                        
                        @this.selectTask(task.id);
                    },
                    on_date_change: function (task, start, end) {
                        // Show visual feedback
                        const taskElement = document.querySelector(`.bar-wrapper[data-id="${task.id}"]`);
                        if (taskElement) {
                            taskElement.classList.add('animate-pulse');
                            setTimeout(() => {
                                taskElement.classList.remove('animate-pulse');
                            }, 1000);
                        }
                        
                        @this.updateTaskDates(task.id, start, end);
                    },
                    on_progress_change: function (task, progress) {
                        // Show visual feedback
                        const taskElement = document.querySelector(`.bar-wrapper[data-id="${task.id}"]`);
                        if (taskElement) {
                            taskElement.classList.add('animate-pulse');
                            setTimeout(() => {
                                taskElement.classList.remove('animate-pulse');
                            }, 1000);
                        }
                        
                        @this.updateTaskProgress(task.id, progress);
                    }
                });

                // Focus on selected task if any
                if (@this.selectedTaskId) {
                    const selectedTask = tasks.find(t => t.id === String(@this.selectedTaskId));
                    if (selectedTask) {
                        ganttChart.scroll_to(selectedTask.start);
                        
                        // Highlight selected task
                        setTimeout(() => {
                            const taskElement = document.querySelector(`.bar-wrapper[data-id="${selectedTask.id}"]`);
                            if (taskElement) {
                                taskElement.classList.add('selected');
                            }
                        }, 100);
                    }
                }
            }

            // Initial load
            initGantt();

            // Reload when tasks are updated
            Livewire.on('tasksUpdated', () => {
                initGantt();
            });

            // Update view mode
            Livewire.on('viewModeChanged', (mode) => {
                if (ganttChart) {
                    ganttChart.change_view_mode(mode);
                }
            });

            // Scroll to today button
            document.getElementById('scroll-to-today-btn').addEventListener('click', function () {
                if (ganttChart) {
                    const today = new Date();
                    const formattedDate = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
                    ganttChart.scroll_to(formattedDate);
                    
                    // Add visual feedback
                    this.classList.add('btn-primary');
                    this.classList.remove('btn-outline');
                    setTimeout(() => {
                        this.classList.remove('btn-primary');
                        this.classList.add('btn-outline');
                    }, 500);
                }
            });

            // Zoom in button
            document.getElementById('zoom-in-btn').addEventListener('click', function () {
                if (ganttChart) {
                    const currentWidth = ganttChart.options.column_width;
                    ganttChart.change_column_width(currentWidth + 10);
                    
                    // Add visual feedback
                    this.classList.add('btn-primary');
                    this.classList.remove('btn-outline');
                    setTimeout(() => {
                        this.classList.remove('btn-primary');
                        this.classList.add('btn-outline');
                    }, 500);
                }
            });

            // Zoom out button
            document.getElementById('zoom-out-btn').addEventListener('click', function () {
                if (ganttChart) {
                    const currentWidth = ganttChart.options.column_width;
                    if (currentWidth > 20) {
                        ganttChart.change_column_width(currentWidth - 10);
                        
                        // Add visual feedback
                        this.classList.add('btn-primary');
                        this.classList.remove('btn-outline');
                        setTimeout(() => {
                            this.classList.remove('btn-primary');
                            this.classList.add('btn-outline');
                        }, 500);
                    }
                }
            });

            // Fit all button
            document.getElementById('fit-all-btn').addEventListener('click', function () {
                if (ganttChart) {
                    ganttChart.change_column_width(30);
                    
                    // Add visual feedback
                    this.classList.add('btn-primary');
                    this.classList.remove('btn-outline');
                    setTimeout(() => {
                        this.classList.remove('btn-primary');
                        this.classList.add('btn-outline');
                    }, 500);
                }
            });

            // Export PNG button
            document.getElementById('export-png-btn').addEventListener('click', function () {
                // Show loading state
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exporting...';
                this.disabled = true;
                
                if (typeof html2canvas === 'undefined') {
                    // Load html2canvas library if not already loaded
                    const script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js';
                    script.onload = function () {
                        exportGanttAsPNG();
                    };
                    document.head.appendChild(script);
                } else {
                    exportGanttAsPNG();
                }
                
                // Export function
                function exportGanttAsPNG() {
                    const element = document.getElementById('gantt-chart');
                    
                    html2canvas(element, {
                        scale: 2, // Higher quality
                        backgroundColor: getComputedStyle(document.documentElement).getPropertyValue('--b1') || '#ffffff',
                        logging: false,
                        allowTaint: true,
                        useCORS: true
                    }).then(canvas => {
                        const link = document.createElement('a');
                        link.download = `${@json($project->name)}_gantt_${new Date().toISOString().split('T')[0]}.png`;
                        link.href = canvas.toDataURL('image/png');
                        link.click();
                        
                        // Reset button state
                        const exportBtn = document.getElementById('export-png-btn');
                        exportBtn.innerHTML = originalText;
                        exportBtn.disabled = false;
                    }).catch(err => {
                        console.error('Export failed:', err);
                        // Reset button state on error
                        const exportBtn = document.getElementById('export-png-btn');
                        exportBtn.innerHTML = originalText;
                        exportBtn.disabled = false;
                    });
                }
            });
        });
    </script>
@endpush
