<?php

new class extends Livewire\Volt\Component {
    public \App\Models\Project $project;
    public $search = '';
    public $statusFilter = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';


    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function with(): array
    {
        $query = $this->project->sprints();

        // Apply search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('goal', 'like', '%' . $this->search . '%');
            });
        }

        // Apply status filter
        if ($this->statusFilter) {
            if ($this->statusFilter === 'active') {
                $query->where('is_active', true);
            } elseif ($this->statusFilter === 'completed') {
                $query->where('is_completed', true);
            } elseif ($this->statusFilter === 'planned') {
                $query->where('is_active', false)->where('is_completed', false);
            }
        }

        // Apply sorting
        $sprints = $query->with(['workflow', 'activities'])->orderBy($this->sortField, $this->sortDirection)->paginate(10);

        return [
            'sprints' => $sprints,
        ];
    }
}

?>

<div>
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div class="flex flex-col sm:flex-row gap-4 w-full">
            <x-input 
                placeholder="Search sprints..." 
                wire:model.live="search" 
                icon="fas.search" 
                class="w-full focus:border-primary/50 transition-all duration-300"
            />

            <x-select
                placeholder="Status"
                wire:model.live="statusFilter"
                :options="[
                    'active' => 'Active',
                    'completed' => 'Completed',
                    'planned' => 'Planned'
                ]"
                empty-message="All Statuses"
                class="w-40 focus:border-primary/50 transition-all duration-300"
            />
        </div>

        <div class="flex gap-2">
            <x-button 
                link="/projects/{{ $project->id }}/sprints/calendar" 
                label="Calendar View" 
                icon="fas.calendar" 
                class="btn-outline hover:shadow-md transition-all duration-300"
            />
            <x-button 
                link="/projects/{{ $project->id }}/sprints/create" 
                label="Create Sprint" 
                icon="fas.plus" 
                class="btn-primary hover:shadow-md transition-all duration-300"
            />
        </div>
    </div>

    <div class="card bg-base-100 shadow-xl border border-base-300 overflow-hidden">
        <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="p-2 rounded-full bg-primary/10 text-primary">
                    <i class="fas fa-flag text-lg"></i>
                </span>
                <h2 class="text-xl font-semibold">Sprint Management</h2>
            </div>
        </div>
        
        <div class="card-body p-0 md:p-5">
            @if($sprints->isEmpty())
                <div class="flex flex-col items-center justify-center py-12 text-center p-5">
                    <div class="p-6 rounded-full bg-base-200 mb-4">
                        <i class="fas fa-flag text-3xl text-base-content/50"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2">No sprints found</h3>
                    <p class="text-base-content/70 max-w-md mb-6">Get started by creating a new sprint to organize your work into time-boxed iterations.</p>
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
                            <th class="cursor-pointer" wire:click="sortBy('name')">
                                <div class="flex items-center gap-1">
                                    <span>Name</span>
                                    @if($sortField === 'name')
                                        <i class="fas fa-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-xs"></i>
                                    @endif
                                </div>
                            </th>
                            <th>Status</th>
                            <th class="cursor-pointer" wire:click="sortBy('start_date')">
                                <div class="flex items-center gap-1">
                                    <span>Start Date</span>
                                    @if($sortField === 'start_date')
                                        <i class="fas fa-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-xs"></i>
                                    @endif
                                </div>
                            </th>
                            <th class="cursor-pointer" wire:click="sortBy('end_date')">
                                <div class="flex items-center gap-1">
                                    <span>End Date</span>
                                    @if($sortField === 'end_date')
                                        <i class="fas fa-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-xs"></i>
                                    @endif
                                </div>
                            </th>
                            <th>Tasks</th>
                            <th class="cursor-pointer" wire:click="sortBy('created_at')">
                                <div class="flex items-center gap-1">
                                    <span>Created</span>
                                    @if($sortField === 'created_at')
                                        <i class="fas fa-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-xs"></i>
                                    @endif
                                </div>
                            </th>
                            <th>Workflow</th>
                            <th>Recent Activity</th>
                            <th class="text-right">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($sprints as $sprint)
                            <tr class="hover:bg-base-200/30 transition-colors duration-150">
                                <td>
                                    <a href="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}"
                                       class="font-medium text-primary hover:underline transition-colors duration-200">
                                        {{ $sprint->name }}
                                    </a>
                                </td>
                                <td>
                                    <div class="badge {{ $sprint->is_completed ? 'badge-info' : ($sprint->is_active ? 'badge-success' : 'badge-warning') }}">
                                        @if($sprint->is_completed)
                                            <i class="fas fa-check-circle mr-1"></i> Completed
                                        @elseif($sprint->is_active)
                                            <i class="fas fa-play-circle mr-1"></i> Active
                                        @else
                                            <i class="fas fa-clock mr-1"></i> Planned
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if($sprint->start_date)
                                        <div class="flex flex-col">
                                            <span>{{ $sprint->start_date->format('M d, Y') }}</span>
                                            <span class="text-xs text-base-content/70">{{ $sprint->start_date->diffForHumans() }}</span>
                                        </div>
                                    @else
                                        <span class="text-base-content/50">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($sprint->end_date)
                                        <div class="flex flex-col">
                                            <span>{{ $sprint->end_date->format('M d, Y') }}</span>
                                            <span class="text-xs text-base-content/70">{{ $sprint->end_date->diffForHumans() }}</span>
                                        </div>
                                    @else
                                        <span class="text-base-content/50">-</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex items-center gap-1">
                                        <span class="font-medium">{{ $sprint->tasks->count() }}</span>
                                        <span class="text-xs text-base-content/70">{{ Str::plural('task', $sprint->tasks->count()) }}</span>
                                    </div>
                                </td>
                                <td>{{ $sprint->created_at->format('M d, Y') }}</td>
                                <td>
                                    @php
                                        $workflow = $sprint->workflow ?? null;
                                    @endphp
                                    @if($workflow)
                                        <span class="badge badge-outline badge-primary">
                                            <i class="fas fa-project-diagram mr-1"></i>
                                            {{ $workflow->name }}
                                        </span>
                                    @else
                                        <span class="text-base-content/50">-</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $activity = $sprint->activities->sortByDesc('created_at')->first();
                                    @endphp
                                    @if($activity)
                                        <span class="badge badge-{{ $activity->color }}">
                                            <i class="fas fa-{{ str_replace(['o-', 'fas.'], '', $activity->icon) }} mr-1"></i>
                                            {{ __(ucfirst(str_replace('_', ' ', $activity->action))) }}
                                        </span>
                                    @else
                                        <span class="text-base-content/50">-</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <div class="flex justify-end gap-1">
                                        <x-button 
                                            link="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}" 
                                            icon="fas.eye" 
                                            class="btn-xs btn-ghost hover:bg-base-200 transition-all duration-200" 
                                            tooltip="View Sprint"
                                        />
                                        <x-button 
                                            link="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}/edit" 
                                            icon="fas.pencil" 
                                            class="btn-xs btn-ghost hover:bg-base-200 transition-all duration-200" 
                                            tooltip="Edit Sprint"
                                        />
                                        <x-button 
                                            link="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}/board" 
                                            icon="fas.columns" 
                                            class="btn-xs btn-ghost hover:bg-base-200 transition-all duration-200" 
                                            tooltip="Sprint Board"
                                        />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 p-4 border-t border-base-200">
                    {{ $sprints->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
