<?php

use Livewire\Volt\Volt;

// Pomodoro Routes
Volt::route('/pomodoro', 'pomodoro.dashboard')->name('pomodoro.dashboard');
Volt::route('/pomodoro/sessions', 'pomodoro.sessions.index')->name('pomodoro.sessions.index');
Volt::route('/pomodoro/sessions/create', 'pomodoro.sessions.create')->name('pomodoro.sessions.create');
Volt::route('/pomodoro/sessions/{session}', 'pomodoro.sessions.show')->name('pomodoro.sessions.show');
Volt::route('/pomodoro/sessions/{session}/edit', 'pomodoro.sessions.edit')->name('pomodoro.sessions.edit');
Volt::route('/pomodoro/sessions/{session}/details', 'pomodoro.sessions.details')->name('pomodoro.sessions.details');
Volt::route('/pomodoro/timer', 'pomodoro.timer')->name('pomodoro.timer');
Volt::route('/pomodoro/reports', 'pomodoro.reports')->name('pomodoro.reports');
Volt::route('/pomodoro/settings', 'pomodoro.settings')->name('pomodoro.settings');

// Pomodoro Tags
Volt::route('/pomodoro/tags', 'pomodoro.tags.index')->name('pomodoro.tags.index');
Volt::route('/pomodoro/tags/create', 'pomodoro.tags.create')->name('pomodoro.tags.create');
