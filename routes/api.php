<?php

use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// API Endpoints for Projects
Route::middleware('auth:sanctum')->group(function () {
    // API Test Aracı Rotaları
    Route::prefix('api-tester')->group(function () {
        Route::get('/endpoints', [\App\Http\Controllers\ApiEndpointController::class, 'index']);
        Route::post('/endpoints', [\App\Http\Controllers\ApiEndpointController::class, 'store']);
        Route::get('/endpoints/{apiEndpoint}', [\App\Http\Controllers\ApiEndpointController::class, 'show']);
        Route::put('/endpoints/{apiEndpoint}', [\App\Http\Controllers\ApiEndpointController::class, 'update']);
        Route::delete('/endpoints/{apiEndpoint}', [\App\Http\Controllers\ApiEndpointController::class, 'destroy']);
        Route::post('/endpoints/{apiEndpoint}/execute', [\App\Http\Controllers\ApiEndpointController::class, 'execute']);
        Route::get('/endpoints/{apiEndpoint}/history', [\App\Http\Controllers\ApiEndpointController::class, 'history']);
        Route::get('/history/{history}', [\App\Http\Controllers\ApiEndpointController::class, 'historyDetail']);
    });

    // Projects
    Route::get('/projects', function () {
        return Project::where('user_id', auth()->id())->get();
    });

    Route::get('/projects/{project}', function (Project $project) {
        if ($project->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return $project->load('tasks', 'sprints', 'statuses');
    });

    // Sprints
    Route::get('/projects/{project}/sprints', function (Project $project) {
        if ($project->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return $project->sprints;
    });

    Route::get('/projects/{project}/sprints/{sprint}', function (Project $project, Sprint $sprint) {
        if ($project->user_id !== auth()->id() || $sprint->project_id !== $project->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return $sprint->load('tasks');
    });

    Route::post('/projects/{project}/sprints', function (Request $request, Project $project) {
        if ($project->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'goal' => 'nullable|string|max:1000',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean',
        ]);

        // If marking this sprint as active, deactivate all other sprints first
        if (isset($validated['is_active']) && $validated['is_active']) {
            Sprint::where('project_id', $project->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        $sprint = $project->sprints()->create($validated);

        return response()->json($sprint, 201);
    });

    Route::put('/projects/{project}/sprints/{sprint}', function (Request $request, Project $project, Sprint $sprint) {
        if ($project->user_id !== auth()->id() || $sprint->project_id !== $project->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'goal' => 'nullable|string|max:1000',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean',
            'is_completed' => 'boolean',
        ]);

        // If marking this sprint as active, deactivate all other sprints first
        if (isset($validated['is_active']) && $validated['is_active'] && !$sprint->is_active) {
            Sprint::where('project_id', $project->id)
                ->where('id', '!=', $sprint->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        $sprint->update($validated);

        return response()->json($sprint);
    });

    Route::delete('/projects/{project}/sprints/{sprint}', function (Project $project, Sprint $sprint) {
        if ($project->user_id !== auth()->id() || $sprint->project_id !== $project->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Update tasks to remove sprint association
        Task::where('sprint_id', $sprint->id)->update(['sprint_id' => null]);

        $sprint->delete();

        return response()->json(null, 204);
    });

    // Tasks in Sprint
    Route::get('/projects/{project}/sprints/{sprint}/tasks', function (Project $project, Sprint $sprint) {
        if ($project->user_id !== auth()->id() || $sprint->project_id !== $project->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return $sprint->tasks()->with('status', 'user')->get();
    });

    Route::post('/projects/{project}/sprints/{sprint}/tasks/{task}', function (Project $project, Sprint $sprint, Task $task) {
        if ($project->user_id !== auth()->id() || $sprint->project_id !== $project->id || $task->project_id !== $project->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $task->update(['sprint_id' => $sprint->id]);

        return response()->json($task);
    });

    Route::delete('/projects/{project}/sprints/{sprint}/tasks/{task}', function (Project $project, Sprint $sprint, Task $task) {
        if ($project->user_id !== auth()->id() || $sprint->project_id !== $project->id || $task->project_id !== $project->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $task->update(['sprint_id' => null]);

        return response()->json(null, 204);
    });

    // Sprint Report
    Route::get('/projects/{project}/sprints/{sprint}/report', function (Project $project, Sprint $sprint) {
        if ($project->user_id !== auth()->id() || $sprint->project_id !== $project->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $sprint->load(['tasks.status', 'tasks.user']);

        // Sprint başlangıç ve bitiş tarihleri
        $startDate = $sprint->start_date ?? $sprint->created_at;
        $endDate = $sprint->end_date ?? $startDate->copy()->addDays(14);

        // Görevlerin durumlara göre dağılımı
        $tasksByStatus = $sprint->tasks->groupBy(function ($task) {
            return $task->status ? $task->status->name : 'No Status';
        });

        // Tamamlanan görevler
        $completedTasks = $sprint->tasks->filter(function ($task) {
            return $task->status && $task->status->slug === 'done';
        });

        // Görevlerin kullanıcılara göre dağılımı
        $tasksByUser = $sprint->tasks->groupBy(function ($task) {
            return $task->user ? $task->user->name : 'Unassigned';
        });

        return response()->json([
            'sprint' => $sprint,
            'stats' => [
                'total_tasks' => $sprint->tasks->count(),
                'completed_tasks' => $completedTasks->count(),
                'completion_percentage' => $sprint->tasks->count() > 0
                    ? round(($completedTasks->count() / $sprint->tasks->count()) * 100)
                    : 0,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'duration_days' => $startDate->diffInDays($endDate) + 1,
                'remaining_days' => now()->diffInDays($endDate, false),
                'status_distribution' => $tasksByStatus->map->count()->toArray(),
                'user_distribution' => $tasksByUser->map(function ($tasks, $user) {
                    return [
                        'total' => $tasks->count(),
                        'completed' => $tasks->filter(function ($task) {
                            return $task->status && $task->status->slug === 'done';
                        })->count(),
                    ];
                })->toArray(),
            ],
        ]);
    });
});
