<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class ProjectHealthAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Project $project,
        public Collection $alerts
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $criticalCount = $this->alerts->where('severity', 'critical')->count();
        $highCount = $this->alerts->where('severity', 'high')->count();

        return (new MailMessage)
            ->subject("🚨 Critical Health Alert: {$this->project->name}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("We've detected critical issues in your project **{$this->project->name}** that require immediate attention.")
            ->line("**Alert Summary:**")
            ->line("• Critical Alerts: {$criticalCount}")
            ->line("• High Priority Alerts: {$highCount}")
            ->line('')
            ->line("**Recent Alerts:**")
            ->when($this->alerts->take(3), function ($mail, $recentAlerts) {
                foreach ($recentAlerts as $alert) {
                    $mail->line("• **{$alert->title}** ({$alert->severity})");
                    $mail->line("  {$alert->description}");
                }
            })
            ->action('View Project Health Dashboard', route('projects.health', $this->project))
            ->line('Please review these alerts and take necessary actions to maintain project health.')
            ->salutation('Best regards, Fokus Team');
    }

    public function toArray($notifiable): array
    {
        return [
            'project_id' => $this->project->id,
            'project_name' => $this->project->name,
            'alert_count' => $this->alerts->count(),
            'critical_count' => $this->alerts->where('severity', 'critical')->count(),
            'high_count' => $this->alerts->where('severity', 'high')->count(),
            'message' => "Critical health alerts detected in {$this->project->name}",
            'action_url' => route('projects.health', $this->project),
        ];
    }
}
