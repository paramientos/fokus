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

<div>
    <x-slot:title>Sprint Calendar - {{ $project->name }}</x-slot:title>

    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <div class="flex items-center gap-2">
                <x-button link="/projects/{{ $project->id }}/sprints" icon="o-arrow-left" class="btn-ghost btn-sm" />
                <h1 class="text-2xl font-bold text-primary">Sprint Calendar</h1>
            </div>

            <div class="flex gap-2">
                <x-button link="/projects/{{ $project->id }}/sprints/create" label="Create Sprint" icon="o-plus" class="btn-primary" />
            </div>
        </div>

        <!-- Calendar Navigation -->
        <div class="card bg-base-100 shadow-xl mb-6">
            <div class="card-body">
                <div class="flex justify-between items-center">
                    <x-button wire:click="changeMonth('prev')" icon="o-chevron-left" class="btn-ghost" />

                    <h2 class="text-xl font-bold">
                        {{ \Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y') }}
                    </h2>

                    <x-button wire:click="changeMonth('next')" icon="o-chevron-right" class="btn-ghost" />
                </div>
            </div>
        </div>

        <!-- Calendar -->
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <div class="grid grid-cols-7 gap-4">
                    <!-- Gün isimleri -->
                    @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $dayName)
                        <div class="text-center font-bold">{{ $dayName }}</div>
                    @endforeach

                    <!-- Takvim günleri -->
                    @foreach($calendar as $week)
                        @foreach($week as $day)
                            <div class="min-h-[120px] border rounded-lg p-2 {{ $day['isCurrentMonth'] ? 'bg-base-100' : 'bg-base-200 opacity-50' }} {{ $day['isToday'] ? 'border-primary' : 'border-gray-200' }}">
                                <div class="text-right {{ $day['isToday'] ? 'text-primary font-bold' : '' }}">
                                    {{ $day['day'] }}
                                </div>

                                <div class="mt-1 space-y-1 overflow-y-auto max-h-[80px]">
                                    @foreach($day['sprints'] as $sprint)
                                        <a
                                            href="/projects/{{ $project->id }}/sprints/{{ $sprint->id }}"
                                            class="block text-xs p-1 rounded truncate {{ $sprint->is_completed ? 'bg-info text-info-content' : ($sprint->is_active ? 'bg-success text-success-content' : 'bg-warning text-warning-content') }}"
                                            title="{{ $sprint->name }}"
                                        >
                                            {{ $sprint->name }}
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
        <div class="mt-6 flex justify-center gap-4">
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 bg-warning rounded"></div>
                <span>Planned</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 bg-success rounded"></div>
                <span>Active</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 bg-info rounded"></div>
                <span>Completed</span>
            </div>
        </div>
    </div>
</div>
