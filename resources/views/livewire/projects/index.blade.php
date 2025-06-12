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

<div>
    <x-slot:title>Projects</x-slot:title>

    <div class="p-6">
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-bold text-primary">Projects</h1>

            <div class="flex flex-col sm:flex-row gap-4">
                <x-input placeholder="Search projects..." wire:model.live="search" icon="o-magnifying-glass"/>
                <x-button link="/projects/create" label="Create Project" icon="o-plus" class="btn-primary"/>
            </div>
        </div>

        <div class="tabs tabs-boxed mb-4">
            <button wire:click="$set('showArchived', false)" class="tab {{ !$showArchived ? 'tab-active' : '' }}">
                <x-icon name="fas.folder-open" class="w-4 h-4 mr-2"/>
                Active Projects
            </button>
            <button wire:click="$set('showArchived', true)" class="tab {{ $showArchived ? 'tab-active' : '' }}">
                <x-icon name="fas.archive" class="w-4 h-4 mr-2"/>
                Archived Projects
            </button>
        </div>

        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                @if($projects->isEmpty())
                    <div class="py-8 text-center">
                        <x-icon name="{{ $showArchived ? 'fas.archive' : 'o-folder' }}"
                                class="w-16 h-16 mx-auto text-gray-400"/>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">
                            {{ $showArchived ? 'No archived projects found' : 'No projects found' }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ $showArchived ? 'Archived projects will appear here.' : 'Get started by creating a new project.' }}
                        </p>
                        @if(!$showArchived)
                            <div class="mt-6">
                                <x-button link="/projects/create" label="Create Project" icon="o-plus"
                                          class="btn-primary"/>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="table table-zebra w-full">
                            <thead>
                            <tr>
                                <th class="cursor-pointer" wire:click="sortBy('key')">
                                    Key
                                    @if($sortField === 'key')
                                        <x-icon
                                            name="{{ $sortDirection === 'asc' ? 'o-chevron-up' : 'o-chevron-down' }}"
                                            class="w-4 h-4 inline"/>
                                    @endif
                                </th>
                                <th class="cursor-pointer" wire:click="sortBy('name')">
                                    Name
                                    @if($sortField === 'name')
                                        <x-icon
                                            name="{{ $sortDirection === 'asc' ? 'o-chevron-up' : 'o-chevron-down' }}"
                                            class="w-4 h-4 inline"/>
                                    @endif
                                </th>
                                <th>Tasks</th>
                                <th class="cursor-pointer" wire:click="sortBy('created_at')">
                                    Created
                                    @if($sortField === 'created_at')
                                        <x-icon
                                            name="{{ $sortDirection === 'asc' ? 'o-chevron-up' : 'o-chevron-down' }}"
                                            class="w-4 h-4 inline"/>
                                    @endif
                                </th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($projects as $project)
                                <tr>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            @if($project->avatar)
                                                <img src="{{ $project->avatar }}" alt="{{ $project->name }}"
                                                     class="w-8 h-8 rounded-full">
                                            @else
                                                <div class="avatar placeholder">
                                                    <div class="bg-neutral text-neutral-content rounded-full w-8">
                                                        <span>{{ substr($project->name, 0, 1) }}</span>
                                                    </div>
                                                </div>
                                            @endif
                                            <span class="font-bold">{{ $project->key }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $project->name }}</td>
                                    <td>{{ $project->tasks->count() }}</td>
                                    <td>{{ $project->created_at->format('M d, Y') }}</td>
                                    <td>
                                        <div class="badge {{ $project->is_active ? 'badge-success' : 'badge-error' }}">
                                            {{ $project->is_active ? 'Active' : 'Inactive' }}
                                        </div>
                                        @if($project->is_archived)
                                            <div class="badge badge-warning ml-1">Archived</div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="flex gap-2">
                                            <x-button link="/projects/{{ $project->id }}" icon="o-eye"
                                                      class="btn-sm btn-ghost" tooltip="View"/>
                                            <x-button link="/projects/{{ $project->id }}/edit" icon="o-pencil"
                                                      class="btn-sm btn-ghost" tooltip="Edit"/>
                                            <x-button link="/projects/{{ $project->id }}/board" icon="o-view-columns"
                                                      class="btn-sm btn-ghost" tooltip="Board"/>
                                            <x-button link="/projects/{{ $project->id }}/activities"
                                                      icon="fas.clock-rotate-left" class="btn-sm btn-ghost"
                                                      tooltip="Activity Timeline"/>

                                            @if($project->is_archived)
                                                <x-button icon="fas.box-archive"
                                                          wire:click="unarchiveProject({{ $project->id }})"
                                                          class="btn-sm btn-ghost" tooltip="Unarchive"/>
                                            @else
                                                <x-button icon="fas.archive"
                                                          wire:click="archiveProject({{ $project->id }})"
                                                          class="btn-sm btn-ghost" tooltip="Archive"/>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $projects->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
