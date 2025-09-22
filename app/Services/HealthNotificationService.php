<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectAlert;
use App\Notifications\ProjectHealthAlert;
use App\Notifications\ProjectHealthDigest;
use Illuminate\Support\Collection;

class HealthNotificationService
{
    public function sendCriticalAlerts(Project $project): void
    {
        $criticalAlerts = $project->alerts()
            ->where('severity', 'critical')
            ->where('is_resolved', false)
            ->where('created_at', '>=', now()->subHour()) // Son 1 saatteki critical alertler
            ->get();

        if ($criticalAlerts->isEmpty()) {
            return;
        }

        // Project owner ve admin'lere bildirim gönder
        $recipients = $this->getCriticalAlertRecipients($project);

        foreach ($recipients as $user) {
            $user->notify(new ProjectHealthAlert($project, $criticalAlerts));
        }
    }

    public function sendDailyHealthDigest(): void
    {
        $projects = Project::active()
            ->with(['latestHealthMetric', 'unresolvedAlerts', 'members'])
            ->get();

        foreach ($projects as $project) {
            $healthData = $this->prepareDailyDigestData($project);

            if ($this->shouldSendDigest($healthData)) {
                $recipients = $this->getDigestRecipients($project);

                foreach ($recipients as $user) {
                    $user->notify(new ProjectHealthDigest($project, $healthData));
                }
            }
        }
    }

    public function sendWeeklyHealthReport(): void
    {
        $projects = Project::active()
            ->with(['healthMetrics' => function ($query) {
                $query->where('metric_date', '>=', now()->subWeek());
            }])
            ->get();

        foreach ($projects as $project) {
            $weeklyData = $this->prepareWeeklyReportData($project);
            $recipients = $this->getWeeklyReportRecipients($project);

            foreach ($recipients as $user) {
                // Weekly report notification implementation
            }
        }
    }

    public function sendSlackNotification(Project $project, ProjectAlert $alert): void
    {
        // Slack webhook integration için
        if (!config('services.slack.webhook_url')) {
            return;
        }

        $message = $this->formatSlackMessage($project, $alert);

        // Slack API call implementation
        // Http::post(config('services.slack.webhook_url'), $message);
    }

    private function getCriticalAlertRecipients(Project $project): Collection
    {
        return $project->members()
            ->wherePivot('role', 'admin')
            ->orWhere('id', $project->user_id) // Project owner
            ->get();
    }

    private function getDigestRecipients(Project $project): Collection
    {
        return $project->members()
            ->wherePivotIn('role', ['admin', 'manager'])
            ->get();
    }

    private function getWeeklyReportRecipients(Project $project): Collection
    {
        return $project->members()->get();
    }

    private function prepareDailyDigestData(Project $project): array
    {
        $latestMetric = $project->latestHealthMetric;
        $unresolvedAlerts = $project->unresolvedAlerts;

        return [
            'health_score' => $latestMetric?->health_score ?? 0,
            'health_trend' => $this->calculateHealthTrend($project),
            'critical_alerts' => $unresolvedAlerts->where('severity', 'critical')->count(),
            'high_alerts' => $unresolvedAlerts->where('severity', 'high')->count(),
            'overdue_tasks' => $latestMetric?->overdue_tasks_count ?? 0,
            'blocked_tasks' => $latestMetric?->blocked_tasks_count ?? 0,
            'velocity' => $latestMetric?->velocity ?? 0,
            'risk_factors' => $latestMetric?->risk_factors ?? [],
        ];
    }

    private function prepareWeeklyReportData(Project $project): array
    {
        $weeklyMetrics = $project->healthMetrics;

        return [
            'avg_health_score' => $weeklyMetrics->avg('health_score'),
            'health_trend' => $this->calculateWeeklyTrend($weeklyMetrics),
            'total_alerts' => $project->alerts()->where('created_at', '>=', now()->subWeek())->count(),
            'resolved_alerts' => $project->alerts()->where('created_at', '>=', now()->subWeek())->where('is_resolved', true)->count(),
            'velocity_trend' => $weeklyMetrics->avg('velocity'),
            'team_performance' => $this->calculateTeamPerformance($project),
        ];
    }

    private function shouldSendDigest(array $healthData): bool
    {
        // Digest gönderme kriterleri
        return $healthData['health_score'] < 70 ||
               $healthData['critical_alerts'] > 0 ||
               $healthData['high_alerts'] > 2 ||
               count($healthData['risk_factors']) > 2;
    }

    private function calculateHealthTrend(Project $project): string
    {
        $lastTwoMetrics = $project->healthMetrics()
            ->orderBy('metric_date', 'desc')
            ->take(2)
            ->get();

        if ($lastTwoMetrics->count() < 2) {
            return 'stable';
        }

        $current = $lastTwoMetrics->first()->health_score;
        $previous = $lastTwoMetrics->last()->health_score;
        $diff = $current - $previous;

        return match (true) {
            $diff > 5 => 'improving',
            $diff < -5 => 'declining',
            default => 'stable'
        };
    }

    private function calculateWeeklyTrend(Collection $metrics): array
    {
        if ($metrics->count() < 2) {
            return ['trend' => 'stable', 'change' => 0];
        }

        $first = $metrics->first()->health_score;
        $last = $metrics->last()->health_score;
        $change = $last - $first;

        return [
            'trend' => $change > 0 ? 'improving' : ($change < 0 ? 'declining' : 'stable'),
            'change' => round($change, 1),
        ];
    }

    private function calculateTeamPerformance(Project $project): array
    {
        return $project->tasks()
            ->whereNotNull('user_id')
            ->where('updated_at', '>=', now()->subWeek())
            ->with('assignee')
            ->get()
            ->groupBy('user_id')
            ->map(function ($tasks) {
                $completed = $tasks->where('status', 'done')->count();
                $total = $tasks->count();

                return [
                    'user' => $tasks->first()->assignee->name,
                    'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
                    'total_tasks' => $total,
                ];
            })->values()->toArray();
    }

    private function formatSlackMessage(Project $project, ProjectAlert $alert): array
    {
        $color = match ($alert->severity) {
            'critical' => '#dc2626',
            'high' => '#ea580c',
            'medium' => '#d97706',
            default => '#2563eb'
        };

        return [
            'text' => "🚨 Project Health Alert: {$project->name}",
            'attachments' => [
                [
                    'color' => $color,
                    'fields' => [
                        [
                            'title' => 'Alert Type',
                            'value' => ucwords(str_replace('_', ' ', $alert->type)),
                            'short' => true,
                        ],
                        [
                            'title' => 'Severity',
                            'value' => ucfirst($alert->severity),
                            'short' => true,
                        ],
                        [
                            'title' => 'Description',
                            'value' => $alert->description,
                            'short' => false,
                        ],
                    ],
                    'footer' => 'Fokus Project Health Monitor',
                    'ts' => $alert->created_at->timestamp,
                ],
            ],
        ];
    }
}
