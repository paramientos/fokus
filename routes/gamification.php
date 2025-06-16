<?php

use Livewire\Volt\Volt;

// Gamification Routes
Volt::route('/gamification', 'gamification.dashboard')->name('gamification.dashboard');
Volt::route('/gamification/achievements', 'gamification.achievements')->name('gamification.achievements');
Volt::route('/gamification/leaderboard', 'gamification.leaderboard')->name('gamification.leaderboard');
