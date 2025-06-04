<?php

use App\Models\ApiEndpoint;
use App\Models\Project;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new class extends Component {
    public $apiEndpoints = [];
    public ?int $selectedEndpointId = null;
    public string $currentView = 'index';
    public $projects = [];

    #[\Livewire\Attributes\Url]
    public string $selectedProjectId = 'all'; // 'all', 'none', or project_id

    public function mount()
    {
        $this->projects = Project::where('user_id', auth()->id())->orderBy('name')->get();
        $this->loadApiEndpoints();
    }

    public function updatedSelectedProjectId()
    {
        $this->loadApiEndpoints();
        $this->selectedEndpointId = null;
        $this->currentView = 'index';

        $this->js('window.location.reload();');
    }

    public function loadApiEndpoints(): void
    {
        $query = ApiEndpoint::where('user_id', auth()->id());

        if ($this->selectedProjectId === 'none') {
            $query->whereNull('project_id');
        } elseif ($this->selectedProjectId && $this->selectedProjectId !== 'all') {
            $query->where('project_id', $this->selectedProjectId);
        }

        $this->apiEndpoints = $query->with('project')->orderBy('name')->get();
    }

    #[On('api-endpoint-created')]
    #[On('api-endpoint-updated')]
    #[On('api-endpoint-deleted')]
    public function refreshEndpointsAndSelect($endpointId = null)
    {
        $this->loadApiEndpoints();
        if ($endpointId) {
            $this->selectEndpoint($endpointId);
        } else {
            // If no specific endpoint to select, maybe go to index or clear selection
            // For now, if an endpoint was deleted and it was selected, clear selection
            if ($this->selectedEndpointId && !$this->apiEndpoints->contains('id', $this->selectedEndpointId)) {
                $this->selectedEndpointId = null;
                $this->currentView = 'index';
            }
        }
    }

    public function selectEndpoint($endpointId)
    {
        $this->selectedEndpointId = $endpointId;
        $this->currentView = 'show';
    }

    public function showCreateForm()
    {
        $this->selectedEndpointId = null;
        $this->currentView = 'create';
        // Proje ID'sini create formuna göndermek için burada bir değişiklik yapmıyoruz,
        // çünkü livewire:api-tester.create çağrısında yapacağız.
    }

    #[On('edit-api-endpoint')]
    public function showEditForm($endpointId)
    {
        $this->selectedEndpointId = $endpointId;
        $this->currentView = 'edit';
    }

    #[On('view-api-endpoint')]
    public function viewApiEndpoint($endpointId)
    {
        $this->selectEndpoint($endpointId);
    }

    #[On('close-view')]
    public function closeView()
    {
        $this->selectedEndpointId = null;
        $this->currentView = 'index';
    }

    #[Computed]
    public function getSelectedEndpointProperty()
    {
        if ($this->selectedEndpointId) {
            return ApiEndpoint::find($this->selectedEndpointId);
        }
        return null;
    }
};

?>

<div class="flex h-[calc(100vh-4rem)]">
    <!-- Sidebar -->
    <div class="w-1/4 bg-gray-50 border-r border-gray-200 p-4 overflow-y-auto space-y-4">
        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold">API Endpoints</h2>
            <x-button wire:click="showCreateForm" variant="primary" size="sm">
                <i class="fas fa-plus mr-1"></i> Yeni
            </x-button>
        </div>

        <div>
            <x-select id="project_filter" label="Projeye Göre Filtrele" wire:model.live="selectedProjectId"
                      class="w-full text-sm"
                      :options="[
                          ['id' => 'all', 'name' => 'Tüm Projeler'],
                          ['id' => 'none', 'name' => 'Projesiz Endpointler'],
                          ...$projects->map(fn($p) => ['id' => $p->id, 'name' => $p->name])->toArray()
                      ]">
            </x-select>
        </div>

        @if(count($apiEndpoints) > 0)
            <ul class="space-y-1">
                @foreach($apiEndpoints as $endpoint)
                    <li>
                        <button
                            wire:click="selectEndpoint({{ $endpoint->id }})"
                            class="w-full text-left px-3 py-2 rounded-md text-sm hover:bg-gray-200 focus:outline-none focus:bg-primary-100 focus:text-primary-700 {{ $selectedEndpointId == $endpoint->id ? 'bg-primary-100 text-primary-700 font-semibold' : 'text-gray-700' }}"
                        >
                            <div class="flex items-center justify-between">
                                <div class="truncate">
                                    <span class="block truncate">{{ $endpoint->name }}</span>
                                    @if($endpoint->project)
                                        <span
                                            class="text-xs text-gray-500 block truncate">{{ $endpoint->project->name }}</span>
                                    @else
                                        <span class="text-xs text-gray-400 italic block truncate">Projesiz</span>
                                    @endif
                                </div>
                                <span class="ml-2 px-2 py-0.5 text-xs font-semibold rounded-full
                                    @if(strtoupper($endpoint->method) == 'GET') bg-green-100 text-green-800
                                    @elseif(strtoupper($endpoint->method) == 'POST') bg-blue-100 text-blue-800
                                    @elseif(strtoupper($endpoint->method) == 'PUT') bg-yellow-100 text-yellow-800
                                    @elseif(strtoupper($endpoint->method) == 'PATCH') bg-purple-100 text-purple-800
                                    @elseif(strtoupper($endpoint->method) == 'DELETE') bg-red-100 text-red-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ strtoupper($endpoint->method) }}
                                </span>
                            </div>
                        </button>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-sm text-gray-500">Henüz API endpoint'i oluşturulmamış.</p>
        @endif
    </div>

    <!-- Main Content -->
    <div class="w-3/4 p-6 overflow-y-auto">
        @if($currentView === 'index' && !$selectedEndpointId)
            <div class="text-center text-gray-500 pt-10">
                <i class="fas fa-mouse-pointer text-4xl mb-3"></i>
                <p>Başlamak için kenar çubuğundan bir API endpoint'i seçin veya yeni bir tane oluşturun.</p>
            </div>
        @elseif($currentView === 'create')
            @livewire('api-tester.create', ['isEmbedded' => true, 'projectId' => ($selectedProjectId !== 'all' && $selectedProjectId !== 'none' ? $selectedProjectId : null)], key('create-endpoint-' . ($selectedProjectId ?? 'none')))
        @elseif($currentView === 'show' && $this->selectedEndpoint)
            @livewire('api-tester.show', ['apiEndpoint' => $this->selectedEndpoint->id, 'isEmbedded' => true], key('show-endpoint-' . $this->selectedEndpoint->id))
        @elseif($currentView === 'edit' && $this->selectedEndpoint)
            @livewire('api-tester.edit', ['apiEndpoint' => $this->selectedEndpoint, 'isEmbedded' => true], key('edit-endpoint-' . $this->selectedEndpoint->id))
        @endif
    </div>
</div>
