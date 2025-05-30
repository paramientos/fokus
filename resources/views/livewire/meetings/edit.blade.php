<?php
new class extends Livewire\Volt\Component {
    public \App\Models\Meeting $meeting;
    public $title = '';
    public $description = '';
    public $meetingType = '';
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

    public function mount($meeting)
    {
        $this->meeting = \App\Models\Meeting::with(['users'])->findOrFail($meeting);

        // Populate form fields with meeting data
        $this->title = $this->meeting->title;
        $this->description = $this->meeting->description;
        $this->meetingType = $this->meeting->meeting_type;
        $this->projectId = $this->meeting->project_id;
        $this->scheduledAt = $this->meeting->scheduled_at->format('Y-m-d');
        $this->scheduledTime = $this->meeting->scheduled_at->format('H:i');
        $this->duration = $this->meeting->duration;
        $this->isRecurring = $this->meeting->is_recurring;
        $this->recurrencePattern = $this->meeting->recurrence_pattern ?? 'daily';
        $this->meetingLink = $this->meeting->meeting_link;

        // Load attendees
        $this->attendees = $this->meeting->users->pluck('id')->toArray();

        // Load available users and projects
        $this->availableUsers = \App\Models\User::all();
        $this->projects = \App\Models\Project::all();
    }

    public function updateMeeting()
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

        // Update the meeting
        $this->meeting->update([
            'project_id' => $this->projectId,
            'title' => $this->title,
            'description' => $this->description,
            'meeting_type' => $this->meetingType,
            'scheduled_at' => $scheduledDateTime,
            'duration' => $this->duration,
            'is_recurring' => $this->isRecurring,
            'recurrence_pattern' => $this->isRecurring ? $this->recurrencePattern : null,
            'meeting_link' => $this->meetingLink,
        ]);

        // Update attendees
        $this->meeting->users()->sync($this->attendees);

        // Redirect to meeting detail page
        return redirect()->route('meetings.show', $this->meeting->id);
    }

    public function updatedProjectId($value)
    {
        if ($value) {
            $project = \App\Models\Project::find($value);
            if ($project && $project->teamMembers) {
                $this->attendees = $project->teamMembers->pluck('id')->toArray();
            }
        }
    }
}
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold flex items-center">
            <i class="fas fa-edit mr-2 text-indigo-500"></i> Edit Meeting
        </h1>
    </div>

    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <form wire:submit.prevent="updateMeeting">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Left Column -->
                    <div>
                        <!-- Meeting Title -->
                        <div class="mb-4">
                            <x-input id="title" label="Meeting Title" wire:model="title" class="w-full" placeholder="Enter meeting title" required />
                            @error('title') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Project -->
                        <div class="mb-4">
                            <select id="projectId" label="Project"  wire:model="projectId" class="select select-bordered w-full">
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
                                    <select id="recurrencePattern" label="Recurrence Pattern" wire:model="recurrencePattern" class="select select-bordered w-full">
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
                            <x-textarea id="description" label="Description" wire:model="description" class="w-full" rows="4" placeholder="Meeting agenda and details"></x-textarea>
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
                    <x-button type="button" link="/meetings/{{ $meeting->id }}" class="btn-outline">Cancel</x-button>
                    <x-button type="submit" color="primary">Update Meeting</x-button>
                </div>
            </form>
        </div>
    </div>
</div>
