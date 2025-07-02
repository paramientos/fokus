<?php
use App\Models\PomodoroTag;
use Illuminate\Support\Facades\Auth;

new class extends Livewire\Volt\Component {
    public $tags;

    public function mount()
    {
        $this->tags = PomodoroTag::where('workspace_id', session('workspace_id'))->orderBy('name')->get();
    }
};
?>
<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Pomodoro Tags</h1>
        <x-button link="{{ route('pomodoro.tags.create') }}" wire:navigate icon="fas.plus" color="primary">New Tag</x-button>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($tags as $tag)
            <x-card class="p-4 flex flex-col gap-2">
                <div class="flex items-center gap-2">
                    <x-icon name="fas.tag" :style="'color:' . $tag->color" />
                    <span class="font-semibold text-lg">{{ $tag->name }}</span>
                </div>
                <div class="flex items-center gap-2 text-xs text-gray-500">
                    <span class="rounded px-2 py-0.5" style="background: {{ $tag->color }}20; color: {{ $tag->color }}">{{ $tag->color }}</span>
                </div>
                <div class="flex gap-2 mt-2">
                    <x-button link="{{ route('pomodoro.tags.edit', ['tag' => $tag->id]) }}" wire:navigate size="sm" icon="fas.edit" color="secondary">Edit</x-button>
                    <x-button wire:click.prevent="$emit('deleteTag', {{ $tag->id }})" size="sm" icon="fas.trash" color="danger">Delete</x-button>
                </div>
            </x-card>
        @empty
            <x-card class="p-6 text-center text-gray-500">No tags found.</x-card>
        @endforelse
    </div>
</div>
