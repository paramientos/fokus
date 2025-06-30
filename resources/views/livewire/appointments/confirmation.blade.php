<?php

namespace App\Livewire\Appointments;

use App\Models\Appointment;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new
#[Layout('components.layouts.empty')]
class extends Component {
    public Appointment $appointment;

    public function mount(Appointment $appointment): void
    {
        $this->appointment = $appointment;
    }
}
?>

<div class="max-w-2xl mx-auto bg-white border border-slate-200 rounded-2xl shadow-2xl p-8 mt-16">
    <div class="text-center mb-6">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 mb-4">
            <i class="fas fa-check-circle text-3xl text-green-500"></i>
        </div>
        <h1 class="text-2xl font-bold gradient-text text-slate-800">Appointment Confirmed!</h1>
        <p class="text-slate-500 mt-2">Your appointment request has been received</p>
    </div>

    <div class="bg-slate-50 rounded-xl p-6 border border-slate-200 mb-6">
        <h2 class="text-lg font-medium mb-4 flex items-center text-slate-700">
            <i class="fas fa-info-circle text-blue-400 mr-2"></i> Appointment Details
        </h2>

        <div class="space-y-4">
            <div class="flex justify-between border-b border-slate-200 pb-2">
                <span class="text-slate-400">Name:</span>
                <span class="font-medium text-slate-800">{{ $appointment->name }}</span>
            </div>

            <div class="flex justify-between border-b border-slate-200 pb-2">
                <span class="text-slate-400">Date:</span>
                <span
                    class="font-medium text-slate-800">{{ \Carbon\Carbon::parse($appointment->slot->date)->format('F j, Y') }}</span>
            </div>

            <div class="flex justify-between border-b border-slate-200 pb-2">
                <span class="text-slate-400">Time:</span>
                <span class="font-medium text-slate-800">{{ $appointment->slot->formatted_time }}</span>
            </div>

            <div class="flex justify-between border-b border-slate-200 pb-2">
                <span class="text-slate-400">Status:</span>
                <span class="font-medium">
                    @if($appointment->status === 'pending')
                        <span class="text-yellow-600">
                            <i class="fas fa-clock mr-1"></i> Pending
                        </span>
                    @elseif($appointment->status === 'confirmed')
                        <span class="text-green-600">
                            <i class="fas fa-check-circle mr-1"></i> Confirmed
                        </span>
                    @elseif($appointment->status === 'cancelled')
                        <span class="text-red-600">
                            <i class="fas fa-times-circle mr-1"></i> Cancelled
                        </span>
                    @elseif($appointment->status === 'completed')
                        <span class="text-blue-600">
                            <i class="fas fa-check-double mr-1"></i> Completed
                        </span>
                    @endif
                </span>
            </div>

            @if($appointment->note)
                <div class="flex justify-between border-b border-slate-200 pb-2">
                    <span class="text-slate-400">Note:</span>
                    <span class="font-medium text-slate-800">{{ $appointment->note }}</span>
                </div>
            @endif
        </div>
    </div>

    <div class="flex justify-center">
        <a href="{{ route('appointments.book') }}"
           class="inline-flex items-center px-5 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i> Book Another Appointment
        </a>
    </div>
</div>
