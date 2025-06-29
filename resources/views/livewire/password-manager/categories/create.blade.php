<?php
new class extends Livewire\Volt\Component {
    public \App\Models\PasswordVault $vault;

    public function mount(\App\Models\PasswordVault $vault)
    {
        $this->vault = $vault;
    }
};
?>
<div class="p-6">Create category — coming soon.</div>
