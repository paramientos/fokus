<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 
 *
 * @property int $id
 * @property int $project_id
 * @property \Illuminate\Support\Carbon $metric_date
 * @property numeric $health_score
 * @property array<array-key, mixed> $risk_factors
 * @property array<array-key, mixed> $bottlenecks
 * @property array<array-key, mixed> $warnings
 * @property int $completed_tasks_count
 * @property int $overdue_tasks_count
 * @property int $blocked_tasks_count
 * @property numeric|null $velocity
 * @property numeric|null $burndown_rate
 * @property int $team_workload_score
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $health_color
 * @property-read string $health_status
 * @property-read \App\Models\Project $project
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectHealthMetric newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectHealthMetric newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectHealthMetric query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectHealthMetric whereBlockedTasksCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectHealthMetric whereBottlenecks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectHealthMetric whereBurndownRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectHealthMetric whereCompletedTasksCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectHealthMetric whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectHealthMetric whereHealthScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectHealthMetric whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectHealthMetric whereMetricDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectHealthMetric whereOverdueTasksCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectHealthMetric whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectHealthMetric whereRiskFactors($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectHealthMetric whereTeamWorkloadScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectHealthMetric whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectHealthMetric whereVelocity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectHealthMetric whereWarnings($value)
 * @mixin \Eloquent
 */
class ProjectHealthMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'metric_date',
        'health_score',
        'risk_factors',
        'bottlenecks',
        'warnings',
        'completed_tasks_count',
        'overdue_tasks_count',
        'blocked_tasks_count',
        'velocity',
        'burndown_rate',
        'team_workload_score',
    ];

    protected $casts = [
        'metric_date' => 'date',
        'health_score' => 'decimal:2',
        'risk_factors' => 'array',
        'bottlenecks' => 'array',
        'warnings' => 'array',
        'velocity' => 'decimal:2',
        'burndown_rate' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function getHealthStatusAttribute(): string
    {
        return match (true) {
            $this->health_score >= 80 => 'excellent',
            $this->health_score >= 60 => 'good',
            $this->health_score >= 40 => 'warning',
            $this->health_score >= 20 => 'poor',
            default => 'critical'
        };
    }

    public function getHealthColorAttribute(): string
    {
        return match ($this->health_status) {
            'excellent' => 'success',
            'good' => 'info',
            'warning' => 'warning',
            'poor' => 'error',
            'critical' => 'error'
        };
    }

    public function hasRiskFactor(string $factor): bool
    {
        return in_array($factor, $this->risk_factors ?? []);
    }

    public function hasBottleneck(string $type): bool
    {
        return collect($this->bottlenecks ?? [])->contains('type', $type);
    }
}
