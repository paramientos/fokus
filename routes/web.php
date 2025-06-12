<?php

use App\Http\Controllers\MeetingExportController;
use App\Http\Controllers\SprintCloneController;
use App\Http\Controllers\SprintExportController;
use App\Models\WorkspaceInvitation;
use Livewire\Volt\Volt;

Volt::route('/login', 'auth.login')->name('login');
Volt::route('/register', 'auth.register')->name('register');

Route::get('/workspaces/invitation/{token}', function ($token) {
    $invitation = WorkspaceInvitation::where('token', $token)->firstOrFail();

    if ($invitation->isExpired()) {
        return to_route('login')->with('error', 'This invitation has expired.');
    }
    if ($invitation->accepted_at) {
        return to_route('login')->with('info', 'This invitation has already been accepted.');
    }

    if (!auth()->check()) {
        session(['invitation_token' => $token]);

        return to_route('login')->with('info', 'Please login or register to accept the workspace invitation.');
    }

    // Check if user is already a member of the workspace
    if ($invitation->workspace->members()->where('user_id', auth()->id())->exists()) {
        session(['workspace_id' => $invitation->workspace_id]);

        return to_route('dashboard')->with('info', 'You are already a member of ' . $invitation->workspace->name . '.');
    }

    if (auth()->user()->email !== $invitation->email) {
        return to_route('dashboard')->with('error', 'Invitation not found!');
    }

    $invitation->update(['accepted_at' => now()]);

    $invitation->workspace->members()->attach(auth()->id(), [
        'role' => $invitation->role
    ]);

    session(['workspace_id' => $invitation->workspace_id]);

    return redirect()->route('dashboard')->with('success', 'Welcome to ' . $invitation->workspace->name . '!');
})->name('workspaces.invitation.accept');

Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        if (session()->hasAny(['info', 'warning', 'error', 'success'])) {
            return redirect()->route('dashboard.show')->with([
                'info' => session('info'),
                'warning' => session('warning'),
                'error' => session('error'),
                'success' => session('success')
            ]);
        }

        return redirect()->route('dashboard.show');
    })->name('dashboard');

    Volt::route('/dashboard', 'pages.dashboard')->name('dashboard.show');

// Workspaces
    Volt::route('/workspaces', 'workspaces.index')->name('workspaces.index');
    Volt::route('/workspaces/{id}', 'workspaces.show')->name('workspaces.show');
    Volt::route('/workspaces/{workspace}/members', 'workspaces.members')->name('workspaces.members');

// Projeler
    Volt::route('/projects', 'projects.index')->name('projects.index');
    Volt::route('/projects/create', 'projects.create')->name('projects.create');
    Volt::route('/projects/{project}', 'projects.show')->name('projects.show');
    Volt::route('/projects/{project}/edit', 'projects.edit')->name('projects.edit');

// Proje Statü Yönetimi
    Volt::route('/projects/{project}/statuses', 'projects.status-manager')->name('projects.statuses');

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

// Proje Kanban Board ve Statü Geçiş Yönetimi
    Volt::route('/projects/{project}/status-transitions', 'projects.status-transition-manager')->name('projects.status-transitions');
    Volt::route('/projects/{project}/activities', 'activities.timeline')->name('activities.timeline');
    Volt::route('/projects/{project}/tasks/{task}/activities', 'activities.timeline')->name('activities.timeline-task');
    Volt::route('/projects/{project}/sprints/{sprint}/activities', 'activities.timeline')->name('activities.timeline-sprint');

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

// Wiki
    Volt::route('/wiki', 'wiki.main')->name('wiki.main');
    Volt::route('/projects/{project}/wiki', 'wiki.index')->name('wiki.index');
    Volt::route('/projects/{project}/wiki/create', 'wiki.create')->name('wiki.create');
    Volt::route('/projects/{project}/wiki/{slug}', 'wiki.show')->name('wiki.show');

// Profil
    Volt::route('/profile', 'profile.index')->name('profile.index');

// Gmail Entegrasyonu
    Volt::route('/mail', 'mail.inbox')->name('mail.inbox');
    Route::get('/auth/google', [\App\Http\Controllers\GoogleAuthController::class, 'redirect'])->name('google.redirect');
    Route::get('/auth/google/callback', [\App\Http\Controllers\GoogleAuthController::class, 'callback'])->name('google.callback');

    Volt::route('/logout', 'auth.logout')->name('logout');

    // API Test Aracı (Workspace)
    Volt::route('/api-tester', 'api-tester.workspace')->name('api-tester.workspace');
    Volt::route('/api-tester/{apiEndpoint}/history', 'api-tester.history')->name('api-tester.history');
    Volt::route('/api-tester/history/{historyEntry}', 'api-tester.history-detail')->name('api-tester.history-detail');
    // Task ile ilişkili API test aracı
    Volt::route('/projects/{project}/tasks/{task}/api-tester', 'api-tester.task')->name('api-tester.task');
});
