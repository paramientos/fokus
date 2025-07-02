<?php
use App\Models\PomodoroSession;
use Illuminate\Support\Facades\Auth;

new class extends Livewire\Volt\Component {
    public $session;
    public function mount($session)
    {
        $this->session = PomodoroSession::with('tags','logs')
            ->where('id', $session)
            ->where('user_id', Auth::id())
            ->where('workspace_id', session('workspace_id'))
            ->firstOrFail();
    }
};
?>
<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold mb-4 flex items-center gap-2">
        <x-icon name="fas.clock" class="text-blue-500" />
        {{ $session->title }}
    </h1>
    <div class="mb-4 text-gray-600">{{ $session->description }}</div>
    <div class="mb-2 flex gap-4">
        <span><x-icon name="fas.flag-checkered" /> {{ $session->completed_pomodoros }} / {{ $session->target_pomodoros }} Pomodoros</span>
        <span><x-icon name="fas.calendar" /> {{ $session->started_at?->format('d M Y H:i') }}</span>
        @if($session->completed_at)
            <span><x-icon name="fas.check" /> Completed: {{ $session->completed_at->format('d M Y H:i') }}</span>
        @endif
    </div>
    <div class="mb-4 flex gap-2">
        @foreach($session->tags as $tag)
            <span class="bg-blue-100 text-blue-700 rounded px-2 py-0.5">{{ $tag->name }}</span>
        @endforeach
    </div>
    <x-card class="p-4 mb-6">
        <div class="font-semibold mb-2">Logs</div>
        <ul>
            @forelse($session->logs as $log)
                <li class="mb-1 flex gap-2 items-center">
                    <x-icon name="fas.{{ $log->type === 'work' ? 'play' : ($log->type === 'break' ? 'coffee' : 'bed') }}" />
                    <span>{{ ucfirst($log->type) }}</span>
                    <span class="text-xs text-gray-500">{{ $log->started_at?->format('H:i') }} - {{ $log->ended_at?->format('H:i') }}</span>
                    @if($log->completed)
                        <x-icon name="fas.check" class="text-green-500 ml-2" />
                    @endif
                </li>
            @empty
                <li class="text-gray-400">No logs</li>
            @endforelse
        </ul>
    </x-card>
    <div class="flex gap-2">
        <x-button link="{{ route('pomodoro.sessions.edit', ['session' => $session->id]) }}" wire:navigate color="secondary" icon="fas.edit">Edit</x-button>
        <x-button link="{{ route('pomodoro.sessions.index') }}" wire:navigate color="primary" icon="fas.arrow-left">Back</x-button>
    </div>
</div>
