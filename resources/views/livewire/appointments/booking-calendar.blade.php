<?php

namespace App\Livewire\Appointments;

use App\Models\Appointment;
use App\Models\AppointmentSlot;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new
#[Layout('components.layouts.empty')]
class extends Component {
    use Toast;

    public $selectedDate = null;
    public $availableSlots = [];
    public $selectedSlotId = null;

    // Form fields
    public $name = '';
    public $email = '';
    public $note = '';

    // Calendar navigation
    public $currentMonth;
    public $currentYear;
    public $daysInMonth = [];
    public $firstDayOfMonth;

    public string $monthName = '';

    public function mount()
    {
        $today = Carbon::today();
        $this->currentMonth = $today->month;
        $this->currentYear = $today->year;

        $this->monthName = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->format('F Y');

        $this->generateCalendar();
    }

    public function generateCalendar()
    {
        $this->daysInMonth = [];
        $date = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1);
        $this->firstDayOfMonth = $date->dayOfWeek;

        $daysInMonth = $date->daysInMonth;

        // Get days with available slots
        $daysWithSlots = AppointmentSlot::where('is_available', true)
            ->whereNull('appointments.id')
            ->leftJoin('appointments', 'appointment_slots.id', '=', 'appointments.slot_id')
            ->whereMonth('date', $this->currentMonth)
            ->whereYear('date', $this->currentYear)
            ->where('date', '>=', Carbon::today())
            ->select('date')
            ->distinct()
            ->get()
            ->pluck('date')
            ->map(function ($date) {
                return Carbon::parse($date)->day;
            })
            ->toArray();

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::createFromDate($this->currentYear, $this->currentMonth, $day);
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
        $date = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->addMonth();
        $this->currentMonth = $date->month;
        $this->currentYear = $date->year;
        $this->generateCalendar();
        $this->resetSelection();
    }

    public function prevMonth()
    {
        $date = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->subMonth();

        // Don't allow going to past months
        if ($date->lt(Carbon::today()->startOfMonth())) {
            return;
        }

        $this->currentMonth = $date->month;
        $this->currentYear = $date->year;
        $this->generateCalendar();
        $this->resetSelection();
    }

    public function selectDate($date)
    {
        $this->selectedDate = $date;
        $this->loadAvailableSlots();
        $this->selectedSlotId = null;
    }

    public function loadAvailableSlots()
    {
        if (!$this->selectedDate) {
            $this->availableSlots = [];
            return;
        }

        $this->availableSlots = AppointmentSlot::where('date', $this->selectedDate)
            ->where('is_available', true)
            ->whereDoesntHave('appointment')
            ->orderBy('start_time')
            ->get();
    }

    public function selectSlot($slotId)
    {
        $this->selectedSlotId = $slotId;
    }

    public function resetSelection()
    {
        $this->selectedDate = null;
        $this->availableSlots = [];
        $this->selectedSlotId = null;
    }

    public function bookAppointment()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'selectedSlotId' => 'required|exists:appointment_slots,id',
        ]);

        // Check if slot is still available
        $slot = AppointmentSlot::find($this->selectedSlotId);

        if (!$slot || !$slot->is_available || $slot->isBooked()) {
            $this->error('This time slot is no longer available. Please select another one.');
            $this->loadAvailableSlots();
            return;
        }

        // Create appointment
        $appointment = Appointment::create([
            'slot_id' => $this->selectedSlotId,
            'name' => $this->name,
            'email' => $this->email,
            'note' => $this->note,
            'status' => 'pending',
        ]);

        $this->success('Your appointment has been booked successfully!');

        // Redirect to confirmation page
        return redirect()->route('appointments.confirmation', $appointment);
    }
}
?>

