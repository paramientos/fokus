<?php

use Livewire\Volt\Component;

new class extends Component {
    //
}; ?>

<div>
    @if(session()->has('info'))
        <x-alert title="Info" description="{{ session('info') }}" icon="fas.info"
                 class="alert-info mb-2"/>
    @endif
</div>
