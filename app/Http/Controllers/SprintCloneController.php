<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Sprint;
use Illuminate\Http\Request;

class SprintCloneController extends Controller
{
    /**
     * Sprint'i kopyala.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Project  $project
     * @param  \App\Models\Sprint  $sprint
     * @return \Illuminate\Http\Response
     */
    public function clone(Request $request, Project $project, Sprint $sprint)
    {
        // Yetkilendirme kontrolü
        $this->authorize('create', $project);
        
        if ($sprint->project_id !== $project->id) {
            abort(404);
        }
        
        // Yeni sprint oluştur
        $newSprint = $sprint->replicate();
        $newSprint->name = 'Copy of ' . $sprint->name;
        $newSprint->is_active = false;
        $newSprint->is_completed = false;
        
        // Tarih ayarları
        if ($request->has('adjust_dates') && $request->adjust_dates) {
            // Eğer sprint'in başlangıç ve bitiş tarihleri varsa, yeni sprint için tarihleri ayarla
            if ($sprint->start_date && $sprint->end_date) {
                $duration = $sprint->start_date->diffInDays($sprint->end_date);
                
                // Yeni sprint için başlangıç tarihi, mevcut tarihin 1 gün sonrası olarak ayarla
                $newSprint->start_date = now()->addDay();
                $newSprint->end_date = $newSprint->start_date->copy()->addDays($duration);
            } else {
                $newSprint->start_date = null;
                $newSprint->end_date = null;
            }
        } else {
            // Tarihleri null olarak ayarla
            $newSprint->start_date = null;
            $newSprint->end_date = null;
        }
        
        $newSprint->save();
        
        // Görevleri kopyala
        if ($request->has('include_tasks') && $request->include_tasks) {
            foreach ($sprint->tasks as $task) {
                $newTask = $task->replicate();
                $newTask->sprint_id = $newSprint->id;
                $newTask->status_id = null; // Yeni görevler başlangıçta durum atanmamış olsun
                $newTask->save();
            }
        }
        
        session()->flash('message', 'Sprint cloned successfully!');
        
        return redirect()->route('sprints.show', ['project' => $project->id, 'sprint' => $newSprint->id]);
    }
}
