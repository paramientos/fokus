<?php

new class extends Livewire\Volt\Component {
    public $project;
    public $selectedTab = 'overview';

    public function mount($project)
    {
        $this->project = \App\Models\Project::with(['tasks', 'sprints', 'statuses'])->findOrFail($project);
    }

    public function setTab($tab)
    {
        $this->selectedTab = $tab;
    }

    public function archiveProject()
    {
        $this->project->is_archived = true;
        $this->project->save();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Project archived successfully'
        ]);
    }

    public function unarchiveProject()
    {
        $this->project->is_archived = false;
        $this->project->save();
    }

    public function with(): array
    {
        $tasks = $this->project->tasks()
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $sprints = $this->project->sprints()
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $tasksByStatus = $this->project->tasks()
            ->selectRaw('status_id, count(*) as count')
            ->groupBy('status_id')
            ->get()
            ->mapWithKeys(function ($item) {
                $status = \App\Models\Status::find($item->status_id);
                return [$status->name => $item->count];
            });

        return [
            'tasks' => $tasks,
            'sprints' => $sprints,
            'tasksByStatus' => $tasksByStatus,
        ];
    }
}

?>

<div class="bg-gradient-to-br from-base-100 to-base-200 min-h-screen">
    <x-slot:title>{{ $project->name }}</x-slot:title>

    <div class="p-6 max-w-7xl mx-auto">
        <!-- Project Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
            <div class="flex items-center gap-4">
                <div class="relative">
                    @if($project->avatar)
                        <img src="{{ $project->avatar }}" alt="{{ $project->name }}" class="w-16 h-16 rounded-xl shadow-md border-2 border-primary/20">
                    @else
                        <div class="avatar placeholder">
                            <div class="bg-primary text-primary-content rounded-xl w-16 h-16 shadow-md flex items-center justify-center">
                                <span class="text-2xl font-bold">{{ substr($project->name, 0, 1) }}</span>
                            </div>
                        </div>
                    @endif
                    
                    <div class="absolute -bottom-2 -right-2 bg-{{ $project->is_active ? 'success' : 'error' }} text-white rounded-full w-6 h-6 flex items-center justify-center shadow-md border-2 border-base-100">
                        <i class="fas fa-{{ $project->is_active ? 'check' : 'times' }} text-xs"></i>
                    </div>
                </div>
                
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-3xl font-bold text-primary">{{ $project->name }}</h1>
                        @if($project->is_archived)
                            <span class="badge badge-warning">Archived</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-2 text-base-content/70">
                        <span class="badge badge-primary badge-sm">{{ $project->key }}</span>
                        <span class="text-sm">Created {{ $project->created_at->format('M d, Y') }}</span>
                        <span class="text-sm">·</span>
                        <span class="text-sm">{{ $project->tasks->count() }} tasks</span>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <x-button 
                    link="/projects/{{ $project->id }}/edit" 
                    label="Edit" 
                    icon="fas.edit" 
                    class="btn-outline btn-primary hover:shadow-md transition-all duration-300"
                />
                <x-button 
                    link="/projects/{{ $project->id }}/board" 
                    label="Board" 
                    icon="fas.columns"
                    class="btn-primary hover:shadow-lg transition-all duration-300"
                />
                @if($project->is_archived)
                    <x-button 
                        wire:click="unarchiveProject" 
                        label="Unarchive" 
                        icon="fas.box-archive" 
                        class="btn-warning hover:shadow-md transition-all duration-300"
                    />
                @else
                    <x-button 
                        wire:click="archiveProject" 
                        label="Archive" 
                        icon="fas.archive" 
                        class="btn-error hover:shadow-md transition-all duration-300"
                    />
                @endif
            </div>
        </div>

        <!-- Project Navigation -->
        <div class="tabs tabs-boxed p-1 bg-base-200/50 rounded-xl mb-6 border border-base-300 overflow-x-auto flex-nowrap">
            <a 
                wire:click="setTab('overview')" 
                class="tab gap-2 transition-all duration-200 {{ $selectedTab === 'overview' ? 'tab-active' : 'hover:bg-base-300' }}"
            >
                <i class="fas fa-home text-sm"></i>
                <span>Overview</span>
            </a>
            <a 
                wire:click="setTab('tasks')" 
                class="tab gap-2 transition-all duration-200 {{ $selectedTab === 'tasks' ? 'tab-active' : 'hover:bg-base-300' }}"
            >
                <i class="fas fa-tasks text-sm"></i>
                <span>Tasks</span>
            </a>
            <a 
                wire:click="setTab('sprints')" 
                class="tab gap-2 transition-all duration-200 {{ $selectedTab === 'sprints' ? 'tab-active' : 'hover:bg-base-300' }}"
            >
                <i class="fas fa-flag text-sm"></i>
                <span>Sprints</span>
            </a>
            <a 
                wire:click="setTab('team')" 
                class="tab gap-2 transition-all duration-200 {{ $selectedTab === 'team' ? 'tab-active' : 'hover:bg-base-300' }}"
            >
                <i class="fas fa-users text-sm"></i>
                <span>Team Members</span>
            </a>
            <a 
                wire:click="setTab('status')" 
                onclick="setTimeout(() => window.dispatchEvent(new Event('init-sortable')), 100);" 
                class="tab gap-2 transition-all duration-200 {{ $selectedTab === 'status' ? 'tab-active' : 'hover:bg-base-300' }}"
            >
                <i class="fas fa-list-ul text-sm"></i>
                <span>Status</span>
            </a>
            <a 
                wire:click="setTab('health')" 
                class="tab gap-2 transition-all duration-200 {{ $selectedTab === 'health' ? 'tab-active' : 'hover:bg-base-300' }}"
            >
                <i class="fas fa-heartbeat text-sm"></i>
                <span>Health</span>
            </a>
            <a 
                wire:click="setTab('wiki')" 
                class="tab gap-2 transition-all duration-200 {{ $selectedTab === 'wiki' ? 'tab-active' : 'hover:bg-base-300' }}"
            >
                <i class="fas fa-book text-sm"></i>
                <span>Wiki</span>
            </a>
            <a 
                wire:click="setTab('files')" 
                class="tab gap-2 transition-all duration-200 {{ $selectedTab === 'files' ? 'tab-active' : 'hover:bg-base-300' }}"
            >
                <i class="fas fa-file text-sm"></i>
                <span>Files</span>
            </a>
            <a 
                wire:click="setTab('settings')" 
                class="tab gap-2 transition-all duration-200 {{ $selectedTab === 'settings' ? 'tab-active' : 'hover:bg-base-300' }}"
            >
                <i class="fas fa-cog text-sm"></i>
                <span>Settings</span>
            </a>
        </div>

        <!-- Tab Content -->
        <div>
            <!-- Files Tab -->
            @if($selectedTab === 'files')
                <div class="mt-4">
                    <livewire:file-manager :fileable_type="'App\\Models\\Project'" :fileable_id="$project->id" />
                </div>
            @endif
            <!-- Overview Tab -->
            @if($selectedTab === 'overview')
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Project Description -->
                    <div class="card bg-base-100 shadow-xl border border-base-300 overflow-hidden lg:col-span-2">
                        <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center gap-3">
                            <span class="p-2 rounded-full bg-primary/10 text-primary">
                                <i class="fas fa-align-left text-lg"></i>
                            </span>
                            <h2 class="text-xl font-semibold">Project Description</h2>
                        </div>
                        <div class="card-body p-5">
                            <div class="prose max-w-none">
                                @if($project->description)
                                    <p class="text-base-content/90">{{ $project->description }}</p>
                                @else
                                    <div class="flex flex-col items-center justify-center py-8 text-center">
                                        <div class="p-4 rounded-full bg-base-200 mb-3">
                                            <i class="fas fa-file-alt text-2xl text-base-content/50"></i>
                                        </div>
                                        <p class="text-base-content/50 italic">No description provided</p>
                                        <x-button 
                                            link="/projects/{{ $project->id }}/edit" 
                                            label="Add Description" 
                                            icon="fas.edit" 
                                            class="btn-sm btn-outline mt-4 hover:shadow-md transition-all duration-300"
                                        />
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Project Stats -->
                    <div class="card bg-base-100 shadow-xl border border-base-300 overflow-hidden">
                        <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center gap-3">
                            <span class="p-2 rounded-full bg-primary/10 text-primary">
                                <i class="fas fa-chart-pie text-lg"></i>
                            </span>
                            <h2 class="text-xl font-semibold">Project Statistics</h2>
                        </div>
                        <div class="card-body p-5">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="p-4 bg-primary/5 rounded-lg text-center">
                                    <div class="text-3xl font-bold text-primary mb-1">{{ $project->tasks->count() }}</div>
                                    <div class="text-sm text-base-content/70">Total Tasks</div>
                                </div>
                                
                                <div class="p-4 bg-success/5 rounded-lg text-center">
                                    <div class="text-3xl font-bold text-success mb-1">
                                        {{ $project->tasks->whereNotNull('status_id')->where(function($query) {
                                            return $query->whereHas('status', function($q) {
                                                return $q->where('slug', 'done');
                                            });
                                        })->count() }}
                                    </div>
                                    <div class="text-sm text-base-content/70">Completed</div>
                                </div>
                                
                                <div class="p-4 bg-warning/5 rounded-lg text-center">
                                    <div class="text-3xl font-bold text-warning mb-1">{{ $project->sprints->where('is_active', true)->count() }}</div>
                                    <div class="text-sm text-base-content/70">Active Sprints</div>
                                </div>
                                
                                <div class="p-4 bg-info/5 rounded-lg text-center">
                                    <div class="text-3xl font-bold text-info mb-1">{{ $project->statuses->count() }}</div>
                                    <div class="text-sm text-base-content/70">Statuses</div>
                                </div>
                            </div>
                            
                            <div class="mt-4 pt-4 border-t border-base-200">
                                <h3 class="font-medium mb-2 flex items-center gap-2">
                                    <i class="fas fa-tasks text-primary"></i>
                                    <span>Tasks by Status</span>
                                </h3>
                                
                                @if($tasksByStatus->isEmpty())
                                    <p class="text-base-content/50 text-sm italic">No tasks data available</p>
                                @else
                                    <div class="space-y-2">
                                        @foreach($tasksByStatus as $status => $count)
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm">{{ $status }}</span>
                                                <span class="text-sm font-medium">{{ $count }}</span>
                                            </div>
                                            <div class="w-full bg-base-200 rounded-full h-1.5">
                                                <div class="bg-primary h-1.5 rounded-full" style="width: {{ ($count / $project->tasks->count()) * 100 }}%"></div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Recent Tasks -->
                    <div class="card bg-base-100 shadow-xl border border-base-300 overflow-hidden">
                        <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="p-2 rounded-full bg-primary/10 text-primary">
                                    <i class="fas fa-tasks text-lg"></i>
                                </span>
                                <h2 class="text-xl font-semibold">Recent Tasks</h2>
                            </div>
                            <x-button 
                                link="/projects/{{ $project->id }}/tasks" 
                                icon="fas.arrow-right" 
                                class="btn-sm btn-ghost hover:bg-base-200 transition-all duration-200"
                                tooltip="View All"
                            />
                        </div>

                        <div class="card-body p-0">
                            @if($tasks->isEmpty())
                                <div class="flex flex-col items-center justify-center py-12 text-center p-5">
                                    <div class="p-6 rounded-full bg-base-200 mb-4">
                                        <i class="fas fa-clipboard-list text-3xl text-base-content/50"></i>
                                    </div>
                                    <h3 class="text-xl font-bold mb-2">No tasks yet</h3>
                                    <p class="text-base-content/70 max-w-md mb-6">Create your first task to start tracking your project progress</p>
                                    <x-button 
                                        no-wire-navigate 
                                        link="/projects/{{ $project->id }}/tasks/create" 
                                        label="Create Task" 
                                        icon="fas.plus"
                                        class="btn-primary hover:shadow-lg transition-all duration-300"
                                    />
                                </div>
                            @else
                                <div class="overflow-x-auto">
                                    <table class="table w-full">
                                        <thead class="bg-base-200/50">
                                            <tr>
                                                <th>ID</th>
                                                <th>Title</th>
                                                <th>Status</th>
                                                <th>Created</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($tasks as $task)
                                                <tr class="hover:bg-base-200/30 transition-colors duration-150">
                                                    <td>
                                                        <span class="font-medium">{{ $project->key }}-{{ $task->id }}</span>
                                                    </td>
                                                    <td>
                                                        <a 
                                                            href="/projects/{{ $project->id }}/tasks/{{ $task->id }}" 
                                                            class="font-medium text-primary hover:underline transition-colors duration-200"
                                                        >
                                                            {{ $task->title }}
                                                        </a>
                                                    </td>
                                                    <td>
                                                        @if($task->status)
                                                            <div class="badge" style="background-color: {{ $task->status->color }}; color: white;">
                                                                {{ $task->status->name }}
                                                            </div>
                                                        @else
                                                            <span class="text-base-content/50 italic">-</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <div class="flex flex-col">
                                                            <span>{{ $task->created_at->format('M d, Y') }}</span>
                                                            <span class="text-xs text-base-content/70">{{ $task->created_at->diffForHumans() }}</span>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="p-4 border-t border-base-200 flex justify-center">
                                    <x-button 
                                        no-wire-navigate 
                                        link="/projects/{{ $project->id }}/tasks/create" 
                                        label="Create New Task" 
                                        icon="fas.plus"
                                        class="btn-sm btn-outline hover:shadow-md transition-all duration-300"
                                    />
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Recent Sprints -->
                    <div class="card bg-base-100 shadow-xl border border-base-300 overflow-hidden lg:col-span-2">
                        <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="p-2 rounded-full bg-primary/10 text-primary">
                                    <i class="fas fa-flag text-lg"></i>
                                </span>
                                <h2 class="text-xl font-semibold">Recent Sprints</h2>
                            </div>
                            <x-button 
                                link="/projects/{{ $project->id }}/sprints" 
                                icon="fas.arrow-right" 
                                class="btn-sm btn-ghost hover:bg-base-200 transition-all duration-200"
                                tooltip="View All"
                            />
                        </div>

                        <div class="card-body p-0">
                            @if($sprints->isEmpty())
                                <div class="flex flex-col items-center justify-center py-12 text-center p-5">
                                    <div class="p-6 rounded-full bg-base-200 mb-4">
                                        <i class="fas fa-flag-checkered text-3xl text-base-content/50"></i>
                                    </div>
                                    <h3 class="text-xl font-bold mb-2">No sprints yet</h3>
                                    <p class="text-base-content/70 max-w-md mb-6">Create your first sprint to organize your project timeline</p>
                                    <x-button 
                                        link="/projects/{{ $project->id }}/sprints/create" 
                                        label="Create Sprint" 
                                        icon="fas.plus"
                                        class="btn-primary hover:shadow-lg transition-all duration-300"
                                    />
                                </div>
                            @else
                                <div class="overflow-x-auto">
                                    <table class="table w-full">
                                        <thead class="bg-base-200/50">
                                            <tr>
                                                <th>Name</th>
                                                <th>Status</th>
                                                <th>Duration</th>
                                                <th>Tasks</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($sprints as $sprint)
                                                <tr class="hover:bg-base-200/30 transition-colors duration-150">
                                                    <td>
                                                        <a 
                                                            href="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}" 
                                                            class="font-medium text-primary hover:underline transition-colors duration-200"
                                                        >
                                                            {{ $sprint->name }}
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <div class="badge {{ $sprint->is_active ? 'badge-success' : ($sprint->is_completed ? 'badge-info' : 'badge-warning') }}">
                                                            {{ $sprint->is_completed ? 'Completed' : ($sprint->is_active ? 'Active' : 'Planned') }}
                                                        </div>
                                                    </td>
                                                    <td>
                                                        @if($sprint->start_date && $sprint->end_date)
                                                            <div class="flex flex-col">
                                                                <span class="font-medium">{{ $sprint->start_date->format('M d') }} - {{ $sprint->end_date->format('M d') }}</span>
                                                                <span class="text-xs text-base-content/70">{{ $sprint->start_date->diffInDays($sprint->end_date) + 1 }} days</span>
                                                            </div>
                                                        @else
                                                            <span class="text-base-content/50 italic">Not scheduled</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <div class="flex items-center gap-1">
                                                            <span class="font-medium">{{ $sprint->tasks->count() }}</span>
                                                            <span class="text-xs text-base-content/70">{{ Str::plural('task', $sprint->tasks->count()) }}</span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="flex gap-1">
                                                            <x-button 
                                                                link="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}" 
                                                                icon="fas.eye"
                                                                class="btn-sm btn-ghost hover:bg-base-200 transition-all duration-200"
                                                                tooltip="View Sprint"
                                                            />
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="p-4 border-t border-base-200 flex justify-center">
                                    <x-button 
                                        link="/projects/{{ $project->id }}/sprints/create" 
                                        label="Create New Sprint" 
                                        icon="fas.plus"
                                        class="btn-sm btn-outline hover:shadow-md transition-all duration-300"
                                    />
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- Tasks Tab -->
            @if($selectedTab === 'tasks')
                <div class="card bg-base-100 shadow-xl border border-base-300 overflow-hidden">
                    <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="p-2 rounded-full bg-primary/10 text-primary">
                                <i class="fas fa-tasks text-lg"></i>
                            </span>
                            <h2 class="text-xl font-semibold">Project Tasks</h2>
                        </div>
                        <x-button 
                            no-wire-navigate 
                            link="/projects/{{ $project->id }}/tasks/create" 
                            label="Create Task" 
                            icon="fas.plus"
                            class="btn-primary hover:shadow-lg transition-all duration-300"
                        />
                    </div>

                    <div class="card-body p-0">
                        <livewire:tasks.index :project="$project"/>
                    </div>
                </div>
            @endif

            <!-- Sprints Tab -->
            @if($selectedTab === 'sprints')
                <div class="card bg-base-100 shadow-xl border border-base-300 overflow-hidden">
                    <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="p-2 rounded-full bg-primary/10 text-primary">
                                <i class="fas fa-flag text-lg"></i>
                            </span>
                            <h2 class="text-xl font-semibold">Project Sprints</h2>
                        </div>
                        <x-button 
                            link="/projects/{{ $project->id }}/sprints/create" 
                            label="Create Sprint" 
                            icon="fas.plus"
                            class="btn-primary hover:shadow-lg transition-all duration-300"
                        />
                    </div>

                    <div class="card-body p-0">
                        <livewire:sprints.index :project="$project"/>
                    </div>
                </div>
            @endif

            <!-- Team Members Tab -->
            @if($selectedTab === 'team')
                <div class="card bg-base-100 shadow-xl border border-base-300 overflow-hidden mb-6">
                    <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="p-2 rounded-full bg-primary/10 text-primary">
                                <i class="fas fa-users text-lg"></i>
                            </span>
                            <h2 class="text-xl font-semibold">Team Members</h2>
                        </div>
                    </div>
                </div>
                <livewire:projects.team-members :project="$project" />
            @endif

            <!-- Status Tab -->
            @if($selectedTab === 'status')
                <livewire:projects.status-manager :project="$project" />
            @endif

            <!-- Wiki Tab -->
            @if($selectedTab === 'wiki')
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="card-title">Proje Wiki</h2>
                            <div class="flex gap-2">
                                <x-button
                                    link="/projects/{{ $project->id }}/wiki"
                                    label="Wiki'ye Git"
                                    icon="o-arrow-right"
                                    class="btn-primary"
                                />
                            </div>
                        </div>

                        <div class="py-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <h3 class="text-lg font-medium mb-3">Otomatik Dokümantasyon</h3>
                                    <p class="mb-4">Fokus, task açıklamaları ve yorumlarından otomatik olarak wiki sayfaları oluşturabilir. Bu özellik sayesinde:</p>

                                    <ul class="list-disc pl-5 space-y-2 mb-4">
                                        <li>Task'larınız ve yorumlarınız otomatik olarak dokümantasyona dönüşür</li>
                                        <li>Statülere ve task tiplerine göre kategorize edilmiş wiki sayfaları oluşturulur</li>
                                        <li>Teknik ve kullanıcı dokümantasyonu otomatik olarak güncellenir</li>
                                        <li>Proje ilerledikçe dokümantasyon her zaman güncel kalır</li>
                                    </ul>

                                    <x-button
                                        link="/projects/{{ $project->id }}/wiki"
                                        label="Wiki'yi Görüntüle"
                                        icon="o-document-text"
                                        class="btn-outline"
                                    />
                                </div>

                                <div>
                                    <h3 class="text-lg font-medium mb-3">Nasıl Çalışır?</h3>

                                    <div class="space-y-4">
                                        <div class="flex items-start gap-3">
                                            <div class="bg-primary text-primary-content rounded-full w-8 h-8 flex items-center justify-center flex-shrink-0">1</div>
                                            <div>
                                                <p class="font-medium">Task'ları oluşturun ve güncelleyin</p>
                                                <p class="text-sm text-gray-500">Normal iş akışınızda task'ları oluşturun, açıklamalar ekleyin ve yorumlar yapın.</p>
                                            </div>
                                        </div>

                                        <div class="flex items-start gap-3">
                                            <div class="bg-primary text-primary-content rounded-full w-8 h-8 flex items-center justify-center flex-shrink-0">2</div>
                                            <div>
                                                <p class="font-medium">Otomatik Dokümantasyon oluşturun</p>
                                                <p class="text-sm text-gray-500">Wiki sayfasındaki "Otomatik Dokümantasyon Oluştur" butonuna tıklayın.</p>
                                            </div>
                                        </div>

                                        <div class="flex items-start gap-3">
                                            <div class="bg-primary text-primary-content rounded-full w-8 h-8 flex items-center justify-center flex-shrink-0">3</div>
                                            <div>
                                                <p class="font-medium">Dokümantasyonu inceleyin ve düzenleyin</p>
                                                <p class="text-sm text-gray-500">Oluşturulan wiki sayfalarını görüntüleyin, gerekirse manuel olarak düzenleyin.</p>
                                            </div>
                                        </div>

                                        <div class="flex items-start gap-3">
                                            <div class="bg-primary text-primary-content rounded-full w-8 h-8 flex items-center justify-center flex-shrink-0">4</div>
                                            <div>
                                                <p class="font-medium">İstediğiniz zaman yeniden oluşturun</p>
                                                <p class="text-sm text-gray-500">Proje ilerledikçe dokümantasyonu güncel tutmak için yeniden oluşturabilirsiniz.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Health Tab -->
            @if($selectedTab === 'health')
                <livewire:projects.health :project="$project" />
            @endif

            <!-- Settings Tab -->
            @if($selectedTab === 'settings')
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Project Details -->
                    <div class="card bg-base-100 shadow-xl border border-base-300 overflow-hidden">
                        <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center gap-3">
                            <span class="p-2 rounded-full bg-primary/10 text-primary">
                                <i class="fas fa-info-circle text-lg"></i>
                            </span>
                            <h2 class="text-xl font-semibold">Project Details</h2>
                        </div>
                        <div class="card-body p-5">
                            <div class="space-y-4">
                                <div class="flex items-center justify-between p-3 bg-base-200/50 rounded-lg">
                                    <span class="font-medium">Name</span>
                                    <span>{{ $project->name }}</span>
                                </div>
                                
                                <div class="flex items-center justify-between p-3 bg-base-200/50 rounded-lg">
                                    <span class="font-medium">Key</span>
                                    <span class="badge badge-primary">{{ $project->key }}</span>
                                </div>
                                
                                <div class="flex items-center justify-between p-3 bg-base-200/50 rounded-lg">
                                    <span class="font-medium">Status</span>
                                    <div class="flex gap-2">
                                        <div class="badge {{ $project->is_active ? 'badge-success' : 'badge-error' }}">
                                            {{ $project->is_active ? 'Active' : 'Inactive' }}
                                        </div>
                                        @if($project->is_archived)
                                            <div class="badge badge-warning">Archived</div>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="flex items-center justify-between p-3 bg-base-200/50 rounded-lg">
                                    <span class="font-medium">Created</span>
                                    <div class="flex flex-col items-end">
                                        <span>{{ $project->created_at->format('M d, Y') }}</span>
                                        <span class="text-xs text-base-content/70">{{ $project->created_at->format('H:i') }}</span>
                                    </div>
                                </div>
                                
                                <div class="flex items-center justify-between p-3 bg-base-200/50 rounded-lg">
                                    <span class="font-medium">Last Updated</span>
                                    <div class="flex flex-col items-end">
                                        <span>{{ $project->updated_at->format('M d, Y') }}</span>
                                        <span class="text-xs text-base-content/70">{{ $project->updated_at->format('H:i') }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2 mt-6 pt-4 border-t border-base-200">
                                <x-button 
                                    link="/projects/{{ $project->id }}/edit" 
                                    label="Edit Project"
                                    icon="fas.edit" 
                                    class="btn-outline btn-primary hover:shadow-md transition-all duration-300"
                                />

                                @if($project->is_archived)
                                    <x-button 
                                        wire:click="unarchiveProject" 
                                        label="Unarchive Project"
                                        icon="fas.box-archive" 
                                        class="btn-warning hover:shadow-md transition-all duration-300"
                                    />
                                @else
                                    <x-button 
                                        wire:click="archiveProject" 
                                        label="Archive Project"
                                        icon="fas.archive" 
                                        class="btn-warning hover:shadow-md transition-all duration-300"
                                    />
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Project Statuses -->
                    <div class="card bg-base-100 shadow-xl border border-base-300 overflow-hidden lg:col-span-2">
                        <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="p-2 rounded-full bg-primary/10 text-primary">
                                    <i class="fas fa-list-ul text-lg"></i>
                                </span>
                                <h2 class="text-xl font-semibold">Project Statuses</h2>
                            </div>
                            <a 
                                wire:click="setTab('status')" 
                                onclick="setTimeout(() => window.dispatchEvent(new Event('init-sortable')), 100);" 
                                class="btn btn-sm btn-ghost hover:bg-base-200 transition-all duration-200"
                            >
                                <i class="fas fa-cog text-sm mr-1"></i>
                                <span>Manage</span>
                            </a>
                        </div>
                        <div class="card-body p-0">
                            <div class="overflow-x-auto">
                                <table class="table w-full">
                                    <thead class="bg-base-200/50">
                                        <tr>
                                            <th>Status</th>
                                            <th>Color</th>
                                            <th>Order</th>
                                            <th>Tasks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($project->statuses->sortBy('order') as $status)
                                            <tr class="hover:bg-base-200/30 transition-colors duration-150">
                                                <td class="font-medium">{{ $status->name }}</td>
                                                <td>
                                                    <div class="flex items-center gap-2">
                                                        <div class="w-6 h-6 rounded-full border border-base-300"
                                                            style="background-color: {{ $status->color }}"></div>
                                                        <span class="text-xs">{{ $status->color }}</span>
                                                    </div>
                                                </td>
                                                <td>{{ $status->order }}</td>
                                                <td>
                                                    <div class="flex items-center gap-1">
                                                        <span class="font-medium">{{ $status->tasks->count() }}</span>
                                                        <span class="text-xs text-base-content/70">{{ Str::plural('task', $status->tasks->count()) }}</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                        
                                        @if($project->statuses->isEmpty())
                                            <tr>
                                                <td colspan="4" class="text-center py-6 text-base-content/50 italic">No statuses defined</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Git Repositories -->
                    <div class="card bg-base-100 shadow-xl border border-base-300 overflow-hidden lg:col-span-3">
                        <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center gap-3">
                            <span class="p-2 rounded-full bg-primary/10 text-primary">
                                <i class="fas fa-code-branch text-lg"></i>
                            </span>
                            <h2 class="text-xl font-semibold">Git Repositories</h2>
                        </div>
                        <div class="card-body">
                            <livewire:projects.settings.git-repositories :project="$project" />
                        </div>
                    </div>
                    
                    <!-- Danger Zone -->
                    <div class="card bg-base-100 shadow-xl border-2 border-error/50 overflow-hidden lg:col-span-3">
                        <div class="bg-error/10 p-4 border-b border-error/30">
                            <div class="flex items-center gap-3">
                                <span class="p-2 rounded-full bg-error/20 text-error">
                                    <i class="fas fa-exclamation-triangle text-lg"></i>
                                </span>
                                <h2 class="text-xl font-bold text-error">Danger Zone</h2>
                            </div>
                        </div>
                        <div class="card-body p-5">
                            <p class="mb-6 text-base-content/80">These actions are <b>irreversible</b> and should be used with caution.</p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Archive Project -->
                                <div class="p-4 border border-warning/30 rounded-lg bg-warning/5 flex flex-col items-center text-center">
                                    <div class="p-3 rounded-full bg-warning/10 mb-3">
                                        <i class="fas fa-archive text-warning text-xl"></i>
                                    </div>
                                    <h3 class="font-bold mb-2">{{ $project->is_archived ? 'Unarchive' : 'Archive' }} Project</h3>
                                    <p class="text-sm text-base-content/70 mb-4">
                                        {{ $project->is_archived ? 'Make this project available again' : 'Hide this project from active projects list' }}
                                    </p>
                                    @if($project->is_archived)
                                        <x-button 
                                            wire:click="unarchiveProject" 
                                            class="btn-warning btn-sm hover:shadow-md transition-all duration-300 mt-auto"
                                        >
                                            Unarchive Project
                                        </x-button>
                                    @else
                                        <x-button 
                                            wire:click="archiveProject" 
                                            class="btn-warning btn-sm hover:shadow-md transition-all duration-300 mt-auto"
                                        >
                                            Archive Project
                                        </x-button>
                                    @endif
                                </div>
                                
                                <!-- Export Data -->
                                <div class="p-4 border border-info/30 rounded-lg bg-info/5 flex flex-col items-center text-center">
                                    <div class="p-3 rounded-full bg-info/10 mb-3">
                                        <i class="fas fa-file-export text-info text-xl"></i>
                                    </div>
                                    <h3 class="font-bold mb-2">Export Project Data</h3>
                                    <p class="text-sm text-base-content/70 mb-4">Download all project data in JSON format</p>
                                    <form method="POST" action="#" class="mt-auto">
                                        @csrf
                                        <x-button 
                                            type="submit" 
                                            class="btn-info btn-sm hover:shadow-md transition-all duration-300"
                                        >
                                            Export Data
                                        </x-button>
                                    </form>
                                </div>
                                
                                <!-- Delete Project -->
                                <div class="p-4 border border-error/30 rounded-lg bg-error/5 flex flex-col items-center text-center">
                                    <div class="p-3 rounded-full bg-error/10 mb-3">
                                        <i class="fas fa-trash-alt text-error text-xl"></i>
                                    </div>
                                    <h3 class="font-bold mb-2">Delete Project</h3>
                                    <p class="text-sm text-base-content/70 mb-4">Permanently delete this project and all its data</p>
                                    <x-button 
                                        class="btn-error btn-sm hover:shadow-md transition-all duration-300 mt-auto"
                                        disabled
                                    >
                                        Delete Project
                                    </x-button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
