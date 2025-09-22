<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Sprint;
use Illuminate\Http\Request;

class SprintController extends Controller
{
    /**
     * Başlat bir sprint.
     *
     * @return \Illuminate\Http\Response
     */
    public function start(Request $request, Project $project, Sprint $sprint)
    {
        // Yetkilendirme kontrolü
        $this->authorize('update', $project);

        if ($sprint->project_id !== $project->id) {
            abort(404);
        }

        // Önce diğer aktif sprintleri devre dışı bırak
        Sprint::where('project_id', $project->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        // Bu sprinti aktif yap
        $sprint->update([
            'is_active' => true,
            'is_completed' => false,
            'start_date' => $sprint->start_date ?? now(),
        ]);

        session()->flash('message', 'Sprint started successfully!');

        return redirect()->back();
    }

    /**
     * Tamamla bir sprint.
     *
     * @return \Illuminate\Http\Response
     */
    public function complete(Request $request, Project $project, Sprint $sprint)
    {
        // Yetkilendirme kontrolü
        $this->authorize('update', $project);

        if ($sprint->project_id !== $project->id) {
            abort(404);
        }

        // Sprinti tamamla
        $sprint->update([
            'is_active' => false,
            'is_completed' => true,
            'end_date' => $sprint->end_date ?? now(),
        ]);

        session()->flash('message', 'Sprint completed successfully!');

        return redirect()->back();
    }

    /**
     * İptal et bir sprint.
     *
     * @return \Illuminate\Http\Response
     */
    public function cancel(Request $request, Project $project, Sprint $sprint)
    {
        // Yetkilendirme kontrolü
        $this->authorize('update', $project);

        if ($sprint->project_id !== $project->id) {
            abort(404);
        }

        // Sprinti iptal et
        $sprint->update([
            'is_active' => false,
            'is_completed' => false,
        ]);

        session()->flash('message', 'Sprint cancelled successfully!');

        return redirect()->back();
    }

    /**
     * Sil bir sprint.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Project $project, Sprint $sprint)
    {
        // Yetkilendirme kontrolü
        $this->authorize('delete', $project);

        if ($sprint->project_id !== $project->id) {
            abort(404);
        }

        // Sprint'e bağlı görevleri güncelle
        $sprint->tasks()->update(['sprint_id' => null]);

        // Sprinti sil
        $sprint->delete();

        session()->flash('message', 'Sprint deleted successfully!');

        return redirect()->route('projects.sprints.index', $project);
    }
}
