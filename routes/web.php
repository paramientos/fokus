<?php

use Livewire\Volt\Volt;
use App\Http\Controllers\SprintExportController;
use App\Http\Controllers\SprintCloneController;
use App\Http\Controllers\MeetingExportController;

// Ana sayfa
Volt::route('/', 'pages.dashboard')->name('dashboard');

// Projeler
Volt::route('/projects', 'projects.index')->name('projects.index');
Volt::route('/projects/create', 'projects.create')->name('projects.create');
Volt::route('/projects/{project}', 'projects.show')->name('projects.show');
Volt::route('/projects/{project}/edit', 'projects.edit')->name('projects.edit');

// Proje Üyeleri
Volt::route('/projects/{project}/members', 'projects.members.index')->name('projects.members.index');

// Görevler
Volt::route('/projects/{project}/tasks', 'tasks.index')->name('tasks.index');
Volt::route('/projects/{project}/tasks/create', 'tasks.create')->name('tasks.create');
Volt::route('/projects/{project}/tasks/{task}', 'tasks.show')->name('tasks.show');
Volt::route('/projects/{project}/tasks/{task}/edit', 'tasks.edit')->name('tasks.edit');
Volt::route('/tasks/{task}/history', 'tasks.history');

// Sprintler
Volt::route('/projects/{project}/sprints', 'sprints.index')->name('sprints.index');
Volt::route('/projects/{project}/sprints/create', 'sprints.create')->name('sprints.create');
Volt::route('/projects/{project}/sprints/{sprint}', 'sprints.show')->name('sprints.show');
Volt::route('/projects/{project}/sprints/{sprint}/edit', 'sprints.edit')->name('sprints.edit');
Volt::route('/projects/{project}/sprints/{sprint}/report', 'sprints.report')->name('sprints.report');
Volt::route('/projects/{project}/sprints/{sprint}/board', 'sprints.board')->name('sprints.board');
Volt::route('/projects/{project}/sprints/{sprint}/burndown', 'sprints.burndown')->name('sprints.burndown');
Volt::route('/projects/{project}/sprints/{sprint}/retrospective', 'sprints.retrospective')->name('sprints.retrospective');
Volt::route('/projects/{project}/sprints/calendar', 'sprints.calendar')->name('sprints.calendar');

// Aktivite Zaman Çizelgesi
Volt::route('/projects/{project}/activities', 'activities.timeline')->name('activities.timeline');
Volt::route('/projects/{project}/tasks/{task}/activities', 'activities.timeline')->name('activities.timeline');
Volt::route('/projects/{project}/sprints/{sprint}/activities', 'activities.timeline')->name('activities.timeline');

// Sprint İşlemleri
Route::get('/projects/{project}/sprints/{sprint}/export/csv', [SprintExportController::class, 'exportCsv'])->name('sprints.export.csv');
Route::get('/projects/{project}/sprints/{sprint}/export/json', [SprintExportController::class, 'exportJson'])->name('sprints.export.json');
Route::post('/projects/{project}/sprints/{sprint}/clone', [SprintCloneController::class, 'clone'])->name('sprints.clone');

// Kanban Board
Volt::route('/projects/{project}/board', 'board.index')->name('board.index');
Volt::route('/projects/{project}/kanban', 'board.kanban')->name('board.kanban');
Volt::route('/projects/{project}/kanban-board', 'tasks.kanban-board')->name('tasks.kanban-board');

// Gantt Şeması
Volt::route('/projects/{project}/gantt', 'tasks.gantt-chart')->name('tasks.gantt-chart');

// Toplantılar
Volt::route('/meetings', 'meetings.index')->name('meetings.index');
Volt::route('/meetings/create', 'meetings.create')->name('meetings.create');
Volt::route('/meetings/{meeting}', 'meetings.show')->name('meetings.show');
Volt::route('/meetings/{meeting}/edit', 'meetings.edit')->name('meetings.edit');
Volt::route('/meetings/{meeting}/join', 'meetings.video-conference')->name('meetings.join');
Volt::route('/projects/{project}/meetings', 'meetings.index')->name('projects.meetings.index');

// Toplantı dışa aktarma işlemleri
Route::get('/meetings/export/ics/{id?}', [MeetingExportController::class, 'exportICalendar'])->name('meetings.export.ics');
Route::get('/meetings/export/csv/{id?}', [MeetingExportController::class, 'exportCsv'])->name('meetings.export.csv');

// Profil
Volt::route('/profile', 'profile.index')->name('profile.index');

// Yetkilendirme
Volt::route('/login', 'auth.login')->name('login');
Volt::route('/register', 'auth.register')->name('register');
Volt::route('/logout', 'auth.logout')->name('logout');
