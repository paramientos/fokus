<?php

namespace App\Livewire;

use App\Models\Meeting;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;

class MeetingCalendarComponent extends Component
{
    public $meetings = [];

    public $currentMonth;

    public $currentYear;

    public $selectedDate = null;

    public $projectId = null;

    public $projects = [];

    public $meetingTypes = [
        'all' => 'All Types',
        'daily' => 'Daily Standup',
        'planning' => 'Sprint Planning',
        'retro' => 'Retrospective',
        'other' => 'Other',
    ];

    public $selectedType = 'all';

    public function mount($projectId = null)
    {
        $this->projectId = $projectId;
        $this->currentMonth = now()->month;
        $this->currentYear = now()->year;
        $this->selectedDate = now()->format('Y-m-d');
        $this->projects = Project::all();
        $this->loadMeetings();
    }

    public function loadMeetings()
    {
        $startDate = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->endOfMonth();

        $query = Meeting::whereBetween('scheduled_at', [$startDate, $endDate])
            ->with(['project', 'creator', 'users']);

        if ($this->projectId) {
            $query->where('project_id', $this->projectId);
        }

        if ($this->selectedType !== 'all') {
            $query->where('meeting_type', $this->selectedType);
        }

        $this->meetings = $query->get()->groupBy(function ($meeting) {
            return $meeting->scheduled_at->format('Y-m-d');
        })
            ->collect();
    }

    public function previousMonth()
    {
        $date = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->subMonth();
        $this->currentMonth = $date->month;
        $this->currentYear = $date->year;
        $this->loadMeetings();
    }

    public function nextMonth()
    {
        $date = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->addMonth();
        $this->currentMonth = $date->month;
        $this->currentYear = $date->year;
        $this->loadMeetings();
    }

    public function selectDate($date)
    {
        $this->selectedDate = $date;
    }

    public function updatedProjectId()
    {
        $this->loadMeetings();
    }

    public function updatedSelectedType()
    {
        $this->loadMeetings();
    }

    public function getCalendarDaysProperty()
    {
        $date = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1);
        $daysInMonth = $date->daysInMonth;
        $firstDayOfWeek = $date->copy()->firstOfMonth()->dayOfWeek;

        $days = [];

        // Add empty cells for days before the first day of the month
        for ($i = 0; $i < $firstDayOfWeek; $i++) {
            $days[] = [
                'day' => null,
                'date' => null,
                'isCurrentMonth' => false,
                'isToday' => false,
                'isSelected' => false,
                'meetings' => [],
            ];
        }

        // Current day
        $today = now()->day;
        $isCurrentMonth = now()->month === $this->currentMonth && now()->year === $this->currentYear;

        // Add days of the month
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::createFromDate($this->currentYear, $this->currentMonth, $day)->format('Y-m-d');
            $isToday = $isCurrentMonth && $day === $today;
            $isSelected = $date === $this->selectedDate;

            // Ensure meetings for this day is a Collection
            $dayMeetings = isset($this->meetings[$date]) ? $this->meetings[$date] : collect();

            $days[] = [
                'day' => $day,
                'date' => $date,
                'isCurrentMonth' => true,
                'isToday' => $isToday,
                'isSelected' => $isSelected,
                'meetings' => $dayMeetings,
            ];
        }

        // Add empty cells for days after the last day of the month
        $remainingCells = 42 - count($days); // 6 rows of 7 days
        for ($i = 0; $i < $remainingCells; $i++) {
            $days[] = [
                'day' => null,
                'date' => null,
                'isCurrentMonth' => false,
                'isToday' => false,
                'isSelected' => false,
                'meetings' => [],
            ];
        }

        return $days;
    }

    public function getSelectedDateMeetingsProperty()
    {
        if (!$this->selectedDate) {
            return collect();
        }

        return $this->meetings[$this->selectedDate] ?? collect();
    }

    public function render()
    {
        return view('livewire.meeting-calendar-component', [
            'calendarDays' => $this->getCalendarDaysProperty(),
            'selectedDateMeetings' => $this->getSelectedDateMeetingsProperty(),
            'currentMonthName' => Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->format('F Y'),
        ]);
    }
}
