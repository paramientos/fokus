<?php

use App\Models\Project;
use Illuminate\Support\Facades\Request as HttpRequest;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new class extends Component
{
    #[Url(as: 'selectedProjectId', history: true, keep: true)]
    public $urlProjectId = null;

    public ?Project $project = null;

    public function mount(): void
    {
        $this->determineProject();
    }

    // updated<PropertyName> magic method for reactive updates when urlProjectId changes
    public function updatedUrlProjectId(): void
    {
        $this->determineProject();
    }

    // Listen for Livewire's navigate event if using wire:navigate extensively
    #[On('navigated')]
    public function handleNavigation(): void
    {
        $this->determineProject();
    }

    public function determineProject(): void
    {
        $route = HttpRequest::route();
        $routeProjectModel = null;

        if ($route) {
            $projectParam = $route->parameter('project');
            if ($projectParam) {
                $routeProjectModel = ($projectParam instanceof Project) ? $projectParam : Project::find($projectParam);
            }
        }

        $queryProjectModel = null;
        // Ensure urlProjectId is not 'all', 'none', or an empty string before trying to find the Project
        if ($this->urlProjectId && !in_array((string)$this->urlProjectId, ['all', 'none', ''], true)) {
            $queryProjectModel = Project::find($this->urlProjectId);
        }

        $isProjectSpecificRoute = false;
        if ($route && $routeProjectModel) {
            $projectSpecificRouteNames = [
                'projects.show',
                'projects.edit',
                'tasks.index', 'tasks.show', 'tasks.create', 'tasks.edit',
                'sprints.index', 'sprints.show',
                'board.show',
                'projects.meetings.index', // Ensure this route exists and is project specific
                'projects.wiki.show',      // Ensure this route exists and is project specific
                'tasks.gantt-chart'
            ];
            // Check if the current route name is in the list of project-specific routes
            if ($route->getName() && in_array($route->getName(), $projectSpecificRouteNames)) {
                $isProjectSpecificRoute = true;
            }
        }

        if ($isProjectSpecificRoute) {
            // If on a designated project-specific page, that project takes precedence
            $this->project = $routeProjectModel;
        } elseif ($queryProjectModel) {
            // If selectedProjectId is in the URL, that takes next precedence
            $this->project = $queryProjectModel;
        } elseif ($routeProjectModel &&
                  !$this->urlProjectId && // No overriding selectedProjectId in URL
                  $route && $route->getName() &&
                  // Check if it's a general project-related route, not necessarily in the specific list
                  (Str::startsWith($route->getName(), 'projects.') || Str::startsWith($route->getName(), 'tasks.') || Str::startsWith($route->getName(), 'sprints.') || Str::startsWith($route->getName(), 'board.')) &&
                  // Ensure the project parameter from route is a valid ID-like value
                  !in_array((string)$route->parameter('project'), ['all', 'none', ''], true)
                  ) {
            // Fallback: if on a generic project page (e.g. /projects/{id}/some-other-subpage)
            // and no selectedProjectId is in URL, use the project from the route.
            $this->project = $routeProjectModel;
        } else {
            $this->project = null;
        }
    }
};

?>

<div>
    @if($project)
        <x-menu-separator/>

        <div class="px-4 py-2">
            <p class="text-xs text-gray-500">CURRENT PROJECT</p>
            <p class="font-medium">{{ $project->name }}</p>
        </div>

        <div class="px-4 py-2">
            <p class="text-xs text-gray-500">CURRENT PROJECT</p>
            <p class="font-medium">{{ $project->name }}</p>
        </div>

        <x-menu-item title="Project Overview" icon="o-document-text" link="/projects/{{ $project->id }}"/>
        <x-menu-item title="Board" icon="o-view-columns" link="/projects/{{ $project->id }}/board"/>
        <x-menu-item title="Tasks" icon="o-clipboard-document-list"
                     link="/projects/{{ $project->id }}/tasks"/>
        <x-menu-item title="Sprints" icon="fas.calendar" link="/projects/{{ $project->id }}/sprints"/>
        <x-menu-item title="API Tester" icon="fas.calendar" link="/api-tester"/>
        <x-menu-item title="Meetings" icon="fas.calendar-alt" link="/projects/{{ $project->id }}/meetings"/>
        <x-menu-item title="Wiki" icon="fas.book" link="/projects/{{ $project->id }}/wiki"/>
        <x-menu-item title="Gantt Chart" icon="fas.chart-gantt"
                     :link="route('tasks.gantt-chart', $project)"/>
    @endif
</div>
