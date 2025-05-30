<?php
new class extends Livewire\Volt\Component {
    public \App\Models\Meeting $meeting;

    public $notes = '';
    public $actionItemDescription = '';
    public $actionItemAssignee = null;
    public $actionItemDueDate = null;
    public $availableUsers = [];
    public $isMeetingActive = false;

    public function mount()
    {
        $this->meeting = \App\Models\Meeting::with(['project', 'creator', 'users', 'notes', 'actionItems.assignee'])->findOrFail($this->meeting->id);
        $this->availableUsers = \App\Models\User::all();

        // Check if meeting is active
        $this->isMeetingActive = $this->meeting->status === 'in_progress';

        // Pre-fill due date with today's date
        $this->actionItemDueDate = now()->format('Y-m-d');
    }

    public function startMeeting()
    {
        $this->meeting->update(['status' => 'in_progress']);
        $this->isMeetingActive = true;
    }

    public function endMeeting()
    {
        $this->meeting->update(['status' => 'completed']);
        $this->isMeetingActive = false;
    }

    public function addNote()
    {
        $this->validate([
            'notes' => 'required|min:3',
        ]);

        $this->meeting->notes()->create([
            'user_id' => auth()->id(),
            'content' => $this->notes,
        ]);

        $this->notes = '';
    }

    public function addActionItem()
    {
        $this->validate([
            'actionItemDescription' => 'required|min:3',
            'actionItemAssignee' => 'nullable|exists:users,id',
            'actionItemDueDate' => 'nullable|date',
        ]);

        $this->meeting->actionItems()->create([
            'assigned_to' => $this->actionItemAssignee,
            'description' => $this->actionItemDescription,
            'due_date' => $this->actionItemDueDate,
            'status' => 'open',
        ]);

        $this->actionItemDescription = '';
        $this->actionItemAssignee = null;
    }

    public function updateActionItemStatus($actionItemId, $status)
    {
        $actionItem = \App\Models\MeetingActionItem::findOrFail($actionItemId);
        $actionItem->update(['status' => $status]);
    }

    public function updateAttendeeStatus($attendeeId, $status)
    {
        $this->meeting->users()->updateExistingPivot($attendeeId, ['status' => $status]);
    }
}
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold flex items-center">
                <i class="fas fa-calendar-day mr-2 text-indigo-500"></i> {{ $meeting->title }}
            </h1>
            <div class="text-sm text-gray-500 mt-1">
                {{ $meeting->project->name }} |
                <x-badge :color="match($meeting->meeting_type) {
                    'daily' => 'success',
                    'planning' => 'info',
                    'retro' => 'secondary',
                    default => 'neutral',
                }">
                    {{ ucfirst($meeting->meeting_type) }}
                </x-badge>
            </div>
        </div>

        <div class="flex gap-2">
            @if($meeting->canBeJoined())
                <x-button link="{{ route('meetings.join', $meeting->id) }}" no-wire-navigate icon="fas.video" color="success">
                    {{ $meeting->isInProgress() ? 'Join Meeting' : 'Start Meeting' }}
                </x-button>
            @endif

            @if($meeting->status === 'scheduled')
                <x-button wire:click="startMeeting" color="success" icon="fas.play">Start Meeting</x-button>
            @elseif($meeting->status === 'in_progress')
                <x-button wire:click="endMeeting" color="error" icon="fas.stop">End Meeting</x-button>
            @endif

            <x-button link="/meetings/{{ $meeting->id }}/edit" icon="fas.edit" class="btn-outline">Edit</x-button>
            <x-button link="/meetings" icon="fas.arrow-left" class="btn-outline">Back</x-button>

            <x-dropdown>
                <x-slot:trigger>
                    <x-button icon="fas.ellipsis-vertical" class="btn-sm"></x-button>
                </x-slot:trigger>

                <x-menu>
                    <x-menu-item link="{{ route('meetings.export.ics', ['id' => $meeting->id]) }}" icon="fas.calendar-alt">
                        Export to iCalendar
                    </x-menu-item>
                    <x-menu-item link="{{ route('meetings.export.csv', ['id' => $meeting->id]) }}" icon="fas.file-csv">
                        Export to CSV
                    </x-menu-item>
                </x-menu>
            </x-dropdown>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-2">
            <div class="card bg-base-100 shadow-xl mb-6">
                <div class="card-body">
                    <h2 class="card-title">Meeting Details</h2>

                    <div class="mt-4 space-y-4">
                        <div>
                            <div class="font-medium text-gray-500">Description</div>
                            <div class="mt-1">{{ $meeting->description ?: 'No description provided.' }}</div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <div class="font-medium text-gray-500">Scheduled Time</div>
                                <div class="mt-1">{{ $meeting->scheduled_at->format('F d, Y - H:i') }}</div>
                            </div>

                            <div>
                                <div class="font-medium text-gray-500">Duration</div>
                                <div class="mt-1">{{ $meeting->duration }} minutes</div>
                            </div>

                            <div>
                                <div class="font-medium text-gray-500">Status</div>
                                <div class="mt-1">
                                    <x-badge :color="match($meeting->status) {
                                        'scheduled' => 'info',
                                        'in_progress' => 'success',
                                        'completed' => 'neutral',
                                        'cancelled' => 'error',
                                    }">
                                        {{ ucfirst(str_replace('_', ' ', $meeting->status)) }}
                                    </x-badge>
                                </div>
                            </div>

                            <div>
                                <div class="font-medium text-gray-500">Created By</div>
                                <div class="mt-1">{{ $meeting->creator->name }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card bg-base-100 shadow-xl mb-6">
                <div class="card-body">
                    <h2 class="card-title">Meeting Notes</h2>

                    @if($isMeetingActive)
                        <div class="mt-4 mb-4">
                            <x-textarea wire:model="notes" placeholder="Add meeting notes..." rows="3"></x-textarea>
                            <div class="mt-2">
                                <x-button wire:click="addNote" color="primary" icon="fas.plus">Add Note</x-button>
                            </div>
                        </div>
                    @endif

                    <div class="mt-4 space-y-4">
                        @forelse($meeting->notes as $note)
                            <div class="border-l-4 border-gray-300 pl-4 py-2">
                                <div class="flex justify-between">
                                    <div class="font-medium">{{ $note->user->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $note->created_at->diffForHumans() }}</div>
                                </div>
                                <div class="mt-1">{{ $note->content }}</div>
                            </div>
                        @empty
                            <div class="text-center py-4 text-gray-500">No notes have been added yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">Action Items</h2>

                    @if($isMeetingActive)
                        <div class="mt-4 mb-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <x-input wire:model="actionItemDescription" placeholder="Action item description..." class="w-full"></x-input>
                                </div>
                                <div>
                                    <select wire:model="actionItemAssignee" class="select select-bordered w-full">
                                        <option value="">Assign to...</option>
                                        @foreach($availableUsers as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <x-input type="date" wire:model="actionItemDueDate" class="w-full"></x-input>
                                </div>
                            </div>
                            <div class="mt-2">
                                <x-button wire:click="addActionItem" color="primary" icon="fas.plus">Add Action Item</x-button>
                            </div>
                        </div>
                    @endif

                    <div class="mt-4 space-y-4">
                        @forelse($meeting->actionItems as $item)
                            <div class="flex items-center">
                                <div class="mr-4">
                                    <x-badge :color="match($item->status) {
                                        'open' => 'info',
                                        'in_progress' => 'warning',
                                        'completed' => 'success',
                                    }">
                                        {{ ucfirst($item->status) }}
                                    </x-badge>
                                </div>
                                <div>
                                    <div>{{ $item->description }}</div>
                                    <div class="text-sm text-gray-500">
                                        Assigned to: {{ $item->assignee->name ?? 'Unassigned' }}
                                        @if($item->due_date)
                                            | Due: {{ \Carbon\Carbon::parse($item->due_date)->format('M d, Y') }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4 text-gray-500">No action items have been added yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="card bg-base-100 shadow-xl mb-6">
                <div class="card-body">
                    <h2 class="card-title">Attendees</h2>

                    <div class="mt-4 space-y-2">
                        @foreach($meeting->users as $user)
                            <div class="flex justify-between items-center">
                                <div class="flex items-center">
                                    <div class="avatar placeholder mr-2">
                                        <div class="bg-neutral text-neutral-content rounded-full w-8">
                                            <span>{{ substr($user->name, 0, 1) }}</span>
                                        </div>
                                    </div>
                                    <div>
                                        <div>{{ $user->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $user->email }}</div>
                                    </div>
                                </div>
                                <div>
                                    <x-badge :color="match($user->pivot->status) {
                                        'pending' => 'warning',
                                        'accepted' => 'success',
                                        'declined' => 'error',
                                    }">
                                        {{ ucfirst($user->pivot->status) }}
                                    </x-badge>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            @if($meeting->isInProgress() || $meeting->isCompleted())
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h2 class="card-title">Meeting Timeline</h2>

                        <div class="mt-4 space-y-2">
                            @if($meeting->scheduled_at)
                                <div class="flex">
                                    <div class="mr-4 text-gray-500">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium">Scheduled</div>
                                        <div class="text-sm text-gray-500">
                                            {{ $meeting->scheduled_at->format('F d, Y - H:i') }}
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if($meeting->started_at)
                                <div class="flex">
                                    <div class="mr-4 text-green-500">
                                        <i class="fas fa-play-circle"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium">Started</div>
                                        <div class="text-sm text-gray-500">
                                            {{ $meeting->started_at->format('F d, Y - H:i') }}
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if($meeting->ended_at)
                                <div class="flex">
                                    <div class="mr-4 text-red-500">
                                        <i class="fas fa-stop-circle"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium">Ended</div>
                                        <div class="text-sm text-gray-500">
                                            {{ $meeting->ended_at->format('F d, Y - H:i') }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            Duration: {{ $meeting->started_at->diffInMinutes($meeting->ended_at) }} minutes
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
