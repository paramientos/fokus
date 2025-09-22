<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $user_id
 * @property string $workspace_id
 * @property string $employee_id
 * @property string|null $department
 * @property string|null $position
 * @property \Illuminate\Support\Carbon $hire_date
 * @property string|null $salary
 * @property string $employment_type
 * @property string $work_location
 * @property string|null $emergency_contact_name
 * @property string|null $emergency_contact_phone
 * @property string|null $bank_name
 * @property string|null $bank_account
 * @property string|null $iban
 * @property array<array-key, mixed>|null $skills
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PerformanceReview> $assignedReviews
 * @property-read int|null $assigned_reviews_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Certification> $certifications
 * @property-read int|null $certifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EmployeeCertification> $employeeCertifications
 * @property-read int|null $employee_certifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EmployeeTraining> $employeeTrainings
 * @property-read int|null $employee_trainings_count
 * @property-read mixed $active_certifications
 * @property-read mixed $current_okr_objectives
 * @property-read mixed $expired_certifications
 * @property-read mixed $expiring_soon_certifications
 * @property-read mixed $latest_payroll
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OkrGoal> $keyResults
 * @property-read int|null $key_results_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LeaveRequest> $leaveRequests
 * @property-read int|null $leave_requests_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OkrGoal> $objectives
 * @property-read int|null $objectives_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OkrGoal> $okrGoals
 * @property-read int|null $okr_goals_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payroll> $payrolls
 * @property-read int|null $payrolls_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PerformanceReview> $performanceReviews
 * @property-read int|null $performance_reviews_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Training> $trainings
 * @property-read int|null $trainings_count
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Workspace $workspace
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereBankAccount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereBankName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereDepartment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereEmergencyContactName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereEmergencyContactPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereEmploymentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereHireDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereIban($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereSalary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereSkills($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereWorkLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereWorkspaceId($value)
 *
 * @mixin \Eloquent
 */
class Employee extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'workspace_id',
        'employee_id',
        'department',
        'position',
        'hire_date',
        'salary',
        'employment_type',
        'work_location',
        'emergency_contact_name',
        'emergency_contact_phone',
        'bank_name',
        'bank_account',
        'iban',
        'skills',
        'notes',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'skills' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function performanceReviews(): HasMany
    {
        return $this->hasMany(PerformanceReview::class, 'employee_id');
    }

    public function assignedReviews(): HasMany
    {
        return $this->hasMany(PerformanceReview::class, 'reviewer_id');
    }

    public function trainings(): BelongsToMany
    {
        return $this->belongsToMany(Training::class, 'employee_training')
            ->withPivot(['status', 'score', 'feedback', 'certificate_path'])
            ->withTimestamps();
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    public function certifications(): BelongsToMany
    {
        return $this->belongsToMany(Certification::class, 'employee_certifications')
            ->withPivot(['obtained_date', 'expiry_date', 'status', 'score', 'certificate_number'])
            ->withTimestamps();
    }

    public function employeeCertifications(): HasMany
    {
        return $this->hasMany(EmployeeCertification::class);
    }

    public function employeeTrainings(): HasMany
    {
        return $this->hasMany(EmployeeTraining::class);
    }

    public function okrGoals(): HasMany
    {
        return $this->hasMany(OkrGoal::class);
    }

    public function objectives(): HasMany
    {
        return $this->hasMany(OkrGoal::class)->where('type', 'objective');
    }

    public function keyResults(): HasMany
    {
        return $this->hasMany(OkrGoal::class)->where('type', 'key_result');
    }

    // Helper methods for HR functionality
    public function getActiveCertificationsAttribute()
    {
        return $this->employeeCertifications()->where('status', 'active')->get();
    }

    public function getExpiredCertificationsAttribute()
    {
        return $this->employeeCertifications()->where('status', 'expired')->get();
    }

    public function getExpiringSoonCertificationsAttribute()
    {
        return $this->employeeCertifications()->expiringSoon()->get();
    }

    public function getCurrentOkrObjectivesAttribute()
    {
        return $this->objectives()
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->get();
    }

    public function getLatestPayrollAttribute()
    {
        return $this->payrolls()->latest('payroll_period')->first();
    }
}
