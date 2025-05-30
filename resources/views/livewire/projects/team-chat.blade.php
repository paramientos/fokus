<?php
new class extends Livewire\Volt\Component {
    public \App\Models\Project $project;
    public $messages = [];
    public $messageText = '';

    public function mount($project)
    {
        $this->project = \App\Models\Project::findOrFail($project);
        // Varsayılan olarak boş, ileride mesajlar DB'den çekilecek
    }

    public function sendMessage()
    {
        if (trim($this->messageText) === '') return;
        $this->messages[] = [
            'user' => auth()->user()->name,
            'text' => $this->messageText,
            'time' => now()->format('H:i'),
        ];
        $this->messageText = '';
        // Gerçek uygulamada mesajı DB'ye kaydet
    }
}
?>

<div class="p-6">
    <h1 class="text-2xl font-bold mb-4 flex items-center">
        <i class="fas fa-comments mr-2 text-indigo-400"></i> Team Chat
    </h1>
    <div class="bg-gray-100 rounded p-4 h-72 overflow-y-auto mb-4">
        @foreach($messages as $message)
            <div class="mb-2 flex items-center">
                <x-badge color="blue" icon="fas.user">{{ $message['user'] }}</x-badge>
                <span class="ml-2">{{ $message['text'] }}</span>
                <span class="ml-auto text-xs text-gray-500">{{ $message['time'] }}</span>
            </div>
        @endforeach
    </div>
    <div class="flex gap-2">
        <x-mary-input wire:model.defer="messageText" placeholder="Type a message..." class="flex-1"/>
        <x-mary-button color="primary" wire:click="sendMessage">Send</x-mary-button>
    </div>
</div>
