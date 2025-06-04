<?php

use App\Models\ApiEndpoint;

new class extends Livewire\Volt\Component {
    public ApiEndpoint $apiEndpoint;

    public function mount(ApiEndpoint $apiEndpoint)
    {
        $this->apiEndpoint = $apiEndpoint;

        // Yetki kontrolü
        if ($this->apiEndpoint->user_id !== auth()->id()) {
            abort(403, 'Bu API endpoint geçmişine erişim yetkiniz yok.');
        }
    }

    public function with(): array
    {
        return [
            'historyEntries' => $this->apiEndpoint->history()->latest()->paginate(15),
        ];
    }

    public function deleteHistoryEntry($historyId)
    {
        $entry = \App\Models\ApiEndpointHistory::findOrFail($historyId);

        // Ekstra yetki kontrolü, bu geçmiş kaydı gerçekten bu endpoint'e ve kullanıcıya ait mi?
        if ($entry->api_endpoint_id !== $this->apiEndpoint->id || $this->apiEndpoint->user_id !== auth()->id()) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Bu geçmiş kaydını silme yetkiniz yok.'
            ]);
            return;
        }

        $entry->delete();
        $this->loadHistory(); // Listeyi yenile
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Geçmiş kaydı başarıyla silindi.'
        ]);
    }
}
?>

<div>
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold">API Endpoint Geçmişi</h1>
            <p class="text-gray-500">{{ $apiEndpoint->name }}</p>
        </div>

        <div>
            <a href="{{ route('api-tester.show', $apiEndpoint) }}" class="inline-flex items-center">
                <x-button variant="secondary">
                    <i class="fas fa-chevron-left mr-2"></i> Endpoint'e Dön
                </x-button>
            </a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        @if($historyEntries->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                    <tr class="bg-gray-50 text-left">
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Tarih</th>
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Metod</th>
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">URL</th>
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Durum</th>
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Süre</th>
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">İşlemler</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                    @foreach($historyEntries as $entry)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('api-tester.history-detail', $entry->id) }}"
                                   class="text-primary-600 hover:text-primary-900">
                                    {{ $entry->created_at->format('d.m.Y H:i:s') }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                        @if(strtoupper($entry->request_method) == 'GET') bg-green-100 text-green-800
                                        @elseif(strtoupper($entry->request_method) == 'POST') bg-blue-100 text-blue-800
                                        @elseif(strtoupper($entry->request_method) == 'PUT') bg-yellow-100 text-yellow-800
                                        @elseif(strtoupper($entry->request_method) == 'PATCH') bg-purple-100 text-purple-800
                                        @elseif(strtoupper($entry->request_method) == 'DELETE') bg-red-100 text-red-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ strtoupper($entry->request_method) }}
                                    </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 truncate max-w-xs"
                                     title="{{ $entry->request_url }}">{{ Str::limit($entry->request_url, 50) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-semibold
                                        @if($entry->response_status_code >= 200 && $entry->response_status_code < 300) text-green-600
                                        @elseif($entry->response_status_code >= 400) text-red-600
                                        @else text-yellow-600
                                        @endif">
                                        {{ $entry->response_status_code }}
                                    </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $entry->execution_time_ms }} ms
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex space-x-2 justify-end">
                                    <a href="{{ route('api-tester.history-detail', $entry->id) }}"
                                       class="text-primary-600 hover:text-primary-900">
                                       <x-icon name="fas.eye" class="w-4 h-4" />
                                    </a>
                                    <button wire:click="deleteHistoryEntry({{ $entry->id }})"
                                            wire:confirm="Bu geçmiş kaydını silmek istediğinizden emin misiniz?"
                                            class="text-red-600 hover:text-red-900">
                                        <x-icon name="fas.trash" class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            @if ($historyEntries->hasPages())
                <div class="p-4">
                    {{ $historyEntries->links() }}
                </div>
            @endif
        @else
            <div class="p-6 text-center">
                <div class="text-gray-500 mb-4">
                    <i class="fas fa-history text-4xl mb-2"></i>
                    <p>Bu API endpoint için henüz geçmiş kayıt bulunmamaktadır.</p>
                </div>
                <a href="{{ route('api-tester.show', $apiEndpoint) }}">
                    <x-button>
                        <i class="fas fa-play mr-2"></i> Endpoint'i Çalıştır
                    </x-button>
                </a>
            </div>
        @endif
    </div>
</div>
