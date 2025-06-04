<?php

use App\Models\ApiEndpointHistory;

new class extends Livewire\Volt\Component {
    public ApiEndpointHistory $historyEntry;

    public function mount()
    {
        // Yetki kontrolü - Bu geçmiş kaydının ilişkili olduğu endpoint'in kullanıcısı ile aynı mı?
        if ($this->historyEntry->apiEndpoint->user_id !== auth()->id()) {
            abort(403, 'Bu API endpoint geçmiş detayına erişim yetkiniz yok.');
        }
    }

    protected function formatJson($data)
    {
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
        }
        if (is_array($data) || is_object($data)) {
            return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        return $data; // Eğer decode edilemezse veya zaten array/object değilse orijinal veriyi döndür
    }
}
?>

<div>
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold">API Çalıştırma Detayı</h1>
            <p class="text-gray-500">{{ $historyEntry->apiEndpoint->name }}
                - {{ $historyEntry->created_at->format('d.m.Y H:i:s') }}</p>
        </div>

        <div>
            <a href="{{ route('api-tester.history', $historyEntry->apiEndpoint) }}" class="inline-flex items-center">
                <x-button variant="secondary">
                    <i class="fas fa-chevron-left mr-2"></i> Geçmiş Listesine Dön
                </x-button>
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Request Details -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-xl font-semibold mb-4">İstek Detayları</h2>

            <div class="mb-4">
                <strong class="block text-gray-700">URL:</strong>
                <p class="text-gray-600 break-all">{{ $historyEntry->request_url }}</p>
            </div>

            <div class="mb-4">
                <strong class="block text-gray-700">Metod:</strong>
                <p class="text-gray-600">{{ strtoupper($historyEntry->request_method) }}</p>
            </div>

            @if($historyEntry->request_headers)
                <div class="mb-4">
                    <strong class="block text-gray-700">Headers:</strong>
                    <pre
                        class="bg-gray-100 p-3 rounded-md text-sm overflow-x-auto"><code>{{ $this->formatJson($historyEntry->request_headers) }}</code></pre>
                </div>
            @endif

            @if($historyEntry->request_body)
                <div class="mb-4">
                    <strong class="block text-gray-700">Body:</strong>
                    <pre
                        class="bg-gray-100 p-3 rounded-md text-sm overflow-x-auto"><code>{{ $this->formatJson($historyEntry->request_body) }}</code></pre>
                </div>
            @endif
        </div>

        <!-- Response Details -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-xl font-semibold mb-4">Yanıt Detayları</h2>

            <div class="mb-4">
                <strong class="block text-gray-700">Durum Kodu:</strong>
                <p class="font-semibold
                    @if($historyEntry->response_status_code >= 200 && $historyEntry->response_status_code < 300) text-green-600
                    @elseif($historyEntry->response_status_code >= 400) text-red-600
                    @else text-yellow-600
                    @endif">
                    {{ $historyEntry->response_status_code }}
                </p>
            </div>

            <div class="mb-4">
                <strong class="block text-gray-700">Çalışma Süresi:</strong>
                <p class="text-gray-600">{{ $historyEntry->execution_time_ms }} ms</p>
            </div>

            @if($historyEntry->response_headers)
                <div class="mb-4">
                    <strong class="block text-gray-700">Headers:</strong>
                    <pre
                        class="bg-gray-100 p-3 rounded-md text-sm overflow-x-auto"><code>{{ $this->formatJson($historyEntry->response_headers) }}</code></pre>
                </div>
            @endif

            @if($historyEntry->response_body)
                <div class="mb-4">
                    <strong class="block text-gray-700">Body:</strong>
                    <pre
                        class="bg-gray-100 p-3 rounded-md text-sm overflow-x-auto"><code>{{ $this->formatJson($historyEntry->response_body) }}</code></pre>
                </div>
            @endif
        </div>
    </div>
</div>
