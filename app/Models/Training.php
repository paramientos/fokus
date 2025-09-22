<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $workspace_id
 * @property string $title
 * @property string $description
 * @property string $type
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property numeric|null $cost
 * @property string|null $provider
 * @property string|null $location
 * @property int|null $max_participants
 * @property bool $is_mandatory
 * @property array<array-key, mixed>|null $prerequisites
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EmployeeTraining> $employeeTrainings
 * @property-read int|null $employee_trainings_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Employee> $employees
 * @property-read int|null $employees_count
 * @property-read \App\Models\Workspace $workspace
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereIsMandatory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereMaxParticipants($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training wherePrerequisites($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereWorkspaceId($value)
 *
 * @mixin \Eloquent
 */
class Training extends Model
{
    use HasUuids;

    protected $fillable = [
        'workspace_id',
        'title',
        'description',
        'type',
        'start_date',
        'end_date',
        'cost',
        'provider',
        'location',
        'max_participants',
        'is_mandatory',
        'prerequisites',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'cost' => 'decimal:2',
        'is_mandatory' => 'boolean',
        'prerequisites' => 'array',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'employee_training')
            ->withPivot(['status', 'score', 'feedback', 'certificate_path', 'assigned_at', 'due_date', 'is_required', 'notes'])
            ->withTimestamps();
    }

    public function employeeTrainings(): HasMany
    {
        return $this->hasMany(EmployeeTraining::class);
    }
}
