<?php

new class extends Livewire\Volt\Component {
    public array $messages = [];
    public bool $loading = false;
    public ?array $selectedMessage = null;
    public bool $composing = false;
    public bool $hasToken = false;
    public ?string $errorMessage = null;

    public function mount()
    {
        $this->checkGoogleToken();
        if ($this->hasToken) {
            $this->loadMessages();
        }
    }

    public function checkGoogleToken()
    {
        $this->hasToken = \Illuminate\Support\Facades\Cache::has('google_token_' . auth()->id());
    }

    public function loadMessages()
    {
        $this->loading = true;
        $this->errorMessage = null;

        try {
            // Token kontrolü
            if (!\Illuminate\Support\Facades\Cache::has('google_token_' . auth()->id())) {
                $this->hasToken = false;
                $this->loading = false;
                return;
            }

            $token = \Illuminate\Support\Facades\Cache::get('google_token_' . auth()->id());
            $gmailService = app(App\Services\GmailService::class);
            $gmailService->setAccessToken($token);
            $this->messages = $gmailService->getInbox(20);

            $this->hasToken = true;
        } catch (\Exception $e) {
            dd($e);
            $this->errorMessage = $e->getMessage();
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to load messages: ' . $e->getMessage()
            ]);

            // Token geçersiz olabilir
            if (strpos($e->getMessage(), 'invalid') !== false ||
                strpos($e->getMessage(), 'expired') !== false ||
                strpos($e->getMessage(), 'auth') !== false) {
                $this->hasToken = false;
                \Illuminate\Support\Facades\Cache::forget('google_token_' . auth()->id());
            }
        }

        $this->loading = false;
    }

    public function viewMessage($messageId)
    {
        $this->selectedMessage = collect($this->messages)->firstWhere('id', $messageId);
    }

    public string $composeMode = '';
    public string $composeTo = '';
    public string $composeSubject = '';
    public string $composeBody = '';

    public function toggleCompose()
    {
        $this->composing = !$this->composing;
        $this->selectedMessage = null;
    }

    public function replyMessage()
    {
        $this->composing = true;
        $this->composeMode = 'reply';
        $this->composeTo = $this->selectedMessage['from'] ?? '';
        $this->composeSubject = 'Re: ' . ($this->selectedMessage['subject'] ?? '');
        $this->composeBody = "\n\n--- Orijinal Mesaj ---\n" . ($this->selectedMessage['snippet'] ?? '');
    }

    public function forwardMessage()
    {
        $this->composing = true;
        $this->composeMode = 'forward';
        $this->composeTo = '';
        $this->composeSubject = 'Fwd: ' . ($this->selectedMessage['subject'] ?? '');
        $this->composeBody = "\n\n--- İletilen Mesaj ---\n" . ($this->selectedMessage['snippet'] ?? '');
    }

    public function connectGmail()
    {
        return redirect()->route('google.redirect');
    }
}; ?>

<div class="h-screen flex">
    <!-- Sidebar -->
    <div class="w-64 bg-white border-r">
        <div class="p-4">
            <x-button class="w-full" wire:click="toggleCompose">
                <x-icon name="fas.pen" class="w-4 h-4 mr-2" />
                Compose
            </x-button>
        </div>

        <nav class="space-y-1 px-2">
            <a href="#" class="flex items-center px-2 py-2 text-sm font-medium text-primary-700 bg-primary-50 rounded-md">
                <x-icon name="fas.inbox" class="w-4 h-4 mr-3" />
                Inbox
            </a>
            <a href="#" class="flex items-center px-2 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 rounded-md">
                <x-icon name="fas.paper-plane" class="w-4 h-4 mr-3" />
                Sent
            </a>
            <a href="#" class="flex items-center px-2 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 rounded-md">
                <x-icon name="fas.star" class="w-4 h-4 mr-3" />
                Starred
            </a>
            <a href="#" class="flex items-center px-2 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 rounded-md">
                <x-icon name="fas.trash" class="w-4 h-4 mr-3" />
                Trash
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col bg-gray-50">
        @if($loading)
            <div class="flex-1 flex items-center justify-center">
                <x-spinner />
            </div>
        @elseif(!$hasToken)
            <div class="flex-1 flex flex-col items-center justify-center p-6 space-y-6">
                <div class="text-center">
                    <x-icon name="fas.envelope" class="w-16 h-16 mx-auto text-primary-500 mb-4" />
                    <h2 class="text-2xl font-bold mb-2">Gmail Hesabınızı Bağlayın</h2>
                    <p class="text-gray-600 mb-6">E-postalarınızı görüntülemek için Gmail hesabınızı bağlamanız gerekiyor.</p>

                    @if($errorMessage)
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                            <p>Hata: {{ $errorMessage }}</p>
                        </div>
                    @endif

                    <x-button wire:click="connectGmail" class="bg-primary-600 hover:bg-primary-700">
                        <x-icon name="fas.user" class="w-5 h-5 mr-2" />
                        Gmail Hesabını Bağla
                    </x-button>
                </div>
            </div>
        @elseif($composing)
            <livewire:mail.compose :to="$composeTo" :subject="$composeSubject" :body="$composeBody" :mode="$composeMode" />
        @elseif($selectedMessage)
            <div class="flex-1 p-6 space-y-4">
                <div class="flex justify-between items-start">
                    <div>
                        <h2 class="text-xl font-semibold">{{ $selectedMessage['subject'] }}</h2>
                        <p class="text-sm text-gray-600">From: {{ $selectedMessage['from'] }}</p>
                        <p class="text-sm text-gray-600">Date: {{ $selectedMessage['date'] }}</p>
                    </div>
                    <div class="flex space-x-2">
                <x-button wire:click="replyMessage" color="primary" size="sm">
                    <x-icon name="fas.reply" class="w-4 h-4 mr-1" /> Yanıtla
                </x-button>
                <x-button wire:click="forwardMessage" color="secondary" size="sm">
                    <x-icon name="fas.share" class="w-4 h-4 mr-1" /> İlet
                </x-button>
                <x-button wire:click="$set('selectedMessage', null)" variant="ghost">
                    <x-icon name="fas.times" class="w-4 h-4" />
                </x-button>
            </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="prose max-w-none">
                        {!! $selectedMessage['snippet'] !!}
                    </div>
                </div>
            </div>
        @elseif(count($messages) > 0)
            <div class="flex-1 overflow-y-auto">
                <div class="divide-y">
                    @foreach($messages as $message)
                        <div wire:click="viewMessage('{{ $message['id'] }}')" class="p-4 hover:bg-gray-50 cursor-pointer">
                            <div class="flex justify-between items-start">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $message['from'] }}</p>
                                    <p class="text-sm font-medium text-gray-900">{{ $message['subject'] }}</p>
                                    <p class="text-sm text-gray-500 truncate">{{ $message['snippet'] }}</p>
                                </div>
                                <div class="ml-3 flex-shrink-0">
                                    <p class="text-sm text-gray-500">{{ $message['date'] }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="flex-1 flex items-center justify-center">
                <div class="text-center">
                    <x-icon name="fas.inbox" class="w-12 h-12 mx-auto text-gray-400 mb-4" />
                    <h3 class="text-lg font-medium text-gray-900">Inbox Boş</h3>
                    <p class="text-gray-500">Henüz hiç mesajınız yok.</p>
                </div>
            </div>
        @endif
    </div>
</div>
