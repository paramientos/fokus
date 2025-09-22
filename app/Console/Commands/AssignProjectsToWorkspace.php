<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Console\Command;

class AssignProjectsToWorkspace extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:assign-projects-to-workspace';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign existing projects to workspaces';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to assign projects to workspaces...');

        // Tüm projeleri al
        $projects = Project::whereNull('workspace_id')->get();

        if ($projects->isEmpty()) {
            $this->info('No projects found that need to be assigned to a workspace.');

            return;
        }

        $this->info('Found '.$projects->count().' projects that need to be assigned to a workspace.');

        // Her proje sahibi için bir workspace oluştur
        $projectsByOwner = $projects->groupBy('user_id');

        foreach ($projectsByOwner as $userId => $ownerProjects) {
            $owner = User::find($userId);

            if (!$owner) {
                $this->error('Owner with ID '.$userId.' not found. Skipping projects.');

                continue;
            }

            // Kullanıcının zaten bir workspace'i var mı kontrol et
            $workspace = Workspace::where('owner_id', $userId)->first();

            if (!$workspace) {
                // Workspace oluştur
                $workspace = Workspace::create([
                    'name' => $owner->name.'\'s Workspace',
                    'description' => 'Default workspace for '.$owner->name,
                    'owner_id' => $userId,
                    'created_by' => $userId,
                ]);

                $this->info('Created new workspace for '.$owner->name);

                // Workspace sahibini üye olarak ekle
                $workspace->members()->attach($userId, [
                    'role' => 'admin',
                ]);
            }

            // Projeleri workspace'e ata
            foreach ($ownerProjects as $project) {
                $project->workspace_id = $workspace->id;
                $project->save();

                $this->info('Assigned project "'.$project->name.'" to workspace "'.$workspace->name.'"');

                // Proje üyelerini workspace'e ekle
                foreach ($project->teamMembers as $member) {
                    // Eğer üye zaten workspace'de değilse ekle
                    if (!$workspace->members()->where('user_id', $member->id)->exists()) {
                        $workspace->members()->attach($member->id, [
                            'role' => $member->pivot->role,
                        ]);

                        $this->info('Added project member '.$member->name.' to workspace '.$workspace->name);
                    }
                }
            }
        }

        $this->info('All projects have been assigned to workspaces successfully!');
    }
}
