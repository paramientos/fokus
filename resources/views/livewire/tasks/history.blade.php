<?php
new class extends Livewire\Volt\Component {
    public \App\Models\Task $task;

    public function with(): array
    {
        return [
            'task' => $this->task,
            'activities' => $this->task->activities()->with('user')->latest()->get(),
        ];
    }
}
?>

<div class="bg-gradient-to-br from-base-100 to-base-200 min-h-screen">
    <x-slot:title>Task History</x-slot:title>

    <div class="max-w-7xl mx-auto p-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div class="flex items-center gap-3">
                <x-button 
                    link="/projects/{{ $task->project_id }}/tasks/{{ $task->id }}" 
                    icon="fas.arrow-left" 
                    class="btn-ghost btn-sm hover:bg-base-200 transition-all duration-200"
                    tooltip="Back to Task"
                />
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xs font-mono bg-primary/10 text-primary px-2 py-1 rounded">
                            {{ $task->project->key }}-{{ $task->id }}
                        </span>
                    </div>
                    <h1 class="text-2xl font-bold text-primary">Activity Timeline</h1>
                </div>
            </div>
        </div>

        <div class="bg-base-100 rounded-xl shadow-xl border border-base-300 overflow-hidden">
            <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center gap-3">
                <span class="p-2 rounded-full bg-primary/10 text-primary">
                    <i class="fas fa-clock-rotate-left"></i>
                </span>
                <div>
                    <h2 class="text-lg font-semibold">{{ $task->title }}</h2>
                    <p class="text-sm text-base-content/70">Complete history of changes and updates</p>
                </div>
            </div>

            <div class="p-6">
                @if($activities->isEmpty())
                    <div class="flex flex-col items-center justify-center py-12 text-base-content/50 bg-base-200/30 rounded-lg border border-base-300">
                        <i class="fas fa-history text-4xl mb-3 text-base-content/30"></i>
                        <h3 class="text-lg font-medium text-base-content/80">No activity yet</h3>
                        <p class="mt-1 text-sm text-center max-w-md">Activities will appear here when changes are made to this task</p>
                    </div>
                @else
                    <div class="relative">
                        <!-- Timeline line -->
                        <div class="absolute left-5 top-0 bottom-0 w-0.5 bg-primary/10"></div>

                        <!-- Timeline items -->
                        <ul class="space-y-6 relative">
                            @foreach($activities as $activity)
                                <li class="ml-10 relative">
                                    <!-- Timeline dot with icon -->
                                    <div class="absolute -left-10 mt-1.5 flex items-center justify-center w-8 h-8 rounded-full border-4 border-base-100 {{ $loop->first ? 'bg-primary' : 'bg-base-200' }}">
                                        <i class="{{ str_replace('o-', 'fas fa-', $activity->icon) }} {{ $loop->first ? 'text-white' : 'text-base-content/60' }}"></i>
                                    </div>

                                    <!-- Timeline content -->
                                    <div class="bg-base-100 border border-base-300 rounded-xl hover:shadow-md transition-all duration-200">
                                        <div class="p-4">
                                            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-2">
                                                <div class="flex items-center gap-3">
                                                    @if($activity->user)
                                                        <div class="avatar">
                                                            <div class="w-8 h-8 rounded-full">
                                                                @if($activity->user->avatar)
                                                                    <img src="{{ $activity->user->avatar }}" alt="{{ $activity->user->name }}"/>
                                                                @else
                                                                    <div class="bg-primary/10 text-primary rounded-full w-8 h-8 flex items-center justify-center">
                                                                        <span class="font-medium">{{ substr($activity->user->name, 0, 1) }}</span>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <span class="font-medium text-primary/90">{{ $activity->user->name }}</span>
                                                    @else
                                                        <div class="flex items-center gap-2">
                                                            <div class="bg-info/10 text-info rounded-full w-8 h-8 flex items-center justify-center">
                                                                <i class="fas fa-robot"></i>
                                                            </div>
                                                            <span class="font-medium text-info">System</span>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="text-xs text-base-content/50 flex items-center gap-1">
                                                    <i class="fas fa-clock"></i>
                                                    <span title="{{ $activity->created_at->format('Y-m-d H:i:s') }}">
                                                        {{ $activity->created_at->diffForHumans() }}
                                                    </span>
                                                </div>
                                            </div>

                                            <p class="mt-3 text-base-content/80">{{ $activity->description }}</p>

                                            @if($activity->changes)
                                                <div class="mt-3 p-3 bg-base-200/50 rounded-lg text-sm border border-base-300">
                                                    @foreach($activity->changes as $field => $change)
                                                        <div class="mb-2">
                                                            <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $field)) }}:</span>
                                                            <div class="flex items-center gap-2 mt-1 ml-2">
                                                                <span class="line-through text-error/80">{{ $change['from'] }}</span>
                                                                <i class="fas fa-arrow-right text-xs text-base-content/50"></i>
                                                                <span class="text-success/80">{{ $change['to'] }}</span>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                            
                                            @if($loop->first)
                                                <div class="mt-3 text-xs text-base-content/50 flex items-center gap-1">
                                                    <i class="fas fa-star-of-life"></i>
                                                    <span>Latest activity</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
