<?php

new class extends Livewire\Volt\Component {
    public string $to = '';
    public string $subject = '';
    public string $body = '';
    public string $mode = '';
    public array $attachments = [];
    public bool $sending = false;

    public function mount($to = '', $subject = '', $body = '', $mode = '')
    {
        $this->to = $to;
        $this->subject = $subject;
        $this->body = $body;
        $this->mode = $mode;
    }
    
    public function send()
    {
        $this->validate([
            'to' => 'required|email',
            'subject' => 'required',
            'body' => 'required'
        ]);
        
        $this->sending = true;
        
        try {
            $gmailService = app(App\Services\GmailService::class);
            $gmailService->sendEmail($this->to, $this->subject, $this->body, $this->attachments);
            
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Email sent successfully!'
            ]);
            
            $this->reset(['to', 'subject', 'body', 'attachments']);
            $this->dispatch('mail-sent');
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to send email: ' . $e->getMessage()
            ]);
        }
        
        $this->sending = false;
    }
    
    public function updatedAttachments()
    {
        $this->validate([
            'attachments.*' => 'file|max:10240', // max 10MB
        ]);
    }
}; ?>

<div class="flex-1 flex flex-col bg-white">
    <div class="border-b px-6 py-2">
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-medium">
    @if($mode === 'reply')
        Yanıtla
    @elseif($mode === 'forward')
        İlet
    @else
        New Message
    @endif
</h3>
            <x-button wire:click="$dispatch('mail-compose-cancelled')" variant="ghost">
                <x-icon name="fas.times" class="w-4 h-4" />
            </x-button>
        </div>
    </div>

    <div class="flex-1 p-6">
        <form wire:submit="send" class="space-y-4">
            <div>
                @if($mode === 'reply')
    <x-input
        wire:model="to"
        type="email"
        placeholder="To"
        class="w-full"
        disabled
    />
@else
    <x-input
        wire:model="to"
        type="email"
        placeholder="To"
        class="w-full"
    />
@endif
            </div>

            <div>
                <x-input
                    wire:model="subject"
                    type="text"
                    placeholder="Subject"
                    class="w-full"
                />
            </div>

            <div class="flex-1">
                <x-textarea
                    wire:model="body"
                    placeholder="Write your message..."
                    class="w-full h-64"
                />
            </div>

            <div>
                <x-input
                    type="file"
                    wire:model="attachments"
                    multiple
                    class="w-full"
                />
                
                @if(!empty($attachments))
                    <div class="mt-2 space-y-2">
                        @foreach($attachments as $attachment)
                            <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                <span class="text-sm">{{ $attachment->getClientOriginalName() }}</span>
                                <x-button
                                    wire:click="$set('attachments', [])"
                                    size="sm"
                                    variant="ghost"
                                >
                                    <x-icon name="fas.times" class="w-4 h-4" />
                                </x-button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="flex justify-between items-center pt-4">
                <div class="flex space-x-2">
                    <x-button type="submit" :disabled="$sending">
                        <x-icon name="fas.paper-plane" class="w-4 h-4 mr-2" />
                        Send
                    </x-button>
                    
                    <x-button wire:click="$dispatch('mail-compose-cancelled')" variant="secondary">
                        <x-icon name="fas.times" class="w-4 h-4 mr-2" />
                        Cancel
                    </x-button>
                </div>
                
                @if($sending)
                    <x-spinner />
                @endif
            </div>
        </form>
    </div>
</div>
