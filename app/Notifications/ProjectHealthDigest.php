<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectHealthDigest extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Project $project,
        public array $healthData
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $healthScore = $this->healthData['health_score'];
        $trend = $this->healthData['health_trend'];

        $healthStatus = match (true) {
            $healthScore >= 80 => '🟢 Excellent',
            $healthScore >= 60 => '🔵 Good',
            $healthScore >= 40 => '🟡 Warning',
            default => '🔴 Critical'
        };

        $trendEmoji = match ($trend) {
            'improving' => '📈',
            'declining' => '📉',
            default => '➡️'
        };

        return (new MailMessage)
            ->subject("📊 Daily Health Digest: {$this->project->name}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("Here's your daily health summary for **{$this->project->name}**:")
            ->line('')
            ->line("**Overall Health: {$healthStatus} ({$healthScore}%)**")
            ->line("**Trend: {$trendEmoji} ".ucfirst($trend).'**')
            ->line('')
            ->line('**Key Metrics:**')
            ->line("• Overdue Tasks: {$this->healthData['overdue_tasks']}")
            ->line("• Blocked Tasks: {$this->healthData['blocked_tasks']}")
            ->line("• Team Velocity: {$this->healthData['velocity']}")
            ->line('')
            ->when($this->healthData['critical_alerts'] > 0, function ($mail) {
                $mail->line("⚠️ **{$this->healthData['critical_alerts']} Critical Alerts** require immediate attention!");
            })
            ->when($this->healthData['high_alerts'] > 0, function ($mail) {
                $mail->line("🔶 **{$this->healthData['high_alerts']} High Priority Alerts** need review.");
            })
            ->when(count($this->healthData['risk_factors']) > 0, function ($mail) {
                $mail->line('')
                    ->line('**Risk Factors Detected:**');
                foreach ($this->healthData['risk_factors'] as $risk) {
                    $mail->line('• '.ucwords(str_replace('_', ' ', $risk)));
                }
            })
            ->action('View Full Health Dashboard', route('projects.health', $this->project))
            ->line('Stay on top of your project health with Fokus!')
            ->salutation('Best regards, Fokus Team');
    }

    public function toArray($notifiable): array
    {
        return [
            'project_id' => $this->project->id,
            'project_name' => $this->project->name,
            'health_score' => $this->healthData['health_score'],
            'health_trend' => $this->healthData['health_trend'],
            'critical_alerts' => $this->healthData['critical_alerts'],
            'high_alerts' => $this->healthData['high_alerts'],
            'message' => "Daily health digest for {$this->project->name}",
            'action_url' => route('projects.health', $this->project),
        ];
    }
}
