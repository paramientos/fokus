<?php
new class extends Livewire\Volt\Component {
    public \App\Models\ApiEndpoint $apiEndpoint;

    public $name = '';
    public $url = '';
    public $method = 'GET';
    public $description = '';
    public $headers = [];
    public $params = [];
    public $body = [];
    public $task_id = null;
    public $project_id = null;

    public $projects = [];
    public $tasks = [];

    public $newHeaderKey = '';
    public $newHeaderValue = '';
    public $newParamKey = '';
    public $newParamValue = '';
    public $newBodyKey = '';
    public $newBodyValue = '';

    public bool $isEmbedded = false;

    public function mount(\App\Models\ApiEndpoint $apiEndpoint, bool $isEmbedded = false)
    {
        $this->isEmbedded = $isEmbedded;
        $this->apiEndpoint = $apiEndpoint;

        // Yetki kontrolü
        if ($this->apiEndpoint->user_id !== auth()->id()) {
            abort(403, 'Bu API endpoint\'ini düzenleme yetkiniz yok.');
        }

        $this->name = $this->apiEndpoint->name;
        $this->url = $this->apiEndpoint->url;
        $this->method = $this->apiEndpoint->method;
        $this->description = $this->apiEndpoint->description;
        $this->headers = $this->apiEndpoint->headers ?? [];
        $this->params = $this->apiEndpoint->params ?? [];
        $this->body = $this->apiEndpoint->body ?? [];
        $this->task_id = $this->apiEndpoint->task_id;
        $this->project_id = $this->apiEndpoint->project_id;

        $this->projects = \App\Models\Project::where('user_id', auth()->id())->orderBy('name')->get();
        $this->loadTasks();
    }

    public function updatedProjectId()
    {
        $this->task_id = null;
        $this->loadTasks();
    }

    public function loadTasks()
    {
        if ($this->project_id) {
            $this->tasks = \App\Models\Task::where('project_id', $this->project_id)->orderBy('title')->get();
        } else {
            $this->tasks = collect();
        }
    }

    public function addHeader()
    {
        if (empty($this->newHeaderKey)) return;
        $this->headers[$this->newHeaderKey] = $this->newHeaderValue;
        $this->newHeaderKey = '';
        $this->newHeaderValue = '';
    }

    public function removeHeader($key)
    {
        unset($this->headers[$key]);
    }

    public function addParam()
    {
        if (empty($this->newParamKey)) return;
        $this->params[$this->newParamKey] = $this->newParamValue;
        $this->newParamKey = '';
        $this->newParamValue = '';
    }

    public function removeParam($key)
    {
        unset($this->params[$key]);
    }

    public function addBodyField()
    {
        if (empty($this->newBodyKey)) return;
        $this->body[$this->newBodyKey] = $this->newBodyValue;
        $this->newBodyKey = '';
        $this->newBodyValue = '';
    }

    public function removeBodyField($key)
    {
        unset($this->body[$key]);
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|string|max:2000',
            'method' => 'required|string|in:GET,POST,PUT,PATCH,DELETE',
            'description' => 'nullable|string',
            'project_id' => 'nullable|exists:projects,id',
            'task_id' => 'nullable|exists:tasks,id',
        ]);

        try {
            $this->apiEndpoint->update([
                'name' => $this->name,
                'url' => $this->url,
                'method' => $this->method,
                'description' => $this->description,
                'headers' => $this->headers,
                'params' => $this->params,
                'body' => $this->body,
                'task_id' => $this->task_id,
                'project_id' => $this->project_id,
            ]);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'API endpoint başarıyla güncellendi.'
            ]);

            if ($this->isEmbedded) {
                $this->dispatch('api-endpoint-updated', endpointId: $this->apiEndpoint->id)->to('api-tester.workspace');
            } else {
                return redirect()->route('api-tester.workspace'); // Show'a değil workspace'e yönlendir
            }
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Bir hata oluştu: ' . $e->getMessage()
            ]);
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
                <h1 class="text-2xl font-bold">API Endpoint Düzenle</h1>
                <p class="text-gray-500">{{ $apiEndpoint->name }}</p>
            </div>
            <div>
                <a href="{{ route('api-tester.workspace') }}" class="inline-flex items-center">
                    <x-button variant="secondary">
                        <i class="fas fa-arrow-left mr-2"></i> Çalışma Alanına Dön
                    </x-button>
                </a>
            </div>
        @else
            <div>
                <h1 class="text-xl font-semibold">API Endpoint Düzenle</h1>
                <p class="text-gray-500 text-sm">{{ $apiEndpoint->name }}</p>
            </div>
            <x-button wire:click="close" variant="ghost" size="sm">
                <i class="fas fa-times mr-1"></i> Kapat
            </x-button>
        @endif
    </div>

    <div class="bg-white rounded-lg {{ $isEmbedded ? '' : 'shadow-sm p-6' }}">
        <form wire:submit="save">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <x-input id="name" label="Endpoint Adı" id="name" type="text" wire:model="name" class="w-full" placeholder="Örn: Kullanıcı Listesi"
                             required/>
                </div>

                <div>
                    <x-select id="method" name="method" wire:model="method" class="w-full" :options="[
                        ['id' => 'GET', 'name' => 'GET'],
                        ['id' => 'POST', 'name' => 'POST'],
                        ['id' => 'PUT', 'name' => 'PUT'],
                        ['id' => 'PATCH', 'name' => 'PATCH'],
                        ['id' => 'DELETE', 'name' => 'DELETE'],
                    ]" />
                </div>

                <div class="md:col-span-2">
                    <x-input label="URL" id="url" type="text" wire:model="url" class="w-full"
                             placeholder="https://api.example.com/users" required/>
                </div>

                <div class="md:col-span-2">
                    <x-textarea label="Açıklama" id="description" wire:model="description" class="w-full"
                                placeholder="Bu endpoint hakkında açıklama..." rows="3"/>
                </div>

                <div>
                    <x-select name="project_id" id="project_id" wire:model.live="project_id" class="w-full"
                              :options="[
                                  ['id' => '', 'name' => 'Proje Seçin (Opsiyonel)'],
                                  ...$projects->map(fn($project) => ['id' => $project->id, 'name' => $project->name])->toArray()
                              ]">
                    </x-select>
                </div>

                <div>
                    <x-select label="task_id" id="task_id" wire:model="task_id" class="w-full"
                              :options="[
                                  ['id' => '', 'name' => 'Görev Seçin (Opsiyonel)'],
                                  ...$tasks->map(fn($task) => ['id' => $task->id, 'name' => $task->title])->toArray()
                              ]">
                    </x-select>
                    @if(!$project_id)
                        <p class="text-sm text-gray-500 mt-1">Görev seçmek için önce bir proje seçmelisiniz.</p>
                    @endif
                </div>
            </div>

            <!-- Headers -->
            <div class="mb-6">
                <h3 class="text-lg font-medium mb-3">Headers</h3>
                <div class="bg-gray-50 p-4 rounded-lg mb-3">
                    @if(count($headers) > 0)
                        <div class="mb-3">
                            @foreach($headers as $key => $value)
                                <div class="flex items-center mb-2">
                                    <div class="flex-1 grid grid-cols-2 gap-2">
                                        <div class="text-sm font-medium text-gray-700">{{ $key }}</div>
                                        <div class="text-sm text-gray-600">{{ $value }}</div>
                                    </div>
                                    <button type="button" wire:click="removeHeader('{{ $key }}')"
                                            class="text-red-500 hover:text-red-700">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <x-input type="text" wire:model="newHeaderKey" placeholder="Header Adı"/>
                        <div class="flex">
                            <x-input type="text" wire:model="newHeaderValue" placeholder="Değer" class="flex-1"/>
                            <x-button type="button" wire:click="addHeader" class="ml-2" size="sm">
                                <i class="fas fa-plus"></i>
                            </x-button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Params -->
            <div class="mb-6">
                <h3 class="text-lg font-medium mb-3">URL Parametreleri</h3>
                <div class="bg-gray-50 p-4 rounded-lg mb-3">
                    @if(count($params) > 0)
                        <div class="mb-3">
                            @foreach($params as $key => $value)
                                <div class="flex items-center mb-2">
                                    <div class="flex-1 grid grid-cols-2 gap-2">
                                        <div class="text-sm font-medium text-gray-700">{{ $key }}</div>
                                        <div class="text-sm text-gray-600">{{ $value }}</div>
                                    </div>
                                    <button type="button" wire:click="removeParam('{{ $key }}')"
                                            class="text-red-500 hover:text-red-700">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <x-input type="text" wire:model="newParamKey" placeholder="Parametre Adı"/>
                        <div class="flex">
                            <x-input type="text" wire:model="newParamValue" placeholder="Değer" class="flex-1"/>
                            <x-button type="button" wire:click="addParam" class="ml-2" size="sm">
                                <i class="fas fa-plus"></i>
                            </x-button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Body -->
            <div class="mb-6">
                <h3 class="text-lg font-medium mb-3">İstek Gövdesi (Body)</h3>
                <div class="bg-gray-50 p-4 rounded-lg mb-3">
                    @if(count($body) > 0)
                        <div class="mb-3">
                            @foreach($body as $key => $value)
                                <div class="flex items-center mb-2">
                                    <div class="flex-1 grid grid-cols-2 gap-2">
                                        <div class="text-sm font-medium text-gray-700">{{ $key }}</div>
                                        <div class="text-sm text-gray-600">{{ is_array($value) ? json_encode($value) : $value }}</div>
                                    </div>
                                    <button type="button" wire:click="removeBodyField('{{ $key }}')"
                                            class="text-red-500 hover:text-red-700">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <x-input type="text" wire:model="newBodyKey" placeholder="Alan Adı" />
                        <div class="flex">
                            <x-input type="text" wire:model="newBodyValue" placeholder="Değer" class="flex-1" />
                            <x-button type="button" wire:click="addBodyField" class="ml-2" size="sm">
                                <i class="fas fa-plus"></i>
                            </x-button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <x-button type="submit" class="w-full md:w-auto">
                    <i class="fas fa-save mr-2"></i> Güncelle
                </x-button>
            </div>
        </form>
    </div>
</div>
