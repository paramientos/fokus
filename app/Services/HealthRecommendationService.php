<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Collection;

class HealthRecommendationService
{
    public function generateRecommendations(Project $project): array
    {
        $healthData = app(ProjectHealthService::class)->calculateHealthScore($project);
        $recommendations = [];

        // Health score based recommendations
        if ($healthData['health_score'] < 60) {
            $recommendations = array_merge($recommendations, $this->getCriticalHealthRecommendations($project, $healthData));
        }

        // Risk factor based recommendations
        foreach ($healthData['risk_factors'] as $risk) {
            $recommendations = array_merge($recommendations, $this->getRiskSpecificRecommendations($risk, $project, $healthData));
        }

        // Bottleneck based recommendations
        foreach ($healthData['bottlenecks'] as $bottleneck) {
            $recommendations = array_merge($recommendations, $this->getBottleneckRecommendations($bottleneck, $project));
        }

        // Trend based recommendations
        $trendRecommendations = $this->getTrendBasedRecommendations($project);
        $recommendations = array_merge($recommendations, $trendRecommendations);

        return $this->prioritizeRecommendations($recommendations);
    }

    private function getCriticalHealthRecommendations(Project $project, array $healthData): array
    {
        $recommendations = [];

        if ($healthData['metrics']['overdue_rate'] > 30) {
            $recommendations[] = [
                'type' => 'urgent_action',
                'priority' => 'high',
                'title' => 'Address Overdue Tasks Immediately',
                'description' => 'Your project has a high number of overdue tasks. Consider redistributing workload or extending deadlines.',
                'actions' => [
                    'Review and prioritize overdue tasks',
                    'Reassign tasks to available team members',
                    'Consider extending project timeline',
                    'Hold emergency team meeting',
                ],
                'impact' => 'high',
                'effort' => 'medium',
            ];
        }

        if ($healthData['metrics']['blocked_rate'] > 20) {
            $recommendations[] = [
                'type' => 'process_improvement',
                'priority' => 'high',
                'title' => 'Resolve Blocked Tasks',
                'description' => 'Too many tasks are blocked. Identify and remove blockers to improve team velocity.',
                'actions' => [
                    'Identify root causes of blocked tasks',
                    'Assign dedicated resources to unblock tasks',
                    'Implement daily blocker review process',
                    'Create escalation procedures for blockers',
                ],
                'impact' => 'high',
                'effort' => 'low',
            ];
        }

        return $recommendations;
    }

    private function getRiskSpecificRecommendations(string $risk, Project $project, array $healthData): array
    {
        return match ($risk) {
            'high_overdue_rate' => [[
                'type' => 'time_management',
                'priority' => 'high',
                'title' => 'Implement Better Time Management',
                'description' => 'High overdue rate indicates time management issues.',
                'actions' => [
                    'Break down large tasks into smaller chunks',
                    'Implement time boxing techniques',
                    'Review task estimation accuracy',
                    'Provide time management training',
                ],
                'impact' => 'high',
                'effort' => 'medium',
            ]],

            'team_overload' => [[
                'type' => 'resource_management',
                'priority' => 'high',
                'title' => 'Balance Team Workload',
                'description' => 'Team appears to be overloaded. Consider redistributing tasks or adding resources.',
                'actions' => [
                    'Analyze individual workloads',
                    'Redistribute tasks among team members',
                    'Consider hiring additional resources',
                    'Implement workload monitoring',
                ],
                'impact' => 'high',
                'effort' => 'high',
            ]],

            'low_velocity' => [[
                'type' => 'process_optimization',
                'priority' => 'medium',
                'title' => 'Improve Team Velocity',
                'description' => 'Team velocity is below expected levels.',
                'actions' => [
                    'Identify velocity bottlenecks',
                    'Streamline development processes',
                    'Reduce context switching',
                    'Implement pair programming',
                ],
                'impact' => 'medium',
                'effort' => 'medium',
            ]],

            'deadline_risk' => [[
                'type' => 'schedule_management',
                'priority' => 'critical',
                'title' => 'Mitigate Deadline Risk',
                'description' => 'Project is at risk of missing its deadline.',
                'actions' => [
                    'Reassess project scope',
                    'Prioritize critical features',
                    'Consider scope reduction',
                    'Increase team capacity temporarily',
                ],
                'impact' => 'critical',
                'effort' => 'high',
            ]],

            default => []
        };
    }

