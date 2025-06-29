<?php

new class extends Livewire\Volt\Component {
    public function mount(): void
    {
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();

        $this->redirectRoute('landing');
    }
}

?>

<div>
    <div class="flex justify-center items-center min-h-screen">
        <div class="text-center">
            <x-icon name="o-arrow-path" class="w-12 h-12 mx-auto animate-spin text-primary" />
            <h2 class="mt-4 text-xl font-semibold">Logging out...</h2>
            <p class="mt-2 text-gray-500">You will be redirected shortly.</p>
        </div>
    </div>
</div>
