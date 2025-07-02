<?php
use App\Models\PomodoroTag;
use Illuminate\Support\Facades\Auth;
use Mary\Traits\Toast;

new class extends Livewire\Volt\Component {
    use Toast;

    public $name;
    public $color = '#2563eb';
    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:50',
            'color' => 'required|string|max:7',
        ]);
        PomodoroTag::create([
            'workspace_id' => session('workspace_id'),
            'name' => $this->name,
            'color' => $this->color,
        ]);
        $this->success('Tag created successfully!');
        return redirect()->route('pomodoro.tags.index');
    }
};
?>
<div class="max-w-md mx-auto">
    <h1 class="text-2xl font-bold mb-6">New Pomodoro Tag</h1>
    <form wire:submit.prevent="save" class="space-y-5">
        <x-input label="Name" wire:model.defer="name" required icon="fas.tag" />
        <x-input label="Color" wire:model.defer="color" type="color" icon="fas.palette" />
        <div class="flex gap-2 mt-4">
            <x-button type="submit" color="primary" icon="fas.save">Save</x-button>
            <x-button href="{{ route('pomodoro.tags.index') }}" wire:navigate color="secondary" icon="fas.arrow-left">Cancel</x-button>
        </div>
    </form>
</div>
