<?php

use App\Models\GitRepository;
use App\Models\Project;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new class extends Component {
    use WithPagination;
    use Toast;

    public Project $project;
    // Form fields
    public string $provider = 'github';
    public string $name = '';
    public string $repository_url = '';
    public string $api_token = '';
    public string $webhook_secret = '';
    public ?string $default_branch = 'main';
    public ?string $branch_prefix = 'feature';

    // Dialog flags / selections
    public bool $showAddModal = false;
    public bool $showEditModal = false;
    public bool $showDeleteModal = false;
    public ?GitRepository $editingRepository = null;
    public ?GitRepository $deletingRepository = null;

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    // ---------- Dialog helpers ----------
    public function openAddModal(): void
    {
        $this->resetForm();
        $this->webhook_secret = bin2hex(random_bytes(16));
        $this->showAddModal = true;
    }

    public function openEditModal(int $id): void
    {
        $repo = GitRepository::findOrFail($id);
        $this->editingRepository = $repo;
        $this->provider = $repo->provider;
        $this->name = $repo->name;
        $this->repository_url = $repo->repository_url;
        $this->api_token = $repo->api_token;
        $this->webhook_secret = $repo->webhook_secret;
        $this->default_branch = $repo->default_branch;
        $this->branch_prefix = $repo->branch_prefix;
        $this->showEditModal = true;
    }

    public function openDeleteModal(int $id): void
    {
        $this->deletingRepository = GitRepository::findOrFail($id);
        $this->showDeleteModal = true;
    }

    // ---------- CRUD operations ----------
    public function addRepository(): void
    {
        $this->validateData();

        $this->project->gitRepositories()->create($this->getPayload());
        $this->showAddModal = false;
        $this->success('Repository added successfully.');
    }

    public function updateRepository(): void
    {
        $this->validateData();
        $this->editingRepository->update($this->getPayload());
        $this->showEditModal = false;
        $this->success('Repository updated successfully.');
    }

    public function deleteRepository(): void
    {
        $this->deletingRepository->delete();
        $this->showDeleteModal = false;
        $this->success('Repository deleted successfully.');
    }

    // ---------- Helpers ----------
    protected function resetForm(): void
    {
        $this->reset(['provider', 'name', 'repository_url', 'api_token', 'webhook_secret', 'default_branch', 'branch_prefix', 'editingRepository', 'deletingRepository']);
        $this->provider = 'github';
        $this->default_branch = 'main';
        $this->branch_prefix = 'feature';
    }

    protected function validateData(): void
    {
        $this->validate([
            'provider' => 'required|in:github,gitlab,bitbucket',
            'name' => 'required|string|max:255',
            'repository_url' => 'required|url',
            'api_token' => 'required|string',
            'webhook_secret' => 'required|string',
            'default_branch' => 'nullable|string|max:100',
            'branch_prefix' => 'nullable|string|max:100',
        ]);
    }

    protected function getPayload(): array
    {
        return [
            'workspace_id' => $this->project->workspace_id,
            'provider' => $this->provider,
            'name' => $this->name,
            'repository_url' => $this->repository_url,
            'api_token' => $this->api_token,
            'webhook_secret' => $this->webhook_secret,
            'default_branch' => $this->default_branch,
            'branch_prefix' => $this->branch_prefix,
        ];
    }

    public function getWebhookUrl(GitRepository $repository): string
    {
        return url("/webhooks/{$repository->provider}/{$repository->webhook_secret}");
    }


    public function with(): array
    {
        return [
            'repositories' => $this->project->gitRepositories()->latest()->get(),
        ];
    }
};
?>

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold">Git Repositories</h2>
        <x-button icon="fas.plus" wire:click="openAddModal">Add Repository</x-button>
    </div>

    @if($repositories->isEmpty())
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 text-center">
            <i class="fas fa-code-branch text-4xl text-gray-400 mb-3"></i>
            <p class="text-gray-600 dark:text-gray-400 mb-4">No repositories connected yet.</p>
            <x-button icon="fas.plus" wire:click="openAddModal">Connect Repository</x-button>
        </div>
    @else
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Provider</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Default Branch</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Webhook</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider">Actions</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($repositories as $repository)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $repository->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap capitalize">{{ $repository->provider }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $repository->default_branch }}</td>
                        <td class="px-6 py-4 text-xs">
                            <code>{{ $this->getWebhookUrl($repository) }}</code>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <x-button icon="fas.pencil" flat primary
                                      wire:click="openEditModal({{ $repository->id }})"/>
                            <x-button icon="fas.trash" flat negative
                                      wire:click="openDeleteModal({{ $repository->id }})" class="ml-2"/>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <!-- Add Modal -->
    <x-modal wire:model="showAddModal" title="Add Git Repository" maxWidth="2xl">
        @include('livewire.projects.settings.git-repositories-form', ['action' => 'addRepository'])
    </x-modal>

    <!-- Edit Modal -->
    <x-modal wire:model="showEditModal" title="Edit Git Repository" maxWidth="2xl">
        @include('livewire.projects.settings.git-repositories-form', ['action' => 'updateRepository'])
    </x-modal>

    <!-- Delete Confirmation -->
    <x-button wire:model="showDeleteModal" icon="fas.trash" title="Delete Repository"
              wire:confirm="Are you sure? This will remove the repository connection." action="deleteRepository"/>
</div>
