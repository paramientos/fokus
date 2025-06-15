<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectHealthMetric;
use App\Models\ProjectAlert;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ProjectHealthService
{
    public function calculateHealthScore(Project $project): array
    {
        $metrics = $this->gatherProjectMetrics($project);
        $healthScore = $this->computeHealthScore($metrics);
        $riskFactors = $this->identifyRiskFactors($metrics);
        $bottlenecks = $this->detectBottlenecks($project, $metrics);
        $warnings = $this->generateWarnings($metrics, $riskFactors);

        return [
            'health_score' => $healthScore,
            'risk_factors' => $riskFactors,
            'bottlenecks' => $bottlenecks,
            'warnings' => $warnings,
            'metrics' => $metrics,
        ];
    }

    public function updateProjectHealth(Project $project): ProjectHealthMetric
    {
        $healthData = $this->calculateHealthScore($project);

        $metric = ProjectHealthMetric::updateOrCreate(
            [
                'project_id' => $project->id,
                'metric_date' => now()->toDateString(),
            ],
            [
                'health_score' => $healthData['health_score'],
                'risk_factors' => $healthData['risk_factors'],
                'bottlenecks' => $healthData['bottlenecks'],
                'warnings' => $healthData['warnings'],
                'completed_tasks_count' => $healthData['metrics']['completed_tasks'],
                'overdue_tasks_count' => $healthData['metrics']['overdue_tasks'],
                'blocked_tasks_count' => $healthData['metrics']['blocked_tasks'],
                'velocity' => $healthData['metrics']['velocity'],
                'burndown_rate' => $healthData['metrics']['burndown_rate'],
                'team_workload_score' => $healthData['metrics']['team_workload'],
            ]
        );

        $this->generateAlerts($project, $healthData);

        return $metric;
    }

    private function gatherProjectMetrics(Project $project): array
    {
        $tasks = $project->tasks;
        $totalTasks = $tasks->count();
        $completedTasks = $tasks->where('status', 'done')->count();
        $overdueTasks = $tasks->where('due_date', '<', now())->where('status', '!=', 'done')->count();
        $blockedTasks = $tasks->where('status', 'blocked')->count();

        // Sprint velocity hesaplama (son 4 sprint ortalaması)
        $velocity = $this->calculateVelocity($project);

        // Burndown rate hesaplama
        $burndownRate = $this->calculateBurndownRate($project);

        // Team workload hesaplama
        $teamWorkload = $this->calculateTeamWorkload($project);

        // Deadline proximity
        $deadlineProximity = $this->calculateDeadlineProximity($project);

        return [
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'overdue_tasks' => $overdueTasks,
            'blocked_tasks' => $blockedTasks,
            'completion_rate' => $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0,
            'overdue_rate' => $totalTasks > 0 ? ($overdueTasks / $totalTasks) * 100 : 0,
            'blocked_rate' => $totalTasks > 0 ? ($blockedTasks / $totalTasks) * 100 : 0,
            'velocity' => $velocity,
            'burndown_rate' => $burndownRate,
            'team_workload' => $teamWorkload,
            'deadline_proximity' => $deadlineProximity,
        ];
    }

    private function computeHealthScore(array $metrics): float
    {
        $score = 100;

        // Completion rate etkisi (40% ağırlık)
        $completionImpact = ($metrics['completion_rate'] * 0.4);

        // Overdue tasks etkisi (25% ağırlık)
        $overdueImpact = max(0, 25 - ($metrics['overdue_rate'] * 1.5));

        // Blocked tasks etkisi (15% ağırlık)
        $blockedImpact = max(0, 15 - ($metrics['blocked_rate'] * 2));

        // Velocity etkisi (10% ağırlık)
        $velocityImpact = min(10, $metrics['velocity'] / 10);

        // Team workload etkisi (10% ağırlık)
        $workloadImpact = max(0, 10 - (($metrics['team_workload'] - 5) * 2));

        $finalScore = $completionImpact + $overdueImpact + $blockedImpact + $velocityImpact + $workloadImpact;

        return round(max(0, min(100, $finalScore)), 2);
    }

    private function identifyRiskFactors(array $metrics): array
    {
        $risks = [];

        if ($metrics['overdue_rate'] > 20) {
            $risks[] = 'high_overdue_rate';
        }

        if ($metrics['blocked_rate'] > 15) {
            $risks[] = 'high_blocked_rate';
        }

        if ($metrics['velocity'] < 5) {
            $risks[] = 'low_velocity';
        }

        if ($metrics['team_workload'] > 8) {
            $risks[] = 'team_overload';
        }

        if ($metrics['deadline_proximity'] < 7 && $metrics['completion_rate'] < 80) {
            $risks[] = 'deadline_risk';
        }

        if ($metrics['burndown_rate'] < 0.5) {
            $risks[] = 'poor_burndown';
        }

        return $risks;
    }

    private function detectBottlenecks(Project $project, array $metrics): array
    {
        $bottlenecks = [];

        // Task assignment bottlenecks
        $unassignedTasks = $project->tasks()->whereNull('user_id')->count();
        if ($unassignedTasks > 5) {
            $bottlenecks[] = [
                'type' => 'unassigned_tasks',
                'severity' => 'medium',
                'count' => $unassignedTasks,
                'description' => "Too many unassigned tasks ({$unassignedTasks})"
            ];
        }

        // User workload bottlenecks
        $userWorkloads = $project->tasks()
            ->whereNotNull('user_id')
            ->whereNotNull('completed_at')
            ->groupBy('user_id')
            ->selectRaw('user_id, count(*) as task_count')
            ->get();

        foreach ($userWorkloads as $workload) {
            if ($workload->task_count > 10) {
                $bottlenecks[] = [
                    'type' => 'user_overload',
                    'severity' => 'high',
                    'user_id' => $workload->user_id,
                    'task_count' => $workload->task_count,
                    'description' => "User overloaded with {$workload->task_count} active tasks"
                ];
            }
        }

        // Status bottlenecks
        $statusCounts = $project->tasks()
            ->groupBy('status_id')
            ->selectRaw('status_id, count(*) as count')
            ->get()
            ->pluck('count', 'status_id');

        if (($statusCounts['in_progress'] ?? 0) > ($statusCounts['todo'] ?? 0) * 2) {
            $bottlenecks[] = [
                'type' => 'status_bottleneck',
                'severity' => 'medium',
                'description' => 'Too many tasks in progress compared to todo'
            ];
        }

        return $bottlenecks;
    }

    private function generateWarnings(array $metrics, array $riskFactors): array
    {
        $warnings = [];

        foreach ($riskFactors as $risk) {
            $warnings[] = match ($risk) {
                'high_overdue_rate' => [
                    'type' => 'overdue',
                    'message' => 'High number of overdue tasks detected',
                    'severity' => 'high'
                ],
                'high_blocked_rate' => [
                    'type' => 'blocked',
                    'message' => 'Many tasks are currently blocked',
                    'severity' => 'high'
                ],
                'low_velocity' => [
                    'type' => 'velocity',
                    'message' => 'Team velocity is below expected levels',
                    'severity' => 'medium'
                ],
                'team_overload' => [
                    'type' => 'workload',
                    'message' => 'Team appears to be overloaded',
                    'severity' => 'high'
                ],
                'deadline_risk' => [
                    'type' => 'deadline',
                    'message' => 'Project deadline is at risk',
                    'severity' => 'critical'
                ],
                'poor_burndown' => [
                    'type' => 'burndown',
                    'message' => 'Burndown rate is concerning',
                    'severity' => 'medium'
                ],
                default => [
                    'type' => 'general',
                    'message' => 'Unknown risk factor detected',
                    'severity' => 'low'
                ]
            };
        }

        return $warnings;
    }

    private function generateAlerts(Project $project, array $healthData): void
    {
        foreach ($healthData['warnings'] as $warning) {
            if ($warning['severity'] === 'critical' || $warning['severity'] === 'high') {
                ProjectAlert::create([
                    'project_id' => $project->id,
                    'type' => $this->mapWarningToAlertType($warning['type']),
                    'severity' => $warning['severity'],
                    'title' => $warning['message'],
                    'description' => $this->generateAlertDescription($warning, $healthData['metrics']),
                    'metadata' => [
                        'health_score' => $healthData['health_score'],
                        'warning_data' => $warning,
                    ],
                ]);
            }
        }
    }

    private function mapWarningToAlertType(string $warningType): string
    {
        return match ($warningType) {
            'overdue' => 'overdue_tasks',
            'blocked' => 'blocked_tasks',
            'velocity' => 'velocity_drop',
            'workload' => 'team_overload',
            'deadline' => 'deadline_risk',
            'burndown' => 'velocity_drop',
            default => 'bottleneck_detected'
        };
    }

    private function generateAlertDescription(array $warning, array $metrics): string
    {
        return match ($warning['type']) {
            'overdue' => "Project has {$metrics['overdue_tasks']} overdue tasks ({$metrics['overdue_rate']}% of total)",
            'blocked' => "Project has {$metrics['blocked_tasks']} blocked tasks ({$metrics['blocked_rate']}% of total)",
            'velocity' => "Current velocity is {$metrics['velocity']}, below expected levels",
            'workload' => "Team workload score is {$metrics['team_workload']}/10, indicating overload",
            'deadline' => "Project completion rate is {$metrics['completion_rate']}% with {$metrics['deadline_proximity']} days to deadline",
            default => $warning['message']
        };
    }

    private function calculateVelocity(Project $project): float
    {
        // Son 4 sprint'teki tamamlanan task sayısının ortalaması
        // Şimdilik basit bir hesaplama yapıyoruz
        $completedLastWeek = $project->tasks()
            ->whereNotNull('completed_at')
            ->where('updated_at', '>=', now()->subWeek())
            ->count();

        return $completedLastWeek;
    }

    private function calculateBurndownRate(Project $project): float
    {
        // Basit burndown rate hesaplama
        $totalTasks = $project->tasks()->count();
        $completedTasks = $project->tasks()->whereNotNull('completed_at')->count();

        if ($totalTasks === 0) return 0;

        return ($completedTasks / $totalTasks);
    }

    private function calculateTeamWorkload(Project $project): int
    {
        $members = $project->members()->count();
        $activeTasks = $project->tasks()->whereNotNull('completed_at')->count();

        if ($members === 0) return 10;

        $tasksPerMember = $activeTasks / $members;

        // 1-10 arası skor (5 ideal, 10 aşırı yük)
        return min(10, max(1, round($tasksPerMember / 2)));
    }

    private function calculateDeadlineProximity(Project $project): int
    {
        if (!$project->end_date) return 365; // Deadline yoksa uzak gelecek

        return now()->diffInDays($project->end_date, false);
    }
}