    private function getBottleneckRecommendations(array $bottleneck, Project $project): array
    {
        return match ($bottleneck['type']) {
            'unassigned_tasks' => [[
                'type' => 'task_management',
                'priority' => 'medium',
                'title' => 'Assign Unassigned Tasks',
                'description' => "You have {$bottleneck['count']} unassigned tasks that need attention.",
                'actions' => [
                    'Review unassigned tasks',
                    'Assign tasks based on team capacity',
                    'Implement automatic assignment rules',
                    'Create task assignment workflow',
                ],
                'impact' => 'medium',
                'effort' => 'low',
            ]],

            'user_overload' => [[
                'type' => 'workload_balancing',
                'priority' => 'high',
                'title' => 'Rebalance User Workload',
                'description' => "Some team members are overloaded with {$bottleneck['task_count']} active tasks.",
                'actions' => [
                    'Redistribute tasks from overloaded members',
                    'Implement workload limits',
                    'Cross-train team members',
                    'Monitor workload distribution regularly',
                ],
                'impact' => 'high',
                'effort' => 'medium',
            ]],

            'status_bottleneck' => [[
                'type' => 'workflow_optimization',
                'priority' => 'medium',
                'title' => 'Optimize Workflow Status',
                'description' => 'Tasks are accumulating in certain status columns.',
                'actions' => [
                    'Review workflow status definitions',
                    'Implement WIP limits',
                    'Identify status transition blockers',
                    'Optimize review processes',
                ],
                'impact' => 'medium',
                'effort' => 'medium',
            ]],

            default => []
        };
    }

    private function getTrendBasedRecommendations(Project $project): array
    {
        $recentMetrics = $project->healthMetrics()
            ->orderBy('metric_date', 'desc')
            ->take(7)
            ->get();

        if ($recentMetrics->count() < 3) {
            return [];
        }

        $recommendations = [];

        // Velocity trend analysis
        $velocityTrend = $this->calculateTrend($recentMetrics->pluck('velocity'));
        if ($velocityTrend < -0.1) {
            $recommendations[] = [
                'type' => 'performance_improvement',
                'priority' => 'medium',
                'title' => 'Address Declining Velocity',
                'description' => 'Team velocity has been declining over the past week.',
                'actions' => [
                    'Conduct velocity retrospective',
                    'Identify impediments to productivity',
                    'Review and optimize processes',
                    'Consider team motivation factors',
                ],
                'impact' => 'medium',
                'effort' => 'low',
            ];
        }

        // Health score trend analysis
        $healthTrend = $this->calculateTrend($recentMetrics->pluck('health_score'));
        if ($healthTrend < -0.05) {
            $recommendations[] = [
                'type' => 'health_improvement',
                'priority' => 'high',
                'title' => 'Reverse Health Decline',
                'description' => 'Project health has been declining consistently.',
                'actions' => [
                    'Conduct comprehensive health review',
                    'Address top risk factors',
                    'Implement daily health monitoring',
                    'Create health improvement action plan',
                ],
                'impact' => 'high',
                'effort' => 'medium',
            ];
        }

        return $recommendations;
    }

    private function calculateTrend(Collection $values): float
    {
        if ($values->count() < 2) {
            return 0;
        }

        $n = $values->count();
        $x = range(1, $n);
        $y = $values->values()->toArray();

        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = array_sum(array_map(fn ($i) => $x[$i] * $y[$i], range(0, $n - 1)));
        $sumX2 = array_sum(array_map(fn ($val) => $val * $val, $x));

        return ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
    }

    private function prioritizeRecommendations(array $recommendations): array
    {
        $priorityOrder = ['critical' => 4, 'high' => 3, 'medium' => 2, 'low' => 1];
        $impactOrder = ['critical' => 4, 'high' => 3, 'medium' => 2, 'low' => 1];

        usort($recommendations, function ($a, $b) use ($priorityOrder, $impactOrder) {
            $aPriority = $priorityOrder[$a['priority']] ?? 0;
            $bPriority = $priorityOrder[$b['priority']] ?? 0;

            if ($aPriority === $bPriority) {
                $aImpact = $impactOrder[$a['impact']] ?? 0;
                $bImpact = $impactOrder[$b['impact']] ?? 0;

                return $bImpact <=> $aImpact;
            }

            return $bPriority <=> $aPriority;
        });

        return array_slice($recommendations, 0, 8); // Top 8 recommendations
    }

    public function getQuickWins(Project $project): array
    {
        $allRecommendations = $this->generateRecommendations($project);

        return array_filter($allRecommendations, function ($recommendation) {
            return $recommendation['effort'] === 'low' &&
                   in_array($recommendation['impact'], ['medium', 'high']);
        });
    }

    public function getActionableInsights(Project $project): array
    {
        $healthData = app(ProjectHealthService::class)->calculateHealthScore($project);

        return [
            'health_score_interpretation' => $this->interpretHealthScore($healthData['health_score']),
            'velocity_analysis' => $this->analyzeVelocity($project),
            'team_efficiency' => $this->analyzeTeamEfficiency($project),
            'risk_assessment' => $this->assessRisks($healthData['risk_factors']),
            'improvement_opportunities' => $this->identifyImprovementOpportunities($project, $healthData),
        ];
    }

