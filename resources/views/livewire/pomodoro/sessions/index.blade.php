<?php
use App\Models\PomodoroSession;
use Illuminate\Support\Facades\Auth;

new class extends Livewire\Volt\Component {
    public $sessions;

    public function mount()
    {
        $this->sessions = PomodoroSession::where('user_id', Auth::id())
            ->where('workspace_id', session('workspace_id'))
            ->latest('started_at')
            ->get();
    }
};
?>
<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Pomodoro Sessions</h1>
        <x-button href="{{ route('pomodoro.sessions.create') }}" wire:navigate icon="fas.plus" color="primary">New Session</x-button>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($sessions as $session)
            <x-card class="p-4 flex flex-col gap-2">
                <div class="flex items-center gap-2">
                    <x-icon name="fas.clock" class="text-blue-500" />
                    <span class="font-semibold text-lg">{{ $session->title }}</span>
                </div>
                <div class="flex items-center gap-2 text-gray-500 text-sm">
                    <x-icon name="fas.calendar" />
                    {{ $session->started_at?->format('d M Y H:i') ?? '-' }}
                </div>
                <div class="flex items-center gap-2 text-sm">
                    <x-icon name="fas.flag-checkered" />
                    {{ $session->completed_pomodoros }} / {{ $session->target_pomodoros }} Pomodoros
                </div>
                <div class="flex items-center gap-2 text-xs">
                    <x-icon name="fas.tags" />
                    @foreach($session->tags as $tag)
                        <span class="bg-blue-100 text-blue-700 rounded px-2 py-0.5 mr-1">{{ $tag->name }}</span>
                    @endforeach
                </div>
                <div class="flex gap-2 mt-2">
                    <x-button href="{{ route('pomodoro.timer', ['session' => $session->id]) }}" wire:navigate size="sm" icon="fas.play" color="success">Start</x-button>
                    <x-button href="{{ route('pomodoro.sessions.edit', ['session' => $session->id]) }}" wire:navigate size="sm" icon="fas.edit" color="secondary">Edit</x-button>
                </div>
            </x-card>
        @empty
            <x-card class="p-6 text-center text-gray-500">No pomodoro sessions found.</x-card>
        @endforelse
    </div>
</div>
