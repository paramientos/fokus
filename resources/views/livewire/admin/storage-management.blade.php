<?php

namespace App\Livewire\Admin;

use App\Models\Workspace;
use Illuminate\Support\Str;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public $workspaces = [];
    public $selectedWorkspace = null;
    public $editingStorage = null;

    public array $packages = [
        'free' => [
            'name' => 'Forever Free',
            'limit' => 1073741824, // 1GB
            'price' => 'Free'
        ],
        'solo_legend' => [
            'name' => ' Solo Legend',
            'limit' => 1073741824, // 1GB
            'price' => '$9.99/month'
        ],
        'team_multiverse' => [
            'name' => ' Team Multiverse',
            'limit' => 5737418240, // 5GB
            'price' => '$19.99/month'
        ]
    ];

    public function mount(): void
    {
        $this->loadWorkspaces();
    }

    public function loadWorkspaces(): void
    {
        $this->workspaces = Workspace::with('storageUsage')->get();
    }

    public function selectWorkspace($workspaceId): void
    {
        $this->selectedWorkspace = Workspace::with('storageUsage')->find($workspaceId);

        if (!$this->selectedWorkspace->storageUsage) {
            $this->selectedWorkspace->storageUsage()->create([
                'workspace_id' => $this->selectedWorkspace->id,
                'used_bytes' => 0,
                'limit_bytes' => 1073741824, // Default 1GB
                'plan_name' => 'basic'
            ]);
            $this->selectedWorkspace->refresh();
        }

        $this->editingStorage = [
            'plan_name' => $this->selectedWorkspace->storageUsage->plan_name,
            'limit_bytes' => $this->selectedWorkspace->storageUsage->limit_bytes
        ];
    }

    public function updateStoragePlan(): void
    {
        if (!$this->selectedWorkspace) {
            $this->error('No workspace selected');
            return;
        }

        $storageUsage = $this->selectedWorkspace->storageUsage;
        $storageUsage->plan_name = $this->editingStorage['plan_name'];
        $storageUsage->limit_bytes = $this->editingStorage['limit_bytes'];
        $storageUsage->save();

        $this->success('Storage plan updated successfully');
        $this->loadWorkspaces();
        $this->selectWorkspace($this->selectedWorkspace->id);
    }

    public function setPackage($packageName): void
    {
        if (!isset($this->packages[$packageName])) {
            $this->error('Invalid package');
            return;
        }

        $this->editingStorage['plan_name'] = $packageName;
        $this->editingStorage['limit_bytes'] = $this->packages[$packageName]['limit'];
    }
}
?>

<div class="max-w-5xl mx-auto py-8">
    <h2 class="text-2xl font-bold mb-6 flex items-center gap-2">
        <x-icon name="fas.database" class="w-6 h-6 text-primary"/>
        Storage Management
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div>
            <h3 class="text-lg font-semibold mb-2">Workspaces</h3>
            <ul class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900 rounded-lg shadow">
                @foreach($workspaces as $workspace)
                    <li class="p-4 cursor-pointer hover:bg-primary/10 {{ $selectedWorkspace && $selectedWorkspace->id === $workspace->id ? 'bg-primary/10' : '' }}"
                        wire:click="selectWorkspace({{ $workspace->id }})">
                        <div class="flex justify-between items-center">
                            <span class="font-medium">{{ $workspace->name }}</span>
                            <span
                                class="text-xs text-gray-500">{{ $workspace->storageUsage?->plan_name ? ucfirst($workspace->storageUsage->plan_name) : 'Basic' }}</span>
                        </div>
                        <div class="text-xs text-gray-400 mt-1">
                            {{ $workspace->storageUsage?->formatted_used ?? '0 B' }}
                            / {{ $workspace->storageUsage?->formatted_limit ?? '1 GB' }}
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>

        <div>
            @if($selectedWorkspace)
                <div class="bg-white dark:bg-gray-900 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                        <x-icon name="fas.layer-group" class="w-5 h-5 text-primary"/>
                        {{ $selectedWorkspace->name }} Storage
                    </h3>
                    <div class="mb-4">
                        <span class="text-sm font-medium">Current Plan:</span>
                        <span class="ml-2 px-2 py-1 rounded bg-primary/10 text-primary font-semibold text-xs">
                            {{ Str::headline($editingStorage['plan_name']) }}
                        </span>
                    </div>
                    <div class="mb-4">
                        <span class="text-sm">Usage:</span>
                        <span
                            class="ml-2 text-xs">{{ $selectedWorkspace->storageUsage->formatted_used }} / {{ $selectedWorkspace->storageUsage->formatted_limit }}</span>
                        <div class="w-full bg-gray-200 rounded-full h-2.5 mt-1 dark:bg-gray-700">
                            <div class="bg-primary h-2.5 rounded-full"
                                 style="width: {{ $selectedWorkspace->storageUsage->usage_percentage }}%"></div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2">Change Plan</label>
                        <div class="flex gap-2">
                            @foreach($packages as $key => $pkg)
                                <button type="button"
                                        wire:click="setPackage('{{ $key }}')"
                                        class="px-3 py-2 rounded border border-primary/30 text-xs font-semibold {{ $editingStorage['plan_name'] === $key ? 'bg-primary text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200' }}">
                                    {{ $pkg['name'] }}<br>
                                    <span class="text-[10px]">{{ $pkg['price'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">Custom Limit (bytes)</label>
                        <x-input type="number" min="1048576" step="1048576"
                                 wire:model.live="editingStorage.limit_bytes"/>
                    </div>
                    <x-button color="primary" wire:click="updateStoragePlan" class="w-full mt-2">
                        <x-icon name="fas.save" class="w-4 h-4 mr-1"/>
                        Save Changes
                    </x-button>
                </div>
            @else
                <div class="flex items-center justify-center h-64 text-gray-400">
                    <x-icon name="fas.arrow-left" class="w-6 h-6 mr-2"/>
                    Select a workspace to manage storage
                </div>
            @endif
        </div>
    </div>
</div>
