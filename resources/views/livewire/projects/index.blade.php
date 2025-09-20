<?php

use App\Models\Project;

new class extends Livewire\Volt\Component {
    public $search = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $showArchived = false;

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function toggleArchived()
    {
        $this->showArchived = !$this->showArchived;
    }

    public function archiveProject($projectId)
    {
        $project = Project::find($projectId);
        if ($project) {
            $project->is_archived = true;
            $project->save();

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Project archived successfully'
            ]);
        }
    }

    public function unarchiveProject($projectId)
    {
        $project = Project::find($projectId);
        if ($project) {
            $project->is_archived = false;
            $project->save();

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Project unarchived successfully'
            ]);
        }
    }

    public function with(): array
    {
        $projects = Project::query()
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('key', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->showArchived, function ($query) {
                $query->where('is_archived', true);
            }, function ($query) {
                $query->where('is_archived', false);
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);

        return [
            'projects' => $projects,
        ];
    }
}

?>

<div class="bg-gradient-to-br from-base-100 to-base-200 min-h-screen">
    <x-slot:title>Projects</x-slot:title>

    <div class="p-6 max-w-7xl mx-auto">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold text-primary mb-1">Projects</h1>
                <p class="text-base-content/70">Manage and organize all your projects</p>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                <x-input 
                    placeholder="Search projects..." 
                    wire:model.live="search" 
                    icon="fas.search"
                    class="transition-all duration-300 focus:ring-2 focus:ring-primary/30"
                />
                <x-button 
                    link="/projects/create" 
                    label="Create Project" 
                    icon="fas.plus" 
                    class="btn-primary hover:shadow-lg transition-all duration-300"
                />
            </div>
        </div>

        <div class="tabs tabs-boxed p-1 bg-base-200/50 rounded-xl mb-6 border border-base-300">
            <button 
                wire:click="$set('showArchived', false)" 
                class="tab gap-2 transition-all duration-200 {{ !$showArchived ? 'tab-active' : 'hover:bg-base-300' }}"
            >
                <i class="fas fa-folder-open text-sm"></i>
                Active Projects
            </button>
            <button 
                wire:click="$set('showArchived', true)" 
                class="tab gap-2 transition-all duration-200 {{ $showArchived ? 'tab-active' : 'hover:bg-base-300' }}"
            >
                <i class="fas fa-archive text-sm"></i>
                Archived Projects
            </button>
        </div>

        <div class="card bg-base-100 shadow-xl border border-base-300 overflow-hidden">
            <div class="bg-primary/5 p-4 border-b border-base-300">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="p-2 rounded-full bg-primary/10 text-primary">
                            <i class="fas fa-{{ $showArchived ? 'archive' : 'project-diagram' }} text-lg"></i>
                        </span>
                        <h2 class="text-xl font-semibold">{{ $showArchived ? 'Archived Projects' : 'Active Projects' }}</h2>
                    </div>
                    <span class="badge bg-primary/10 text-primary border-0 font-medium">
                        {{ $projects->total() }} {{ Str::plural('project', $projects->total()) }}
                    </span>
                </div>
            </div>
            
            <div class="card-body p-0">
                @if($projects->isEmpty())
                    <div class="flex flex-col items-center justify-center py-16 text-center p-6">
                        <div class="p-6 rounded-full bg-base-200 mb-4">
                            <i class="fas fa-{{ $showArchived ? 'archive' : 'folder-open' }} text-3xl text-base-content/50"></i>
                        </div>
                        <h3 class="text-xl font-bold mb-2">
                            {{ $showArchived ? 'No archived projects found' : 'No projects found' }}
                        </h3>
                        <p class="text-base-content/70 max-w-md mb-6">
                            {{ $showArchived ? 'Archived projects will appear here when you archive them.' : 'Get started by creating a new project to organize your tasks and collaborate with your team.' }}
                        </p>
                        @if(!$showArchived)
                            <x-button 
                                link="/projects/create" 
                                label="Create Your First Project" 
                                icon="fas.plus"
                                class="btn-primary hover:shadow-lg transition-all duration-300"
                            />
                        @endif
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="table w-full">
                            <thead class="bg-base-200/50">
                                <tr>
                                    <th class="cursor-pointer hover:bg-base-300/50 transition-colors duration-200" wire:click="sortBy('key')">
                                        <div class="flex items-center gap-1">
                                            <span>Key</span>
                                            @if($sortField === 'key')
                                                <i class="fas fa-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-xs text-primary"></i>
                                            @endif
                                        </div>
                                    </th>
                                    <th class="cursor-pointer hover:bg-base-300/50 transition-colors duration-200" wire:click="sortBy('name')">
                                        <div class="flex items-center gap-1">
                                            <span>Project</span>
                                            @if($sortField === 'name')
                                                <i class="fas fa-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-xs text-primary"></i>
                                            @endif
                                        </div>
                                    </th>
                                    <th>Tasks</th>
                                    <th class="cursor-pointer hover:bg-base-300/50 transition-colors duration-200" wire:click="sortBy('created_at')">
                                        <div class="flex items-center gap-1">
                                            <span>Created</span>
                                            @if($sortField === 'created_at')
                                                <i class="fas fa-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-xs text-primary"></i>
                                            @endif
                                        </div>
                                    </th>
                                    <th>Status</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($projects as $project)
                                    <tr class="hover:bg-base-200/30 transition-colors duration-150">
                                        <td>
                                            <div class="flex items-center gap-2">
                                                <div class="avatar placeholder">
                                                    <div class="bg-primary text-primary-content rounded-lg w-8 h-8 flex items-center justify-center">
                                                        <span class="font-bold">{{ $project->key }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="/projects/{{ $project->id }}" class="font-medium text-primary hover:underline flex items-center gap-2">
                                                @if($project->avatar)
                                                    <img src="{{ $project->avatar }}" alt="{{ $project->name }}" class="w-6 h-6 rounded-full">
                                                @endif
                                                {{ $project->name }}
                                            </a>
                                            @if($project->description)
                                                <p class="text-xs text-base-content/70 truncate max-w-xs">{{ $project->description }}</p>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="flex items-center gap-1">
                                                <span class="font-medium">{{ $project->tasks->count() }}</span>
                                                <span class="text-xs text-base-content/70">{{ Str::plural('task', $project->tasks->count()) }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex flex-col">
                                                <span>{{ $project->created_at->format('M d, Y') }}</span>
                                                <span class="text-xs text-base-content/70">{{ $project->created_at->diffForHumans() }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex flex-wrap gap-1">
                                                <div class="badge {{ $project->is_active ? 'badge-success' : 'badge-error' }} badge-sm">
                                                    {{ $project->is_active ? 'Active' : 'Inactive' }}
                                                </div>
                                                @if($project->is_archived)
                                                    <div class="badge badge-warning badge-sm">Archived</div>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex justify-center gap-1">
                                                <x-button 
                                                    link="/projects/{{ $project->id }}" 
                                                    icon="fas.eye"
                                                    class="btn-sm btn-ghost hover:bg-base-200 transition-all duration-200"
                                                    tooltip="View Project"
                                                />
                                                <x-button 
                                                    link="/projects/{{ $project->id }}/edit" 
                                                    icon="fas.edit"
                                                    class="btn-sm btn-ghost hover:bg-base-200 transition-all duration-200"
                                                    tooltip="Edit Project"
                                                />
                                                <x-button 
                                                    link="/projects/{{ $project->id }}/board" 
                                                    icon="fas.columns"
                                                    class="btn-sm btn-ghost hover:bg-base-200 transition-all duration-200"
                                                    tooltip="Kanban Board"
                                                />
                                                <x-button 
                                                    link="/projects/{{ $project->id }}/activities"
                                                    icon="fas.clock-rotate-left" 
                                                    class="btn-sm btn-ghost hover:bg-base-200 transition-all duration-200"
                                                    tooltip="Activity Timeline"
                                                />

                                                @if($project->is_archived)
                                                    <x-button 
                                                        icon="fas.box-archive"
                                                        wire:click="unarchiveProject({{ $project->id }})"
                                                        class="btn-sm btn-ghost hover:bg-base-200 transition-all duration-200"
                                                        tooltip="Unarchive Project"
                                                    />
                                                @else
                                                    <x-button 
                                                        icon="fas.archive"
                                                        wire:click="archiveProject({{ $project->id }})"
                                                        class="btn-sm btn-ghost hover:bg-base-200 transition-all duration-200"
                                                        tooltip="Archive Project"
                                                    />
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="p-4 border-t border-base-300">
                        {{ $projects->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
