<?php
use App\Models\PomodoroSession;
use App\Models\PomodoroTag;
use Illuminate\Support\Facades\Auth;
use Mary\Traits\Toast;

new class extends Livewire\Volt\Component {
    use Toast;

    public $session;
    public $title;
    public $description;
    public $work_duration;
    public $break_duration;
    public $long_break_duration;
    public $long_break_interval;
    public $target_pomodoros;
    public $tags = [];
    public $allTags = [];

    public function mount($session)
    {
        $this->session = PomodoroSession::where('id', $session)
            ->where('user_id', Auth::id())
            ->where('workspace_id', session('workspace_id'))
            ->firstOrFail();
        $this->title = $this->session->title;
        $this->description = $this->session->description;
        $this->work_duration = $this->session->work_duration;
        $this->break_duration = $this->session->break_duration;
        $this->long_break_duration = $this->session->long_break_duration;
        $this->long_break_interval = $this->session->long_break_interval;
        $this->target_pomodoros = $this->session->target_pomodoros;
        $this->tags = $this->session->tags()->pluck('pomodoro_tags.id')->toArray();
        $this->allTags = PomodoroTag::where('workspace_id', session('workspace_id'))->get();
    }

    public function save()
    {
        $this->validate([
            'title' => 'required|string|max:100',
            'work_duration' => 'required|integer|min:1',
            'break_duration' => 'required|integer|min:1',
            'long_break_duration' => 'required|integer|min:1',
            'long_break_interval' => 'required|integer|min:1',
            'target_pomodoros' => 'required|integer|min:1',
        ]);
        $this->session->update([
            'title' => $this->title,
            'description' => $this->description,
            'work_duration' => $this->work_duration,
            'break_duration' => $this->break_duration,
            'long_break_duration' => $this->long_break_duration,
            'long_break_interval' => $this->long_break_interval,
            'target_pomodoros' => $this->target_pomodoros,
        ]);
        $this->session->tags()->sync($this->tags);
        $this->success('Session updated successfully!');
        return redirect()->route('pomodoro.sessions.index');
    }
};
?>
<div class="max-w-xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">Edit Pomodoro Session</h1>
    <form wire:submit.prevent="save" class="space-y-5">
        <x-input label="Title" wire:model.defer="title" required icon="fas.heading" />
        <x-textarea label="Description" wire:model.defer="description" icon="fas.info-circle" />
        <div class="grid grid-cols-2 gap-4">
            <x-input label="Work Duration (min)" type="number" wire:model.defer="work_duration" required min="1" icon="fas.clock" />
            <x-input label="Break Duration (min)" type="number" wire:model.defer="break_duration" required min="1" icon="fas.coffee" />
            <x-input label="Long Break (min)" type="number" wire:model.defer="long_break_duration" required min="1" icon="fas.bed" />
            <x-input label="Long Break Interval" type="number" wire:model.defer="long_break_interval" required min="1" icon="fas.repeat" />
        </div>
        <x-input label="Target Pomodoros" type="number" wire:model.defer="target_pomodoros" required min="1" icon="fas.flag-checkered" />
        <x-select label="Tags" :options="$allTags->pluck('name','id')->toArray()" wire:model.defer="tags" multiple icon="fas.tags" />
        <div class="flex gap-2 mt-4">
            <x-button type="submit" color="primary" icon="fas.save">Save</x-button>
            <x-button link="{{ route('pomodoro.sessions.index') }}" wire:navigate color="secondary" icon="fas.arrow-left">Cancel</x-button>
        </div>
    </form>
</div>
