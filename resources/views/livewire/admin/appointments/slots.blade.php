<?php
new class extends \Livewire\Volt\Component {
    use \Mary\Traits\Toast;
    
    public $date;
    public $startTime;
    public $endTime;
    public $duration = 30; // Dakika cinsinden
    public $slots = [];
    
    // Takvim görünümü için
    public $currentMonth;
    public $currentYear;
    public $daysInMonth = [];
    public $firstDayOfMonth;
    public $selectedDate = null;
    public $monthName = '';
    
    public function mount()
    {
        $this->date = \Carbon\Carbon::today()->format('Y-m-d');
        $this->startTime = '09:00';
        $this->endTime = '17:00';
        
        $this->currentMonth = \Carbon\Carbon::today()->month;
        $this->currentYear = \Carbon\Carbon::today()->year;
        $this->monthName = \Carbon\Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->format('F Y');
        $this->generateCalendar();
    }
    
    public function generateCalendar()
    {
        $this->daysInMonth = [];
        $date = \Carbon\Carbon::createFromDate($this->currentYear, $this->currentMonth, 1);
        $this->firstDayOfMonth = $date->dayOfWeek;
        
        $daysInMonth = $date->daysInMonth;
        
        // Get days with slots
        $daysWithSlots = \App\Models\AppointmentSlot::whereMonth('date', $this->currentMonth)
            ->whereYear('date', $this->currentYear)
            ->select('date')
            ->distinct()
            ->get()
            ->pluck('date')
            ->map(function($date) {
                return \Carbon\Carbon::parse($date)->day;
            })
            ->toArray();
            
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = \Carbon\Carbon::createFromDate($this->currentYear, $this->currentMonth, $day);
            $this->daysInMonth[] = [
                'day' => $day,
                'date' => $date->format('Y-m-d'),
                'isToday' => $date->isToday(),
                'isPast' => $date->isPast() && !$date->isToday(),
                'hasSlots' => in_array($day, $daysWithSlots),
            ];
        }
    }
    
    public function nextMonth()
    {
        $date = \Carbon\Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->addMonth();
        $this->currentMonth = $date->month;
        $this->currentYear = $date->year;
        $this->monthName = \Carbon\Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->format('F Y');
        $this->generateCalendar();
    }
    
    public function prevMonth()
    {
        $date = \Carbon\Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->subMonth();
        $this->currentMonth = $date->month;
        $this->currentYear = $date->year;
        $this->monthName = \Carbon\Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->format('F Y');
        $this->generateCalendar();
    }
    
    public function selectDate($date)
    {
        $this->selectedDate = $date;
        $this->date = $date;
        $this->loadSlots();
    }
    
    public function loadSlots()
    {
        $this->slots = \App\Models\AppointmentSlot::with('appointment')
            ->where('date', $this->selectedDate)
            ->orderBy('start_time')
            ->get();
    }
    
    public function createSlots()
    {
        $this->validate([
            'date' => 'required|date|after_or_equal:today',
            'startTime' => 'required',
            'endTime' => 'required|after:startTime',
            'duration' => 'required|integer|min:5|max:120',
        ]);
        
        $start = \Carbon\Carbon::parse($this->date . ' ' . $this->startTime);
        $end = \Carbon\Carbon::parse($this->date . ' ' . $this->endTime);
        
        $slotStart = clone $start;
        $slotsCreated = 0;
        
        while ($slotStart->lt($end)) {
            $slotEnd = (clone $slotStart)->addMinutes($this->duration);
            
            // Son slot bitiş zamanı, genel bitiş zamanını geçmemeli
            if ($slotEnd->gt($end)) {
                break;
            }
            
            // Çakışma kontrolü
            $existingSlot = \App\Models\AppointmentSlot::where('date', $this->date)
                ->where(function($query) use ($slotStart, $slotEnd) {
                    $query->whereBetween('start_time', [$slotStart->format('H:i:s'), $slotEnd->format('H:i:s')])
                        ->orWhereBetween('end_time', [$slotStart->format('H:i:s'), $slotEnd->format('H:i:s')])
                        ->orWhere(function($q) use ($slotStart, $slotEnd) {
                            $q->where('start_time', '<=', $slotStart->format('H:i:s'))
                              ->where('end_time', '>=', $slotEnd->format('H:i:s'));
                        });
                })
                ->exists();
                
            if (!$existingSlot) {
                \App\Models\AppointmentSlot::create([
                    'user_id' => auth()->id(),
                    'date' => $this->date,
                    'start_time' => $slotStart->format('H:i:s'),
                    'end_time' => $slotEnd->format('H:i:s'),
                    'is_available' => true,
                ]);
                
                $slotsCreated++;
            }
            
            $slotStart->addMinutes($this->duration);
        }
        
        if ($slotsCreated > 0) {
            $this->success($slotsCreated . ' zaman dilimi başarıyla oluşturuldu.');
            $this->loadSlots();
            $this->generateCalendar();
        } else {
            $this->error('Hiçbir zaman dilimi oluşturulamadı. Lütfen zamanları kontrol edin.');
        }
    }
    
    public function deleteSlot($slotId)
    {
        $slot = \App\Models\AppointmentSlot::find($slotId);
        
        if ($slot) {
            if ($slot->isBooked()) {
                $this->error('Bu zaman dilimi için randevu alınmış. Önce randevuyu iptal edin.');
                return;
            }
            
            $slot->delete();
            $this->success('Zaman dilimi silindi.');
            $this->loadSlots();
            $this->generateCalendar();
        }
    }
}
?>

