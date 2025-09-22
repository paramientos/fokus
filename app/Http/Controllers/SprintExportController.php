<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Sprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class SprintExportController extends Controller
{
    /**
     * Sprint'i CSV formatında dışa aktar.
     *
     * @return \Illuminate\Http\Response
     */
    public function exportCsv(Request $request, Project $project, Sprint $sprint)
    {
        // Yetkilendirme kontrolü
        $this->authorize('view', $project);

        if ($sprint->project_id !== $project->id) {
            abort(404);
        }

        // Sprint verilerini yükle
        $sprint->load(['tasks.status', 'tasks.user']);

        // CSV başlıkları
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="sprint_'.$sprint->id.'_export.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        // CSV verilerini oluştur
        $callback = function () use ($sprint) {
            $file = fopen('php://output', 'w');

            // Başlık satırı
            fputcsv($file, [
                'ID', 'Title', 'Description', 'Status', 'Assignee',
                'Priority', 'Story Points', 'Created At', 'Updated At',
            ]);

            // Görev verileri
            foreach ($sprint->tasks as $task) {
                fputcsv($file, [
                    $task->id,
                    $task->title,
                    $task->description,
                    $task->status ? $task->status->name : 'No Status',
                    $task->user ? $task->user->name : 'Unassigned',
                    $task->priority,
                    $task->story_points,
                    $task->created_at->format('Y-m-d H:i:s'),
                    $task->updated_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Sprint'i JSON formatında dışa aktar.
     *
     * @return \Illuminate\Http\Response
     */
    public function exportJson(Request $request, Project $project, Sprint $sprint)
    {
        // Yetkilendirme kontrolü
        $this->authorize('view', $project);

        if ($sprint->project_id !== $project->id) {
            abort(404);
        }

        // Sprint verilerini yükle
        $sprint->load(['tasks.status', 'tasks.user']);

        // Sprint istatistiklerini hesapla
        $startDate = $sprint->start_date ?? $sprint->created_at;
        $endDate = $sprint->end_date ?? $startDate->copy()->addDays(14);

        // Tamamlanan görevler
        $completedTasks = $sprint->tasks->filter(function ($task) {
            return $task->status && $task->status->slug === 'done';
        });

        // Sprint verilerini hazırla
        $data = [
            'sprint' => [
                'id' => $sprint->id,
                'name' => $sprint->name,
                'goal' => $sprint->goal,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'is_active' => $sprint->is_active,
                'is_completed' => $sprint->is_completed,
                'created_at' => $sprint->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $sprint->updated_at->format('Y-m-d H:i:s'),
            ],
            'stats' => [
                'total_tasks' => $sprint->tasks->count(),
                'completed_tasks' => $completedTasks->count(),
                'completion_percentage' => $sprint->tasks->count() > 0
                    ? round(($completedTasks->count() / $sprint->tasks->count()) * 100)
                    : 0,
                'duration_days' => $startDate->diffInDays($endDate) + 1,
                'remaining_days' => now()->diffInDays($endDate, false),
            ],
            'tasks' => $sprint->tasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'status' => $task->status ? $task->status->name : 'No Status',
                    'assignee' => $task->user ? $task->user->name : 'Unassigned',
                    'priority' => $task->priority,
                    'story_points' => $task->story_points,
                    'created_at' => $task->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $task->updated_at->format('Y-m-d H:i:s'),
                ];
            })->toArray(),
        ];

        // JSON dosyasını oluştur
        $headers = [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="sprint_'.$sprint->id.'_export.json"',
        ];

        return response()->json($data, 200, $headers);
    }
}
