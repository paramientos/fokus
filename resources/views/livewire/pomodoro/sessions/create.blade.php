<?php
use App\Models\PomodoroTag;
use Mary\Traits\Toast;

new class extends Livewire\Volt\Component {
    use Toast;

    public $title = '';
    public $description = '';
    public $work_duration = 25;
    public $break_duration = 5;
    public $long_break_duration = 15;
    public $long_break_interval = 4;
    public $target_pomodoros = 4;
    public $selected_tags = [];
    public $tags = [];

    public function mount() {
        $this->tags = PomodoroTag::where('workspace_id', session('workspace_id'))->get();
    }

    public function save() {
        $session = \App\Models\PomodoroSession::create([
            'user_id' => auth()->id(),
            'workspace_id' => session('workspace_id'),
            'title' => $this->title,
            'description' => $this->description,
            'work_duration' => $this->work_duration,
            'break_duration' => $this->break_duration,
            'long_break_duration' => $this->long_break_duration,
            'long_break_interval' => $this->long_break_interval,
            'target_pomodoros' => $this->target_pomodoros,
            'completed_pomodoros' => 0,
            'status' => 'not_started',
        ]);
        $session->tags()->sync($this->selected_tags);
        $this->success('Pomodoro session created!');
        return redirect()->route('pomodoro.dashboard');
    }
};
?>
<div class="max-w-xl mx-auto py-8">
    <h1 class="text-2xl font-bold mb-6">New Pomodoro Session</h1>
    <form wire:submit.prevent="save" class="space-y-6">
        <x-input label="Title" wire:model.defer="title" placeholder="Session title" icon="fas.heading" />
        <x-textarea label="Description" wire:model.defer="description" placeholder="Optional description..." icon="fas.align-left" />
        <div class="grid grid-cols-2 gap-4">
            <x-input type="number" min="1" max="120" label="Work Duration (min)" wire:model.defer="work_duration" icon="fas.clock" />
            <x-input type="number" min="1" max="60" label="Break Duration (min)" wire:model.defer="break_duration" icon="fas.coffee" />
            <x-input type="number" min="5" max="60" label="Long Break (min)" wire:model.defer="long_break_duration" icon="fas.bed" />
            <x-input type="number" min="2" max="8" label="Long Break Interval" wire:model.defer="long_break_interval" icon="fas.repeat" />
            <x-input type="number" min="1" max="16" label="Target Pomodoros" wire:model.defer="target_pomodoros" icon="fas.bullseye" />
        </div>
        <x-select label="Tags" :options="$tags->map(fn($t) => ['id' => $t->id, 'name' => $t->name])" wire:model.defer="selected_tags" multiple icon="fas.tags" />
        <div class="flex justify-end">
            <x-button type="submit" icon="fas.save" color="primary">Create</x-button>
        </div>
    </form>
</div>
