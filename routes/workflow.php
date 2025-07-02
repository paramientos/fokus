<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware(['auth'])->prefix('workflows')->as('workflows.')->group(function () {
    // Workflow listing and management
    Volt::route('/', 'workspace-workflows.index')->name('index');
    Volt::route('/create', 'workspace-workflows.create')->name('create');
    Volt::route('/{workflow}', 'workspace-workflows.show')->name('show');
    Volt::route('/{workflow}/edit', 'workspace-workflows.edit')->name('edit');

    // Workflow steps management
    Volt::route('/{workflow}/steps', 'workspace-workflows.steps.index')->name('steps.index');
    Volt::route('/{workflow}/steps/create', 'workspace-workflows.steps.create')->name('steps.create');
    Volt::route('/{workflow}/steps/{step}', 'workspace-workflows.steps.show')->name('steps.show');
    Volt::route('/{workflow}/steps/{step}/edit', 'workspace-workflows.steps.edit')->name('steps.edit');

    // Workflow instances
    Volt::route('/{workflow}/instances', 'workspace-workflows.instances.index')->name('instances.index');
    Volt::route('/{workflow}/instances/create', 'workspace-workflows.instances.create')->name('instances.create');
    Volt::route('/{workflow}/instances/{instance}', 'workspace-workflows.instances.show')->name('instances.show');
    Volt::route('/{workflow}/instances/{instance}/process', 'workspace-workflows.instances.process')->name('instances.process');
});
