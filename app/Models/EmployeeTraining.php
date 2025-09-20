<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 *
 *
 * @property int $id
 * @property int $employee_id
 * @property int $training_id
 * @property string $status
 * @property numeric|null $score
 * @property string|null $feedback
 * @property string|null $certificate_path
 * @property \Illuminate\Support\Carbon|null $assigned_at
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property bool $is_required
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee $employee
 * @property-read int $progress_percentage
 * @property-read string $status_color
 * @property-read \App\Models\Training $training
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeTraining newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeTraining newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeTraining query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeTraining whereAssignedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeTraining whereCertificatePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeTraining whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeTraining whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeTraining whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeTraining whereFeedback($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeTraining whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeTraining whereIsRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeTraining whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeTraining whereScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeTraining whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeTraining whereTrainingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeTraining whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class EmployeeTraining extends Model
{
    use HasFactory,HasUuids;

    protected $table = 'employee_training';

    protected $fillable = [
        'employee_id',
        'training_id',
        'status',
        'score',
        'feedback',
        'certificate_path',
        'assigned_at',
        'due_date',
        'is_required',
        'notes'
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'due_date' => 'date',
        'is_required' => 'boolean',
        'score' => 'decimal:2'
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'registered', 'assigned' => 'warning',
            'in_progress' => 'info',
            'completed' => 'success',
            'dropped' => 'danger',
            default => 'secondary'
        };
    }

    public function getProgressPercentageAttribute(): int
    {
        return match($this->status) {
            'registered', 'assigned' => 0,
            'in_progress' => 50,
            'completed' => 100,
            'dropped' => 0,
            default => 0
        };
    }
}
