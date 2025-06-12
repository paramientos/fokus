<?php

use Livewire\Volt\Component;

new class extends Component {
    //
}; ?>

<div>
    @if(session()->has('error'))
        <x-alert title="Error" description="{{ session('error') }}" icon="fas.triangle-exclamation"
                 class="alert-error mb-2"/>
    @endif
</div>
