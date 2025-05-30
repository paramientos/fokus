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

<div>
    <x-slot:title>Task History</x-slot:title>

    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <div class="flex items-center gap-2">
                <x-button link="/projects/{{ $task->project_id }}/tasks/{{ $task->id }}" icon="o-arrow-left"
                          class="btn-ghost btn-sm"/>
                <h1 class="text-2xl font-bold text-primary">{{ $task->title }} - Activity Timeline</h1>
            </div>
        </div>

        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <div class="flex items-center gap-4 mb-6">
                    <x-icon name="fas.clock-rotate-left" class="w-8 h-8 text-primary"/>
                    <div>
                        <h2 class="text-xl font-bold">Activity Timeline</h2>
                        <p class="text-gray-500">All changes and updates for this task</p>
                    </div>
                </div>

                @if($activities->isEmpty())
                    <div class="py-10 text-center">
                        <x-icon name="fas.clock" class="w-16 h-16 mx-auto text-gray-400"/>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">No activity yet</h3>
                        <p class="mt-1 text-sm text-gray-500">Activities will appear here when changes are made to this
                            task.</p>
                    </div>
                @else
                    <div class="relative">
                        <!-- Timeline line -->
                        <div class="absolute left-5 top-0 bottom-0 w-0.5 bg-gray-200"></div>

                        <!-- Timeline items -->
                        <ul class="space-y-6 relative">
                            @foreach($activities as $activity)
                                <li class="ml-10 relative">
                                    <!-- Timeline dot with icon -->
                                    <div class="absolute -left-10 mt-1.5 flex items-center justify-center w-8 h-8 rounded-full border-4 border-white {{ $loop->first ? 'bg-primary' : 'bg-base-200' }}">
                                        <x-icon name="{{ $activity->icon }}"
                                                class="w-4 h-4 {{ $loop->first ? 'text-white' : 'text-gray-500' }}"/>
                                    </div>

                                    <!-- Timeline content -->
                                    <div class="card bg-base-100 border border-gray-100 hover:shadow-md transition-shadow">
                                        <div class="card-body p-4">
                                            <div class="flex justify-between items-start">
                                                <div class="flex items-center gap-3">
                                                    @if($activity->user)
                                                        <div class="avatar">
                                                            <div class="w-8 h-8 rounded-full">
                                                                @if($activity->user->avatar)
                                                                    <img src="{{ $activity->user->avatar }}"
                                                                         alt="{{ $activity->user->name }}"/>
                                                                @else
                                                                    <div class="bg-primary text-white flex items-center justify-center">
                                                                        {{ substr($activity->user->name, 0, 1) }}
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <span class="font-medium">{{ $activity->user->name }}</span>
                                                    @else
                                                        <span class="font-medium">System</span>
                                                    @endif
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    <span title="{{ $activity->created_at->format('Y-m-d H:i:s') }}">
                                                        {{ $activity->created_at->diffForHumans() }}
                                                    </span>
                                                </div>
                                            </div>

                                            <p class="mt-2">{{ $activity->description }}</p>

                                            @if($activity->changes)
                                                <div class="mt-3 p-3 bg-base-200 rounded-lg text-sm">
                                                    @foreach($activity->changes as $field => $change)
                                                        <div class="mb-1">
                                                            <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $field)) }}:</span>
                                                            <span class="line-through text-error">{{ $change['from'] }}</span>
                                                            <x-icon name="fas.arrow-right" class="w-3 h-3 inline mx-1"/>
                                                            <span class="text-success">{{ $change['to'] }}</span>
                                                        </div>
                                                    @endforeach
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
