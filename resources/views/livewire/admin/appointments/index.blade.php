<?php

use App\Models\Appointment;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public $search = '';
    public $status = '';
    public $dateFrom = '';
    public $dateTo = '';

    public function updateAppointmentStatus($appointmentId, $status)
    {
        $appointment = Appointment::find($appointmentId);

        if (!$appointment) {
            $this->error('Randevu bulunamadı.');
            return;
        }

        $appointment->status = $status;
        $appointment->save();

        $statusMessages = [
            'confirmed' => 'Randevu onaylandı.',
            'cancelled' => 'Randevu iptal edildi.',
            'completed' => 'Randevu tamamlandı olarak işaretlendi.',
            'pending' => 'Randevu beklemede olarak işaretlendi.',
        ];

        $this->success($statusMessages[$status] ?? 'Randevu durumu güncellendi.');
    }

    public function with(): array
    {
        $query = Appointment::with(['slot'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%')
                        ->orWhere('note', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })
            ->when($this->dateFrom, function ($query) {
                $query->whereHas('slot', function ($q) {
                    $q->where('date', '>=', $this->dateFrom);
                });
            })
            ->when($this->dateTo, function ($query) {
                $query->whereHas('slot', function ($q) {
                    $q->where('date', '<=', $this->dateTo);
                });
            })
            ->orderBy('created_at', 'desc');

        return [
            'appointments' => $query->paginate(10),
        ];
    }
}
?>

<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold mb-2 text-slate-800">Randevu Yönetimi</h1>
        <p class="text-slate-500">Tüm randevuları görüntüleyin, onaylayın veya iptal edin.</p>
    </div>

    <div class="bg-white rounded-xl p-6 border border-slate-200 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <x-input placeholder="İsim, e-posta veya not ara..." wire:model.live.debounce.300ms="search"
                     icon="fas.search"/>

            <x-select
                wire:model.live="status"
                :options="[
                    ['id' => '', 'name' => 'Tüm Durumlar'],
                    ['id' => 'pending', 'name' => 'Beklemede'],
                    ['id' => 'confirmed', 'name' => 'Onaylandı'],
                    ['id' => 'cancelled', 'name' => 'İptal Edildi'],
                    ['id' => 'completed', 'name' => 'Tamamlandı']
                ]"
                icon="fas.filter"
                placeholder="Durum Filtresi"
            />

            <x-input type="date" wire:model.live="dateFrom" icon="fas.calendar" placeholder="Başlangıç Tarihi"/>
            <x-input type="date" wire:model.live="dateTo" icon="fas.calendar" placeholder="Bitiş Tarihi"/>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                <tr class="text-left text-xs text-slate-400 border-b border-slate-200">
                    <th class="px-6 py-3">Tarih & Saat</th>
                    <th class="px-6 py-3">İsim</th>
                    <th class="px-6 py-3">E-posta</th>
                    <th class="px-6 py-3">Not</th>
                    <th class="px-6 py-3">Durum</th>
                    <th class="px-6 py-3 text-right">İşlemler</th>
                </tr>
                </thead>
                <tbody>
                @forelse($appointments as $appointment)
                    <tr class="border-b border-slate-100 hover:bg-blue-50/60">
                        <td class="px-6 py-4">
                            <div
                                class="font-medium text-slate-800">{{ \Carbon\Carbon::parse($appointment->slot->date)->format('d.m.Y') }}</div>
                            <div class="text-xs text-slate-400">{{ $appointment->slot->formatted_time }}</div>
                        </td>
                        <td class="px-6 py-4 text-slate-700">{{ $appointment->name }}</td>
                        <td class="px-6 py-4">
                            <a href="mailto:{{ $appointment->email }}" class="text-blue-600 hover:underline">
                                {{ $appointment->email }}
                            </a>
                        </td>
                        <td class="px-6 py-4 max-w-xs truncate">
                            @if($appointment->note)
                                <span title="{{ $appointment->note }}"
                                      class="text-slate-700">{{ $appointment->note }}</span>
                            @else
                                <span class="text-slate-300">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($appointment->status === 'pending')
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700 border border-yellow-200">
                                        <i class="fas fa-clock mr-1"></i> Beklemede
                                    </span>
                            @elseif($appointment->status === 'confirmed')
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 border border-green-200">
                                        <i class="fas fa-check-circle mr-1"></i> Onaylandı
                                    </span>
                            @elseif($appointment->status === 'cancelled')
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 border border-red-200">
                                        <i class="fas fa-times-circle mr-1"></i> İptal Edildi
                                    </span>
                            @elseif($appointment->status === 'completed')
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700 border border-blue-200">
                                        <i class="fas fa-check-double mr-1"></i> Tamamlandı
                                    </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end space-x-2">
                                @if($appointment->status === 'pending')
                                    <x-button
                                        wire:click="updateAppointmentStatus({{ $appointment->id }}, 'confirmed')"
                                        icon="fas.check"
                                        class="btn-xs bg-green-600 hover:bg-green-700 border-none"
                                        tooltip="Onayla"
                                        spinner
                                    />
                                    <x-button
                                        wire:click="updateAppointmentStatus({{ $appointment->id }}, 'cancelled')"
                                        icon="fas.times"
                                        class="btn-xs bg-red-600 hover:bg-red-700 border-none"
                                        tooltip="İptal Et"
                                        spinner
                                    />
                                @elseif($appointment->status === 'confirmed')
                                    <x-button
                                        wire:click="updateAppointmentStatus({{ $appointment->id }}, 'completed')"
                                        icon="fas.check-double"
                                        class="btn-xs bg-blue-600 hover:bg-blue-700 border-none"
                                        tooltip="Tamamlandı"
                                        spinner
                                    />
                                    <x-button
                                        wire:click="updateAppointmentStatus({{ $appointment->id }}, 'cancelled')"
                                        icon="fas.times"
                                        class="btn-xs bg-red-600 hover:bg-red-700 border-none"
                                        tooltip="İptal Et"
                                        spinner
                                    />
                                @elseif($appointment->status === 'cancelled' || $appointment->status === 'completed')
                                    <x-button
                                        wire:click="updateAppointmentStatus({{ $appointment->id }}, 'pending')"
                                        icon="fas.undo"
                                        class="btn-xs bg-slate-400 hover:bg-slate-500 border-none text-slate-700"
                                        tooltip="Beklemede"
                                        spinner
                                    />
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-slate-400">
                            <i class="fas fa-calendar-times text-2xl mb-2"></i>
                            <p>Randevu bulunamadı.</p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-3 border-t border-slate-200 bg-white">
            {{ $appointments->links() }}
        </div>
    </div>
</div>
