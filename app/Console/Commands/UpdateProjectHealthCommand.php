<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\ProjectHealthService;
use Illuminate\Console\Command;

class UpdateProjectHealthCommand extends Command
{
    protected $signature = 'projects:update-health {--project=* : Specific project IDs to update}';

    protected $description = 'Update health metrics for all active projects';

    public function handle()
    {
        $projectIds = $this->option('project');

        $query = Project::active()->with(['tasks', 'members']);

        if (!empty($projectIds)) {
            $query->whereIn('id', $projectIds);
        }

        $projects = $query->get();

        if ($projects->isEmpty()) {
            $this->info('No projects found to update.');

            return;
        }

        $healthService = new ProjectHealthService;
        $bar = $this->output->createProgressBar($projects->count());
        $bar->start();

        $updated = 0;
        $errors = 0;

        foreach ($projects as $project) {
            try {
                $healthService->updateProjectHealth($project);
                $updated++;
            } catch (\Exception $e) {
                $this->error("Failed to update health for project {$project->name}: ".$e->getMessage());
                $errors++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info('Health update completed:');
        $this->info("- Updated: {$updated} projects");

        if ($errors > 0) {
            $this->error("- Errors: {$errors} projects");
        }
    }
}
