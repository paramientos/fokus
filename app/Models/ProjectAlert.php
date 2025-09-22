<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $project_id
 * @property string|null $user_id
 * @property string $type
 * @property string $severity
 * @property string $title
 * @property string $description
 * @property array<array-key, mixed>|null $metadata
 * @property bool $is_resolved
 * @property \Illuminate\Support\Carbon|null $resolved_at
 * @property string|null $resolved_by
 * @property string|null $resolution_notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $severity_color
 * @property-read string $severity_icon
 * @property-read string $type_icon
 * @property-read \App\Models\Project $project
 * @property-read \App\Models\User|null $resolvedBy
 * @property-read \App\Models\User|null $user
 *
 * @method static Builder<static>|ProjectAlert bySeverity(string $severity)
 * @method static Builder<static>|ProjectAlert byType(string $type)
 * @method static Builder<static>|ProjectAlert newModelQuery()
 * @method static Builder<static>|ProjectAlert newQuery()
 * @method static Builder<static>|ProjectAlert query()
 * @method static Builder<static>|ProjectAlert unresolved()
 * @method static Builder<static>|ProjectAlert whereCreatedAt($value)
 * @method static Builder<static>|ProjectAlert whereDescription($value)
 * @method static Builder<static>|ProjectAlert whereId($value)
 * @method static Builder<static>|ProjectAlert whereIsResolved($value)
 * @method static Builder<static>|ProjectAlert whereMetadata($value)
 * @method static Builder<static>|ProjectAlert whereProjectId($value)
 * @method static Builder<static>|ProjectAlert whereResolutionNotes($value)
 * @method static Builder<static>|ProjectAlert whereResolvedAt($value)
 * @method static Builder<static>|ProjectAlert whereResolvedBy($value)
 * @method static Builder<static>|ProjectAlert whereSeverity($value)
 * @method static Builder<static>|ProjectAlert whereTitle($value)
 * @method static Builder<static>|ProjectAlert whereType($value)
 * @method static Builder<static>|ProjectAlert whereUpdatedAt($value)
 * @method static Builder<static>|ProjectAlert whereUserId($value)
 *
 * @mixin \Eloquent
 */
class ProjectAlert extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'project_id',
        'user_id',
        'type',
        'severity',
        'title',
        'description',
        'metadata',
        'is_resolved',
        'resolved_at',
        'resolved_by',
        'resolution_notes',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function scopeUnresolved(Builder $query): Builder
    {
        return $query->where('is_resolved', false);
    }

    public function scopeBySeverity(Builder $query, string $severity): Builder
    {
        return $query->where('severity', $severity);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function getSeverityColorAttribute(): string
    {
        return match ($this->severity) {
            'low' => 'info',
            'medium' => 'warning',
            'high' => 'error',
            'critical' => 'error'
        };
    }

    public function getSeverityIconAttribute(): string
    {
        return match ($this->severity) {
            'low' => 'fas.info-circle',
            'medium' => 'fas.exclamation-triangle',
            'high' => 'fas.exclamation-circle',
            'critical' => 'fas.fire'
        };
    }

    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            'deadline_risk' => 'fas.clock',
            'resource_conflict' => 'fas.users',
            'bottleneck_detected' => 'fas.funnel-dollar',
            'velocity_drop' => 'fas.chart-line',
            'overdue_tasks' => 'fas.calendar-times',
            'blocked_tasks' => 'fas.ban',
            'budget_exceeded' => 'fas.dollar-sign',
            'team_overload' => 'fas.weight-hanging',
            default => 'fas.bell'
        };
    }

    public function resolve(User $user, ?string $notes = null): void
    {
        $this->update([
            'is_resolved' => true,
            'resolved_at' => now(),
            'resolved_by' => $user->id,
            'resolution_notes' => $notes,
        ]);
    }
}
