<div id="status-manager-root">
    <x-card class="mb-4">
        <h2 class="text-lg font-bold mb-2 flex items-center gap-2">
    Statuses
    <x-button color="info" icon="fas.exchange-alt" size="xs"
        link="{{ route('projects.status-transitions', ['project' => $project->id]) }}"
        class="ml-2"
    >Manage Status Transitions</x-button>
</h2>

        <div>
            <ul id="sortable-statuses" class="mb-4">
                @foreach($statuses as $status)
                    <li class="flex items-center mb-2 p-2 border border-gray-200 rounded cursor-move"
                        data-id="{{ $status->id }}">
                        <span class="inline-block w-3 h-3 rounded-full mr-2"
                              style="background: {{ $status->color }}"></span>
                        <span class="mr-2">{{ $status->name }}</span>
                        <span class="text-xs text-gray-400">Order: {{ $status->order }}</span>
                        @if($status->is_completed)
                            <span class="ml-2 text-xs text-green-500">Completed</span>
                        @endif

                        <x-button wire:confirm="Are you sure you want to delete this status?" class="btn-link" icon="fas.trash" size="xs"
                                  wire:click="deleteStatus({{ $status->id }})"
                                  :disabled="$status->tasks()->count() > 0"
                                  tooltip="Delete Status"
                        />
                    </li>
                @endforeach
            </ul>
        </div>
        <x-menu-separator/>

        <form wire:submit.prevent="addStatus" class="flex flex-col md:flex-row gap-2 items-end">
            <x-input label="Name" wire:model.defer="name" required class="w-full"/>
            <x-input label="Color" type="color" wire:model.defer="color" class="w-20"/>
            <x-input label="Order" type="number" wire:model.defer="order" class="w-20"/>
            <div class="flex items-center">
                <x-checkbox label="Is Completed" wire:model.defer="is_completed"/>
            </div>
            <x-button color="primary" type="submit" icon="fas.plus">Add Status</x-button>
        </form>
        @if (session()->has('success'))
            <x-alert class="alert-success mt-2">{{ session('success') }}</x-alert>
        @endif

        @if (session()->has('error'))
            <x-alert class="alert-error mt-2">{{ session('error') }}</x-alert>
        @endif
    </x-card>


</div>
