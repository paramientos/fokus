<?php

namespace App\Console\Commands;

use App\Models\Workspace;
use App\Services\GamificationService;
use Illuminate\Console\Command;

class InitializeGamification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gamification:init {--workspace= : Specific workspace ID to initialize}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize gamification system with default achievements for workspaces';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $gamificationService = app(GamificationService::class);
        
        if ($workspaceId = $this->option('workspace')) {
            $workspace = Workspace::findOrFail($workspaceId);
            $this->initializeWorkspace($workspace, $gamificationService);
        } else {
            $workspaces = Workspace::all();
            $this->info("Initializing gamification for {$workspaces->count()} workspaces...");
            
            foreach ($workspaces as $workspace) {
                $this->initializeWorkspace($workspace, $gamificationService);
            }
        }

        $this->info('Gamification initialization completed!');
    }

    private function initializeWorkspace(Workspace $workspace, GamificationService $gamificationService)
    {
        $this->info("Initializing workspace: {$workspace->name}");
        
        try {
            $gamificationService->initializeDefaultAchievements($workspace->id);
            $this->info("✓ Default achievements created for {$workspace->name}");
            
            // Initialize leaderboards for all workspace members
            foreach ($workspace->members as $user) {
                $gamificationService->updateUserLeaderboards($user);
            }
            $this->info("✓ Leaderboards initialized for {$workspace->members->count()} members");
            
        } catch (\Exception $e) {
            $this->error("✗ Failed to initialize {$workspace->name}: {$e->getMessage()}");
        }
    }
}
