<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Models\Status;
use Livewire\Component;

class StatusManager extends Component
{
    public Project $project;

    public $statuses;

    public $name = '';

    public $color = '#3B82F6'; // Varsayılan bir renk

    public $order = 0;

    public $is_completed = false;

    public function mount(Project $project)
    {
        $this->project = $project;
        $this->statuses = $project->statuses()->orderBy('order')->get();
    }

    public function addStatus(): void
    {
        $this->validate([
            'name' => 'required|string|max:50',
            'color' => 'required|string',
        ]);

        if (Status::where('name', $this->name)->where('project_id', $this->project->id)->exists()) {
            session()->flash('error', 'Status already exists!');

            return;
        }

        $baseSlug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($this->name)));
        $slug = $baseSlug;
        $i = 1;

        while (Status::where('slug', $slug)->where('project_id', $this->project->id)->exists()) {
            $slug = $baseSlug.'-'.$i;
            $i++;
        }

        $status = new Status([
            'name' => $this->name,
            'slug' => $slug,
            'color' => $this->color,
            'order' => $this->order,
            'is_completed' => $this->is_completed,
        ]);
        $this->project->statuses()->save($status);

        $this->statuses = $this->project->statuses()->orderBy('order')->get();

        $this->reset(['name', 'color', 'order', 'is_completed']);
        session()->flash('success', 'Status added successfully!');
    }

    public function deleteStatus(Status $status): void
    {
        if ($status->project_id !== $this->project->id) {
            session()->flash('error', 'Status not found.');

            return;
        }
        if ($status->tasks()->count() > 0) {
            session()->flash('error', 'Cannot delete status: There are tasks assigned to this status.');

            return;
        }

        $status->delete();
        $this->statuses = $this->project->statuses()->orderBy('order')->get();

        session()->flash('success', 'Status deleted successfully!');
    }

    public function updateStatusOrder($items): void
    {
        // Her bir statü için yeni sıra numarasını güncelle
        foreach ($items as $item) {
            $status = Status::find($item['value']);
            if ($status && $status->project_id === $this->project->id) {
                $status->update(['order' => $item['order']]);
            }
        }

        // Statüleri yeniden yükle
        $this->statuses = $this->project->statuses()->orderBy('order')->get();
        $this->dispatch('notify', ['message' => 'Status order updated successfully!', 'type' => 'success']);
    }

    public function render()
    {
        return view('livewire.projects.status-manager');
    }
}
