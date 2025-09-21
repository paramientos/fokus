<?php

new class extends Livewire\Volt\Component {
    public $project;
    public $month;
    public $year;
    public $calendar = [];
    public $sprints = [];
    public $sprintsByDate = [];

    public function mount($project)
    {
        $this->project = \App\Models\Project::findOrFail($project);

        // Varsayılan olarak mevcut ay ve yıl
        $this->month = now()->month;
        $this->year = now()->year;

        $this->loadSprints();
        $this->generateCalendar();
    }

    public function loadSprints()
    {
        $this->sprints = \App\Models\Sprint::where('project_id', $this->project->id)
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->get();

        // Sprint'leri tarihlere göre grupla
        $this->sprintsByDate = [];
        foreach ($this->sprints as $sprint) {
            $startDate = $sprint->start_date->copy();
            $endDate = $sprint->end_date->copy();

            // Sprint'in her günü için bir giriş ekle
            while ($startDate->lte($endDate)) {
                $dateKey = $startDate->format('Y-m-d');
                if (!isset($this->sprintsByDate[$dateKey])) {
                    $this->sprintsByDate[$dateKey] = [];
                }
                $this->sprintsByDate[$dateKey][] = $sprint;
                $startDate->addDay();
            }
        }
    }

    public function generateCalendar()
    {
        $this->calendar = [];

        // Ayın ilk günü
        $firstDay = \Carbon\Carbon::createFromDate($this->year, $this->month, 1);

        // Ayın son günü
        $lastDay = \Carbon\Carbon::createFromDate($this->year, $this->month, 1)->endOfMonth();

        // Takvimin başlangıç günü (haftanın ilk günü)
        $startDay = $firstDay->copy()->startOfWeek();

        // Takvimin bitiş günü (haftanın son günü)
        $endDay = $lastDay->copy()->endOfWeek();

        // Takvim haftalarını oluştur
        $currentDay = $startDay->copy();
        $week = [];

        while ($currentDay->lte($endDay)) {
            $dateKey = $currentDay->format('Y-m-d');
            $isCurrentMonth = $currentDay->month === (int)$this->month;

            $week[] = [
                'date' => $currentDay->copy(),
                'day' => $currentDay->day,
                'isCurrentMonth' => $isCurrentMonth,
                'isToday' => $currentDay->isToday(),
                'hasSprints' => isset($this->sprintsByDate[$dateKey]),
                'sprints' => $this->sprintsByDate[$dateKey] ?? [],
            ];

            // Haftanın son günü ise, haftayı takvime ekle ve yeni hafta başlat
            if ($currentDay->dayOfWeek === \Carbon\Carbon::SUNDAY) {
                $this->calendar[] = $week;
                $week = [];
            }

            $currentDay->addDay();
        }

        // Son haftayı ekle
        if (!empty($week)) {
            $this->calendar[] = $week;
        }
    }

    public function changeMonth($direction)
    {
        $date = \Carbon\Carbon::createFromDate($this->year, $this->month, 1);

        if ($direction === 'prev') {
            $date->subMonth();
        } else {
            $date->addMonth();
        }

        $this->month = $date->month;
        $this->year = $date->year;

        $this->generateCalendar();
    }
}

?>

