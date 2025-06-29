<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <x-select label="Provider" id="provider" :options="[['id'=>'github','name'=>'GitHub'],['id'=>'gitlab','name'=>'GitLab'],['id'=>'bitbucket','name'=>'Bitbucket']]" wire:model="provider" />
    </div>
    <div>
        <x-input label="Name" id="name" wire:model="name" />
    </div>
    <div class="md:col-span-2">
        <x-input label="Repository URL" id="repository_url" wire:model="repository_url" placeholder="https://github.com/owner/repo" />
    </div>
    <div class="md:col-span-2">
        <x-input label="API Token" id="api_token" wire:model="api_token" type="password" />
    </div>
    <div>
        <x-input label="Default Branch" id="default_branch" wire:model="default_branch" />
    </div>
    <div>
        <x-input label="Branch Prefix" id="branch_prefix" wire:model="branch_prefix" />
    </div>
    <div class="md:col-span-2">
        <x-input label="Webhook Secret" id="webhook_secret" wire:model="webhook_secret" />
    </div>
</div>

<div class="mt-6 flex justify-end space-x-2">
    <x-button flat secondary wire:click="$set('showAddModal', false)" wire:click="$set('showEditModal', false)">Cancel</x-button>
    <x-button primary wire:click="{{ $action }}">Save</x-button>
</div>
