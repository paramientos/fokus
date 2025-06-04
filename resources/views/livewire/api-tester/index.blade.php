<?php

use App\Models\Project;
use App\Models\Task;

new class extends Livewire\Volt\Component {
    public $endpoints = [];
    public $projectFilter = null;
    public $taskFilter = null;
    public array $projects = [];

    public function mount(): void
    {
        $this->loadEndpoints();

        $this->projects = Project::where('user_id', auth()->id())->get()->select('id', 'name')->toArray();
    }

    public function loadEndpoints()
    {
        $query = \App\Models\ApiEndpoint::with(['project', 'task'])
            ->where('user_id', auth()->id());

        if ($this->projectFilter) {
            $query->where('project_id', $this->projectFilter);
        }

        if ($this->taskFilter) {
            $query->where('task_id', $this->taskFilter);
        }

        $this->endpoints = $query->latest()->get();
    }

    public function updatedProjectFilter()
    {
        $this->taskFilter = null;
        $this->loadEndpoints();
    }

    public function updatedTaskFilter()
    {
        $this->loadEndpoints();
    }

    public function deleteEndpoint($id)
    {
        $endpoint = \App\Models\ApiEndpoint::findOrFail($id);

        if ($endpoint->user_id !== auth()->id()) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Bu API endpoint\'i silme yetkiniz yok.'
            ]);
            return;
        }

        $endpoint->delete();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'API endpoint başarıyla silindi.'
        ]);

        $this->loadEndpoints();
    }
}
?>

<div>
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold">API Test Aracı</h1>
            <p class="text-gray-500">Projeleriniz için API endpoint'lerini test edin</p>
        </div>

        <div>
            <a href="{{ route('api-tester.create') }}" class="inline-flex items-center">
                <x-button>
                    <i class="fas fa-plus mr-2"></i> Yeni API Endpoint
                </x-button>
            </a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <x-select id="projectFilter" label="Projeye Göre Filtrele" wire:model.live="projectFilter"
                          :options="$projects" placeholder="Tüm Projeler" class="w-full"/>
            </div>

            <div>
                <x-select id="taskFilter" wire:model.live="taskFilter" label="Göreve Göre Filtrele" class="w-full"
                          :options="$projectFilter ? Task::where('project_id', $projectFilter)->get()->select('name', 'id')->toArray() : []" />
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        @if(count($endpoints) > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                    <tr class="bg-gray-50 text-left">
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Adı</th>
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Metod</th>
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">URL</th>
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Proje</th>
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Görev</th>
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">İşlemler</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                    @foreach($endpoints as $endpoint)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('api-tester.show', $endpoint) }}"
                                   class="text-primary-600 hover:text-primary-900 font-medium">
                                    {{ $endpoint->name }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                        @if($endpoint->method == 'GET') bg-green-100 text-green-800
                                        @elseif($endpoint->method == 'POST') bg-blue-100 text-blue-800
                                        @elseif($endpoint->method == 'PUT') bg-yellow-100 text-yellow-800
                                        @elseif($endpoint->method == 'PATCH') bg-purple-100 text-purple-800
                                        @elseif($endpoint->method == 'DELETE') bg-red-100 text-red-800
                                        @endif">
                                        {{ $endpoint->method }}
                                    </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 truncate max-w-xs">{{ $endpoint->url }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($endpoint->project)
                                    <a href="{{ route('projects.show', $endpoint->project) }}"
                                       class="text-primary-600 hover:text-primary-900">
                                        {{ $endpoint->project->name }}
                                    </a>
                                @else
                                    <span class="text-gray-500">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($endpoint->task)
                                    <a href="{{ route('tasks.show', ['project' => $endpoint->project_id, 'task' => $endpoint->task_id]) }}"
                                       class="text-primary-600 hover:text-primary-900">
                                        {{ $endpoint->task->title }}
                                    </a>
                                @else
                                    <span class="text-gray-500">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex space-x-2 justify-end">
                                    <a href="{{ route('api-tester.show', $endpoint) }}"
                                       class="text-primary-600 hover:text-primary-900">
                                        <i class="fas fa-play"></i>
                                    </a>
                                    <a href="{{ route('api-tester.edit', $endpoint) }}"
                                       class="text-gray-600 hover:text-gray-900">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="{{ route('api-tester.history', $endpoint) }}"
                                       class="text-gray-600 hover:text-gray-900">
                                        <i class="fas fa-history"></i>
                                    </a>
                                    <button wire:click="deleteEndpoint({{ $endpoint->id }})"
                                            wire:confirm="Bu API endpoint'i silmek istediğinizden emin misiniz?"
                                            class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-6 text-center">
                <div class="text-gray-500 mb-4">
                    <i class="fas fa-code text-4xl mb-2"></i>
                    <p>Henüz hiç API endpoint'i oluşturmadınız.</p>
                </div>
                <a href="{{ route('api-tester.create') }}">
                    <x-button>
                        <i class="fas fa-plus mr-2"></i> Yeni API Endpoint Oluştur
                    </x-button>
                </a>
            </div>
        @endif
    </div>
</div>
