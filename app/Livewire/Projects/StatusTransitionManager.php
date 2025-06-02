<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Models\Status;
use App\Models\StatusTransition;
use Livewire\Component;

class StatusTransitionManager extends Component
{
    public Project $project;
    public $statuses;
    public $transitions;

    public function mount(Project $project)
    {
        $this->project = $project;
        $this->statuses = $project->statuses()->orderBy('order')->get();
        $this->transitions = StatusTransition::where('project_id', $project->id)->get();
    }

    public function toggleTransition($fromId, $toId)
    {
        $existing = StatusTransition::where('project_id', $this->project->id)
            ->where('from_status_id', $fromId)
            ->where('to_status_id', $toId)
            ->first();
        if ($existing) {
            $existing->delete();
        } else {
            StatusTransition::create([
                'project_id' => $this->project->id,
                'from_status_id' => $fromId,
                'to_status_id' => $toId,
            ]);
        }
        $this->transitions = StatusTransition::where('project_id', $this->project->id)->get();
        $this->dispatch('transitionUpdated');
    }

    public function render()
    {
        return view('livewire.projects.status-transition-manager');
    }
}
