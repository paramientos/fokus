<?php

new class extends Livewire\Volt\Component {
    public $project;
    public $task = null;
    public $sprint = null;
    public $filter = 'all';
    public $activities = [];
    public $perPage = 15;
    public $hasMorePages = false;
    
    public function mount($project, $task = null, $sprint = null)
    {
        $this->project = \App\Models\Project::findOrFail($project);
        
        if ($task) {
            $this->task = \App\Models\Task::findOrFail($task);
        }
        
        if ($sprint) {
            $this->sprint = \App\Models\Sprint::findOrFail($sprint);
        }
        
        $this->loadActivities();
    }
    
    public function loadActivities()
    {
        $query = \App\Models\Activity::with(['user', 'task', 'sprint'])
            ->where('project_id', $this->project->id)
            ->orderBy('created_at', 'desc');
            
        if ($this->task) {
            $query->where('task_id', $this->task->id);
        }
        
        if ($this->sprint) {
            $query->where('sprint_id', $this->sprint->id);
        }
        
        if ($this->filter !== 'all') {
            $query->where('action', $this->filter);
        }
        
        $activities = $query->paginate($this->perPage);
        $this->activities = $activities->items();
        $this->hasMorePages = $activities->hasMorePages();
    }
    
    public function loadMore()
    {
        $this->perPage += 15;
        $this->loadActivities();
    }
    
    public function changeFilter($filter)
    {
        $this->filter = $filter;
        $this->perPage = 15;
        $this->loadActivities();
    }
}
?>

<div>
    <x-slot:title>Activity Timeline - {{ $project->name }}</x-slot:title>

    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-primary">Activity Timeline</h1>
            
            <div class="flex gap-2">
                <x-dropdown>
                    <x-slot:trigger>
                        <x-button class="btn-outline" label="Filter" icon="fas.filter" />
                    </x-slot:trigger>
                    
                    <x-menu-item label="All Activities" icon="fas.list" wire:click="changeFilter('all')" :active="$filter === 'all'" />
                    <x-menu-item label="Created" icon="fas.plus" wire:click="changeFilter('created')" :active="$filter === 'created'" />
                    <x-menu-item label="Updated" icon="fas.pen" wire:click="changeFilter('updated')" :active="$filter === 'updated'" />
                    <x-menu-item label="Status Changed" icon="fas.arrows-up-down" wire:click="changeFilter('status_changed')" :active="$filter === 'status_changed'" />
                    <x-menu-item label="Comments" icon="fas.comment" wire:click="changeFilter('comment_added')" :active="$filter === 'comment_added'" />
                    <x-menu-item label="Assignments" icon="fas.user" wire:click="changeFilter('assigned')" :active="$filter === 'assigned'" />
                </x-dropdown>
                
                <x-button link="/projects/{{ $project->id }}/tasks" label="Back to Tasks" icon="fas.arrow-left" class="btn-ghost" />
            </div>
        </div>
        
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                @if(empty($activities))
                    <div class="py-8 text-center">
                        <x-icon name="fas.clock-rotate-left" class="w-16 h-16 mx-auto text-gray-400"/>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">No activities found</h3>
                        <p class="mt-2 text-sm text-gray-500">
                            @if($filter !== 'all')
                                Try changing your filter or check back later.
                            @else
                                Activities will appear here as you work on your project.
                            @endif
                        </p>
                    </div>
                @else
                    <div class="relative">
                        <!-- Timeline line -->
                        <div class="absolute left-5 top-0 bottom-0 w-0.5 bg-gray-200"></div>
                        
                        <div class="space-y-8">
                            @foreach($activities as $activity)
                                <div class="relative flex items-start">
                                    <!-- Timeline dot -->
                                    <div class="absolute left-5 mt-1.5 -ml-2.5 h-5 w-5 rounded-full border-4 border-white bg-{{ $activity->color }} shadow"></div>
                                    
                                    <!-- Timeline content -->
                                    <div class="ml-10 w-full">
                                        <div class="flex justify-between">
                                            <div class="flex items-center gap-2">
                                                <x-icon name="{{ $activity->icon }}" class="w-5 h-5 text-{{ $activity->color }}" />
                                                <p class="text-sm font-medium">
                                                    {{ $activity->user->name }}
                                                    <span class="text-gray-500">{{ $activity->description }}</span>
                                                </p>
                                            </div>
                                            <time class="text-xs text-gray-500">{{ $activity->created_at->diffForHumans() }}</time>
                                        </div>
                                        
                                        @if($activity->task)
                                            <div class="mt-2 flex items-center gap-2">
                                                <a href="/projects/{{ $project->id }}/tasks/{{ $activity->task->id }}" class="text-sm text-primary hover:underline">
                                                    {{ $project->key }}-{{ $activity->task->id }}: {{ $activity->task->title }}
                                                </a>
                                            </div>
                                        @endif
                                        
                                        @if($activity->changes)
                                            <div class="mt-2 text-xs border rounded-lg overflow-hidden">
                                                <div class="bg-base-200 px-3 py-1 font-medium">Changes</div>
                                                <div class="p-3 space-y-1">
                                                    @foreach($activity->changes as $field => $change)
                                                        <div class="grid grid-cols-3 gap-2">
                                                            <div class="font-medium">{{ ucfirst(str_replace('_', ' ', $field)) }}</div>
                                                            <div class="text-error line-through">{{ $change['old'] ?? '-' }}</div>
                                                            <div class="text-success">{{ $change['new'] ?? '-' }}</div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        @if($hasMorePages)
                            <div class="mt-8 text-center">
                                <x-button wire:click="loadMore" label="Load More" icon="fas.arrow-down" class="btn-outline" />
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
