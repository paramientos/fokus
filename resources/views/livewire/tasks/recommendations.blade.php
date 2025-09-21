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

<div class="bg-gradient-to-br from-base-100 to-base-200 min-h-screen p-6">
    <div class="max-w-7xl mx-auto">
        <div class="bg-base-100 rounded-xl shadow-xl border border-base-300 overflow-hidden">
            <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center gap-3">
                <span class="p-2 rounded-full bg-warning/10 text-warning">
                    <i class="fas fa-lightbulb"></i>
                </span>
                <div>
                    <h2 class="text-lg font-semibold">Önerilen Görevler</h2>
                    <p class="text-sm text-base-content/70">Becerilerinize ve ilgi alanlarınıza göre önerilen görevler</p>
                </div>
            </div>

            <div class="p-6">
                @if($recommendedTasks->isEmpty())
                    <div class="flex flex-col items-center justify-center py-12 text-base-content/50 bg-base-200/30 rounded-lg border border-base-300">
                        <i class="fas fa-search text-4xl mb-3 text-base-content/30"></i>
                        <h3 class="text-lg font-medium text-base-content/80">Önerilen görev bulunamadı</h3>
                        <p class="mt-1 text-sm text-center max-w-md">Şu anda önerilen görev bulunmuyor. Tüm görevler tamamlanmış olabilir veya henüz yeterli veri yok.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-4">
                        @foreach($recommendedTasks as $task)
                            <div class="bg-base-100 border border-base-300 rounded-lg hover:shadow-md transition-all duration-200 overflow-hidden group">
                                <div class="p-4">
                                    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
                                        <div class="space-y-3">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-mono bg-primary/10 text-primary px-1.5 py-0.5 rounded">
                                                    {{ $project->key }}-{{ $task->id }}
                                                </span>
                                                <span class="badge" style="background-color: {{ $task->status->color }}; color: white;">
                                                    {{ $task->status->name }}
                                                </span>
                                                <span class="badge {{ 
                                                    $task->priority->value === 'high' ? 'badge-error' : 
                                                    ($task->priority->value === 'medium' ? 'badge-warning' : 'badge-info') 
                                                }}">
                                                    @if($task->priority->value === 'high')
                                                        <i class="fas fa-arrow-up text-xs mr-1"></i>
                                                    @elseif($task->priority->value === 'medium')
                                                        <i class="fas fa-equals text-xs mr-1"></i>
                                                    @else
                                                        <i class="fas fa-arrow-down text-xs mr-1"></i>
                                                    @endif
                                                    {{ $task->priority->label() }}
                                                </span>
                                            </div>
                                            
                                            <div>
                                                <a href="/projects/{{ $project->id }}/tasks/{{ $task->id }}" 
                                                   class="text-lg font-medium text-primary/90 hover:text-primary transition-colors duration-200 group-hover:underline flex items-center gap-2">
                                                    <i class="fas fa-{{ $task->task_type->icon() }} text-sm"></i>
                                                    <span>{{ $task->title }}</span>
                                                </a>
                                            </div>

                                            @if($task->description)
                                                <div class="text-sm text-base-content/70 bg-base-200/30 p-3 rounded-lg line-clamp-2">
                                                    {{ $task->description }}
                                                </div>
                                            @endif

                                            <div class="flex flex-wrap items-center gap-2">
                                                @if($task->sprint)
                                                    <div class="badge badge-outline border-primary/30 text-primary/80 hover:border-primary hover:text-primary transition-all duration-200">
                                                        <i class="fas fa-flag mr-1 text-xs"></i>
                                                        {{ $task->sprint->name }}
                                                    </div>
                                                @endif

                                                @if($task->story_points)
                                                    <div class="badge badge-outline border-info/30 text-info/80">
                                                        <i class="fas fa-chart-simple mr-1 text-xs"></i>
                                                        {{ $task->story_points }} pts
                                                    </div>
                                                @endif
                                                
                                                @if($task->due_date)
                                                    <div class="badge badge-outline {{ $task->due_date < now() ? 'border-error/30 text-error/80' : 'border-success/30 text-success/80' }}">
                                                        <i class="fas fa-calendar-day mr-1 text-xs"></i>
                                                        {{ $task->due_date->format('d M Y') }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="flex justify-end">
                                            <x-button
                                                wire:click="assignTask({{ $task->id }})"
                                                label="Üzerine Al"
                                                icon="fas.user-plus"
                                                class="btn-primary hover:shadow-md transition-all duration-300"
                                                spinner
                                                tooltip="Bu görevi kendinize atayın"
                                            />
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="bg-base-200/30 px-4 py-2 border-t border-base-300">
                                    <div class="flex justify-between items-center text-xs text-base-content/60">
                                        <div class="flex items-center gap-1">
                                            <i class="fas fa-lightbulb text-warning"></i>
                                            <span>Becerilerinize uygun</span>
                                        </div>
                                        <div>
                                            <a href="/projects/{{ $project->id }}/tasks/{{ $task->id }}" class="flex items-center gap-1 hover:text-primary transition-colors duration-200">
                                                <span>Detayları Gör</span>
                                                <i class="fas fa-arrow-right"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
