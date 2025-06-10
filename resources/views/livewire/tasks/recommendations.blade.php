<?php

new class extends Livewire\Volt\Component {
    public $project;
    public $recommendedTasks = [];

    public function mount($project)
    {
        $this->project = $project;
        $this->loadRecommendations();
    }

    public function loadRecommendations()
    {
        $user = auth()->user() ?? \App\Models\User::first(); // Demo için fallback
        $recommendationService = new \App\Services\TaskRecommendationService();
        $this->recommendedTasks = $recommendationService->recommendTasksForUser($user, $this->project);
    }

    public function assignTask($taskId)
    {
        $task = \App\Models\Task::findOrFail($taskId);
        $user = auth()->user() ?? \App\Models\User::first(); // Demo için fallback

        $task->update(['user_id' => $user->id]);

        // Aktivite kaydı oluştur
        \App\Models\Activity::create([
            'user_id' => $user->id,
            'project_id' => $this->project->id,
            'task_id' => $task->id,
            'action' => 'assigned',
            'description' => 'Task assigned to ' . $user->name,
        ]);

        $this->loadRecommendations();

        $this->dispatch('task-assigned', ['taskId' => $taskId]);
    }
}
?>

<div>
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <h2 class="card-title flex items-center gap-2">
                <x-icon name="fas.lightbulb" class="w-5 h-5 text-warning" />
                Önerilen Görevler
            </h2>

            @if($recommendedTasks->isEmpty())
                <div class="py-4 text-center">
                    <x-icon name="fas.user" class="w-12 h-12 mx-auto text-gray-400"/>
                    <p class="mt-2 text-sm text-gray-500">
                        Şu anda önerilen görev bulunmuyor. Tüm görevler tamamlanmış olabilir veya henüz yeterli veri yok.
                    </p>
                </div>
            @else
                <div class="space-y-3 mt-2">
                    @foreach($recommendedTasks as $task)
                        <div class="card bg-base-200 shadow-sm hover:shadow transition-shadow">
                            <div class="card-body p-4">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <x-icon name="fas.{{ $task->task_type->icon() }}" class="w-4 h-4" />
                                            <a href="/projects/{{ $project->id }}/tasks/{{ $task->id }}" class="font-medium hover:text-primary">
                                                {{ $project->key }}-{{ $task->id }}: {{ $task->title }}
                                            </a>
                                        </div>

                                        @if($task->description)
                                            <p class="text-sm text-gray-500 mt-1 line-clamp-2">
                                                {{ $task->description }}
                                            </p>
                                        @endif

                                        <div class="flex items-center gap-3 mt-2">
                                            @if($task->sprint)
                                                <div class="badge badge-outline">
                                                    <x-icon name="fas.calendar" class="w-3 h-3 mr-1" />
                                                    {{ $task->sprint->name }}
                                                </div>
                                            @endif

                                            <div class="badge" style="background-color: {{ $task->status->color }}">
                                                {{ $task->status->name }}
                                            </div>

                                            <div class="badge badge-{{ $task->priority->color() }}">
                                                {{ $task->priority->label() }}
                                            </div>

                                            @if($task->story_points)
                                                <div class="badge badge-outline">
                                                    {{ $task->story_points }} pts
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <x-button
                                        wire:click="assignTask({{ $task->id }})"
                                        label="Üzerine Al"
                                        icon="fas.user-plus"
                                        class="btn-sm btn-primary"
                                        spinner
                                    />
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