<div class="bg-gradient-to-br from-base-100 to-base-200 min-h-screen">
    <x-slot:title>Sprint Calendar - {{ $project->name }}</x-slot:title>

    <div class="max-w-7xl mx-auto p-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div class="flex items-center gap-3">
                <x-button 
                    link="/projects/{{ $project->id }}/sprints" 
                    icon="fas.arrow-left" 
                    class="btn-ghost btn-sm hover:bg-base-200 transition-all duration-200"
                    tooltip="Back to Sprints"
                />
                <div>
                    <h1 class="text-2xl font-bold text-primary">Sprint Calendar</h1>
                    <p class="text-sm text-base-content/70">{{ $project->name }}</p>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <x-button 
                    link="/projects/{{ $project->id }}/sprints/create" 
                    label="Create Sprint" 
                    icon="fas.plus" 
                    class="btn-primary hover:shadow-md transition-all duration-300"
                />
            </div>
        </div>

        <!-- Calendar Navigation -->
        <div class="bg-base-100 rounded-xl shadow-xl border border-base-300 overflow-hidden mb-6">
            <div class="bg-primary/5 p-4 border-b border-base-300 flex items-center gap-3">
                <span class="p-2 rounded-full bg-primary/10 text-primary">
                    <i class="fas fa-calendar-alt text-lg"></i>
                </span>
                <h2 class="text-xl font-semibold">Sprint Schedule</h2>
            </div>
            
            <div class="p-6">
                <div class="flex justify-between items-center">
                    <x-button 
                        wire:click="changeMonth('prev')" 
                        icon="fas.chevron-left" 
                        class="btn-ghost hover:bg-base-200 transition-all duration-200"
                        tooltip="Previous Month"
                    />

                    <h2 class="text-xl font-bold">
                        {{ \Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y') }}
                    </h2>

                    <x-button 
                        wire:click="changeMonth('next')" 
                        icon="fas.chevron-right" 
                        class="btn-ghost hover:bg-base-200 transition-all duration-200"
                        tooltip="Next Month"
                    />
                </div>
            </div>
        </div>

        <!-- Calendar -->
        <div class="bg-base-100 rounded-xl shadow-xl border border-base-300 overflow-hidden mb-6">
            <div class="p-6">
                <div class="grid grid-cols-7 gap-2">
                    <!-- Day Names -->
                    @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dayName)
                        <div class="text-center font-medium text-base-content/70 p-2 text-sm">
                            {{ $dayName }}
                        </div>
                    @endforeach

                    <!-- Calendar Days -->
                    @foreach($calendar as $week)
                        @foreach($week as $day)
                            <div 
                                class="min-h-[120px] border rounded-lg p-2 transition-all duration-200
                                {{ $day['isCurrentMonth'] ? 'bg-base-100' : 'bg-base-200/30' }} 
                                {{ $day['isToday'] ? 'border-primary shadow-md' : 'border-base-300 hover:border-primary/30' }}"
                            >
                                <div class="flex justify-between items-center mb-1">
                                    <div class="text-xs text-base-content/50">
                                        {{ $day['date']->format('M') }}
                                    </div>
                                    <div class="{{ $day['isToday'] ? 'bg-primary text-primary-content w-6 h-6 rounded-full flex items-center justify-center font-medium' : 'text-right' }}">
                                        {{ $day['day'] }}
                                    </div>
                                </div>

                                <div class="mt-1 space-y-1 overflow-y-auto max-h-[80px]">
                                    @foreach($day['sprints'] as $sprint)
                                        <a
                                            href="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}"
                                            class="block text-xs p-1.5 rounded-md truncate transition-all duration-200 hover:shadow-sm
                                                {{ $sprint->is_completed ? 'bg-info/20 text-info border border-info/20 hover:bg-info/30' : 
                                                   ($sprint->is_active ? 'bg-success/20 text-success border border-success/20 hover:bg-success/30' : 
                                                   'bg-warning/20 text-warning border border-warning/20 hover:bg-warning/30') }}"
                                            title="{{ $sprint->name }}"
                                        >
                                            <div class="flex items-center gap-1">
                                                @if($sprint->is_completed)
                                                    <i class="fas fa-check-circle text-xs"></i>
                                                @elseif($sprint->is_active)
                                                    <i class="fas fa-play-circle text-xs"></i>
                                                @else
                                                    <i class="fas fa-clock text-xs"></i>
                                                @endif
                                                <span>{{ $sprint->name }}</span>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Legend -->
        <div class="bg-base-100 rounded-xl shadow-md border border-base-300 overflow-hidden">
            <div class="p-4 flex flex-wrap justify-center gap-6">
                <div class="flex items-center gap-2">
                    <div class="flex items-center justify-center w-6 h-6 bg-warning/20 text-warning border border-warning/20 rounded-md">
                        <i class="fas fa-clock text-xs"></i>
                    </div>
                    <span>Planned</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="flex items-center justify-center w-6 h-6 bg-success/20 text-success border border-success/20 rounded-md">
                        <i class="fas fa-play-circle text-xs"></i>
                    </div>
                    <span>Active</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="flex items-center justify-center w-6 h-6 bg-info/20 text-info border border-info/20 rounded-md">
                        <i class="fas fa-check-circle text-xs"></i>
                    </div>
                    <span>Completed</span>
                </div>
            </div>
        </div>
    </div>
</div>
