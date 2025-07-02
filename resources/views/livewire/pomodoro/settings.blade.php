<?php

use App\Models\PomodoroSetting;
use Illuminate\Support\Facades\Auth;
use Mary\Traits\Toast;

new class extends Livewire\Volt\Component {
    use Toast;

    public bool $notification = true;
    public bool $sound = true;
    public PomodoroSetting $setting;

    public function mount(): void
    {
        $userId = Auth::id();
        $workspaceId = session('workspace_id');

        $this->setting = PomodoroSetting::firstOrNew([
            'user_id' => $userId,
            'workspace_id' => $workspaceId,
        ]);

        $this->notification = $this->setting->notification ?? true;
        $this->sound = $this->setting->sound ?? true;
    }

    public function save(): void
    {
        $this->setting->notification = $this->notification;
        $this->setting->sound = $this->sound;
        $this->setting->user_id = Auth::id();
        $this->setting->workspace_id = session('workspace_id');
        $this->setting->save();
        $this->success('Settings saved!');
    }
};
?>
<div class="max-w-lg mx-auto">
    <h1 class="text-2xl font-bold mb-6">Pomodoro Settings</h1>
    <form wire:submit.prevent="save" class="space-y-5">
        <x-toggle label="Enable Notifications" wire:model.defer="notification" icon="fas.bell"/>
        <x-toggle label="Enable Sound" wire:model.defer="sound" icon="fas.volume-up"/>
        <div class="mt-4">
            <x-button type="submit" color="primary" icon="fas.save">Save</x-button>
        </div>
    </form>
</div>
