<?php

use App\Models\WorkspaceWorkflow;
use Illuminate\Database\Eloquent\Collection;
use Mary\Traits\Toast;

new class extends Livewire\Volt\Component {
    use Toast;

    public $search = '';
    public $status = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';

    public function mount()
    {
        // Initialize component
    }

    public function getWorkflowsProperty(): Collection
    {
        return WorkspaceWorkflow::query()
            ->where('workspace_id', session('workspace_id'))
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%');
            })
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->get();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function toggleStatus($id)
    {
        $workflow = WorkspaceWorkflow::findOrFail($id);
        $workflow->update([
            'is_active' => !$workflow->is_active
        ]);

        $status = $workflow->is_active ? 'activated' : 'deactivated';
        $this->success("Workflow {$status} successfully.");
    }

    public function deleteWorkflow($id)
    {
        $workflow = WorkspaceWorkflow::findOrFail($id);
        $name = $workflow->name;

        $workflow->delete();
        $this->success("Workflow '{$name}' deleted successfully.");
    }
}

?>

<div>
    <div class="mb-6">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-bold">Workflow Management</h1>
            <a href="{{ route('workflows.create') }}" class="inline-flex">
                <x-button icon="fas.plus">Create Workflow</x-button>
            </a>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-6">
            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <x-input
                        icon="fas.search"
                        placeholder="Search workflows..."
                        wire:model.live.debounce.300ms="search"
                    />
                </div>
                <div class="w-full md:w-48">
                    <x-select
                        wire:model.live="status"
                        :options="[
                            ['id' => '', 'name' => 'All Statuses'],
                            ['id' => 'draft', 'name' => 'Draft'],
                            ['id' => 'active', 'name' => 'Active'],
                            ['id' => 'archived', 'name' => 'Archived']
                        ]"
                    />
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-700 text-left">
                            <th class="px-4 py-3 cursor-pointer" wire:click="sortBy('name')">
                                <div class="flex items-center">
                                    Name
                                    @if ($sortField === 'name')
                                        <span class="ml-1">
                                            @if ($sortDirection === 'asc')
                                                <i class="fas fa-sort-up"></i>
                                            @else
                                                <i class="fas fa-sort-down"></i>
                                            @endif
                                        </span>
                                    @endif
                                </div>
                            </th>
                            <th class="px-4 py-3">Description</th>
                            <th class="px-4 py-3 cursor-pointer" wire:click="sortBy('status')">
                                <div class="flex items-center">
                                    Status
                                    @if ($sortField === 'status')
                                        <span class="ml-1">
                                            @if ($sortDirection === 'asc')
                                                <i class="fas fa-sort-up"></i>
                                            @else
                                                <i class="fas fa-sort-down"></i>
                                            @endif
                                        </span>
                                    @endif
                                </div>
                            </th>
                            <th class="px-4 py-3 cursor-pointer" wire:click="sortBy('created_at')">
                                <div class="flex items-center">
                                    Created
                                    @if ($sortField === 'created_at')
                                        <span class="ml-1">
                                            @if ($sortDirection === 'asc')
                                                <i class="fas fa-sort-up"></i>
                                            @else
                                                <i class="fas fa-sort-down"></i>
                                            @endif
                                        </span>
                                    @endif
                                </div>
                            </th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($this->workflows as $workflow)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-4 py-3">
                                    <a href="{{ route('workflows.show', $workflow) }}" class="font-medium text-blue-600 dark:text-blue-400 hover:underline">
                                        {{ $workflow->name }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 max-w-xs truncate">{{ $workflow->description }}</td>
                                <td class="px-4 py-3">
                                    @if ($workflow->status === 'draft')
                                        <x-badge color="gray">Draft</x-badge>
                                    @elseif ($workflow->status === 'active')
                                        <x-badge color="green">Active</x-badge>
                                    @elseif ($workflow->status === 'archived')
                                        <x-badge color="yellow">Archived</x-badge>
                                    @endif
                                </td>
                                <td class="px-4 py-3">{{ $workflow->created_at->format('M d, Y') }}</td>
                                <td class="px-4 py-3 text-right space-x-1">
                                    <x-button icon="fas.eye" link="{{ route('workflows.show', $workflow) }}" size="xs" />
                                    <x-button icon="fas.pen" link="{{ route('workflows.edit', $workflow) }}" size="xs" />
                                    <x-button icon="fas.list" link="{{ route('workflows.steps.index', $workflow) }}" size="xs" title="Manage Steps" />
                                    <x-button
                                        icon="{{ $workflow->is_active ? 'fas.toggle-on' : 'fas.toggle-off' }}"
                                        wire:click="toggleStatus({{ $workflow->id }})"
                                        size="xs"
                                        :color="$workflow->is_active ? 'green' : 'gray'"
                                        title="{{ $workflow->is_active ? 'Deactivate' : 'Activate' }}"
                                    />
                                    <x-button
                                        icon="fas.trash"
                                        wire:click="deleteWorkflow({{ $workflow->id }})"
                                        wire:confirm="Are you sure you want to delete this workflow?"
                                        size="xs"
                                        color="red"
                                    />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <i class="fas fa-sitemap text-4xl mb-3 text-gray-400"></i>
                                        <p class="text-lg font-medium">No workflows found</p>
                                        <p class="text-sm mt-1">Create your first workflow to get started</p>
                                        <a href="{{ route('workflows.create') }}" class="mt-3">
                                            <x-button icon="fas.plus" size="sm">Create Workflow</x-button>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