    private function interpretHealthScore(float $score): array
    {
        return match (true) {
            $score >= 90 => [
                'status' => 'excellent',
                'message' => 'Project is performing exceptionally well',
                'advice' => 'Maintain current practices and share best practices with other teams',
            ],
            $score >= 80 => [
                'status' => 'good',
                'message' => 'Project is in good health with minor areas for improvement',
                'advice' => 'Focus on continuous improvement and monitor for any declining trends',
            ],
            $score >= 60 => [
                'status' => 'fair',
                'message' => 'Project has some health issues that need attention',
                'advice' => 'Address key risk factors and implement improvement measures',
            ],
            $score >= 40 => [
                'status' => 'poor',
                'message' => 'Project health is concerning and requires immediate action',
                'advice' => 'Implement urgent corrective measures and increase monitoring frequency',
            ],
            default => [
                'status' => 'critical',
                'message' => 'Project is in critical condition',
                'advice' => 'Emergency intervention required - consider project restructuring',
            ]
        };
    }

    private function analyzeVelocity(Project $project): array
    {
        $recentMetrics = $project->healthMetrics()
            ->orderBy('metric_date', 'desc')
            ->take(10)
            ->get();

        if ($recentMetrics->isEmpty()) {
            return ['status' => 'no_data', 'message' => 'Insufficient data for velocity analysis'];
        }

        $avgVelocity = $recentMetrics->avg('velocity');
        $trend = $this->calculateTrend($recentMetrics->pluck('velocity'));

        return [
            'average_velocity' => round($avgVelocity, 1),
            'trend' => $trend > 0.1 ? 'increasing' : ($trend < -0.1 ? 'decreasing' : 'stable'),
            'recommendation' => $this->getVelocityRecommendation($avgVelocity, $trend),
        ];
    }

    private function analyzeTeamEfficiency(Project $project): array
    {
        $tasks = $project->tasks()->whereNotNull('user_id')->get();

        if ($tasks->isEmpty()) {
            return ['status' => 'no_data', 'message' => 'No assigned tasks for efficiency analysis'];
        }

        $efficiency = $tasks->groupBy('user_id')->map(function ($userTasks) {
            $completed = $userTasks->whereNotNull('completed_at')->count();
            $total = $userTasks->count();

            return $total > 0 ? ($completed / $total) * 100 : 0;
        });

        return [
            'team_average' => round($efficiency->avg(), 1),
            'efficiency_distribution' => $efficiency->values()->toArray(),
            'top_performer' => $efficiency->max(),
            'needs_support' => $efficiency->min(),
        ];
    }

    private function assessRisks(array $riskFactors): array
    {
        $riskLevels = [
            'deadline_risk' => 'critical',
            'team_overload' => 'high',
            'high_overdue_rate' => 'high',
            'high_blocked_rate' => 'medium',
            'low_velocity' => 'medium',
            'poor_burndown' => 'medium',
        ];

        $assessedRisks = [];
        foreach ($riskFactors as $risk) {
            $assessedRisks[] = [
                'factor' => $risk,
                'level' => $riskLevels[$risk] ?? 'low',
                'description' => $this->getRiskDescription($risk),
            ];
        }

        return $assessedRisks;
    }

    private function identifyImprovementOpportunities(Project $project, array $healthData): array
    {
        $opportunities = [];

        // Low hanging fruits
        if ($healthData['metrics']['blocked_rate'] > 10) {
            $opportunities[] = [
                'type' => 'quick_win',
                'title' => 'Unblock Tasks',
                'effort' => 'low',
                'impact' => 'high',
            ];
        }

        // Process improvements
        if ($healthData['metrics']['velocity'] < 5) {
            $opportunities[] = [
                'type' => 'process_improvement',
                'title' => 'Optimize Development Process',
                'effort' => 'medium',
                'impact' => 'high',
            ];
        }

        return $opportunities;
    }

    private function getVelocityRecommendation(float $velocity, float $trend): string
    {
        if ($velocity < 3) {
            return 'Velocity is low. Consider process optimization and removing impediments.';
        }

        if ($trend < -0.1) {
            return 'Velocity is declining. Investigate causes and implement corrective measures.';
        }

        if ($velocity > 8 && $trend > 0.1) {
            return 'Excellent velocity! Ensure sustainable pace to avoid burnout.';
        }

        return 'Velocity is within acceptable range. Monitor for consistency.';
    }

    private function getRiskDescription(string $risk): string
    {
        return match ($risk) {
            'deadline_risk' => 'Project may miss its deadline based on current progress',
            'team_overload' => 'Team members have too many active tasks',
            'high_overdue_rate' => 'Too many tasks are past their due dates',
            'high_blocked_rate' => 'Many tasks are blocked and cannot progress',
            'low_velocity' => 'Team is completing tasks slower than expected',
            'poor_burndown' => 'Project is not burning down tasks at expected rate',
            default => 'Unknown risk factor'
        };
    }
}
