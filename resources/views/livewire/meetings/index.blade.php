<?php
new class extends Livewire\Volt\Component {
    public $meetings = [];
    public $upcomingMeetings = [];
    public $projectId = null;
    public $selectedDate = null;
    public $viewMode = 'calendar'; // calendar, list
    public $projects = [];
    public $selectedType = 'all';
    public $meetingTypes = [
        'all' => 'All Types',
        'daily' => 'Daily Standup',
        'planning' => 'Sprint Planning',
        'retro' => 'Retrospective',
        'other' => 'Other'
    ];

    public function mount($projectId = null)
    {
        $this->projectId = $projectId;
        $this->projects = \App\Models\Project::all();
        $this->selectedDate = now()->format('Y-m-d');
        $this->loadMeetings();
    }

    public function loadMeetings()
    {
        $query = \App\Models\Meeting::with(['project', 'creator', 'users']);

        if ($this->projectId) {
            $query->where('project_id', $this->projectId);
        }

        if ($this->viewMode === 'calendar') {
            // For calendar view, get meetings for the current month
            $startDate = now()->startOfMonth();
            $endDate = now()->endOfMonth();
            $query->whereBetween('scheduled_at', [$startDate, $endDate]);
        } else {
            // For list view, get upcoming meetings
            $query->upcoming();
        }

        $this->meetings = $query->get();
        $this->upcomingMeetings = \App\Models\Meeting::upcoming()
            ->with(['project', 'creator'])
            ->when($this->projectId, function ($q) {
                return $q->where('project_id', $this->projectId);
            })
            ->take(5)
            ->get();
    }

    public function changeViewMode($mode)
    {
        $this->viewMode = $mode;
        $this->loadMeetings();
    }

    public function selectDate($date)
    {
        $this->selectedDate = $date;
        $this->dispatch('date-selected', date: $date);
    }

    public function exportCalendar($format)
    {
        $url = '/meetings/export/' . $format;

        if ($this->projectId) {
            $url .= '/' . $this->projectId;
        }

        return redirect($url);
    }

    public function getMeetingsProperty()
    {
        $query = \App\Models\Meeting::with(['project', 'creator', 'users'])
            ->orderBy('scheduled_at', 'desc');

        if ($this->projectId) {
            $query->where('project_id', $this->projectId);
        }

        if ($this->selectedType !== 'all') {
            $query->where('meeting_type', $this->selectedType);
        }

        return $query->get()->groupBy(function($meeting) {
            return $meeting->scheduled_at->format('Y-m-d');
        });
    }
}
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold flex items-center">
            <i class="fas fa-calendar-alt mr-2 text-indigo-500"></i> Meetings
        </h1>
        <div class="flex gap-2">
            <x-dropdown>
                <x-slot:trigger>
                    <x-button icon="fas.download" class="btn-outline">Export</x-button>
                </x-slot:trigger>

                <x-menu>
                    <x-menu-item link="{{ route('meetings.export.ics', ['id' => $projectId]) }}" icon="fas.calendar-alt">
                        Export to iCalendar
                    </x-menu-item>
                    <x-menu-item link="{{ route('meetings.export.csv', ['id' => $projectId]) }}" icon="fas.file-csv">
                        Export to CSV
                    </x-menu-item>
                </x-menu>
            </x-dropdown>
            <x-button link="/meetings/create" icon="fas.plus" color="primary">New Meeting</x-button>
            <div class="btn-group">
                <x-button wire:click="changeViewMode('calendar')" :class="$viewMode === 'calendar' ? 'btn-active' : ''" icon="fas.calendar">Calendar</x-button>
                <x-button wire:click="changeViewMode('list')" :class="$viewMode === 'list' ? 'btn-active' : ''" icon="fas.list">List</x-button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Main Content Area -->
        <div class="md:col-span-2">
            @if($viewMode === 'calendar')
                <livewire:meeting-calendar-component :project-id="$projectId" />
            @else
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h2 class="card-title mb-4">Upcoming Meetings</h2>
                        <div class="overflow-x-auto">
                            <table class="table w-full">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Project</th>
                                        <th>Type</th>
                                        <th>Date & Time</th>
                                        <th>Duration</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($meetings as $meeting)
                                        <tr>
                                            <td>
                                                <a href="/meetings/{{ $meeting->id }}" class="font-medium hover:text-primary">
                                                    {{ $meeting->title }}
                                                </a>
                                            </td>
                                            <td>{{ $meeting->project->name }}</td>
                                            <td>
                                                <x-badge :color="match($meeting->meeting_type) {
                                                    'daily' => 'success',
                                                    'planning' => 'info',
                                                    'retro' => 'secondary',
                                                    default => 'neutral',
                                                }">
                                                    {{ ucfirst($meeting->meeting_type) }}
                                                </x-badge>
                                            </td>
                                            <td>{{ $meeting->scheduled_at->format('M d, Y H:i') }}</td>
                                            <td>{{ $meeting->duration }} min</td>
                                            <td class="flex gap-1">
                                                <x-button link="/meetings/{{ $meeting->id }}" icon="fas.eye" size="sm" class="btn-ghost btn-sm"></x-button>
                                                <x-button link="/meetings/{{ $meeting->id }}/edit" icon="fas.edit" size="sm" class="btn-ghost btn-sm"></x-button>
                                                @if($meeting->canBeJoined())
                                                    <x-button link="{{ route('meetings.join', $meeting->id) }}" icon="fas.video" size="sm" color="success">
                                                        {{ $meeting->isInProgress() ? 'Join' : 'Start' }}
                                                    </x-button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach

                                    @if($meetings->isEmpty())
                                        <tr>
                                            <td colspan="6" class="text-center py-4">No upcoming meetings found.</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Selected Day Meetings -->
            @if($viewMode === 'calendar' && $selectedDate)
                <div class="card bg-base-100 shadow-xl mt-6">
                    <div class="card-body">
                        <h2 class="card-title mb-4">
                            Meetings on {{ \Carbon\Carbon::parse($selectedDate)->format('F d, Y') }}
                        </h2>
                        <div class="overflow-x-auto">
                            <table class="table w-full">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Title</th>
                                        <th>Type</th>
                                        <th>Project</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $dayMeetings = $meetings->filter(function($meeting) {
                                            return $meeting->scheduled_at->format('Y-m-d') === $this->selectedDate;
                                        })->sortBy('scheduled_at');
                                    @endphp

                                    @foreach($dayMeetings as $meeting)
                                        <tr>
                                            <td>{{ $meeting->scheduled_at->format('H:i') }}</td>
                                            <td>
                                                <a href="/meetings/{{ $meeting->id }}" class="font-medium hover:text-primary">
                                                    {{ $meeting->title }}
                                                </a>
                                            </td>
                                            <td>
                                                <x-badge :color="match($meeting->meeting_type) {
                                                    'daily' => 'success',
                                                    'planning' => 'info',
                                                    'retro' => 'secondary',
                                                    default => 'neutral',
                                                }">
                                                    {{ ucfirst($meeting->meeting_type) }}
                                                </x-badge>
                                            </td>
                                            <td>{{ $meeting->project->name }}</td>
                                            <td class="flex gap-1">
                                                <x-button link="/meetings/{{ $meeting->id }}" icon="fas.eye" size="sm" class="btn-ghost btn-sm"></x-button>
                                                <x-button link="/meetings/{{ $meeting->id }}/edit" icon="fas.edit" size="sm" class="btn-ghost btn-sm"></x-button>
                                                @if($meeting->canBeJoined())
                                                    <x-button link="{{ route('meetings.join', $meeting->id) }}" icon="fas.video" size="sm" color="success">
                                                        {{ $meeting->isInProgress() ? 'Join' : 'Start' }}
                                                    </x-button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach

                                    @if($dayMeetings->isEmpty())
                                        <tr>
                                            <td colspan="5" class="text-center py-4">No meetings scheduled for this day.</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="md:col-span-1">
            <!-- Upcoming Meetings Widget -->
            <div class="card bg-base-100 shadow-xl mb-6">
                <div class="card-body">
                    <h2 class="card-title">Upcoming Meetings</h2>
                    <ul class="divide-y">
                        @foreach($upcomingMeetings as $meeting)
                            <li class="py-2">
                                <a href="/meetings/{{ $meeting->id }}" class="block hover:bg-base-200 rounded p-2 transition-colors">
                                    <div class="font-medium">{{ $meeting->title }}</div>
                                    <div class="text-sm text-gray-500">
                                        {{ $meeting->scheduled_at->format('M d, Y H:i') }} ({{ $meeting->duration }} min)
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        {{ $meeting->project->name }}
                                    </div>
                                </a>
                            </li>
                        @endforeach

                        @if($upcomingMeetings->isEmpty())
                            <li class="py-4 text-center text-gray-500">No upcoming meetings</li>
                        @endif
                    </ul>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">Quick Actions</h2>
                    <div class="flex flex-col gap-2 mt-2">
                        <x-button link="/meetings/create?type=daily" icon="fas.sun" class="w-full justify-start">
                            Schedule Daily Meeting
                        </x-button>
                        <x-button link="/meetings/create?type=planning" icon="fas.tasks" class="w-full justify-start">
                            Schedule Planning Meeting
                        </x-button>
                        <x-button link="/meetings/create?type=retro" icon="fas.history" class="w-full justify-start">
                            Schedule Retrospective
                        </x-button>
                        <x-button link="/meetings/create" icon="fas.plus" class="w-full justify-start">
                            Schedule Other Meeting
                        </x-button>
                    </div>
                </div>
            </div>

            <!-- Project Filter -->
            <div class="card bg-base-100 shadow-xl mt-6">
                <div class="card-body">
                    <h2 class="card-title">Project Filter</h2>
                    <select wire:model.live="projectId" class="select select-bordered w-full">
                        <option value="">All Projects</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Meeting Type Filter -->
            <div class="card bg-base-100 shadow-xl mt-6">
                <div class="card-body">
                    <h2 class="card-title">Meeting Type Filter</h2>
                    <select wire:model.live="selectedType" class="select select-bordered w-full">
                        @foreach($meetingTypes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>