<div class="max-w-5xl mx-auto bg-white border border-slate-200 rounded-2xl shadow-2xl p-8 mt-8">
    <h1 class="text-3xl font-bold mb-6 text-center gradient-text">
        <i class="fas fa-calendar-alt mr-2"></i> Book an Appointment
    </h1>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Calendar Section -->
        <div class="lg:col-span-2 bg-slate-50 rounded-xl p-6 border border-slate-200">
            <div class="flex justify-between items-center mb-6">
                <button wire:click="prevMonth" class="text-blue-600 hover:text-blue-500 transition-colors">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <h2 class="text-lg font-medium text-slate-700">{{ $monthName }}</h2>
                <button wire:click="nextMonth" class="text-blue-600 hover:text-blue-500 transition-colors">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>

            <div class="grid grid-cols-7 gap-1 mb-4">
                @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dayName)
                    <div class="text-center text-xs text-slate-400 py-2">{{ $dayName }}</div>
                @endforeach

                @for($i = 0; $i < $firstDayOfMonth; $i++)
                    <div class="aspect-square"></div>
                @endfor

                @foreach($daysInMonth as $day)
                    <div
                        wire:click="{!! $day['isPast'] || !$day['hasSlots'] ? '' : 'selectDate(\'' . $day['date'] . '\')' !!}"
                        class="aspect-square flex items-center justify-center rounded-lg text-sm relative
                        {{ $day['isToday'] ? 'bg-blue-100 border border-blue-400 text-blue-700' : '' }}
                        {{ $day['isPast'] ? 'text-slate-300' : '' }}
                        {{ $day['hasSlots'] ? 'font-medium' : '' }}
                        {{ $selectedDate === $day['date'] ? 'bg-blue-200 border border-blue-500' : '' }}
                        {{ !$day['isPast'] && $day['hasSlots'] ? 'cursor-pointer hover:bg-blue-100' : '' }}">
                        {{ $day['day'] }}

                        @if($day['hasSlots'])
                            <div class="absolute bottom-1 left-1/2 transform -translate-x-1/2 w-1 h-1 rounded-full bg-blue-500"></div>
                        @endif
                    </div>
                @endforeach
            </div>

            @if($selectedDate)
                <div class="border-t border-slate-200 pt-4">
                    <h3 class="text-lg font-medium mb-3 text-slate-700">
                        Available Slots for {{ Carbon::parse($selectedDate)->format('F j, Y') }}
                    </h3>

                    @if(count($availableSlots) > 0)
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2">
                            @foreach($availableSlots as $slot)
                                <button
                                    wire:click="selectSlot({{ $slot->id }})"
                                    class="py-2 px-3 rounded-lg text-center text-sm transition-colors
                                    {{ $selectedSlotId == $slot->id
                                        ? 'bg-blue-600 text-white'
                                        : 'bg-slate-200 hover:bg-blue-100 text-slate-700' }}">
                                    {{ $slot->formatted_time }}
                                </button>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6 text-slate-400">
                            <i class="fas fa-calendar-times text-2xl mb-2"></i>
                            <p>No available slots for this date.</p>
                        </div>
                    @endif
                </div>
            @else
                <div class="text-center py-8 text-slate-400 border-t border-slate-200">
                    <i class="fas fa-calendar-day text-2xl mb-2"></i>
                    <p>Please select a date to view available slots.</p>
                </div>
            @endif
        </div>

        <!-- Booking Form -->
        <div class="bg-slate-50 rounded-xl p-6 border border-slate-200">
            <h3 class="text-lg font-medium mb-4 text-slate-700">Your Information</h3>

            <form wire:submit.prevent="bookAppointment" class="space-y-4">
                <x-input label="Full Name" wire:model="name" required icon="fas.user"/>

                <x-input type="email" label="Email Address" wire:model="email" required icon="fas.envelope"/>

                <x-textarea label="Note (Optional)" wire:model="note" rows="3" icon="fas.comment"/>

                <div class="pt-2">
                    <x-button
                        type="submit"
                        label="Book Appointment"
                        icon="fas.calendar-check"
                        class="w-full"
                        :disabled="!$selectedSlotId"
                        spinner
                    />
                </div>
            </form>

            @if(!$selectedSlotId)
                <div class="mt-4 text-center text-sm text-slate-400">
                    <i class="fas fa-info-circle mr-1"></i>
                    Please select a date and time slot to book an appointment.
                </div>
            @endif
        </div>
    </div>
</div>
