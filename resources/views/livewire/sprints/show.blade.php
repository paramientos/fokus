<?php

new class extends Livewire\Volt\Component {
    public \App\Models\Project $project;
    public \App\Models\Sprint $sprint;

    public $availableTasks = [];
    /** @var \App\Models\Task[] $selectedTasks */
    public $selectedTasks = [];
    public $tasks = [];

    public array $tasksByStatus = [];
    public $showCloneModal = false;
    public $cloneOptions = [
        'include_tasks' => true,
        'adjust_dates' => true,
    ];

    public function mount()
    {
        $this->sprint = $this->sprint->with(['tasks.status', 'tasks.user'])->firstOrFail();
        $this->loadAvailableTasks();

        // Görevleri durumlara göre grupla
        $this->tasks = $this->sprint->tasks;

        $this->tasksByStatus = $this->tasks->groupBy(function ($task) {
            return $task->status ? $task->status->name : 'No Status';
        })
            ->toArray();
    }

    public function loadAvailableTasks()
    {
        $this->availableTasks = \App\Models\Task::where('project_id', $this->project->id)
            ->whereNull('sprint_id')
            ->get()
            ->toArray();
    }

    public function addTasksToSprint()
    {
        if (empty($this->selectedTasks)) {
            return;
        }

        \App\Models\Task::whereIn('id', $this->selectedTasks)
            ->update(['sprint_id' => $this->sprint->id]);

        $this->selectedTasks = [];
        $this->sprint = \App\Models\Sprint::with(['tasks.status', 'tasks.user'])->findOrFail($this->sprint->id);
        $this->loadAvailableTasks();

        // Görevleri durumlara göre grupla
        $this->tasks = $this->sprint->tasks;

        $this->tasksByStatus = $this->tasks->groupBy(function ($task) {
            return $task->status ? $task->status->name : 'No Status';
        });
    }

    public function removeFromSprint($taskId)
    {
        \App\Models\Task::where('id', $taskId)
            ->update(['sprint_id' => null]);

        $this->sprint = \App\Models\Sprint::with(['tasks.status', 'tasks.user'])->findOrFail($this->sprint->id);
        $this->loadAvailableTasks();

        // Görevleri durumlara göre grupla
        $this->tasks = $this->sprint->tasks;

        $this->tasksByStatus = $this->tasks->groupBy(function ($task) {
            return $task->status ? $task->status->name : 'No Status';
        });
    }

    public function completeSprint()
    {
        $this->sprint->update([
            'is_active' => false,
            'is_completed' => true,
            'end_date' => $this->sprint->end_date ?? now(),
        ]);

        $this->sprint = \App\Models\Sprint::with(['tasks.status', 'tasks.user'])->findOrFail($this->sprint->id);

        session()->flash('message', 'Sprint completed successfully!');
    }

    public function startSprint()
    {
        // First, deactivate any active sprints
        \App\Models\Sprint::where('project_id', $this->project->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        // Then activate this sprint
        $this->sprint->update([
            'is_active' => true,
            'is_completed' => false,
            'start_date' => $this->sprint->start_date ?? now(),
        ]);

        $this->sprint = \App\Models\Sprint::with(['tasks.status', 'tasks.user'])->findOrFail($this->sprint->id);

        session()->flash('message', 'Sprint started successfully!');
    }

    public function cancelSprint()
    {
        $this->sprint->update([
            'is_active' => false,
            'is_completed' => false,
        ]);

        $this->sprint = \App\Models\Sprint::with(['tasks.status', 'tasks.user'])->findOrFail($this->sprint->id);

        session()->flash('message', 'Sprint cancelled successfully!');
    }

    public function toggleCloneModal()
    {
        $this->showCloneModal = !$this->showCloneModal;
    }

    public function with(): array
    {
        $totalTasks = $this->sprint->tasks->count();

        $completedTasks = $this->sprint->tasks->filter(function ($task) {
            return $task->status && $task->status->slug === 'done';
        })->count();

        $progressPercentage = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

        return [
            'totalTasks' => $totalTasks,
            'completedTasks' => $completedTasks,
            'progressPercentage' => $progressPercentage,
            'tasksByStatus' => $this->tasksByStatus,
        ];
    }
}

?>

<div class="bg-gradient-to-br from-base-100 to-base-200 min-h-screen">
    <x-slot:title>{{ $sprint->name }} - {{ $project->name }}</x-slot:title>

    <div class="max-w-7xl mx-auto p-6">
        <!-- Bildirim Mesajı -->
        @if (session()->has('message'))
            <div class="alert alert-success mb-6 shadow-md border border-success/20">
                <i class="fas fa-check-circle text-lg"></i>
                <span>{{ session('message') }}</span>
            </div>
        @endif

        <!-- Sprint Header -->
        <div class="bg-base-100 rounded-xl shadow-xl border border-base-300 overflow-hidden mb-6">
            <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <x-button 
                        link="/projects/{{ $project->id }}/sprints" 
                        icon="fas.arrow-left" 
                        class="btn-ghost btn-sm hover:bg-base-200 transition-all duration-200"
                        tooltip="Back to Sprints"
                    />
                    <h1 class="text-2xl font-bold">{{ $sprint->name }}</h1>
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
            
            <div class="p-5">
                <div class="flex flex-col md:flex-row justify-between gap-4">
                    <div class="flex flex-col md:flex-row gap-6">
                        <div class="flex items-center gap-3">
                            <div class="p-3 rounded-full bg-primary/10 text-primary">
                                <i class="fas fa-calendar-alt text-lg"></i>
                            </div>
                            <div>
                                <div class="text-sm text-base-content/70">Sprint Period</div>
                                <div class="font-medium">
                                    {{ $sprint->start_date ? $sprint->start_date->format('M d, Y') : 'No start date' }} -
                                    {{ $sprint->end_date ? $sprint->end_date->format('M d, Y') : 'No end date' }}
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-3">
                            <div class="p-3 rounded-full bg-primary/10 text-primary">
                                <i class="fas fa-tasks text-lg"></i>
                            </div>
                            <div>
                                <div class="text-sm text-base-content/70">Tasks</div>
                                <div class="font-medium">{{ $tasks->count() }} tasks ({{ $completedTasks }} completed)</div>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-3">
                            <div class="p-3 rounded-full bg-primary/10 text-primary">
                                <i class="fas fa-chart-line text-lg"></i>
                            </div>
                            <div>
                                <div class="text-sm text-base-content/70">Progress</div>
                                <div class="font-medium">{{ $progressPercentage }}% complete</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex flex-wrap gap-2 mt-4 md:mt-0">

                @if(!$sprint->is_active && !$sprint->is_completed)
                    <x-button 
                        wire:click="startSprint" 
                        label="Start Sprint" 
                        icon="fas.play" 
                        class="btn-success hover:shadow-md transition-all duration-300"
                    />
                @endif

                @if($sprint->is_active)
                    <x-button 
                        wire:click="completeSprint" 
                        label="Complete Sprint" 
                        icon="fas.check" 
                        class="btn-info hover:shadow-md transition-all duration-300"
                    />
                    <x-button 
                        wire:click="cancelSprint" 
                        label="Cancel Sprint" 
                        icon="fas.xmark" 
                        class="btn-error hover:shadow-md transition-all duration-300"
                    />
                @endif

                <div class="dropdown dropdown-end">
                    <x-button 
                        label="Actions" 
                        icon="fas.ellipsis-v" 
                        class="btn-outline hover:bg-base-200 transition-all duration-200"
                    />
                    <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow-lg bg-base-100 rounded-box w-52 border border-base-300">
                        <li>
                            <a href="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}/board">
                                <i class="fas fa-columns"></i> Task Board
                            </a>
                        </li>
                        <li>
                            <a href="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}/burndown" no-wire-navigate>
                                <i class="fas fa-chart-line"></i> Burndown Chart
                            </a>
                        </li>
                        <li>
                            <a href="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}/report" no-wire-navigate>
                                <i class="fas fa-chart-bar"></i> View Report
                            </a>
                        </li>
                        <li>
                            <a href="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}/retrospective">
                                <i class="fas fa-users"></i> Retrospective
                            </a>
                        </li>
                        <li>
                            <a href="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}/edit">
                                <i class="fas fa-pencil"></i> Edit Sprint
                            </a>
                        </li>
                        <li>
                            <a href="#" wire:click.prevent="toggleCloneModal">
                                <i class="fas fa-copy"></i> Clone Sprint
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Sprint Hedefi -->
            @if($sprint->goal)
                <div class="card bg-base-100 shadow-xl border border-base-300 overflow-hidden">
                    <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center gap-3">
                        <span class="p-2 rounded-full bg-primary/10 text-primary">
                            <i class="fas fa-flag text-lg"></i>
                        </span>
                        <h2 class="text-xl font-semibold">Sprint Goal</h2>
                    </div>
                    <div class="card-body p-5">
                        <div class="bg-base-200/30 p-4 rounded-lg border border-base-300">
                            <p class="italic text-base-content/90">{{ $sprint->goal }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Sprint İlerleme Durumu -->
            <div class="card bg-base-100 shadow-xl border border-base-300 overflow-hidden">
                <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center gap-3">
                    <span class="p-2 rounded-full bg-primary/10 text-primary">
                        <i class="fas fa-chart-pie text-lg"></i>
                    </span>
                    <h2 class="text-xl font-semibold">Sprint Progress</h2>
                </div>
                <div class="card-body p-5">
                    <div class="flex items-center justify-center mb-4">
                        <div class="radial-progress text-primary" style="--value:{{ $progressPercentage }}; --size:8rem; --thickness: 1rem;">
                            {{ $progressPercentage }}%
                        </div>
                    </div>
                    
                    <div class="flex justify-between items-center mb-2">
                        <span class="font-medium">Task Completion</span>
                        <span class="text-sm font-medium">{{ $completedTasks }} of {{ $totalTasks }} tasks</span>
                    </div>
                    <div class="w-full bg-base-200 rounded-full h-3">
                        <div class="bg-primary h-3 rounded-full transition-all duration-500" style="width: {{ $progressPercentage }}%"></div>
                    </div>
                    
                    <div class="mt-4 text-center text-sm text-base-content/70">
                        @if($progressPercentage < 30)
                            <p>Sprint is in early stages</p>
                        @elseif($progressPercentage < 70)
                            <p>Sprint is progressing well</p>
                        @elseif($progressPercentage < 100)
                            <p>Sprint is nearing completion</p>
                        @else
                            <p>All tasks completed!</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>


        <!-- Tasks By Status -->
        <div class="mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold flex items-center gap-2">
                    <i class="fas fa-tasks text-primary"></i>
                    Sprint Tasks by Status
                </h2>
                <x-button
                    x-data=""
                    x-on:click="$dispatch('open-modal', 'add-tasks-modal')"
                    label="Add Tasks"
                    icon="fas.plus"
                    class="btn-primary btn-sm hover:shadow-md transition-all duration-300"
                />
            </div>
            
            @if(empty($tasksByStatus))
                <div class="card bg-base-100 shadow-xl border border-base-300 overflow-hidden">
                    <div class="card-body p-10 text-center">
                        <div class="p-6 rounded-full bg-base-200 mx-auto mb-4">
                            <i class="fas fa-clipboard-list text-3xl text-base-content/50"></i>
                        </div>
                        <h3 class="text-xl font-bold mb-2">No tasks in this sprint</h3>
                        <p class="text-base-content/70 max-w-md mx-auto mb-6">Get started by adding tasks to this sprint.</p>
                        <div class="flex justify-center gap-3">
                            <x-button  
                                no-wire-navigate 
                                link="/projects/{{ $project->id }}/tasks/create" 
                                label="Create Task" 
                                icon="fas.plus" 
                                class="btn-primary hover:shadow-lg transition-all duration-300"
                            />
                            <x-button
                                x-data=""
                                x-on:click="$dispatch('open-modal', 'add-tasks-modal')"
                                label="Add Existing Tasks"
                                icon="fas.tasks"
                                class="btn-outline hover:shadow-md transition-all duration-300"
                            />
                        </div>
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    @foreach($tasksByStatus as $status => $statusTasks)
                        <div class="card bg-base-100 shadow-xl border border-base-300 overflow-hidden">
                            <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="badge {{ $status === 'Done' ? 'badge-success' : ($status === 'In Progress' ? 'badge-warning' : 'badge-info') }} badge-lg">
                                        {{ count($statusTasks) }}
                                    </span>
                                    <h3 class="font-semibold">{{ $status }}</h3>
                                </div>
                                @if($status === 'Done')
                                    <span class="text-xs text-success flex items-center gap-1">
                                        <i class="fas fa-check-circle"></i> Completed
                                    </span>
                                @endif
                            </div>
                            
                            <div class="card-body p-0">
                                <div class="overflow-y-auto max-h-[400px]">
                                    @if(count($statusTasks) > 0)
                                        <ul class="divide-y divide-base-300">
                                            @foreach($statusTasks as $task)
                                                <li class="p-4 hover:bg-base-200/50 transition-all duration-200">
                                                    <div class="flex items-center justify-between">
                                                        <div class="flex-1 min-w-0">
                                                            <div class="flex items-center gap-2">
                                                                <span class="text-xs font-mono bg-primary/10 text-primary px-2 py-0.5 rounded">{{ $project->key }}-{{ $task['id'] }}</span>
                                                                <a href="/projects/{{ $project->id }}/tasks/{{ $task['id'] }}" class="font-medium text-primary hover:underline transition-colors duration-200 truncate">
                                                                    {{ $task['title'] }}
                                                                </a>
                                                            </div>
                                                            
                                                            <div class="flex items-center gap-3 mt-2">
                                                                @if($task['user'])
                                                                    <div class="flex items-center gap-1.5">
                                                                        <div class="bg-primary/10 text-primary rounded-lg w-6 h-6 flex items-center justify-center">
                                                                            <span class="text-xs font-medium">{{ substr($task['user']['name'], 0, 1) }}</span>
                                                                        </div>
                                                                        <span class="text-xs">{{ $task['user']['name'] }}</span>
                                                                    </div>
                                                                @else
                                                                    <span class="text-xs text-base-content/50 flex items-center gap-1">
                                                                        <i class="fas fa-user-slash"></i> Unassigned
                                                                    </span>
                                                                @endif
                                                                
                                                                @if(!empty($task['priority']))
                                                                    <span class="badge badge-sm {{
                                                                        $task['priority'] === \App\Enums\Priority::HIGH->value ? 'badge-error' :
                                                                        ($task['priority'] === \App\Enums\Priority::MEDIUM->value ? 'badge-warning' : 'badge-info')
                                                                    }}">
                                                                        {{ ucfirst(\App\Enums\Priority::from($task['priority'])->label()) }}
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="ml-4 flex-shrink-0">
                                                            <x-button 
                                                                wire:click="removeFromSprint({{ $task['id'] }})" 
                                                                icon="fas.times" 
                                                                class="btn-xs btn-ghost hover:bg-error/10 hover:text-error transition-all duration-200" 
                                                                tooltip="Remove from Sprint"
                                                                wire:confirm="Are you sure you want to remove this task from the sprint?"
                                                            />
                                                        </div>
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <div class="p-4 text-center text-base-content/50 italic">
                                            No tasks with this status
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <x-modal wire:model="showAddTasksModal" name="add-tasks-modal">
            <x-card title="Add Tasks to Sprint">
                <div class="mb-2">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="p-2 rounded-full bg-primary/10 text-primary">
                            <i class="fas fa-tasks"></i>
                        </span>
                        <h3 class="text-lg font-semibold">Available Tasks</h3>
                    </div>
                    
                    @if(empty($availableTasks))
                        <div class="py-8 text-center">
                            <div class="p-4 rounded-full bg-base-200 mx-auto mb-3">
                                <i class="fas fa-clipboard-list text-2xl text-base-content/50"></i>
                            </div>
                            <p class="text-base-content/70 italic">No available tasks to add to this sprint</p>
                            <div class="mt-4">
                                <x-button 
                                    no-wire-navigate 
                                    link="/projects/{{ $project->id }}/tasks/create" 
                                    label="Create New Task" 
                                    icon="fas.plus" 
                                    class="btn-primary hover:shadow-md transition-all duration-300"
                                />
                            </div>
                        </div>
                    @else
                        <div class="mb-4">
                            <div class="border border-base-300 rounded-lg overflow-hidden">
                                <div class="bg-base-200/50 p-2 border-b border-base-300 flex justify-between items-center">
                                    <span class="font-medium text-sm">Select tasks to add to this sprint</span>
                                    <span class="text-xs text-base-content/70">{{ count($availableTasks) }} tasks available</span>
                                </div>
                                <div class="overflow-y-auto max-h-96 p-2">
                                    @foreach($availableTasks as $task)
                                        <div class="form-control border-b border-base-200 last:border-0 py-2">
                                            <label class="label cursor-pointer justify-start gap-3 hover:bg-base-200/30 p-2 rounded-lg transition-colors duration-200">
                                                <x-checkbox wire:model="selectedTasks" value="{{ $task['id'] }}"/>
                                                <div>
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-xs font-mono bg-primary/10 text-primary px-1.5 py-0.5 rounded">{{ $project->key }}-{{ $task['id'] }}</span>
                                                        <span class="font-medium">{{ $task['title'] }}</span>
                                                    </div>
                                                    @if(!empty($task['priority']))
                                                        <div class="mt-1">
                                                            <span class="badge badge-sm {{
                                                                $task['priority'] === \App\Enums\Priority::HIGH->value ? 'badge-error' :
                                                                ($task['priority'] === \App\Enums\Priority::MEDIUM->value ? 'badge-warning' : 'badge-info')
                                                            }}">
                                                                {{ ucfirst(\App\Enums\Priority::from($task['priority'])->label()) }}
                                                            </span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                
                <x-slot:footer>
                    <div class="flex justify-end gap-2">
                        <x-button
                            x-on:click="$dispatch('close-modal', 'add-tasks-modal')"
                            label="Cancel"
                            class="btn-ghost hover:bg-base-200 transition-all duration-200"
                        />
                        <x-button
                            wire:click="addTasksToSprint"
                            x-on:click="$dispatch('close-modal', 'add-tasks-modal')"
                            label="Add Selected Tasks"
                            icon="fas.plus"
                            class="btn-primary hover:shadow-md transition-all duration-300"
                            :disabled="empty($selectedTasks)"
                        />
                    </div>
                </x-slot:footer>
            </x-card>
        </x-modal>

        <!-- Sprint Kopyalama Modal -->
        <x-modal wire:model="showCloneModal">
            <x-card title="Clone Sprint">
                <div class="mb-4">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="p-2 rounded-full bg-primary/10 text-primary">
                            <i class="fas fa-copy"></i>
                        </span>
                        <h3 class="text-lg font-semibold">Clone Options</h3>
                    </div>
                    
                    <p class="text-base-content/70 mb-6">Create a copy of this sprint with the following options:</p>

                    <div class="space-y-4">
                        <div class="flex items-center p-3 bg-base-200/50 rounded-lg border border-base-300 hover:bg-base-200 transition-all duration-200">
                            <input
                                type="checkbox"
                                id="include_tasks"
                                wire:model="cloneOptions.include_tasks"
                                class="checkbox checkbox-primary"
                            />
                            <label for="include_tasks" class="ml-3 flex-1 cursor-pointer">
                                <div class="font-medium">Include tasks</div>
                                <div class="text-xs text-base-content/70">Copy all tasks from the current sprint to the new sprint</div>
                            </label>
                        </div>

                        <div class="flex items-center p-3 bg-base-200/50 rounded-lg border border-base-300 hover:bg-base-200 transition-all duration-200">
                            <input
                                type="checkbox"
                                id="adjust_dates"
                                wire:model="cloneOptions.adjust_dates"
                                class="checkbox checkbox-primary"
                            />
                            <label for="adjust_dates" class="ml-3 flex-1 cursor-pointer">
                                <div class="font-medium">Adjust dates</div>
                                <div class="text-xs text-base-content/70">Set the start date to tomorrow and adjust the end date accordingly</div>
                            </label>
                        </div>
                    </div>
                </div>

                <x-slot:footer>
                    <div class="flex justify-end gap-2">
                        <x-button 
                            label="Cancel" 
                            wire:click="toggleCloneModal" 
                            class="btn-ghost hover:bg-base-200 transition-all duration-200"
                        />

                        <form
                            action="{{ route('sprints.clone', ['project' => $project->id, 'sprint' => $sprint->id]) }}"
                            method="POST">
                            @csrf
                            <input type="hidden" name="include_tasks" :value="cloneOptions.include_tasks ? 1 : 0"
                                   x-bind:value="$wire.cloneOptions.include_tasks ? 1 : 0">
                            <input type="hidden" name="adjust_dates" :value="cloneOptions.adjust_dates ? 1 : 0"
                                   x-bind:value="$wire.cloneOptions.adjust_dates ? 1 : 0">
                            <x-button 
                                type="submit" 
                                label="Clone Sprint" 
                                icon="fas.copy"
                                class="btn-primary hover:shadow-md transition-all duration-300"
                            />
                        </form>
                    </div>
                </x-slot:footer>
            </x-card>
        </x-modal>
    </div>
</div>
    </div>
</div>
