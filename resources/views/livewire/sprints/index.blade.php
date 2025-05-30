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
            <x-input placeholder="Search sprints..." wire:model.live="search" icon="o-magnifying-glass" class="w-full"/>

            <x-select
                    placeholder="Status"
                    wire:model.live="statusFilter"
                    :options="[
                    'active' => 'Active',
                    'completed' => 'Completed',
                    'planned' => 'Planned'
                ]"
                    empty-message="All Statuses"
                    class="w-40"
            />
        </div>

        <div class="flex gap-2">
            <x-button link="/projects/{{ $project->id }}/sprints/calendar" label="Calendar View" icon="o-calendar"
                      class="btn-outline"/>
            <x-button link="/projects/{{ $project->id }}/sprints/create" label="Create Sprint" icon="o-plus"
                      class="btn-primary"/>
        </div>
    </div>

    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            @if($sprints->isEmpty())
                <div class="py-8 text-center">
                    <x-icon name="o-calendar" class="w-16 h-16 mx-auto text-gray-400"/>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">No sprints found</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by creating a new sprint.</p>
                    <div class="mt-6">
                        <x-button link="/projects/{{ $project->id }}/sprints/create" label="Create Sprint" icon="o-plus"
                                  class="btn-primary"/>
                    </div>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="table table-zebra w-full">
                        <thead>
                        <tr>
                            <th class="cursor-pointer" wire:click="sortBy('name')">
                                Name
                                @if($sortField === 'name')
                                    <x-icon name="{{ $sortDirection === 'asc' ? 'o-chevron-up' : 'o-chevron-down' }}"
                                            class="w-4 h-4 inline"/>
                                @endif
                            </th>
                            <th>Status</th>
                            <th class="cursor-pointer" wire:click="sortBy('start_date')">
                                Start Date
                                @if($sortField === 'start_date')
                                    <x-icon name="{{ $sortDirection === 'asc' ? 'o-chevron-up' : 'o-chevron-down' }}"
                                            class="w-4 h-4 inline"/>
                                @endif
                            </th>
                            <th class="cursor-pointer" wire:click="sortBy('end_date')">
                                End Date
                                @if($sortField === 'end_date')
                                    <x-icon name="{{ $sortDirection === 'asc' ? 'o-chevron-up' : 'o-chevron-down' }}"
                                            class="w-4 h-4 inline"/>
                                @endif
                            </th>
                            <th>Tasks</th>
                            <th class="cursor-pointer" wire:click="sortBy('created_at')">
                                Created
                                @if($sortField === 'created_at')
                                    <x-icon name="{{ $sortDirection === 'asc' ? 'o-chevron-up' : 'o-chevron-down' }}"
                                            class="w-4 h-4 inline"/>
                                @endif
                            </th>
                            <th>Workflow</th>
                            <th>Recent Activity</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($sprints as $sprint)
                            <tr>
                                <td>
                                    <a href="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}"
                                       class="link link-hover font-medium">
                                        {{ $sprint->name }}
                                    </a>
                                </td>
                                <td>
                                    <div
                                            class="badge {{ $sprint->is_completed ? 'badge-info' : ($sprint->is_active ? 'badge-success' : 'badge-warning') }}">
                                        {{ $sprint->is_completed ? 'Completed' : ($sprint->is_active ? 'Active' : 'Planned') }}
                                    </div>
                                </td>
                                <td>{{ $sprint->start_date ? $sprint->start_date->format('M d, Y') : '-' }}</td>
                                <td>{{ $sprint->end_date ? $sprint->end_date->format('M d, Y') : '-' }}</td>
                                <td>{{ $sprint->tasks->count() }}</td>
                                <td>{{ $sprint->created_at->format('M d, Y') }}</td>
                                <td>
                                    @php
                                        $workflow = $sprint->workflow ?? null;
                                    @endphp
                                    @if($workflow)
                                        <span class="badge badge-outline badge-primary">
                                            <x-icon name="fas.project-diagram" class="w-4 h-4 inline mr-1"/>
                                            {{ $workflow->name }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $activity = $sprint->activities->sortByDesc('created_at')->first();
                                    @endphp
                                    @if($activity)
                                        <span class="badge badge-{{ $activity->color }}">
                                            <x-icon name="{{ $activity->icon }}" class="w-4 h-4 inline mr-1"/>
                                            {{ __(ucfirst(str_replace('_', ' ', $activity->action))) }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex gap-2">
                                        <x-button link="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}"
                                                  icon="o-eye" class="btn-sm btn-ghost" tooltip="View"/>
                                        <x-button link="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}/edit"
                                                  icon="o-pencil" class="btn-sm btn-ghost" tooltip="Edit"/>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $sprints->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
