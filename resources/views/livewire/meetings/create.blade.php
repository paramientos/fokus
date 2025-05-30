<?php
new class extends Livewire\Volt\Component {
    public $title = '';
    public $description = '';
    public $meetingType = 'daily';
    public $projectId = null;
    public $scheduledAt = '';
    public $scheduledTime = '';
    public $duration = 15;
    public $isRecurring = false;
    public $recurrencePattern = 'daily';
    public $meetingLink = '';
    public $attendees = [];
    public $availableUsers = [];
    public $projects = [];

    public function mount()
    {
        // Pre-fill meeting type if provided in URL
        $this->meetingType = request()->get('type', 'daily');
        $this->projectId = request()->get('project_id');

        // Set default date and time (tomorrow 9 AM)
        $this->scheduledAt = now()->addDay()->format('Y-m-d');
        $this->scheduledTime = '09:00';

        // Set default duration based on meeting type
        $this->duration = match($this->meetingType) {
            'daily' => 15,
            'planning' => 60,
            'retro' => 45,
            default => 30,
        };

        // Load available users and projects
        $this->availableUsers = \App\Models\User::all();
        $this->projects = \App\Models\Project::all();

        // If project is selected, pre-select team members
        if ($this->projectId) {
            $project = \App\Models\Project::find($this->projectId);
            if ($project && $project->teamMembers) {
                $this->attendees = $project->teamMembers->pluck('id')->toArray();
            }
        }
    }

    public function createMeeting()
    {
        $this->validate([
            'title' => 'required|min:3',
            'projectId' => 'required|exists:projects,id',
            'scheduledAt' => 'required|date',
            'scheduledTime' => 'required',
            'duration' => 'required|integer|min:5',
            'attendees' => 'required|array|min:1',
        ]);

        // Combine date and time
        $scheduledDateTime = \Carbon\Carbon::parse($this->scheduledAt . ' ' . $this->scheduledTime);

        // Create the meeting
        $meeting = \App\Models\Meeting::create([
            'project_id' => $this->projectId,
            'created_by' => auth()->id(),
            'title' => $this->title,
            'description' => $this->description,
            'meeting_type' => $this->meetingType,
            'scheduled_at' => $scheduledDateTime,
            'duration' => $this->duration,
            'is_recurring' => $this->isRecurring,
            'recurrence_pattern' => $this->isRecurring ? $this->recurrencePattern : null,
            'meeting_link' => $this->meetingLink,
            'status' => 'scheduled',
        ]);

        // Add attendees
        foreach ($this->attendees as $userId) {
            $meeting->users()->attach($userId, [
                'is_required' => true,
                'status' => 'pending',
            ]);
        }

        // Redirect to meeting detail page
        return redirect()->route('meetings.show', $meeting->id);
    }

    public function updatedProjectId($value)
    {
        if ($value) {
            $project = \App\Models\Project::find($value);
            if ($project && $project->teamMembers) {
                $this->attendees = $project->teamMembers->pluck('id')->toArray();
            }
        } else {
            $this->attendees = [];
        }
    }

    public function updatedMeetingType($value)
    {
        // Update duration based on meeting type
        $this->duration = match($value) {
            'daily' => 15,
            'planning' => 60,
            'retro' => 45,
            default => 30,
        };
    }
}
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold flex items-center">
            <i class="fas fa-calendar-plus mr-2 text-indigo-500"></i> Schedule New Meeting
        </h1>
    </div>

    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <form wire:submit.prevent="createMeeting">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Left Column -->
                    <div>
                        <!-- Meeting Title -->
                        <div class="mb-4">
                            <x-input id="title" wire:model="title" class="w-full" label="Meeting Title" placeholder="Enter meeting title" required />
                            @error('title') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Project -->
                        <div class="mb-4">
                            <select id="projectId" label="Project" wire:model="projectId" class="select select-bordered w-full">
                                <option value="">Select a project</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                                @endforeach
                            </select>
                            @error('projectId') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Meeting Type -->
                        <div class="mb-4">
                            <select id="meetingType" label="Meeting Type" wire:model="meetingType" class="select select-bordered w-full">
                                <option value="daily">Daily Standup</option>
                                <option value="planning">Sprint Planning</option>
                                <option value="retro">Retrospective</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <!-- Date and Time -->
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <x-input type="date" label="Date" id="scheduledAt" wire:model="scheduledAt" class="w-full" required />
                                @error('scheduledAt') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <x-input type="time" label="Time" id="scheduledTime" wire:model="scheduledTime" class="w-full" required />
                                @error('scheduledTime') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Duration -->
                        <div class="mb-4">
                            <x-input type="number" label="Duration (in minutes)" id="duration" wire:model="duration" class="w-full" min="5" required />
                            @error('duration') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Recurring Meeting -->
                        <div class="mb-4">
                            <div class="form-control">
                                <label class="cursor-pointer label justify-start">
                                    <input type="checkbox" wire:model="isRecurring" class="checkbox checkbox-primary mr-2" />
                                    <span class="label-text">Recurring Meeting</span>
                                </label>
                            </div>

                            @if($isRecurring)
                                <div class="mt-2">
                                    <select label="Recurrence Pattern" id="recurrencePattern" wire:model="recurrencePattern" class="select select-bordered w-full">
                                        <option value="daily">Daily</option>
                                        <option value="weekly">Weekly</option>
                                        <option value="biweekly">Bi-weekly</option>
                                        <option value="monthly">Monthly</option>
                                    </select>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div>
                        <!-- Meeting Link -->
                        <div class="mb-4">
                            <x-input id="meetingLink" label="Meeting Link" wire:model="meetingLink" class="w-full" placeholder="Zoom, Google Meet, etc." />
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <x-textarea label="Description" id="description" wire:model="description" class="w-full" rows="4" placeholder="Meeting agenda and details"></x-textarea>
                        </div>

                        <!-- Attendees -->
                        <div class="mb-4">
                            <label>Attendees</label>
                            <div class="border rounded-lg p-3 max-h-64 overflow-y-auto">
                                @foreach($availableUsers as $user)
                                    <div class="form-control">
                                        <label class="cursor-pointer label justify-start">
                                            <input type="checkbox" wire:model="attendees" value="{{ $user->id }}" class="checkbox checkbox-sm checkbox-primary mr-2" />
                                            <span class="label-text">{{ $user->name }}</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            @error('attendees') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="mt-6 flex justify-end gap-2">
                    <x-button type="button" link="/meetings" class="btn-outline">Cancel</x-button>
                    <x-button type="submit" color="primary">Schedule Meeting</x-button>
                </div>
            </form>
        </div>
    </div>
</div>
