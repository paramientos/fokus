<?php

use Livewire\Volt\Component;

new class extends Component {
    //
}; ?>

<div>
    @if(session()->has('warning'))
        <x-alert title="Warning" description="{{ session('warning') }}" icon="fas.triangle-exclamation"
                 class="alert-warning mb-2"/>
    @endif
</div>
