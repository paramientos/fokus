<?php
new class extends Livewire\Volt\Component {
    public $apiEndpoint;
    public $response = null;
    public $isLoading = false;
    public $activeTab = 'response'; // 'response', 'headers', 'params', 'body'
    public $customHeaders = [];
    public $customParams = [];
    public $customBody = [];

    public $newHeaderKey = '';
    public $newHeaderValue = '';
    public $newParamKey = '';
    public $newParamValue = '';
    public $newBodyKey = '';
    public $newBodyValue = '';

    public bool $isEmbedded = false;

    public function mount($apiEndpoint, bool $isEmbedded = false)
    {
        $this->isEmbedded = $isEmbedded;
        $this->apiEndpoint = \App\Models\ApiEndpoint::with(['project', 'task'])->findOrFail($apiEndpoint);

        if ($this->apiEndpoint->user_id !== auth()->id()) {
            abort(403, 'Bu API endpoint\'ine erişim yetkiniz yok.');
        }

        $this->customHeaders = $this->apiEndpoint->headers ?? [];
        $this->customParams = $this->apiEndpoint->params ?? [];
        $this->customBody = $this->apiEndpoint->body ?? [];
    }

    public function execute()
    {
        $this->isLoading = true;
        $this->response = null; // Önceki yanıtı temizle

        try {
            $client = \Illuminate\Support\Facades\Http::withHeaders($this->customHeaders)
                ->withOptions([
                    'verify' => false, // Geliştirme ortamında SSL doğrulamayı kapatabilirsiniz
                    'timeout' => 30, // 30 saniye zaman aşımı
                ]);

            $url = $this->apiEndpoint->url;

            if (!empty($this->customParams)) {
                $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . http_build_query($this->customParams);
            }

            $bodyData = !empty($this->customBody) ? $this->customBody : null;

            $startTime = microtime(true);
            $httpResponse = null;

            switch (strtoupper($this->apiEndpoint->method)) {
                case 'GET':
                    $httpResponse = $client->get($url);
                    break;
                case 'POST':
                    $httpResponse = $client->post($url, $bodyData);
                    break;
                case 'PUT':
                    $httpResponse = $client->put($url, $bodyData);
                    break;
                case 'PATCH':
                    $httpResponse = $client->patch($url, $bodyData);
                    break;
                case 'DELETE':
                    $httpResponse = $client->delete($url, $bodyData);
                    break;
                default:
                    throw new \Exception('Desteklenmeyen HTTP metodu: ' . $this->apiEndpoint->method);
            }

            $endTime = microtime(true);
            $executionTime = round(($endTime - $startTime) * 1000);

            $this->response = [
                'status' => $httpResponse->status(),
                'headers' => $httpResponse->headers(),
                'body' => $httpResponse->json() ?: $httpResponse->body(),
                'execution_time' => $executionTime,
                'timestamp' => now()->toDateTimeString(),
            ];

            $this->saveToHistory($httpResponse, $executionTime, $url, $bodyData);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $this->response = [
                'error' => 'Bağlantı hatası: ' . $e->getMessage(),
                'execution_time' => $executionTime ?? 0,
                'timestamp' => now()->toDateTimeString(),
            ];
        } catch (\Exception $e) {
            $this->response = [
                'error' => 'Bir hata oluştu: ' . $e->getMessage(),
                'execution_time' => $executionTime ?? 0,
                'timestamp' => now()->toDateTimeString(),
            ];
        }

        $this->isLoading = false;
        $this->activeTab = 'response'; // Yanıt geldikten sonra yanıt sekmesini aktif et
    }

    protected function saveToHistory($httpResponse, $executionTime, $requestUrl, $requestBody)
    {
        try {
            \App\Models\ApiEndpointHistory::create([
                'api_endpoint_id' => $this->apiEndpoint->id,
                'request_url' => $requestUrl,
                'request_method' => $this->apiEndpoint->method,
                'request_headers' => $this->customHeaders,
                'request_body' => $requestBody,
                'response_status_code' => $httpResponse->status(),
                'response_headers' => $httpResponse->headers(),
                'response_body' => $httpResponse->json() ?: $httpResponse->body(),
                'execution_time_ms' => $executionTime,
                'user_id' => auth()->id(),
            ]);
        } catch (\Exception $e) {

            logger()->error('API geçmişi kaydedilirken hata oluştu: ' . $e->getMessage(), ['exception' => $e]);
        }
    }

    public function addCustomHeader()
    {
        if (empty($this->newHeaderKey)) return;

        $this->customHeaders[$this->newHeaderKey] = $this->newHeaderValue;
        $this->newHeaderKey = '';
        $this->newHeaderValue = '';
    }

    public function removeCustomHeader($key)
    {
        unset($this->customHeaders[$key]);
    }

    public function addCustomParam()
    {
        if (empty($this->newParamKey)) return;
        $this->customParams[$this->newParamKey] = $this->newParamValue;
        $this->newParamKey = '';
        $this->newParamValue = '';
    }

    public function removeCustomParam($key)
    {
        unset($this->customParams[$key]);
    }

    public function addCustomBodyField()
    {
        if (empty($this->newBodyKey)) return;
        $this->customBody[$this->newBodyKey] = $this->newBodyValue;
        $this->newBodyKey = '';
        $this->newBodyValue = '';
    }

    public function removeCustomBodyField($key)
    {
        unset($this->customBody[$key]);
    }

    public function editInWorkspace()
    {
        if ($this->apiEndpoint) {
            $this->dispatch('edit-api-endpoint', endpointId: $this->apiEndpoint->id)->to('api-tester.workspace');
        }
    }

    public function close()
    {
        $this->dispatch('close-view')->to('api-tester.workspace');
    }
}
?>

