<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $employee_id
 * @property string $workspace_id
 * @property string|null $performance_review_id
 * @property string $title
 * @property string|null $description
 * @property string $type
 * @property numeric|null $target_value
 * @property numeric $current_value
 * @property string|null $unit
 * @property string $priority
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property string $status
 * @property int $progress_percentage
 * @property string|null $notes
 * @property array<array-key, mixed>|null $milestones
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $parent_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, OkrGoal> $children
 * @property-read int|null $children_count
 * @property-read \App\Models\Employee $employee
 * @property-read int|null $days_remaining
 * @property-read string $priority_color
 * @property-read string $status_color
 * @property-read \Illuminate\Database\Eloquent\Collection<int, OkrGoal> $keyResults
 * @property-read int|null $key_results_count
 * @property-read OkrGoal|null $parent
 * @property-read \App\Models\PerformanceReview|null $performanceReview
 * @property-read \App\Models\Workspace $workspace
 *
 * @method static Builder<static>|OkrGoal byPriority(string $priority)
 * @method static Builder<static>|OkrGoal byStatus(string $status)
 * @method static Builder<static>|OkrGoal keyResults()
 * @method static Builder<static>|OkrGoal newModelQuery()
 * @method static Builder<static>|OkrGoal newQuery()
 * @method static Builder<static>|OkrGoal objectives()
 * @method static Builder<static>|OkrGoal overdue()
 * @method static Builder<static>|OkrGoal query()
 * @method static Builder<static>|OkrGoal whereCreatedAt($value)
 * @method static Builder<static>|OkrGoal whereCurrentValue($value)
 * @method static Builder<static>|OkrGoal whereDescription($value)
 * @method static Builder<static>|OkrGoal whereEmployeeId($value)
 * @method static Builder<static>|OkrGoal whereEndDate($value)
 * @method static Builder<static>|OkrGoal whereId($value)
 * @method static Builder<static>|OkrGoal whereMilestones($value)
 * @method static Builder<static>|OkrGoal whereNotes($value)
 * @method static Builder<static>|OkrGoal whereParentId($value)
 * @method static Builder<static>|OkrGoal wherePerformanceReviewId($value)
 * @method static Builder<static>|OkrGoal wherePriority($value)
 * @method static Builder<static>|OkrGoal whereProgressPercentage($value)
 * @method static Builder<static>|OkrGoal whereStartDate($value)
 * @method static Builder<static>|OkrGoal whereStatus($value)
 * @method static Builder<static>|OkrGoal whereTargetValue($value)
 * @method static Builder<static>|OkrGoal whereTitle($value)
 * @method static Builder<static>|OkrGoal whereType($value)
 * @method static Builder<static>|OkrGoal whereUnit($value)
 * @method static Builder<static>|OkrGoal whereUpdatedAt($value)
 * @method static Builder<static>|OkrGoal whereWorkspaceId($value)
 *
 * @mixin \Eloquent
 */
class OkrGoal extends Model
{
    use HasUuids;

    protected $table = 'okr_goals';

    protected $fillable = [
        'employee_id',
        'workspace_id',
        'performance_review_id',
        'title',
        'description',
        'type',
        'parent_id',
        'target_value',
        'current_value',
        'unit',
        'priority',
        'start_date',
        'end_date',
        'status',
        'progress_percentage',
        'notes',
        'milestones',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'target_value' => 'decimal:2',
        'current_value' => 'decimal:2',
        'progress_percentage' => 'integer',
        'milestones' => 'array',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('workspace', function (Builder $query) {
            if (session('workspace_id')) {
                $query->where('workspace_id', session('workspace_id'));
            }
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function performanceReview(): BelongsTo
    {
        return $this->belongsTo(PerformanceReview::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(OkrGoal::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(OkrGoal::class, 'parent_id');
    }

    public function keyResults(): HasMany
    {
        return $this->hasMany(OkrGoal::class, 'parent_id')->where('type', 'key_result');
    }

    public function updateProgress(): void
    {
        if ($this->type === 'objective') {
            // For objectives, calculate progress based on key results
            $keyResults = $this->keyResults;
            if ($keyResults->count() > 0) {
                $avgProgress = $keyResults->avg('progress_percentage');
                $this->progress_percentage = round($avgProgress);
            }
        } elseif ($this->target_value > 0) {
            // For key results with numeric targets
            $this->progress_percentage = min(100, round(($this->current_value / $this->target_value) * 100));
        }

        $this->updateStatus();
        $this->save();
    }

    public function updateStatus(): void
    {
        if ($this->progress_percentage >= 100) {
            $this->status = 'completed';
        } elseif ($this->progress_percentage >= 70) {
            $this->status = 'on_track';
        } elseif ($this->progress_percentage >= 30) {
            $this->status = 'in_progress';
        } elseif ($this->end_date && $this->end_date->diffInDays(now()) <= 7 && $this->progress_percentage < 50) {
            $this->status = 'at_risk';
        } elseif ($this->progress_percentage > 0) {
            $this->status = 'in_progress';
        }
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'not_started' => 'secondary',
            'in_progress' => 'info',
            'on_track' => 'success',
            'at_risk' => 'warning',
            'completed' => 'success',
            'cancelled' => 'error',
            default => 'secondary',
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'low' => 'secondary',
            'medium' => 'info',
            'high' => 'warning',
            'critical' => 'error',
            default => 'secondary',
        };
    }

    public function isOverdue(): bool
    {
        return $this->end_date && $this->end_date->isPast() && $this->status !== 'completed';
    }

    public function getDaysRemainingAttribute(): ?int
    {
        if (!$this->end_date) {
            return null;
        }

        return $this->end_date->diffInDays(now(), false);
    }

    public function scopeObjectives(Builder $query): Builder
    {
        return $query->where('type', 'objective');
    }

    public function scopeKeyResults(Builder $query): Builder
    {
        return $query->where('type', 'key_result');
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority(Builder $query, string $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('end_date', '<', now())
            ->where('status', '!=', 'completed');
    }
}
