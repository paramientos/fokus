<?php
new class extends Livewire\Volt\Component {
    public \App\Models\Project $project;

    public $notes = '';
    public $isMeetingActive = false;
    public $attendees = [];
    public $todayTasks = [];

    public function mount(\App\Models\Project $project)
    {
        $this->project = $project;
        $this->attendees = $this->project->teamMembers ?? [];
        $this->todayTasks = $this->project->tasks()->whereDate('created_at', now()->toDateString())->get();
    }

    public function startMeeting()
    {
        $this->isMeetingActive = true;
    }

    public function endMeeting()
    {
        $this->isMeetingActive = false;
        // Burada toplantı özetini kaydedebilirsiniz
    }

    public function saveNote()
    {
        // Notu kaydet (örnek, ileride Activity veya ayrı bir tabloya yazılabilir)
    }
}
?>

<div class="p-6">
    <h1 class="text-2xl font-bold mb-4 flex items-center">
        <i class="fas fa-sun mr-2 text-yellow-400"></i> Daily Meeting
    </h1>
    <div class="mb-4">
        <x-button color="green" wire:click="startMeeting" :disabled="$isMeetingActive">Start Meeting
        </x-button>
        <x-button color="red" wire:click="endMeeting" :disabled="!$isMeetingActive">End Meeting</x-button>
    </div>
    <div class="mb-4">
        <h2 class="font-semibold mb-2">Attendees</h2>
        <div class="flex flex-wrap gap-2">
            @foreach($attendees as $user)
                <x-badge color="blue" icon="fas.user">{{ $user->name }}</x-badge>
            @endforeach
        </div>
    </div>
    <div class="mb-4">
        <h2 class="font-semibold mb-2">Today's Tasks</h2>
        <ul class="list-disc ml-6">
            @foreach($todayTasks as $task)
                <li>{{ $task->title }}</li>
            @endforeach
        </ul>
    </div>
    <div class="mb-4">
        <h2 class="font-semibold mb-2">Meeting Notes</h2>
        <x-textarea wire:model.defer="notes" placeholder="Add meeting notes..."/>
        <x-button color="primary" wire:click="saveNote" class="mt-2">Save Note</x-button>
    </div>
    @if($isMeetingActive)
        <div class="mt-4 p-4 bg-blue-50 rounded">
            <i class="fas fa-users"></i> Meeting is active. You can update tasks and share notes.
        </div>
    @endif
</div>
