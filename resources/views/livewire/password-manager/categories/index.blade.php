<?php
new class extends Livewire\Volt\Component {
    public \App\Models\PasswordVault $vault;

    public function mount(\App\Models\PasswordVault $vault)
    {
        $this->vault = $vault;
    }
};
?>
<div class="p-6 space-y-6">
    <h1 class="text-2xl font-bold flex items-center gap-2"><i class="fas fa-folder"></i> Categories</h1>
    <p>This section is coming soon.</p>
</div>
