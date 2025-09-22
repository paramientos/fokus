<?php

namespace App\Http\Livewire\Conversations;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Create extends Component
{
    public $project;

    public $title = '';

    public $description = '';

    public $type = 'general';

    public $isPrivate = false;

    public $selectedParticipants = [];

    public $initialMessage = '';

    public $contextType = null;

    public $contextId = null;

    public $showModal = false;

    protected $rules = [
        'title' => 'required|min:3|max:255',
        'description' => 'nullable|max:1000',
        'type' => 'required|in:general,task,sprint,meeting,direct',
        'isPrivate' => 'boolean',
        'selectedParticipants' => 'required|array|min:1',
        'initialMessage' => 'required|min:1|max:5000',
    ];

    public function mount(Project $project, $contextType = null, $contextId = null)
    {
        $this->project = $project;
        $this->contextType = $contextType;
        $this->contextId = $contextId;

        // Varsayılan olarak mevcut kullanıcıyı ekle
        $this->selectedParticipants = [Auth::id()];
    }

    public function getAvailableParticipantsProperty()
    {
        return User::whereHas('teams', function ($query) {
            $query->where('team_id', $this->project->team_id);
        })->get();
    }

    public function getConversationTypesProperty()
    {
        return [
            'general' => 'Genel',
            'task' => 'Görev',
            'sprint' => 'Sprint',
            'meeting' => 'Toplantı',
            'direct' => 'Direkt Mesaj',
        ];
    }

    public function toggleParticipant($userId)
    {
        if (in_array($userId, $this->selectedParticipants)) {
            $this->selectedParticipants = array_diff($this->selectedParticipants, [$userId]);
        } else {
            $this->selectedParticipants[] = $userId;
        }
    }

    public function createConversation()
    {
        $this->validate();

        // Konuşma oluştur
        $conversation = new Conversation;
        $conversation->title = $this->title;
        $conversation->description = $this->description;
        $conversation->type = $this->type;
        $conversation->is_private = $this->isPrivate;
        $conversation->created_by = Auth::id();
        $conversation->project_id = $this->project->id;

        // Bağlam bilgisini ekle
        if ($this->contextType && $this->contextId) {
            $conversation->context_type = $this->contextType;
            $conversation->context_id = $this->contextId;
        }

        $conversation->last_message_at = now();
        $conversation->save();

        // Katılımcıları ekle
        foreach ($this->selectedParticipants as $userId) {
            $participant = new ConversationParticipant;
            $participant->conversation_id = $conversation->id;
            $participant->user_id = $userId;
            $participant->is_admin = ($userId === Auth::id()); // Oluşturan kişi admin olsun
            $participant->joined_at = now();
            $participant->save();
        }

        // İlk mesajı ekle
        $message = new Message;
        $message->conversation_id = $conversation->id;
        $message->user_id = Auth::id();
        $message->content = $this->initialMessage;
        $message->save();

        // Konuşma sayfasına yönlendir
        return redirect()->route('conversations.show', [
            'project' => $this->project->id,
            'conversation' => $conversation->id,
        ]);
    }

    public function toggleModal()
    {
        $this->showModal = !$this->showModal;

        if (!$this->showModal) {
            // Modal kapatıldığında formu sıfırla
            $this->reset(['title', 'description', 'type', 'isPrivate', 'initialMessage']);
            $this->selectedParticipants = [Auth::id()];
        }
    }

    public function render()
    {
        return view('livewire.conversations.create');
    }
}
