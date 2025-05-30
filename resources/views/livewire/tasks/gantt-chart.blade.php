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

<div class="p-6 bg-white rounded-lg shadow-md">
    <div class="mb-6">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-bold text-gray-800 flex items-center">
                <i class="fas fa-chart-gantt text-blue-500 mr-2"></i>
                {{ $project->name }} - Gantt Chart
            </h1>

            <div class="flex space-x-2">
                <x-button color="blue" wire:click="exportGanttToCSV">
                    <i class="fas fa-file-export mr-1"></i> Export CSV
                </x-button>
                <x-mary-button id="export-png-btn" color="indigo">
                    <i class="fas fa-image mr-1"></i> Export PNG
                </x-mary-button>
            </div>
        </div>

        <div class="gantt-controls bg-gray-50 p-4 rounded-lg mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Tarih Aralığı Seçimi -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
                    <x-select wire:model.live="dateRange" wire:change="loadTasks" class="w-full"
                              :options="[
                                  ['id' => 'week', 'name' => 'Last Week'],
                                  ['id' => 'month', 'name' => 'Last Month'],
                                  ['id' => 'quarter', 'name' => 'Last Quarter'],
                                  ['id' => 'year', 'name' => 'Last Year'],
                              ]">
                    </x-select>
                </div>

                <!-- Görünüm Modu Seçimi -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">View Mode</label>
                    <x-select wire:model.live="viewMode" wire:change="updateViewMode($event.target.value)"
                              class="w-full"
                              :options="[
                                  ['id' => 'Quarter Day', 'name' => 'Quarter Day'],
                                  ['id' => 'Half Day', 'name' => 'Half Day'],
                                  ['id' => 'Day', 'name' => 'Day'],
                                  ['id' => 'Week', 'name' => 'Week'],
                                  ['id' => 'Month', 'name' => 'Month'],
                              ]">
                    </x-select>
                </div>

                <!-- Özel Tarih Aralığı -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Custom Date Range</label>
                    <div class="flex gap-2">
                        <x-input type="date" placeholder="Start Date" wire:model="customStartDate" class="w-full"/>
                        <x-input type="date" placeholder="End Date" wire:model="customEndDate" class="w-full"/>
                        <x-button wire:click="setCustomDateRange" color="blue">Apply</x-button>
                    </div>
                </div>
            </div>

            <div class="mt-4 flex justify-center space-x-2">
                <x-button id="scroll-to-today-btn" color="gray">
                    <i class="fas fa-calendar-day mr-1"></i> Today
                </x-button>
                <x-button id="zoom-in-btn" color="gray">
                    <i class="fas fa-search-plus"></i>
                </x-button>
                <x-button id="zoom-out-btn" color="gray">
                    <i class="fas fa-search-minus"></i>
                </x-button>
                <x-button id="fit-all-btn" color="gray">
                    <i class="fas fa-expand"></i> Fit All
                </x-button>
            </div>
        </div>

        <!-- Gantt Şeması -->
        <div class="gantt-chart-container">
            <div id="gantt-chart" class="gantt-container" style="height: 600px; width: 100%"></div>
        </div>
    </div>

    <!-- Görev Listesi -->
    <div class="mt-8">
        <h2 class="text-xl font-semibold mb-4 flex items-center">
            <i class="fas fa-tasks text-blue-500 mr-2"></i> Tasks
        </h2>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                <tr class="bg-gray-100">
                    <th class="px-4 py-2 text-left">ID</th>
                    <th class="px-4 py-2 text-left">Title</th>
                    <th class="px-4 py-2 text-left">Status</th>
                    <th class="px-4 py-2 text-left">Assignee</th>
                    <th class="px-4 py-2 text-left">Start Date</th>
                    <th class="px-4 py-2 text-left">Due Date</th>
                    <th class="px-4 py-2 text-left">Progress</th>
                    <th class="px-4 py-2 text-left">Actions</th>
                </tr>
                </thead>
                <tbody>
                @foreach($tasks as $task)
                    <tr class="border-b hover:bg-gray-50 {{ $selectedTaskId == $task->id ? 'bg-blue-50' : '' }}">
                        <td class="px-4 py-2">{{ $task->id }}</td>
                        <td class="px-4 py-2">
                            <div class="flex items-center">
                                <span>{{ $task->title }}</span>
                                @if($task->tags->count() > 0)
                                    <div class="ml-2 flex space-x-1">
                                        @foreach($task->tags as $tag)
                                            <span class="inline-block w-2 h-2 rounded-full"
                                                  style="background-color: {{ $tag->color }}"></span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-2">
                            <x-badge color="{{ $task->status->color ?? 'gray' }}">
                                {{ $task->status->name }}
                            </x-badge>
                        </td>
                        <td class="px-4 py-2">
                            @if($task->assignee)
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center mr-2">
                                        <i class="fas fa-user text-gray-500"></i>
                                    </div>
                                    <span>{{ $task->assignee->name }}</span>
                                </div>
                            @else
                                <span class="text-gray-400">Unassigned</span>
                            @endif
                        </td>
                        <td class="px-4 py-2">{{ $task->started_at ? $task->started_at->format('Y-m-d') : '-' }}</td>
                        <td class="px-4 py-2">{{ $task->due_date ? $task->due_date->format('Y-m-d') : '-' }}</td>
                        <td class="px-4 py-2">
                            @php
                                $progress = 0;
                                if ($task->time_estimate && $task->time_spent) {
                                    $progress = min(100, round(($task->time_spent / $task->time_estimate) * 100));
                                } elseif ($task->completed_at) {
                                    $progress = 100;
                                } elseif ($task->started_at) {
                                    $progress = 50;
                                }
                            @endphp
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ $progress }}%"></div>
                            </div>
                            <span class="text-xs text-gray-500">{{ $progress }}%</span>
                        </td>
                        <td class="px-4 py-2">
                            <div class="flex space-x-2">
                                <a href="{{ route('tasks.show', ['project' => $project->id, 'task' => $task->id]) }}"
                                   class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('tasks.edit', ['project' => $project->id, 'task' => $task->id]) }}"
                                   class="text-yellow-600 hover:text-yellow-800">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button wire:click="selectTask({{ $task->id }})"
                                        class="text-indigo-600 hover:text-indigo-800">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/frappe-gantt/dist/frappe-gantt.css">
    <style>
        /* Gantt şeması stilleri burada tanımlanmıştır */
        .gantt-container {
            position: relative;
            overflow-x: auto;
            margin-bottom: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        /* Görev durumlarına göre renkler */
        .task-todo .bar {
            fill: #3498db !important;
        }

        .task-progress .bar {
            fill: #f39c12 !important;
        }

        .task-done .bar {
            fill: #2ecc71 !important;
        }

        .task-blocked .bar {
            fill: #e74c3c !important;
        }

        .task-review .bar {
            fill: #9b59b6 !important;
        }

        .task-default .bar {
            fill: #95a5a6 !important;
        }

        /* Gantt şeması için futuristik tasarım */
        .gantt-chart-container {
            background: linear-gradient(to bottom, #f8fafc, #f1f5f9);
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        /* Gantt popup stilleri */
        .gantt-popup {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            padding: 1rem;
            max-width: 300px;
            z-index: 50;
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
                    document.getElementById('gantt-chart').innerHTML = '<div class="p-4 text-center text-gray-500">No tasks found in the selected date range.</div>';
                    return;
                }

                document.getElementById('gantt-chart').innerHTML = '';

                ganttChart = new Gantt('#gantt-chart', tasks, {
                    header_height: 50,
                    column_width: 30,
                    step: 24,
                    view_mode: @json($viewMode),
                    bar_height: 20,
                    bar_corner_radius: 3,
                    arrow_curve: 5,
                    padding: 18,
                    date_format: 'YYYY-MM-DD',
                    custom_popup_html: function (task) {
                        // Etiketleri hazırla
                        let tagsHtml = '';
                        if (task.tags && task.tags.length > 0) {
                            tagsHtml = '<div class="mt-2"><span class="font-semibold">Tags:</span> ' + task.tags.join(', ') + '</div>';
                        }

                        // Görev durumunu hazırla
                        let statusHtml = '';
                        if (task.status) {
                            statusHtml = '<div><span class="font-semibold">Status:</span> ' + task.status + '</div>';
                        }

                        // Atanan kişiyi hazırla
                        let assigneeHtml = '';
                        if (task.assignee) {
                            assigneeHtml = '<div><span class="font-semibold">Assignee:</span> ' + task.assignee + '</div>';
                        }

                        return `
                        <div class="p-3 bg-white rounded shadow-lg">
                            <h4 class="font-bold text-lg">${task.name}</h4>
                            <div class="text-sm mt-2">
                                <div><span class="font-semibold">Start:</span> ${task.start}</div>
                                <div><span class="font-semibold">End:</span> ${task.end}</div>
                                <div><span class="font-semibold">Progress:</span> ${task.progress}%</div>
                                ${statusHtml}
                                ${assigneeHtml}
                                ${tagsHtml}
                            </div>
                            <div class="mt-3 pt-2 border-t border-gray-200">
                                <a href="/projects/${@json($project->id)}/tasks/${task.id}" class="text-blue-600 hover:underline text-sm">View Details</a>
                            </div>
                        </div>
                    `;
                    },
                    on_click: function (task) {
                        @this.
                        selectTask(task.id);
                    },
                    on_date_change: function (task, start, end) {
                        @this.
                        updateTaskDates(task.id, start, end);
                    },
                    on_progress_change: function (task, progress) {
                        @this.
                        updateTaskProgress(task.id, progress);
                    }
                });

                // Seçili görev varsa, ona odaklan
                if (@this.
                selectedTaskId
            )
                {
                    const selectedTask = tasks.find(t => t.id === String(@this.selectedTaskId)
                )
                    ;
                    if (selectedTask) {
                        ganttChart.scroll_to(selectedTask.start);
                    }
                }
            }

            // İlk yükleme
            initGantt();

            // Görevler güncellendiğinde Gantt şemasını yeniden oluştur
            Livewire.on('tasksUpdated', () => {
                initGantt();
            });

            // Görünüm modu değiştiğinde Gantt şemasını güncelle
            Livewire.on('viewModeChanged', (mode) => {
                if (ganttChart) {
                    ganttChart.change_view_mode(mode);
                }
            });

            // Bugüne kaydır butonu
            document.getElementById('scroll-to-today-btn').addEventListener('click', function () {
                if (ganttChart) {
                    const today = new Date();
                    const formattedDate = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
                    ganttChart.scroll_to(formattedDate);
                }
            });

            // Yakınlaştırma butonu
            document.getElementById('zoom-in-btn').addEventListener('click', function () {
                if (ganttChart) {
                    const currentWidth = ganttChart.options.column_width;
                    ganttChart.change_column_width(currentWidth + 10);
                }
            });

            // Uzaklaştırma butonu
            document.getElementById('zoom-out-btn').addEventListener('click', function () {
                if (ganttChart) {
                    const currentWidth = ganttChart.options.column_width;
                    if (currentWidth > 20) {
                        ganttChart.change_column_width(currentWidth - 10);
                    }
                }
            });

            // Tümünü sığdır butonu
            document.getElementById('fit-all-btn').addEventListener('click', function () {
                if (ganttChart) {
                    ganttChart.change_column_width(30);
                }
            });

            // PNG olarak dışa aktar butonu
            document.getElementById('export-png-btn').addEventListener('click', function () {
                if (typeof html2canvas === 'undefined') {
                    // html2canvas kütüphanesi yüklü değilse, CDN üzerinden yükle
                    const script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js';
                    script.onload = function () {
                        exportGanttAsPNG();
                    };
                    document.head.appendChild(script);
                } else {
                    exportGanttAsPNG();
                }
            });

            function exportGanttAsPNG() {
                const element = document.getElementById('gantt-chart');

                html2canvas(element).then(canvas => {
                    const link = document.createElement('a');
                    link.download = `${@json($project->name)}_gantt_${new Date().toISOString().split('T')[0]}.png`;
                link.href = canvas.toDataURL('image/png');
                link.click();
            });
        }
    });
</script>
@endpush