<div>
    <div class="flex justify-between items-center mb-6">
        @if(!$isEmbedded)
            <div>
                <h1 class="text-2xl font-bold">{{ $apiEndpoint->name }}</h1>
                <p class="text-gray-500">{{ $apiEndpoint->description ?? 'Açıklama yok' }}</p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('api-tester.history', $apiEndpoint) }}" class="inline-flex items-center">
                    <x-button variant="secondary">
                        <i class="fas fa-history mr-2"></i> Geçmiş
                    </x-button>
                </a>
                <a href="{{ route('api-tester.edit', $apiEndpoint) }}" class="inline-flex items-center">
                    <x-button variant="secondary">
                        <i class="fas fa-edit mr-2"></i> Düzenle
                    </x-button>
                </a>
                <a href="{{ route('api-tester.workspace') }}" class="inline-flex items-center">
                    <x-button variant="secondary">
                        <i class="fas fa-arrow-left mr-2"></i> Çalışma Alanına Dön
                    </x-button>
                </a>
            </div>
        @else
            <div class="flex-1 min-w-0">
                <h2 class="text-xl font-bold leading-7 text-gray-900 sm:text-2xl sm:truncate">
                    {{ $apiEndpoint->name }}
                </h2>
                @if($apiEndpoint->description)
                    <p class="text-sm text-gray-500 mt-1 truncate">{{ $apiEndpoint->description }}</p>
                @endif
            </div>
            <div class="flex ml-4 space-x-2">
                <x-button wire:click="editInWorkspace" variant="primary" size="sm">
                    <i class="fas fa-edit mr-1"></i> Düzenle
                </x-button>
                <a href="{{ route('api-tester.history', $apiEndpoint) }}" class="inline-flex items-center">
                    <x-button variant="secondary" size="sm">
                        <i class="fas fa-history mr-1"></i> Geçmiş
                    </x-button>
                </a>
                <x-button wire:click="close" variant="ghost" size="sm">
                    <i class="fas fa-times mr-1"></i> Kapat
                </x-button>
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Sol Panel - İstek Yapılandırması -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-sm p-6 sticky top-4">
                <div class="space-y-4">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-lg font-medium">İstek Detayları</h3>
                            <x-button wire:click="execute" label="Send" class="w-full md:w-auto" spinner />
                        </div>

                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="font-medium text-gray-500">Metod:</span>
                                <span class="px-2 py-1 text-xs font-semibold rounded
                                    @if(strtoupper($apiEndpoint->method) == 'GET') bg-green-100 text-green-800
                                    @elseif(strtoupper($apiEndpoint->method) == 'POST') bg-blue-100 text-blue-800
                                    @elseif(strtoupper($apiEndpoint->method) == 'PUT') bg-yellow-100 text-yellow-800
                                    @elseif(strtoupper($apiEndpoint->method) == 'PATCH') bg-purple-100 text-purple-800
                                    @elseif(strtoupper($apiEndpoint->method) == 'DELETE') bg-red-100 text-red-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ strtoupper($apiEndpoint->method) }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-medium text-gray-500">URL:</span>
                                <span class="text-gray-700 truncate"
                                      title="{{ $apiEndpoint->url }}">{{ Str::limit($apiEndpoint->url, 30) }}</span>
                            </div>
                            @if($apiEndpoint->project)
                                <div class="flex justify-between">
                                    <span class="font-medium text-gray-500">Proje:</span>
                                    <a href="{{ route('projects.show', $apiEndpoint->project) }}"
                                       class="text-primary-600 hover:text-primary-900">
                                        {{ $apiEndpoint->project->name }}
                                    </a>
                                </div>
                            @endif
                            @if($apiEndpoint->task)
                                <div class="flex justify-between">
                                    <span class="font-medium text-gray-500">Görev:</span>
                                    <a href="{{ route('tasks.show', ['project' => $apiEndpoint->project_id, 'task' => $apiEndpoint->task_id]) }}"
                                       class="text-primary-600 hover:text-primary-900">
                                        {{ $apiEndpoint->task->title }}
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Özelleştirilebilir Alanlar -->
                    <div class="space-y-4 pt-4 border-t">
                        <!-- Headers -->
                        <div>
                            <h4 class="font-medium mb-2">Headers</h4>
                            @if(count($customHeaders) > 0)
                                @foreach($customHeaders as $key => $value)
                                    <div class="flex items-center justify-between text-sm mb-1 p-1 bg-gray-50 rounded">
                                        <span><strong>{{ $key }}:</strong> {{ $value }}</span>
                                        <x-button type="button" wire:click="removeCustomHeader('{{ $key }}')"
                                                  class="text-red-500 hover:text-red-700 ml-2"  icon="fas.times"/>
                                    </div>
                                @endforeach
                            @else
                                <p class="text-xs text-gray-400 italic">Varsayılan header yok.</p>
                            @endif
                            <div class="flex space-x-1 mt-2">
                                <x-input wire:model="newHeaderKey" placeholder="Header Adı" class="text-sm flex-1"/>
                                <x-input wire:model="newHeaderValue" placeholder="Değer" class="text-sm flex-1"/>
                                <x-button wire:click="addCustomHeader" size="sm" icon="fas.plus"/>
                            </div>
                        </div>

                        <!-- URL Params -->
                        <div>
                            <h4 class="font-medium mb-2">URL Parametreleri</h4>
                            @if(count($customParams) > 0)
                                @foreach($customParams as $key => $value)
                                    <div class="flex items-center justify-between text-sm mb-1 p-1 bg-gray-50 rounded">
                                        <span><strong>{{ $key }}:</strong> {{ $value }}</span>
                                        <x-button type="button" wire:click="removeCustomParam('{{ $key }}')"
                                                class="text-red-500 hover:text-red-700 ml-2"  icon="fas.times"/>
                                    </div>
                                @endforeach
                            @else
                                <p class="text-xs text-gray-400 italic">Varsayılan URL parametresi yok.</p>
                            @endif
                            <div class="flex space-x-1 mt-2">
                                <x-input wire:model="newParamKey" placeholder="Parametre Adı" class="text-sm flex-1"/>
                                <x-input wire:model="newParamValue" placeholder="Değer" class="text-sm flex-1"/>
                                <x-button wire:click="addCustomParam" size="sm" icon="fas.plus"/>
                            </div>
                        </div>

                        <!-- Body (JSON) -->
                        @if(in_array(strtoupper($apiEndpoint->method), ['POST', 'PUT', 'PATCH']))
                            <div>
                                <h4 class="font-medium mb-2">İstek Gövdesi (JSON)</h4>
                                @if(count($customBody) > 0)
                                    @foreach($customBody as $key => $value)
                                        <div
                                            class="flex items-center justify-between text-sm mb-1 p-1 bg-gray-50 rounded">
                                            <span><strong>{{ $key }}:</strong> {{ is_array($value) ? json_encode($value) : $value }}</span>
                                            <button type="button" wire:click="removeCustomBodyField('{{ $key }}')"
                                                    class="text-red-500 hover:text-red-700 ml-2"><i
                                                    class="fas fa-times"></i></button>
                                        </div>
                                    @endforeach
                                @else
                                    <p class="text-xs text-gray-400 italic">Varsayılan body alanı yok.</p>
                                @endif
                                <div class="flex space-x-1 mt-2">
                                    <x-input wire:model="newBodyKey" placeholder="Alan Adı" class="text-sm flex-1"/>
                                    <x-input wire:model="newBodyValue" placeholder="Değer (JSON)"
                                             class="text-sm flex-1"/>
                                    <x-button wire:click="addCustomBodyField" size="sm"><i class="fas fa-plus"></i>
                                    </x-button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Sağ Panel - Yanıt -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-sm">
                @if($isLoading)
                    <div class="p-6 text-center">
                        <i class="fas fa-spinner fa-spin text-4xl text-primary-500 mb-3"></i>
                        <p class="text-gray-600">API isteği gönderiliyor...</p>
                    </div>
                @elseif($response)
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium">Yanıt</h3>
                            <div class="text-sm text-gray-500">
                                <span class="mr-2">
                                    Durum:
                                    <span class="font-semibold
                                        @if($response['status'] >= 200 && $response['status'] < 300) text-green-600
                                        @elseif($response['status'] >= 400) text-red-600
                                        @else text-yellow-600
                                        @endif">
                                        {{ $response['status'] }}
                                    </span>
                                </span>
                                <span class="mr-2">Süre: <span class="font-semibold">{{ $response['execution_time'] }} ms</span></span>
                                <span>Zaman: <span
                                        class="font-semibold">{{ \Carbon\Carbon::parse($response['timestamp'])->format('H:i:s') }}</span></span>
                            </div>
                        </div>

                        @if(isset($response['error']))
                            <div class="bg-red-50 text-red-700 p-4 rounded-md">
                                <h4 class="font-bold">Hata Oluştu!</h4>
                                <p>{{ $response['error'] }}</p>
                            </div>
                        @else
                            <div class="space-y-3">
                                <div>
                                    <h4 class="font-medium text-gray-700 mb-1">Yanıt Başlıkları (Headers)</h4>
                                    <pre
                                        class="bg-gray-800 text-white p-3 rounded-md text-xs overflow-x-auto max-h-48">@json($response['headers'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)</pre>
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-700 mb-1">Yanıt Gövdesi (Body)</h4>
                                    <pre class="bg-gray-800 text-white p-3 rounded-md text-xs overflow-x-auto max-h-96">@if(is_array($response['body']))
                                            @json($response['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                                        @else
                                            {{ $response['body'] }}
                                        @endif</pre>
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="p-6 text-center">
                        <i class="fas fa-paper-plane text-4xl text-gray-300 mb-3"></i>
                        <p class="text-gray-500">API isteği göndermek için "Gönder" butonuna tıklayın.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
