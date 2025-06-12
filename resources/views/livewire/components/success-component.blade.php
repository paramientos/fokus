<?php

use Livewire\Volt\Component;

new class extends Component {
    //
}; ?>

<div>
    @if(session()->has('success'))
        <x-alert title="Success" description="{{ session('success') }}" icon="fas.check-circle"
                 class="alert-success mb-2"/>
    @endif
</div>
