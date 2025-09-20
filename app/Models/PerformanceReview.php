<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 
 *
 * @property string $id
 * @property string $employee_id
 * @property string $reviewer_id
 * @property \Illuminate\Support\Carbon $review_date
 * @property \Illuminate\Support\Carbon $next_review_date
 * @property array<array-key, mixed> $goals
 * @property array<array-key, mixed> $strengths
 * @property array<array-key, mixed> $improvement_areas
 * @property string $overall_rating
 * @property string|null $feedback
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee $employee
 * @property-read \App\Models\User $reviewer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview whereFeedback($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview whereGoals($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview whereImprovementAreas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview whereNextReviewDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview whereOverallRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview whereReviewDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview whereReviewerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview whereStrengths($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class PerformanceReview extends Model
{
    use HasUuids;

    protected $fillable = [
        'employee_id',
        'reviewer_id',
        'review_date',
        'next_review_date',
        'goals',
        'strengths',
        'improvement_areas',
        'overall_rating',
        'feedback',
        'status'
    ];

    protected $casts = [
        'review_date' => 'date',
        'next_review_date' => 'date',
        'goals' => 'array',
        'strengths' => 'array',
        'improvement_areas' => 'array',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