<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold mb-2 text-slate-800">Randevu Slotları Yönetimi</h1>
        <p class="text-slate-500">Kullanıcıların randevu alabileceği zaman dilimlerini oluşturun ve yönetin.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Sol taraf: Slot oluşturma formu -->
        <div class="bg-white rounded-xl p-6 border border-slate-200">
            <h2 class="text-lg font-medium mb-4 flex items-center text-slate-700">
                <i class="fas fa-plus-circle text-blue-400 mr-2"></i> Yeni Zaman Dilimleri Oluştur
            </h2>

            <form wire:submit.prevent="createSlots" class="space-y-4">
                <x-input type="date" label="Tarih" wire:model="date" icon="fas.calendar" required min="{{ \Carbon\Carbon::today()->format('Y-m-d') }}"/>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-input type="time" label="Başlangıç Saati" wire:model="startTime" icon="fas.clock" required/>
                    <x-input type="time" label="Bitiş Saati" wire:model="endTime" icon="fas.clock" required/>
                </div>
                
                <x-input type="number" label="Slot Süresi (dakika)" wire:model="duration" icon="fas.hourglass" required min="5" max="120"/>
                
                <x-button type="submit" label="Slotları Oluştur" icon="fas.calendar-plus" class="w-full" spinner/>
            </form>
        </div>

        <div class="lg:col-span-2">
            <!-- Takvim -->
            <div class="bg-white rounded-xl p-6 border border-slate-200 mb-6">
                <div class="flex justify-between items-center mb-4">
                    <button wire:click="prevMonth" class="text-blue-600 hover:text-blue-500 transition-colors">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <h2 class="text-lg font-medium text-slate-700">{{ $monthName }}</h2>
                    <button wire:click="nextMonth" class="text-blue-600 hover:text-blue-500 transition-colors">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>

                <div class="grid grid-cols-7 gap-1">
                    @foreach(['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'] as $dayName)
                        <div class="text-center text-xs text-slate-400 py-2">{{ $dayName }}</div>
                    @endforeach

                    @for($i = 0; $i < $firstDayOfMonth; $i++)
                        <div class="aspect-square"></div>
                    @endfor

                    @foreach($daysInMonth as $day)
                        <div 
                            wire:click="{!! !$day['isPast'] ? 'selectDate(\'' . $day['date'] . '\')' : '' !!}"
                            class="aspect-square flex items-center justify-center rounded-lg text-sm relative cursor-pointer
                            {{ $day['isToday'] ? 'bg-blue-100 border border-blue-400 text-blue-700' : '' }}
                            {{ $day['isPast'] ? 'text-slate-300 cursor-not-allowed' : '' }}
                            {{ $day['hasSlots'] ? 'font-medium' : '' }}
                            {{ $selectedDate === $day['date'] ? 'bg-blue-200 border border-blue-500' : '' }}
                            {{ !$day['isPast'] ? 'hover:bg-blue-100' : '' }}">
                            {{ $day['day'] }}
                            
                            @if($day['hasSlots'])
                                <div class="absolute bottom-1 left-1/2 transform -translate-x-1/2 w-1 h-1 rounded-full bg-blue-500"></div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            @if($selectedDate)
                <div class="bg-slate-50 rounded-xl p-6 border border-slate-200">
                    <h3 class="text-lg font-medium mb-4 text-slate-700">{{ \Carbon\Carbon::parse($selectedDate)->format('d F Y') }} Tarihli Slotlar</h3>

                    @if(count($slots) > 0)
                        <div class="space-y-2">
                            @foreach($slots as $slot)
                                <div class="flex items-center justify-between p-3 rounded-lg {{ $slot->isBooked() ? 'bg-blue-50/70' : 'bg-slate-100' }} border {{ $slot->isBooked() ? 'border-amber-200' : 'border-slate-200' }}">
                                    <div>
                                        <div class="font-medium text-slate-800">{{ $slot->formatted_time }}</div>
                                        <div class="text-xs text-slate-400">{{ $slot->duration }} dakika</div>
                                    </div>
                                    
                                    <div class="flex items-center">
                                        @if($slot->isBooked())
                                            <span class="text-amber-600 text-sm mr-3">
                                                <i class="fas fa-user-clock mr-1"></i> Rezerve
                                            </span>
                                        @else
                                            <button 
                                                wire:click="deleteSlot({{ $slot->id }})" 
                                                wire:confirm="Bu slot silinecek. Emin misiniz?"
                                                class="text-red-500 hover:text-red-600 transition-colors">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8 text-slate-400">
                            <i class="fas fa-calendar-times text-2xl mb-2"></i>
                            <p>Bu tarih için henüz slot oluşturulmamış.</p>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
