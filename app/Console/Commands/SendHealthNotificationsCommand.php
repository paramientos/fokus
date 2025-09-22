<?php

namespace App\Console\Commands;

use App\Services\HealthNotificationService;
use Illuminate\Console\Command;

class SendHealthNotificationsCommand extends Command
{
    protected $signature = 'health:send-notifications 
                            {--type=daily : Type of notification (daily, weekly, critical)}
                            {--project= : Specific project ID to process}';

    protected $description = 'Send automated health notifications to project teams';

    public function handle(HealthNotificationService $notificationService): int
    {
        $type = $this->option('type');
        $projectId = $this->option('project');

        $this->info("Starting {$type} health notifications...");

        try {
            match ($type) {
                'daily' => $this->sendDailyDigests($notificationService),
                'weekly' => $this->sendWeeklyReports($notificationService),
                'critical' => $this->sendCriticalAlerts($notificationService, $projectId),
                default => $this->error("Invalid notification type: {$type}")
            };

            $this->info('Health notifications sent successfully!');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to send notifications: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    private function sendDailyDigests(HealthNotificationService $service): void
    {
        $this->info('Sending daily health digests...');
        $service->sendDailyHealthDigest();
        $this->info('Daily digests sent successfully.');
    }

    private function sendWeeklyReports(HealthNotificationService $service): void
    {
        $this->info('Sending weekly health reports...');
        $service->sendWeeklyHealthReport();
        $this->info('Weekly reports sent successfully.');
    }

    private function sendCriticalAlerts(HealthNotificationService $service, ?string $projectId): void
    {
        if ($projectId) {
            $project = \App\Models\Project::findOrFail($projectId);
            $this->info("Checking critical alerts for project: {$project->name}");
            $service->sendCriticalAlerts($project);
        } else {
            $this->info('Checking critical alerts for all active projects...');
            $projects = \App\Models\Project::active()->get();

            foreach ($projects as $project) {
                $service->sendCriticalAlerts($project);
                $this->line("Processed: {$project->name}");
            }
        }

        $this->info('Critical alert check completed.');
    }
}
