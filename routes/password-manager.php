<?php

use Livewire\Volt\Volt;

// Password Manager Dashboard
Volt::route('/password-manager', 'password-manager.dashboard')->name('password-manager.dashboard');

// Password Vaults
Volt::route('/password-manager/vaults', 'password-manager.vaults.index')->name('password-manager.vaults.index');
Volt::route('/password-manager/vaults/create', 'password-manager.vaults.create')->name('password-manager.vaults.create');
Volt::route('/password-manager/vaults/{vault}', 'password-manager.vaults.show')->name('password-manager.vaults.show');
Volt::route('/password-manager/vaults/{vault}/edit', 'password-manager.vaults.edit')->name('password-manager.vaults.edit');

// Password Categories
Volt::route('/password-manager/vaults/{vault}/categories', 'password-manager.categories.index')->name('password-manager.categories.index');
Volt::route('/password-manager/vaults/{vault}/categories/create', 'password-manager.categories.create')->name('password-manager.categories.create');
Volt::route('/password-manager/vaults/{vault}/categories/{category}/edit', 'password-manager.categories.edit')->name('password-manager.categories.edit');

// Password Entries
Volt::route('/password-manager/vaults/{vault}/entries', 'password-manager.entries.index')->name('password-manager.entries.index');
Volt::route('/password-manager/vaults/{vault}/entries/create', 'password-manager.entries.create')->name('password-manager.entries.create');
Volt::route('/password-manager/vaults/{vault}/entries/{entry}', 'password-manager.entries.show')->name('password-manager.entries.show');
Volt::route('/password-manager/vaults/{vault}/entries/{entry}/edit', 'password-manager.entries.edit')->name('password-manager.entries.edit');

// Password Generator
Volt::route('/password-manager/generator', 'password-manager.generator')->name('password-manager.generator');

// Trash
Volt::route('/password-manager/trash', 'password-manager.trash')->name('password-manager.trash');

// Security Check
Volt::route('/password-manager/security-check', 'password-manager.security-check')->name('password-manager.security-check');
